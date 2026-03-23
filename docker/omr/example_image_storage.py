"""
EJEMPLO DE USO - Sistema ImageStorage

Este archivo demuestra cómo integrar el nuevo sistema de almacenamiento
de imágenes en tu pipeline OMR.
"""

from helpers import (
    get_storage,
    pdf_to_images,
    load_image,
    normalize_size,
    draw_markers,
    warp_from_markers,
    detect_markers,
    classify_markers,
    read_folio,
    debug_folio_roi,
    save_processing_stage,
    save_comparison_images,
    print_document_structure,
    load_folio_annotation,
    crop_folio_region,
)
import cv2


def example_basic():
    """Ejemplo básico: Procesar un PDF y guardar imágenes en estructura organizada"""
    storage = get_storage()
    
    # 1️⃣ INICIALIZAR EL DOCUMENTO
    document_name = "formulario_nom035_lote_enero_2024"
    storage.initialize_document(document_name)
    
    # 2️⃣ CARGAR IMÁGENES DEL PDF
    pdf_path = "samples/form.pdf"
    pages = pdf_to_images(pdf_path, dpi=300)
    
    # 3️⃣ PROCESAR CADA PÁGINA
    for page_number, image in enumerate(pages, 1):
        print(f"\n📄 Procesando página {page_number}...")
        
        # Convertir a escala de grises y guardar
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        save_processing_stage(storage, gray, page_number, "grayscale", "image_gray")
        
        # Aplicar threshold y guardar
        thresh = cv2.threshold(gray, 127, 255, cv2.THRESH_BINARY)[1]
        save_processing_stage(storage, thresh, page_number, "threshold", "image_thresh")
        
        # Detectar markers
        top_markers, bottom_markers = detect_markers(thresh)
        
        # Guardar imagen con markers dibujados
        draw_markers(image, list(top_markers) + list(bottom_markers), 
                page_number, save=True, storage=storage)
        
        # Clasificar markers
        TL, TR, BL = classify_markers(top_markers, bottom_markers)
        
        # Si tenemos markers válidos, warpificar
        if TL and TR and BL:
            warped = warp_from_markers(image, TL, TR, BL, 
                                      page_number, save=True, storage=storage)
            
            # Normalizar tamaño
            if warped is not None:
                normalized = normalize_size(warped, 
                                           page_number=page_number, 
                                           save=True,
                                           storage=storage)
                print(f"  ✅ Página {page_number} procesada exitosamente")
        else:
            print(f"  ⚠️ No se detectaron markers válidos en página {page_number}")
    
    # 4️⃣ MOSTRAR ESTRUCTURA FINAL
    print_document_structure(storage)


def example_with_folio():
    """Ejemplo avanzado: Procesar folio y guardar región específica"""
    storage = get_storage()
    
    storage.initialize_document("formulario_con_folio")
    
    # Cargar imagen (puede ser de PDF o archivo individual)
    image = load_image("samples/page.jpg")
    page_number = 1
    
    # Guardar imagen original para referencia
    save_processing_stage(storage, image, page_number, "original", "page_original")
    
    # Convertir a escala de grises
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    
    # Detectar markers de registro
    thresh = cv2.threshold(gray, 127, 255, cv2.THRESH_BINARY)[1]
    top_markers, bottom_markers = detect_markers(thresh)
    
    # Warpificar imagen
    TL, TR, BL = classify_markers(top_markers, bottom_markers)
    warped = warp_from_markers(image, TL, TR, BL, page_number, save=True, storage=storage)
    
    if warped is not None:
        # Cargar anotación del folio
        annotation = load_folio_annotation("folio-annotation.json")
        
        # Guardar la región del folio para debugging
        debug_folio_roi(warped, annotation, page_number, storage=storage)
        
        # Procesar folio
        folio_value = read_folio(warped, annotation)
        print(f"  📋 Folio leído: {folio_value}")


def example_comparison():
    """Ejemplo: Guardar imágenes antes y después de cada etapa"""
    storage = get_storage()
    
    storage.initialize_document("formulario_comparativo")
    
    image = load_image("samples/page.jpg")
    page_number = 1
    
    # Guardar original
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    save_comparison_images(storage, image, gray, page_number, "conversion", "rgb_to_gray")
    
    # Threshold
    thresh = cv2.threshold(gray, 127, 255, cv2.THRESH_BINARY)[1]
    save_comparison_images(storage, gray, thresh, page_number, "thresholding", "binary_threshold")
    
    # Detección de bordes (ejemplo adicional)
    edges = cv2.Canny(gray, 100, 200)
    save_comparison_images(storage, gray, edges, page_number, "edge_detection", "canny_edges")
    
    print_document_structure(storage)


def example_batch_processing():
    """Ejemplo: Procesar múltiples documentos secuencialmente"""
    
    import os
    
    documents = [
        "formulario_lote1.pdf",
        "formulario_lote2.pdf",
        "formulario_lote3.pdf",
    ]
    
    for doc_file in documents:
        if os.path.exists(doc_file):
            storage = get_storage()
            # Inicializar para cada documento
            doc_name = os.path.splitext(doc_file)[0]  # Sin extensión
            storage.initialize_document(doc_name)
            
            pages = pdf_to_images(doc_file)
            
            for page_num, image in enumerate(pages, 1):
                gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
                save_processing_stage(storage, gray, page_num, "grayscale")
                
                # ... resto del procesamiento ...
            
            print(f"✅ {doc_name} completado")
            print_document_structure(storage)
            print("\n" + "="*60 + "\n")


if __name__ == "__main__":
    # Descomenta el ejemplo que quieras ejecutar:
    
    print("🚀 Ejemplo 1: Procesamiento Básico")
    print("-" * 60)
    example_basic()
    
    # example_with_folio()
    # example_comparison()
    # example_batch_processing()
