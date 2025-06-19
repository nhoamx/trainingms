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

# === Configuración de recorte ===
CROP_TOP = 430
CROP_BOTTOM = 450

def recortar_imagen(imagen, top=CROP_TOP, bottom=CROP_BOTTOM):
    """
    Recorta la imagen eliminando 'top' píxeles de arriba y 'bottom' píxeles de abajo.
    Retorna la imagen recortada y los offsets para ajustar coordenadas.
    """
    height, width = imagen.shape[:2]
    
    # Verificar que el recorte no sea mayor que la imagen
    if top + bottom >= height:
        print(f"ADVERTENCIA: Recorte total ({top + bottom}) >= altura de imagen ({height})")
        top = min(top, height // 4)
        bottom = min(bottom, height // 4)
    
    # Recortar
    imagen_recortada = imagen[top:height-bottom, :]
    
    print(f"Imagen recortada: {width}x{height} -> {imagen_recortada.shape[1]}x{imagen_recortada.shape[0]}")
    
    return imagen_recortada, top

def ajustar_coordenadas_marcadores(marcadores, offset_y):
    """
    Ajusta las coordenadas de los marcadores detectados en la imagen recortada
    para que correspondan a la imagen original.
    """
    marcadores_ajustados = []
    for (x, y) in marcadores:
        marcadores_ajustados.append((x, y + offset_y))
    return marcadores_ajustados

def detectar_marcadores(imagen, umbral=150, min_area=3000, debug_path=None, n_points=6):
    """
    Detecta los marcadores en la imagen, recortándola primero para evitar falsos positivos.
    """
    # Recortar la imagen
    imagen_recortada, offset_y = recortar_imagen(imagen)
    
    # Detectar en la imagen recortada
    gris = cv2.cvtColor(imagen_recortada, cv2.COLOR_BGR2GRAY)
    _, binaria = cv2.threshold(gris, umbral, 255, cv2.THRESH_BINARY_INV)
    contornos, _ = cv2.findContours(binaria, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    print(f"Contornos detectados en imagen recortada: {len(contornos)}")
    
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
    
    print(f"Marcadores filtrados por área en imagen recortada: {len(marcadores)}")
    
    # Buscar los centros detectados más cercanos a las posiciones ideales
    if len(marcadores) < n_points:
        raise Exception(f"No se detectaron al menos {n_points} marcadores, se detectaron {len(marcadores)}")
    
    # Ajustar las posiciones ideales para la imagen recortada
    ideal_positions_ajustadas = {}
    for label, (ideal_x, ideal_y) in IDEAL_POSITIONS.items():
        # Escalar las posiciones ideales a las dimensiones de la imagen recortada
        scale_x = imagen_recortada.shape[1] / 2481
        scale_y = imagen_recortada.shape[0] / (3510 - CROP_TOP - CROP_BOTTOM)
        
        scaled_x = int(ideal_x * scale_x)
        # Ajustar la Y considerando el recorte superior
        scaled_y = int((ideal_y - CROP_TOP) * scale_y)
        
        ideal_positions_ajustadas[label] = (scaled_x, scaled_y)
    
    # Para cada posición ideal, buscar el marcador detectado más cercano (sin repetir)
    ordered = []
    marcadores_restantes = marcadores.copy()
    for label in LABELS:
        ideal = np.array(ideal_positions_ajustadas[label])
        # Buscar el más cercano
        dists = [np.linalg.norm(ideal - np.array(pt)) for pt in marcadores_restantes]
        idx_min = int(np.argmin(dists))
        ordered.append(marcadores_restantes[idx_min])
        marcadores_restantes.pop(idx_min)
    
    # Ajustar coordenadas a la imagen original
    ordered_original = ajustar_coordenadas_marcadores(ordered, offset_y)
    
    print("Marcadores seleccionados en imagen original (x, y):", list(zip(LABELS, ordered_original)))
    
    # Imagen de depuración
    if debug_path is not None:
        debug_img = imagen.copy()  # Usar imagen original para debug
        
        # Dibujar líneas de recorte
        cv2.line(debug_img, (0, CROP_TOP), (imagen.shape[1], CROP_TOP), (255, 255, 0), 3)
        cv2.line(debug_img, (0, imagen.shape[0] - CROP_BOTTOM), (imagen.shape[1], imagen.shape[0] - CROP_BOTTOM), (255, 255, 0), 3)
        cv2.putText(debug_img, "ZONA RECORTADA", (50, 50), cv2.FONT_HERSHEY_SIMPLEX, 2, (255, 255, 0), 3)
        cv2.putText(debug_img, "ZONA RECORTADA", (50, imagen.shape[0] - 20), cv2.FONT_HERSHEY_SIMPLEX, 2, (255, 255, 0), 3)
        
        # Dibujar marcadores detectados
        for i, (cx, cy) in enumerate(ordered_original):
            cv2.circle(debug_img, (cx, cy), 30, (0, 0, 255), 5)
            cv2.putText(debug_img, LABELS[i], (cx+10, cy-10), cv2.FONT_HERSHEY_SIMPLEX, 1.5, (255, 0, 0), 4)
            
            # Dibuja la posición ideal también
            ideal_x, ideal_y = IDEAL_POSITIONS[LABELS[i]]
            scale_x = imagen.shape[1] / 2481
            scale_y = imagen.shape[0] / 3510
            ix = int(ideal_x * scale_x)
            iy = int(ideal_y * scale_y)
            cv2.circle(debug_img, (ix, iy), 15, (0, 255, 0), 3)
        
        cv2.imwrite(debug_path, debug_img)
        print(f"Imagen de depuración guardada en {debug_path}")
    
    return ordered_original

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

    # Detectar marcadores en ambas imágenes (ahora con recorte automático)
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
