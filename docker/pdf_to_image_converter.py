from pdf2image import convert_from_path
import os
from PyPDF2 import PdfReader

class PDFToImageConverter:
    def __init__(self, pdf_path, output_folder="output_images", dpi=300):
        """
        Inicializa la clase con la ruta del PDF y la carpeta de salida.
        :param pdf_path: Ruta del archivo PDF de entrada.
        :param output_folder: Carpeta donde se guardarán las imágenes.
        :param dpi: Resolución de las imágenes de salida.
        """
        self.pdf_path = pdf_path
        self.output_folder = output_folder
        self.dpi = dpi
        
        if not os.path.exists(self.output_folder):
            os.makedirs(self.output_folder)

    def convert(self):
        """
        Convierte el PDF en imágenes y las guarda en la carpeta de salida.
        Procesa en bloques si el PDF tiene más de 20 páginas para ahorrar memoria.
        :return: Lista de rutas de las imágenes generadas.
        """
        image_paths = []
        try:
            # Obtener número total de páginas del PDF
            pdf_reader = PdfReader(self.pdf_path)
            total_pages = len(pdf_reader.pages)
        except Exception as e:
            print(f"Error reading PDF: {e}")
            return []

        chunk_size = 20  # Procesar en bloques de 20 páginas

        for start_page in range(1, total_pages + 1, chunk_size):
            end_page = min(start_page + chunk_size - 1, total_pages)
            print(f"Processing pages {start_page} to {end_page}...")
            try:
                # Convierte el bloque actual de páginas
                images = convert_from_path(
                    self.pdf_path, 
                    dpi=self.dpi, 
                    first_page=start_page, 
                    last_page=end_page,
                    thread_count=4 # Ajusta según los cores disponibles
                )
                
                # Guarda cada imagen del bloque
                for i, image in enumerate(images):
                    # El número de página real es start_page + índice
                    current_page_num = start_page + i 
                    image_path = os.path.join(self.output_folder, f"page_{current_page_num}.png")
                    image.save(image_path, "PNG")
                    image_paths.append(image_path)
                
                # Limpiar memoria liberando las imágenes procesadas del bloque actual (opcional pero recomendado)
                del images

            except Exception as e:
                print(f"Error converting pages {start_page}-{end_page}: {e}")
                # Puedes decidir si continuar con el siguiente bloque o detenerte
                # continue 
                # break
            
        print(f"Conversion finished. Total images generated: {len(image_paths)}")
        return image_paths