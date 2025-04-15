import cv2
import numpy as np
import sys

# USO: python alinear_con_marcadores.py imagen_a_alinear.png referencia.png salida.png

# === Posiciones ideales (en píxeles) para imagen de 2481x3510 ===
# Modifica estos valores según tu formato
IDEAL_POSITIONS = {
    "TL": (200, 200),      # top-left
    "TR": (2281, 200),    # top-right
    "ML": (200, 1755),    # middle-left
    "MR": (2281, 1755),   # middle-right
    "BL": (200, 3310),    # bottom-left
    "BR": (2281, 3310),   # bottom-right
}

LABELS = ["TL", "TR", "ML", "MR", "BL", "BR"]


def detectar_marcadores(imagen, umbral=150, min_area=3000, debug_path=None, n_points=6):
    """
    Detecta los 4 marcadores (círculos/cuadrados negros) en las esquinas de la imagen.
    Retorna una lista con las coordenadas (x, y) ordenadas: [top-left, top-right, bottom-right, bottom-left]
    """
    gris = cv2.cvtColor(imagen, cv2.COLOR_BGR2GRAY)
    _, binaria = cv2.threshold(gris, umbral, 255, cv2.THRESH_BINARY_INV)
    contornos, _ = cv2.findContours(binaria, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    print(f"Contornos detectados: {len(contornos)}")
    marcadores = []
    areas = []
    for c in contornos:
        area = cv2.contourArea(c)
        if area > min_area:
            M = cv2.moments(c)
            if M["m00"] != 0:
                cx = int(M["m10"] / M["m00"])
                cy = int(M["m01"] / M["m00"])
                marcadores.append((cx, cy))
                areas.append(area)
    print(f"Marcadores filtrados por área: {len(marcadores)}")
    # Buscar los centros detectados más cercanos a las posiciones ideales
    if len(marcadores) < n_points:
        raise Exception(f"No se detectaron al menos {n_points} marcadores, se detectaron {len(marcadores)}")
    # Para cada posición ideal, buscar el marcador detectado más cercano (sin repetir)
    ordered = []
    marcadores_restantes = marcadores.copy()
    for label in LABELS:
        ideal = np.array(IDEAL_POSITIONS[label])
        # Buscar el más cercano
        dists = [np.linalg.norm(ideal - np.array(pt)) for pt in marcadores_restantes]
        idx_min = int(np.argmin(dists))
        ordered.append(marcadores_restantes[idx_min])
        marcadores_restantes.pop(idx_min)
    print("Marcadores seleccionados (x, y):", list(zip(LABELS, ordered)))
    # Imagen de depuración
    if debug_path is not None:
        debug_img = imagen.copy()
        for i, (cx, cy) in enumerate(ordered):
            cv2.circle(debug_img, (cx, cy), 30, (0,0,255), 5)
            cv2.putText(debug_img, LABELS[i], (cx+10, cy-10), cv2.FONT_HERSHEY_SIMPLEX, 1.5, (255,0,0), 4)
            # Dibuja la posición ideal también
            ix, iy = IDEAL_POSITIONS[LABELS[i]]
            cv2.circle(debug_img, (ix, iy), 15, (0,255,0), 3)
        cv2.imwrite(debug_path, debug_img)
        print(f"Imagen de depuración guardada en {debug_path}")
        print(f"Imagen de depuración guardada en {debug_path}")
    return ordered

def alinear_imagen(imagen, ref_pts, img_pts, output_size):
    """
    Aplica una transformación de perspectiva para alinear la imagen usando los puntos dados.
    """
    ref_pts_np = np.array(ref_pts, dtype="float32")
    img_pts_np = np.array(img_pts, dtype="float32")
    M = cv2.getPerspectiveTransform(img_pts_np, ref_pts_np)
    alineada = cv2.warpPerspective(imagen, M, output_size)
    return alineada

def main():
    if len(sys.argv) != 4:
        print("USO: python alinear_con_marcadores.py imagen_a_alinear.png referencia.png salida.png")
        sys.exit(1)
    img_path = sys.argv[1]
    ref_path = sys.argv[2]
    out_path = sys.argv[3]

    img = cv2.imread(img_path)
    ref = cv2.imread(ref_path)
    if img is None or ref is None:
        print("No se pudo cargar alguna de las imágenes.")
        sys.exit(1)

    # Detectar marcadores en ambas imágenes y guardar imágenes de depuración (6 puntos)
    ref_marcadores = detectar_marcadores(ref, debug_path="debug_ref.png", n_points=6)
    img_marcadores = detectar_marcadores(img, debug_path="debug_img.png", n_points=6)

    # Alinear usando solo las esquinas (TL, TR, BL, BR)
    ref_esquinas = [ref_marcadores[0], ref_marcadores[1], ref_marcadores[4], ref_marcadores[5]]
    img_esquinas = [img_marcadores[0], img_marcadores[1], img_marcadores[4], img_marcadores[5]]
    output_size = (ref.shape[1], ref.shape[0])
    alineada = alinear_imagen(img, ref_esquinas, img_esquinas, output_size)

    cv2.imwrite(out_path, alineada)
    print(f"Imagen alineada guardada en {out_path}")
    print("Imágenes de depuración guardadas como debug_ref.png y debug_img.png")

if __name__ == "__main__":
    main()
