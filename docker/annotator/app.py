"""
OMR Annotation Tool — Flask backend.

Endpoints:
  GET  /                       → Render annotation UI
  POST /upload                 → Save uploaded image, return filename
  POST /save                   → Persist annotation JSON to annotations/<name>.json
  GET  /load/<template_name>   → Load annotation JSON (or {} if missing)
  GET  /image/<filename>       → Serve uploaded image
"""

import json
from pathlib import Path

from flask import Flask, jsonify, render_template, request, send_from_directory
from werkzeug.utils import secure_filename

BASE_DIR = Path(__file__).parent
ANNOTATIONS_DIR = BASE_DIR / "annotations"
UPLOADS_DIR = BASE_DIR / "uploads"

ANNOTATIONS_DIR.mkdir(exist_ok=True)
UPLOADS_DIR.mkdir(exist_ok=True)

ALLOWED_EXTENSIONS = {"png", "jpg", "jpeg", "bmp", "tiff", "tif"}

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = 30 * 1024 * 1024  # 30 MB


def _allowed_file(filename: str) -> bool:
    return "." in filename and filename.rsplit(".", 1)[1].lower() in ALLOWED_EXTENSIONS


def _safe_template_name(name: str) -> str:
    """Strip path separators to prevent directory traversal."""
    return secure_filename(name)


@app.route("/")
def index():
    return render_template("index.html")


@app.route("/upload", methods=["POST"])
def upload():
    if "image" not in request.files:
        return jsonify({"error": "No image field in request"}), 400

    file = request.files["image"]

    if not file.filename:
        return jsonify({"error": "Empty filename"}), 400

    if not _allowed_file(file.filename):
        return jsonify({"error": "File type not allowed"}), 400

    filename = secure_filename(file.filename)
    save_path = UPLOADS_DIR / filename
    file.save(save_path)

    return jsonify({"filename": filename})


@app.route("/save", methods=["POST"])
def save():
    body = request.get_json(silent=True)

    if not body or "template_name" not in body or "data" not in body:
        return jsonify({"error": "Payload must contain template_name and data"}), 400

    template_name = _safe_template_name(str(body["template_name"]))
    if not template_name:
        return jsonify({"error": "Invalid template name"}), 400

    out_path = ANNOTATIONS_DIR / f"{template_name}.json"
    out_path.write_text(
        json.dumps(body["data"], indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    return jsonify({"saved": out_path.name})


@app.route("/load/<template_name>", methods=["GET"])
def load(template_name: str):
    safe_name = _safe_template_name(template_name)
    ann_path = ANNOTATIONS_DIR / f"{safe_name}.json"

    if not ann_path.exists():
        return jsonify({})

    return jsonify(json.loads(ann_path.read_text(encoding="utf-8")))


@app.route("/image/<filename>")
def serve_image(filename: str):
    safe_filename = secure_filename(filename)
    return send_from_directory(UPLOADS_DIR, safe_filename)


if __name__ == "__main__":
    app.run(debug=True, port=5001)
