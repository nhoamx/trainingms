import unittest

from omr.pipeline import build_page_chunks


class PipelineChunkingTest(unittest.TestCase):
    def test_keeps_single_chunk_when_pdf_is_under_threshold(self):
        chunks = build_page_chunks(total_pages=25, threshold_pages=25, chunk_size=10)

        self.assertEqual(chunks, [(1, 25)])

    def test_splits_into_ten_page_chunks_when_pdf_exceeds_threshold(self):
        chunks = build_page_chunks(total_pages=26, threshold_pages=25, chunk_size=10)

        self.assertEqual(chunks, [(1, 10), (11, 20), (21, 26)])

    def test_rejects_invalid_chunk_size(self):
        with self.assertRaises(ValueError):
            build_page_chunks(total_pages=30, threshold_pages=25, chunk_size=0)


if __name__ == "__main__":
    unittest.main()
