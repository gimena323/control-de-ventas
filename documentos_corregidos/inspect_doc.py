from __future__ import annotations

import json
import sys
from pathlib import Path

from docx import Document
from docx.oxml.ns import qn


def paragraph_record(index, paragraph):
    xml = paragraph._p
    return {
        "index": index,
        "style": paragraph.style.name if paragraph.style else None,
        "text": paragraph.text,
        "page_break_before": bool(paragraph.paragraph_format.page_break_before),
        "has_rendered_page_break": bool(xml.xpath('.//w:lastRenderedPageBreak')),
        "has_manual_page_break": any(
            element.get(qn('w:type')) == 'page' for element in xml.xpath('.//w:br')
        ),
    }


def main():
    source = Path(sys.argv[1])
    destination = Path(sys.argv[2])
    document = Document(source)
    payload = {
        "paragraphs": [
            paragraph_record(index, paragraph)
            for index, paragraph in enumerate(document.paragraphs)
        ],
        "tables": [
            {
                "index": index,
                "rows": [[cell.text for cell in row.cells] for row in table.rows],
            }
            for index, table in enumerate(document.tables)
        ],
        "sections": len(document.sections),
    }
    destination.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")


if __name__ == "__main__":
    main()
