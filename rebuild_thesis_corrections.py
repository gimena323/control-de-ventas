from pathlib import Path
import re

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor


SOURCE = Path(r"C:\Users\HP\Documents\PERFIL_TESIS_GRADO-Quinaya4.docx")
OUTPUT = Path(r"C:\laragon\www\control_emmafashion\TESIS_COMPLETA_CORREGIDA_EN_AMARILLO.docx")
SPRINT1 = Path(r"C:\Users\HP\.codex\visualizations\2026\07\28\019faabb-03f0-7721-a83a-520605149e89\revision-tesis\diagrama-casos-uso-sprint-1-corregido.png")
SPRINT2 = Path(r"C:\Users\HP\.codex\visualizations\2026\07\28\019faabb-03f0-7721-a83a-520605149e89\revision-tesis\diagrama-casos-uso-sprint-2-corregido.png")


def yellow(run):
    run.font.highlight_color = 7


def replace_text(paragraph, text, highlight=True):
    for run in list(paragraph.runs):
        run._element.getparent().remove(run._element)
    run = paragraph.add_run(text)
    if highlight:
        yellow(run)
    return run


def set_keep_with_next(paragraph):
    paragraph.paragraph_format.keep_with_next = True


def page_field(paragraph):
    run = paragraph.add_run()
    fld = OxmlElement("w:fldSimple")
    fld.set(qn("w:instr"), "PAGE")
    run._r.addnext(fld)


def set_page_number_format(section, fmt, start=None):
    sect_pr = section._sectPr
    pg = sect_pr.find(qn("w:pgNumType"))
    if pg is None:
        pg = OxmlElement("w:pgNumType")
        sect_pr.append(pg)
    pg.set(qn("w:fmt"), fmt)
    if start is not None:
        pg.set(qn("w:start"), str(start))


def rebuild_footer(section):
    section.footer.is_linked_to_previous = False
    footer = section.footer
    p = footer.paragraphs[0]
    for run in list(p.runs):
        run._element.getparent().remove(run._element)
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    page_field(p)


def remove_drawing(paragraph):
    for drawing in paragraph._p.xpath(".//w:drawing"):
        parent = drawing.getparent()
        parent.remove(drawing)


def insert_image_before(doc, caption, image_path):
    remove_drawing(caption)
    holder = doc.add_paragraph()
    holder.alignment = WD_ALIGN_PARAGRAPH.CENTER
    holder.add_run().add_picture(str(image_path), width=Inches(6.35))
    caption._p.addprevious(holder._p)


doc = Document(SOURCE)

# Numeracion: preliminares en romano minuscula y contenido en decimal centrado.
for index, section in enumerate(doc.sections):
    rebuild_footer(section)
    if index < 2:
        set_page_number_format(section, "lowerRoman")
    else:
        set_page_number_format(section, "decimal", 1)

# Formato coherente para titulos de nivel 3 y 4.
for style_name in ("Heading 3", "Heading 4"):
    style = doc.styles[style_name]
    style.font.name = "Arial"
    style.font.size = Pt(12)
    style.font.bold = True
    style.font.color.rgb = RGBColor(0, 0, 0)
    style.paragraph_format.space_before = Pt(12)
    style.paragraph_format.space_after = Pt(6)
    style.paragraph_format.keep_with_next = True

# Se localizan los apartados por su texto para corregirlos exactamente en su lugar.
paragraphs = doc.paragraphs
by_text = {" ".join(p.text.split()): p for p in paragraphs if p.text.strip()}

problem_heading = by_text.get("1.2.1 Descripci�n del problema")
if problem_heading is not None:
    pos = paragraphs.index(problem_heading)
    problems = paragraphs[pos + 1:pos + 5]
    corrected = [
        "El registro de compras de mercader�a se realiza manualmente en cuadernos, donde se anotan la cantidad de prendas recibidas, el precio y el monto total correspondiente. Esta forma de registro genera demoras al momento de buscar informaci�n relacionada con las compras realizadas.",
        "El control del inventario se lleva a cabo mediante la revisi�n f�sica de las prendas ubicadas en estanter�as, maniqu�es y dep�sito, sin un cat�logo digital actualizado. Esta forma de control dificulta conocer con precisi�n la disponibilidad de las prendas existentes.",
        "El registro de ventas tambi�n se realiza manualmente en cuadernos, en los que se anota la prenda vendida y su precio correspondiente. Esta situaci�n ocasiona demoras al momento de buscar informaci�n y atender a los clientes.",
        "El ajuste diario del total de ventas se efect�a de forma manual mediante la revisi�n del cuaderno y la suma de los montos anotados. Como consecuencia, se pierde tiempo y pueden producirse errores en el c�lculo de los totales.",
    ]
    for p, text in zip(problems, corrected):
        replace_text(p, text)

objective_heading = next((p for p in paragraphs if "Objetivo General" in p.text and p.style.name.startswith("Heading")), None)
if objective_heading is not None:
    general = paragraphs[paragraphs.index(objective_heading) + 1]
    clean = general.text.strip().strip('"�\u201c\u201d')
    replace_text(general, clean)

specific_heading = next((p for p in paragraphs if p.text.strip() == "Objetivos espec�ficos"), None)
if specific_heading is not None:
    replace_text(specific_heading, "1.3.2 Objetivos espec�ficos")
    pos = paragraphs.index(specific_heading)
    objectives = paragraphs[pos + 1:pos + 5]
    corrected = [
        "Implementar un m�dulo de compras que permita registrar la mercader�a adquirida, las cantidades, los precios y los proveedores.",
        "Implementar un m�dulo de inventario que permita conocer de forma precisa y actualizada la disponibilidad, las entradas y las salidas de las prendas.",
        "Implementar un m�dulo de ventas que permita registrar las prendas vendidas y agilizar la atenci�n al cliente.",
        "Implementar un m�dulo de reportes que calcule autom�ticamente los totales de ventas diarios, semanales y mensuales para apoyar la toma de decisiones.",
    ]
    for p, text in zip(objectives, corrected):
        replace_text(p, text)

# Capitulo II: definicion ubicada bajo su titulo, con redaccion propia y enlace al proyecto.
app_heading = next((p for p in paragraphs if p.text.strip() == "2.2.1 Aplicaci�n Web"), None)
if app_heading is not None:
    set_keep_with_next(app_heading)
    pos = paragraphs.index(app_heading)
    target = next((p for p in paragraphs[pos + 1:pos + 5] if p.text.strip()), paragraphs[pos + 1])
    replace_text(
        target,
        "Seg�n Wikipedia (2018), una aplicaci�n web es un programa que se utiliza mediante un navegador y que funciona a trav�s de internet o de una intranet. En este proyecto, la aplicaci�n web permite gestionar productos, proveedores, clientes, compras, ventas, inventario, usuarios y reportes de la tienda Emma Fashion.",
    )

# Citas textuales extensas del Capitulo II: frase de enlace, bloque APA y sin comillas.
chapter2_start = next((i for i, p in enumerate(paragraphs) if p.text.strip().startswith("CAPITULO II")), 270)
chapter3_start = next((i for i, p in enumerate(paragraphs) if p.text.strip().startswith("CAPITULO III")), 520)
for p in paragraphs[chapter2_start:chapter3_start]:
    text = " ".join(p.text.split())
    if len(text.split()) > 40 and ("�" in text or "\u201c" in text or "\u201d" in text):
        cleaned = text.replace("define que �", "se�ala lo siguiente: ").replace("define el �", "se�ala lo siguiente: ")
        cleaned = cleaned.replace("�", "").replace("\u201c", "").replace("\u201d", "")
        replace_text(p, cleaned)
        p.paragraph_format.left_indent = Inches(0.5)
        p.paragraph_format.right_indent = Inches(0.5)
        p.paragraph_format.space_before = Pt(6)
        p.paragraph_format.space_after = Pt(6)

# Capitulo III sin espacio inicial innecesario.
chapter3 = next((p for p in paragraphs if p.text.strip().startswith("CAPITULO III")), None)
if chapter3 is not None:
    chapter3.paragraph_format.space_before = Pt(0)
    chapter3.paragraph_format.page_break_before = True

# Sprint 1: eliminar tarea duplicada del backlog y corregir encabezados.
if len(doc.tables) > 14:
    table = doc.tables[14]
    seen = set()
    for row in list(table.rows)[1:]:
        key = tuple(" ".join(cell.text.split()) for cell in row.cells)
        if key in seen:
            table._tbl.remove(row._tr)
        else:
            seen.add(key)

for old, new in {
    "Ejecuci�n del primer Sprint 1": "3.3 Ejecuci�n del Sprint 1",
    "Sprint Backlog": "3.3.2 Sprint Backlog",
    "Diagrama de Casos de Uso": "3.3.3 Diagrama de casos de uso",
}.items():
    p = next((p for p in paragraphs if p.text.strip() == old), None)
    if p is not None:
        replace_text(p, new)
        p.style = doc.styles["Heading 3"]

# Sprint 2: definicion de hecho correcta y numeracion coherente.
s2_heading = next((p for p in paragraphs if p.text.strip() == "Ejecuci�n del Sprint 2"), None)
if s2_heading is not None:
    replace_text(s2_heading, "3.4 Ejecuci�n del Sprint 2")
    s2_heading.style = doc.styles["Heading 2"]
    pos = paragraphs.index(s2_heading)
    definition_heading = paragraphs[pos + 1]
    definition_body = paragraphs[pos + 2]
    replace_text(definition_heading, "3.4.1 Definici�n de hecho")
    definition_heading.style = doc.styles["Heading 3"]
    replace_text(
        definition_body,
        "El Sprint 2 se considera terminado cuando los m�dulos de proveedores y compras permiten registrar, consultar, editar y anular la informaci�n; cuando cada compra se relaciona con un proveedor y con productos existentes; cuando el sistema calcula subtotales y totales; y cuando las entradas o reversiones del inventario se actualizan correctamente.",
    )
    sprint_backlog = next((p for p in paragraphs[pos:pos + 15] if p.text.strip().lower() == "sprint backlog"), None)
    if sprint_backlog is not None:
        replace_text(sprint_backlog, "3.4.2 Sprint Backlog")
        sprint_backlog.style = doc.styles["Heading 3"]
    use_case = next((p for p in paragraphs[pos:pos + 25] if p.text.strip().lower() == "diagrama de casos de uso"), None)
    if use_case is not None:
        replace_text(use_case, "3.4.3 Diagrama de casos de uso")
        use_case.style = doc.styles["Heading 3"]

# Reemplazar los dos diagramas observados exactamente junto a sus titulos.
cap1 = next((p for p in paragraphs if p.text.strip().startswith("Figura 9:")), None)
if cap1 is not None and SPRINT1.exists():
    insert_image_before(doc, cap1, SPRINT1)
cap2 = next((p for p in paragraphs if p.text.strip().startswith("Figura 18:")), None)
if cap2 is not None and SPRINT2.exists():
    insert_image_before(doc, cap2, SPRINT2)

# Titulos de figuras y notas alineados con la imagen.
for i, p in enumerate(doc.paragraphs):
    text = p.text.strip()
    if re.match(r"^figura\s+\d+", text, flags=re.I):
        corrected = re.sub(r"^figura", "Figura", text, flags=re.I)
        corrected = re.sub(r"^(Figura\s+\d+)\s*:\s*", r"\1. ", corrected)
        replace_text(p, corrected)
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        if i + 1 < len(doc.paragraphs) and doc.paragraphs[i + 1].text.strip().startswith("Nota:"):
            doc.paragraphs[i + 1].alignment = WD_ALIGN_PARAGRAPH.CENTER

# Evitar que titulos queden separados de su primer concepto.
for p in doc.paragraphs:
    if p.style.name.startswith("Heading"):
        p.paragraph_format.keep_with_next = True

# Word actualizara indices, numeracion y referencias al abrir el archivo.
settings = doc.settings._element
update = settings.find(qn("w:updateFields"))
if update is None:
    update = OxmlElement("w:updateFields")
    settings.append(update)
update.set(qn("w:val"), "true")

doc.save(OUTPUT)
print(OUTPUT)
