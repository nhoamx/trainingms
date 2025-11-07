#!/usr/bin/env python3
"""
PDF to Word Converter Script
Converts PDF files to DOCX format using pdf2docx library
"""

import sys
import os
from pdf2docx import Converter
import logging

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


def convert_pdf_to_docx(pdf_path: str, docx_path: str) -> bool:
    """
    Convert a PDF file to DOCX format
    
    Args:
        pdf_path: Path to the input PDF file
        docx_path: Path to the output DOCX file
        
    Returns:
        bool: True if conversion was successful, False otherwise
    """
    try:
        # Verify input file exists
        if not os.path.exists(pdf_path):
            logger.error(f"PDF file not found: {pdf_path}")
            return False
        
        # Create output directory if it doesn't exist
        output_dir = os.path.dirname(docx_path)
        if output_dir and not os.path.exists(output_dir):
            os.makedirs(output_dir, exist_ok=True)
            logger.info(f"Created output directory: {output_dir}")
        
        # Convert PDF to DOCX
        logger.info(f"Converting {pdf_path} to {docx_path}")
        cv = Converter(pdf_path)
        cv.convert(docx_path, start=0, end=None)
        cv.close()
        
        # Verify output file was created
        if os.path.exists(docx_path):
            file_size = os.path.getsize(docx_path)
            logger.info(f"Conversion successful! Output file size: {file_size} bytes")
            return True
        else:
            logger.error("Conversion failed: Output file was not created")
            return False
            
    except Exception as e:
        logger.error(f"Error during conversion: {str(e)}")
        return False


def main():
    """Main function to handle command line arguments"""
    if len(sys.argv) != 3:
        print("Usage: python convert_pdf_to_word.py <input_pdf_path> <output_docx_path>")
        print("Example: python convert_pdf_to_word.py /app/input/report.pdf /app/output/report.docx")
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    docx_path = sys.argv[2]
    
    logger.info(f"Starting PDF to DOCX conversion")
    logger.info(f"Input: {pdf_path}")
    logger.info(f"Output: {docx_path}")
    
    success = convert_pdf_to_docx(pdf_path, docx_path)
    
    if success:
        logger.info("Conversion completed successfully")
        sys.exit(0)
    else:
        logger.error("Conversion failed")
        sys.exit(1)


if __name__ == "__main__":
    main()
