from PyPDF2 import PdfMerger
import glob

merge = PdfMerger()
location = "deprecated/pdfs/*.pdf"
for pdf in glob.glob(location):
    merge.append(pdf)

merge.write("deprecated/pdfs/merged.pdf")
merge.close()