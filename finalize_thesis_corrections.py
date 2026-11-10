from pathlib import Path

from docx import Document
from docx.enum.text import WD_COLOR_INDEX
from docx.shared import Inches, Pt


PATH = Path(r"C:\laragon\www\control_emmafashion\TESIS_COMPLETA_CORREGIDA_EN_AMARILLO.docx")


def replace(paragraph, text):
    for child in list(paragraph._p):
        if child.tag != "{http://schemas.openxmlformats.org/wordprocessingml/2006/main}pPr":
            paragraph._p.remove(child)
    run = paragraph.add_run(text)
    run.font.highlight_color = WD_COLOR_INDEX.YELLOW


doc = Document(PATH)
ps = doc.paragraphs


def find(text):
    return next(p for p in ps if p.text.strip() == text)


# Capitulo I: problemas y objetivos en el mismo orden.
heading = find("1.2.1 Descripción del problema")
i = ps.index(heading)
problem_texts = [
    "El registro de compras de mercadería se realiza manualmente en cuadernos, donde se anotan la cantidad de prendas recibidas, el precio y el monto total. Esta forma de registro genera demoras al buscar información de las compras realizadas.",
    "El control del inventario se lleva a cabo mediante la revisión física de las prendas ubicadas en estanterías, maniquíes y depósito, sin un catálogo digital actualizado. Esta forma de control dificulta conocer con precisión la disponibilidad de las prendas.",
    "El registro de ventas se realiza manualmente en cuadernos, en los que se anota la prenda vendida y su precio. Esta situación ocasiona demoras al consultar información y atender a los clientes.",
    "El cálculo diario del total de ventas se efectúa manualmente revisando el cuaderno y sumando los montos anotados. Como consecuencia, se pierde tiempo y pueden producirse errores en los registros y en los totales.",
]
for paragraph, text in zip(ps[i + 1:i + 5], problem_texts):
    replace(paragraph, text)

specific = next(p for p in ps if p.text.strip() in {"Objetivos específicos", "1.3.2 Objetivos específicos"})
replace(specific, "1.3.2 Objetivos específicos")
i = ps.index(specific)
objective_texts = [
    "Implementar un módulo de compras que permita registrar la mercadería adquirida, las cantidades, los precios y los proveedores.",
    "Implementar un módulo de inventario que permita conocer de forma precisa y actualizada la disponibilidad, las entradas y las salidas de las prendas.",
    "Implementar un módulo de ventas que permita registrar las prendas vendidas y agilizar la atención al cliente.",
    "Implementar un módulo de reportes que calcule automáticamente los totales de ventas diarios, semanales y mensuales para apoyar la toma de decisiones.",
]
for paragraph, text in zip(ps[i + 1:i + 5], objective_texts):
    replace(paragraph, text)

# El objetivo general queda sin comillas.
general_heading = find("1.3.1 Objetivo General")
general = ps[ps.index(general_heading) + 1]
replace(general, general.text.strip().strip('"“”'))

# Capitulo II: definición colocada inmediatamente después del título.
app_heading = find("2.2.1 Aplicación Web")
app_heading.paragraph_format.keep_with_next = True
i = ps.index(app_heading)
definition = next(p for p in ps[i + 1:i + 5] if p.text.strip())
replace(
    definition,
    "Según Wikipedia (2018), una aplicación web es un programa que se utiliza mediante un navegador y funciona a través de internet o de una intranet. En este proyecto, la aplicación web permite gestionar productos, proveedores, clientes, compras, ventas, inventario, usuarios y reportes de la tienda Emma Fashion.",
)

# Capitulo III: retirar espacio superior de la primera hoja.
chapter3 = next((p for p in ps if p.text.strip().upper().startswith("CAPITULO III")), None)
if chapter3 is not None:
    chapter3.paragraph_format.space_before = Pt(0)
    chapter3.paragraph_format.page_break_before = True

# Sprint 1: títulos ubicados en su sección.
sprint1 = next(p for p in ps if p.text.strip() in {"Ejecución del primer Sprint 1", "3.3 Ejecución del Sprint 1"})
replace(sprint1, "3.3 Ejecución del Sprint 1")
sprint1.style = doc.styles["Heading 2"]
i = ps.index(sprint1)
backlog1 = next(p for p in ps[i:i + 15] if p.text.strip() in {"Sprint Backlog", "3.3.2 Sprint Backlog"})
replace(backlog1, "3.3.2 Sprint Backlog")
backlog1.style = doc.styles["Heading 3"]

# Sprint 2: definición de hecho correcta en el lugar original.
sprint2 = next(p for p in ps if p.text.strip() in {"Ejecución del Sprint 2", "3.4 Ejecución del Sprint 2"})
replace(sprint2, "3.4 Ejecución del Sprint 2")
sprint2.style = doc.styles["Heading 2"]
i = ps.index(sprint2)
definition_heading = ps[i + 1]
definition_body = ps[i + 2]
replace(definition_heading, "3.4.1 Definición de hecho")
definition_heading.style = doc.styles["Heading 3"]
replace(
    definition_body,
    "El Sprint 2 se considera terminado cuando los módulos de proveedores y compras permiten registrar, consultar, editar y anular información; cuando cada compra se relaciona con un proveedor y con productos existentes; cuando se calculan correctamente los subtotales y el total; y cuando las entradas o reversiones del inventario se actualizan sin eliminar el historial.",
)
backlog2 = next(p for p in ps[i:i + 16] if p.text.strip().lower() in {"sprint backlog", "3.4.2 sprint backlog"})
replace(backlog2, "3.4.2 Sprint Backlog")
backlog2.style = doc.styles["Heading 3"]
usecase2 = next(p for p in ps[i:i + 25] if p.text.strip().lower() in {"diagrama de casos de uso", "3.4.3 diagrama de casos de uso"})
replace(usecase2, "3.4.3 Diagrama de casos de uso")
usecase2.style = doc.styles["Heading 3"]

# Evitar títulos separados del contenido siguiente.
for paragraph in ps:
    if paragraph.style.name.startswith("Heading"):
        paragraph.paragraph_format.keep_with_next = True

doc.save(PATH)
print(PATH)
