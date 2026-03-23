import json
import logging
import re

import cv2

logger = logging.getLogger(__name__)


def load_folio_annotation(path="folio-annotation.json"):
    with open(path, "r") as file:
        return json.load(file)


def get_grid(annotation):
    return annotation["folio_annotation"]["grid_points"]


def normalize_grid_to_roi(annotation, grid, roi):
    """
    Normaliza el grid para que siempre este en coordenadas relativas al ROI.

    Si el grid fue guardado en coordenadas absolutas de la imagen normalizada,
    se convierte restando el origen de la region.
    """
    if not grid or "folio_annotation" not in annotation:
        return grid

    region = annotation["folio_annotation"].get("region")
    if not region:
        return grid

    max_x = max(point["x"] for point in grid.values())
    max_y = max(point["y"] for point in grid.values())

    roi_h, roi_w = roi.shape[:2]

    is_absolute = max_x > roi_w or max_y > roi_h
    if not is_absolute:
        return grid

    offset_x = int(region["x"])
    offset_y = int(region["y"])

    return {
        key: {
            "x": int(point["x"]) - offset_x,
            "y": int(point["y"]) - offset_y,
        }
        for key, point in grid.items()
    }


def normalize_grid_points_to_region(grid, region, roi):
    if not grid or not region:
        return grid

    max_x = max(point["x"] for point in grid.values())
    max_y = max(point["y"] for point in grid.values())

    roi_h, roi_w = roi.shape[:2]
    is_absolute = max_x > roi_w or max_y > roi_h

    if not is_absolute:
        return grid

    offset_x = int(region["x"])
    offset_y = int(region["y"])

    return {
        key: {
            "x": int(point["x"]) - offset_x,
            "y": int(point["y"]) - offset_y,
        }
        for key, point in grid.items()
    }


def get_fill_ratio(thresh, x, y, size=12):
    roi = thresh[y - size:y + size, x - size:x + size]

    if roi.size == 0:
        return 0

    total = cv2.countNonZero(roi)
    area = roi.shape[0] * roi.shape[1]

    return total / float(area)


def crop_folio_region(image, annotation):
    region = annotation["folio_annotation"]["region"]

    x = int(region["x"])
    y = int(region["y"])
    w = int(region["w"])
    h = int(region["h"])

    return image[y:y + h, x:x + w]


def debug_folio_roi(image, annotation, page_number, page_image=None, storage=None):
    """
    Guarda la region del folio como imagen de debug.

    Args:
        image (numpy.ndarray): Imagen del folio
        annotation (dict): Anotacion con region del folio
        page_number (int): Numero de pagina
        page_image (numpy.ndarray): (Opcional) Imagen completa de la pagina para referencia
    """
    roi = crop_folio_region(image, annotation)
    if storage is None:
        raise ValueError("storage es requerido para guardar folio_roi")
    storage.save_debug_image(roi, page_number, "folio_roi")

    grid = get_grid(annotation)
    grid = normalize_grid_to_roi(annotation, grid, roi)

    roi_marked = roi.copy()
    for point in grid.values():
        x = int(point["x"])
        y = int(point["y"])
        cv2.circle(roi_marked, (x, y), 6, (0, 255, 0), 2)

    storage.save_debug_image(roi_marked, page_number, "folio_roi_marked")


def debug_answers_rois(image, annotation, page_number, storage=None):
    """
    Guarda ROI y ROI marcada por cada bloque de respuestas para debugging.

    Genera dos imagenes por bloque:
    - answer_<block>_roi
    - answer_<block>_roi_marked
    """
    if storage is None:
        raise ValueError("storage es requerido para guardar debug de respuestas")

    answers_annotation = annotation.get("answers_annotation", {})
    if not answers_annotation:
        return

    for block_name, block in answers_annotation.items():
        region = block.get("region")
        grid = block.get("grid_points", {})

        if not region or not grid:
            continue

        x = int(region["x"])
        y = int(region["y"])
        w = int(region["w"])
        h = int(region["h"])

        roi = image[y:y + h, x:x + w]
        if roi.size == 0:
            continue

        block_grid = normalize_grid_points_to_region(grid, region, roi)

        roi_marked = roi.copy()
        for point in block_grid.values():
            px = int(point["x"])
            py = int(point["y"])
            cv2.circle(roi_marked, (px, py), 5, (0, 255, 0), 2)

        safe_block = re.sub(r"[^a-zA-Z0-9_]+", "_", str(block_name)).strip("_").lower()
        storage.save_debug_image(roi, page_number, f"answer_{safe_block}_roi")
        storage.save_debug_image(roi_marked, page_number, f"answer_{safe_block}_roi_marked")


def read_folio(image, annotation) -> str | None:
    """
    Lee el folio de la imagen usando el grid de anotación.

    Returns:
        str con el folio detectado, o None si la confianza promedio
        es demasiado baja o hay demasiados dígitos ambiguos.
    """
    logger.debug("Leyendo folio (modo grid fijo)")

    folio_annotation = annotation["folio_annotation"]

    if "region" in folio_annotation:
        roi = crop_folio_region(image, annotation)
    else:
        roi = image

    gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)

    thresh = cv2.threshold(
        gray,
        0,
        255,
        cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU,
    )[1]

    grid = get_grid(annotation)
    grid = normalize_grid_to_roi(annotation, grid, roi)

    folio = ""
    confidence_scores = []

    for col in range(1, 12):
        values = []

        for row in range(10):
            key = f"C{col}R{row}"
            point = grid[key]

            x = int(point["x"])
            y = int(point["y"])

            fill = get_fill_ratio(thresh, x, y)
            values.append(fill)

        max_val = max(values)
        digit = values.index(max_val)

        confidence_scores.append(max_val)

        if max_val < 0.3:
            folio += "?"
        else:
            folio += str(digit)

    avg_conf = sum(confidence_scores) / len(confidence_scores)

    if avg_conf < 0.2:
        return None

    if folio.count("?") > 5:
        return None

    return folio


def load_answers_annotation(path="answers-annotation.json"):
    with open(path, "r", encoding="utf-8") as file:
        return json.load(file)


def load_answers_mapping(path="answers-mapping.json"):
    with open(path, "r", encoding="utf-8") as file:
        return json.load(file)


def _is_question_in_range(question_number: int, range_expr: str) -> bool:
    if "-" not in range_expr:
        return question_number == int(range_expr)

    start_text, end_text = range_expr.split("-", 1)
    start = int(start_text)
    end = int(end_text)
    return start <= question_number <= end


def _resolve_answer_mapping(question_number: int, selected_column: str, column_count: int, instrument: str, mapping_config):
    default_label = selected_column
    if column_count == 2:
        default_label = "SI" if selected_column == "C1" else "NO"

    if not mapping_config:
        return default_label, None

    instrument_mapping = mapping_config.get(str(instrument).lower())
    if not instrument_mapping:
        return default_label, None

    for rule in instrument_mapping.get("rules", []):
        range_expr = str(rule.get("range", "")).strip()
        if not range_expr:
            continue

        if not _is_question_in_range(question_number, range_expr):
            continue

        value_map = rule.get("map", {})
        mapped_value = value_map.get(selected_column, default_label)
        return mapped_value, rule.get("section")

    column_mappings = instrument_mapping.get("column_mappings", {})
    mapped_by_count = column_mappings.get(str(column_count), {})
    if selected_column in mapped_by_count:
        return mapped_by_count[selected_column], None

    return default_label, None


def _parse_grid_key(key: str):
    match = re.fullmatch(r"C(\d+)R(\d+)", key)
    if not match:
        return None
    return int(match.group(1)), int(match.group(2))


def _get_ordered_block_names(answers_annotation, instrument_mapping):
    block_order = instrument_mapping.get("block_order", [])
    ordered_block_names = []
    seen_blocks = set()

    for block_name in block_order:
        if block_name in answers_annotation and block_name not in seen_blocks:
            ordered_block_names.append(block_name)
            seen_blocks.add(block_name)

    for block_name in answers_annotation.keys():
        if block_name not in seen_blocks:
            ordered_block_names.append(block_name)
            seen_blocks.add(block_name)

    return ordered_block_names


def _extract_block_grid(answers_annotation, block_name, image):
    block = answers_annotation[block_name]
    region = block.get("region")
    grid = block.get("grid_points", {})

    if not region or not grid:
        return None, None, None, None

    x = int(region["x"])
    y = int(region["y"])
    w = int(region["w"])
    h = int(region["h"])

    roi = image[y:y + h, x:x + w]
    if roi.size == 0:
        return None, None, None, None

    gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    thresh = cv2.threshold(
        gray,
        0,
        255,
        cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU,
    )[1]

    block_grid = normalize_grid_points_to_region(grid, region, roi)

    parsed = [_parse_grid_key(key) for key in block_grid.keys()]
    parsed = [entry for entry in parsed if entry is not None]
    if not parsed:
        return None, None, None, None

    columns = sorted({col for col, _ in parsed})
    rows = sorted({row for _, row in parsed})

    return thresh, block_grid, columns, rows


def _score_column_rows(thresh, block_grid, column_number, rows):
    scores = []
    for row in rows:
        key = f"C{column_number}R{row}"
        point = block_grid.get(key)
        if point is None:
            continue

        px = int(point["x"])
        py = int(point["y"])
        score = get_fill_ratio(thresh, px, py)
        scores.append((row, score))

    return scores


def _select_top_row(row_scores, min_fill, min_margin):
    if not row_scores:
        return None

    ranked = sorted(row_scores, key=lambda item: item[1], reverse=True)
    best_row, best_score = ranked[0]
    second_score = ranked[1][1] if len(ranked) > 1 else 0.0
    margin = best_score - second_score
    ambiguous = best_score < min_fill or margin < min_margin

    return {
        "row": int(best_row),
        "confidence": round(float(best_score), 4),
        "margin": round(float(margin), 4),
        "ambiguous": ambiguous,
    }


def _read_answers_field_based(image, answers_annotation, instrument_mapping, min_fill, min_margin):
    fields_config = instrument_mapping.get("fields", {})
    ordered_block_names = _get_ordered_block_names(answers_annotation, instrument_mapping)

    results = {}

    for block_name in ordered_block_names:
        field_config = fields_config.get(block_name)
        if not field_config:
            continue

        thresh, block_grid, columns, rows = _extract_block_grid(answers_annotation, block_name, image)
        if thresh is None:
            continue

        field_type = field_config.get("type", "single_column")

        if field_type == "single_column":
            column_number = int(field_config.get("column", columns[0]))
            row_scores = _score_column_rows(thresh, block_grid, column_number, rows)
            selected = _select_top_row(row_scores, min_fill, min_margin)
            if not selected:
                continue

            options = field_config.get("options", [])
            row_index = selected["row"]
            value = options[row_index] if row_index < len(options) else row_index

            results[block_name] = {
                "value": value,
                "row": row_index,
                "selected_column": f"C{column_number}",
                "confidence": selected["confidence"],
                "margin": selected["margin"],
                "ambiguous": selected["ambiguous"],
            }

        elif field_type == "two_digit_columns":
            tens_column = int(field_config.get("tens_column", 1))
            units_column = int(field_config.get("units_column", 2))

            tens_scores = _score_column_rows(thresh, block_grid, tens_column, rows)
            units_scores = _score_column_rows(thresh, block_grid, units_column, rows)

            tens_selected = _select_top_row(tens_scores, min_fill, min_margin)
            units_selected = _select_top_row(units_scores, min_fill, min_margin)

            if not tens_selected or not units_selected:
                continue

            tens = tens_selected["row"]
            units = units_selected["row"]
            value = (tens * 10) + units

            results[block_name] = {
                "value": value,
                "tens": tens,
                "units": units,
                "selected_columns": {
                    "tens": f"C{tens_column}",
                    "units": f"C{units_column}",
                },
                "confidence": round((tens_selected["confidence"] + units_selected["confidence"]) / 2, 4),
                "margin": round((tens_selected["margin"] + units_selected["margin"]) / 2, 4),
                "ambiguous": bool(tens_selected["ambiguous"] or units_selected["ambiguous"]),
            }

    education_complete = results.get("education_level")
    education_incomplete = results.get("incomplete_education_level")
    if education_complete is not None or education_incomplete is not None:
        complete_ok = bool(education_complete and not education_complete.get("ambiguous", True))
        incomplete_ok = bool(education_incomplete and not education_incomplete.get("ambiguous", True))
        results["_validations"] = {
            "education_any_selected": bool(complete_ok or incomplete_ok)
        }

    return results


def read_answers(image, annotation, min_fill=0.3, min_margin=0.05, mapping_config=None):
    answers_annotation = annotation.get("answers_annotation", {})
    if not answers_annotation:
        return {}

    instrument = annotation.get("meta", {}).get("instrument", "")
    instrument_mapping = {}
    if mapping_config:
        instrument_mapping = mapping_config.get(str(instrument).lower(), {})

    if instrument_mapping.get("mode") == "field_based":
        return _read_answers_field_based(
            image=image,
            answers_annotation=answers_annotation,
            instrument_mapping=instrument_mapping,
            min_fill=min_fill,
            min_margin=min_margin,
        )

    ordered_block_names = _get_ordered_block_names(answers_annotation, instrument_mapping)

    answers = {}
    question_number = 1

    for block_name in ordered_block_names:
        block = answers_annotation[block_name]
        region = block.get("region")
        grid = block.get("grid_points", {})

        if not region or not grid:
            continue

        x = int(region["x"])
        y = int(region["y"])
        w = int(region["w"])
        h = int(region["h"])

        roi = image[y:y + h, x:x + w]
        if roi.size == 0:
            continue

        gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
        thresh = cv2.threshold(
            gray,
            0,
            255,
            cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU,
        )[1]

        block_grid = normalize_grid_points_to_region(grid, region, roi)

        parsed = [_parse_grid_key(key) for key in block_grid.keys()]
        parsed = [entry for entry in parsed if entry is not None]
        if not parsed:
            continue

        columns = sorted({col for col, _ in parsed})
        rows = sorted({row for _, row in parsed})

        for row in rows:
            option_scores = {}

            for col in columns:
                key = f"C{col}R{row}"
                point = block_grid.get(key)
                if point is None:
                    continue

                px = int(point["x"])
                py = int(point["y"])
                option_scores[f"C{col}"] = get_fill_ratio(thresh, px, py)

            if not option_scores:
                continue

            ranked = sorted(option_scores.items(), key=lambda item: item[1], reverse=True)
            selected_column, selected_score = ranked[0]
            second_score = ranked[1][1] if len(ranked) > 1 else 0.0
            margin = selected_score - second_score
            ambiguous = selected_score < min_fill or margin < min_margin

            selected_label, mapping_section = _resolve_answer_mapping(
                question_number=question_number,
                selected_column=selected_column,
                column_count=len(columns),
                instrument=instrument,
                mapping_config=mapping_config,
            )

            answers[str(question_number)] = {
                "value": selected_label,
                "selected_column": selected_column,
                "confidence": round(float(selected_score), 4),
                "margin": round(float(margin), 4),
                "ambiguous": ambiguous,
                "block": block_name,
                "row": row,
            }
            if mapping_section:
                answers[str(question_number)]["mapping_section"] = mapping_section
            question_number += 1

    return answers
