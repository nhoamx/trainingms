from pdf2image import convert_from_path
import cv2
import numpy as np


def pdf_to_images(pdf_path, dpi=300, first_page=None, last_page=None):
    convert_kwargs = {"dpi": dpi}
    if first_page is not None:
        convert_kwargs["first_page"] = first_page
    if last_page is not None:
        convert_kwargs["last_page"] = last_page

    pages = convert_from_path(pdf_path, **convert_kwargs)

    images = []
    for page in pages:
        img = np.array(page)
        img = cv2.cvtColor(img, cv2.COLOR_RGB2BGR)
        images.append(img)
    return images
