from pdf2image import convert_from_path
import os

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
        :return: Lista de rutas de las imágenes generadas.
        """
        images = convert_from_path(self.pdf_path, dpi=self.dpi)
        image_paths = []
        
        for i, image in enumerate(images):
            image_path = os.path.join(self.output_folder, f"page_{i + 1}.png")
            image.save(image_path, "PNG")
            image_paths.append(image_path)
            
        return image_paths