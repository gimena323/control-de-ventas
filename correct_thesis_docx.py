from copy import deepcopy
from pathlib import Path
import re

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.shared import Cm


SOURCE = Path(r"C:\Users\HP\Documents\PERFIL_TESIS_GRADO-Quinaya4.docx")
OUTPUT = Path(r"C:\laragon\www\control_emmafashion\PERFIL_TESIS_GRADO-Quinaya4_CORREGIDO.docx")


def replace_paragraph_text(paragraph, text):
    if paragraph.runs:
        paragraph.runs[0].text = text
        for run in paragraph.runs[1:]:
            run.text = ""
    else:
        paragraph.add_run(text)


def insert_before(paragraph, text, style=None):
    new_p = OxmlElement("w:p")
    paragraph._p.addprevious(new_p)
    created = paragraph._parent.add_paragraph()
    created._p.getparent().remove(created._p)
    created._p = new_p
    created._element = new_p
    if style:
        created.style = style
    created.add_run(text)
    return created


def add_bullet(document, text):
    paragraph = document.add_paragraph()
    paragraph.add_run("·        " + text)
    paragraph.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    return paragraph


doc = Document(SOURCE)

# Uniform page margins and justified body paragraphs.
for section in doc.sections:
    section.top_margin = Cm(2.5)
    section.bottom_margin = Cm(2.5)
    section.left_margin = Cm(3)
    section.right_margin = Cm(2.5)

exact_replacements = {
    "INTRODUCCION": "INTRODUCCIÓN",
    "CAPITULO I": "CAPÍTULO I",
    "PLANTEAMIENTO DEL PROBLEMA": "PLANTEAMIENTO DEL PROBLEMA",
    "CAPITULO II": "CAPÍTULO II",
    "MARCO TEORICO CONCEPTUAL": "MARCO TEÓRICO CONCEPTUAL",
    "BIBLIOGRAFIA": "BIBLIOGRAFÍA",
    "1.1.1 Diagnostico": "1.1.1 Diagnóstico",
    "2.2.6 Catalogo": "2.2.6 Catálogo",
    "2.3.2 Metodologías agiles": "2.3.2 Metodologías ágiles",
    "2.4.7 Caracteristicas de visual Studio Code": "2.4.7 Características de Visual Studio Code",
    "2.5.3.1 Caracteristicas MySQL": "2.5.3.1 Características de MySQL",
    "Objetivo de la investigación": "1.3 Objetivos de la investigación",
    "Objetivos específicos": "1.3.2 Objetivos específicos",
    "3.4.2.1 Lista de tareas": "3.5.2.1 Lista de tareas",
    "figura 28:Interfaz control de inventario “Sprint 4”": "Figura 28: Interfaz de control de inventario del Sprint 4",
    "Tabla 21:Pruebas unitarias de caja blanca “Sprint 4”": "Tabla 22: Pruebas de caja negra del Sprint 4",
}

contains_replacements = [
    ("APLICACION WEB PARA EL CONTROL", "APLICACIÓN WEB PARA EL CONTROL"),
    ("GRADO ACADEMICO DE TECNICO SUPERIOR EN SISTEMAS INFORMATICOS", "GRADO ACADÉMICO DE TÉCNICO SUPERIOR EN SISTEMAS INFORMÁTICOS"),
    ("1 CAPITULO I PLANTEAMIENTO DEL PROBLEMA", "1 CAPÍTULO I PLANTEAMIENTO DEL PROBLEMA"),
    ("2   CAPITULO II MARCO TEORICO CONCEPTUAL", "2 CAPÍTULO II MARCO TEÓRICO CONCEPTUAL"),
    ("PROPUESTA DE INNOVACION O SOLUCION AL PROBLEMA", "PROPUESTA DE INNOVACIÓN O SOLUCIÓN AL PROBLEMA"),
    ("Planning Pocker", "Planning Poker"),
    ("Estimacion", "Estimación"),
    ("Tiempo (Dias)", "Tiempo (Días)"),
    ("Pruebas unitarias de caja blanca", "Pruebas de caja negra"),
    ("Pruebas unitarias realizadas", "pruebas de caja negra realizadas"),
    ("HU-9", "HU-09"),
    ("Gestionar de proveedores", "Gestión de proveedores"),
    ("Gestionar de reportes", "Gestión de reportes"),
    ("Registra talla", "Registrar talla"),
    ("Registra producto", "Registrar producto"),
    ("Compra anular correctamente", "Compra anulada correctamente"),
    ("a anulación de una venta", "La anulación de una venta"),
    ("un aplicación web", "una aplicación web"),
    ("del aplicación web", "de la aplicación web"),
    ("21 características particulares", "características particulares"),
    ("reportes 51 detallados", "reportes detallados"),
    ("un paso de hormigón hacia el Objetivo del Producto", "un paso concreto hacia el Objetivo del Producto"),
    ("Schwaber Sutherland", "Schwaber y Sutherland"),
    ("Ana Pérez Porto Julián Gardey", "Pérez Porto y Gardey"),
    ("Merchan Quispe", "Merchán Quispe"),
]

for paragraph in doc.paragraphs:
    stripped = paragraph.text.strip()
    if stripped in exact_replacements:
        replace_paragraph_text(paragraph, exact_replacements[stripped])
        stripped = paragraph.text.strip()
    updated = paragraph.text
    for old, new in contains_replacements:
        updated = updated.replace(old, new)
    if updated != paragraph.text:
        replace_paragraph_text(paragraph, updated)

# Targeted paragraph corrections.
for paragraph in doc.paragraphs:
    text = paragraph.text.strip()
    if text.startswith("La tienda Emma Fashion es un establecimiento"):
        replace_paragraph_text(paragraph, "La tienda Emma Fashion es un establecimiento comercial dedicado a la venta de prendas de vestir para damas, caballeros y niños. Se encuentra ubicada en la plaza principal de la localidad de Eterazama, en el municipio de Villa Tunari, departamento de Cochabamba, Bolivia. Desarrolla sus actividades comerciales desde hace aproximadamente siete años y se caracteriza por ofrecer una amplia variedad de prendas para distintas ocasiones.")
    elif text == "El cambio se efectuaba por otra prenda de diferente.":
        replace_paragraph_text(paragraph, "El cambio se efectúa por otra prenda de diferente talla, color o modelo, de acuerdo con la disponibilidad existente en la tienda.")
    elif "Los integrantes del equipo siempre buscan lograr los 11 objetivos" in text:
        replace_paragraph_text(paragraph, text.replace("Los integrantes del equipo siempre buscan lograr los 11 objetivos en equipo no de manera individual.", "Los integrantes del equipo buscan alcanzar los objetivos de manera conjunta y no individual."))
    elif text.startswith("La ejecución del Sprint 2 se enfoca"):
        replace_paragraph_text(paragraph, "El Sprint 2 se considera terminado cuando se hayan implementado correctamente los módulos de gestión de proveedores y compras. El sistema debe permitir registrar, consultar, editar y anular proveedores, además de registrar compras relacionadas con proveedores y productos existentes. También debe calcular automáticamente los subtotales y el total de cada compra, actualizar las entradas de productos en el inventario y permitir la consulta e impresión de los comprobantes correspondientes. Todas las funcionalidades deben cumplir los criterios de aceptación definidos en las historias de usuario.")
    elif text.startswith("La estudiante Gimena Quinaya Colque asume el rol"):
        replace_paragraph_text(paragraph, "La propietaria de la tienda Emma Fashion asume el rol de Product Owner, debido a que conoce las necesidades del negocio, define los requerimientos de la aplicación y establece la prioridad de las funcionalidades. La estudiante Gimena Quinaya Colque asume el rol de Development Team y es responsable del análisis, diseño, desarrollo, implementación y pruebas de la aplicación web.")
    elif text.startswith("Para la recolección de datos respecto al tema"):
        replace_paragraph_text(paragraph, "El presente proyecto corresponde a una investigación aplicada, debido a que utiliza conocimientos y herramientas informáticas para desarrollar una solución destinada a mejorar el control de compras, ventas e inventario de la tienda de ropa Emma Fashion. Asimismo, presenta un alcance descriptivo porque permite identificar y describir los procesos del establecimiento y las dificultades relacionadas con el manejo manual de la información. Para la recolección de datos se emplearon las técnicas de entrevista y observación directa. La entrevista se realizó a la propietaria mediante una guía de preguntas y la observación directa permitió identificar el registro de operaciones en cuadernos, la organización física de las prendas y el procedimiento utilizado para comprobar las existencias.")
    elif text.startswith("Para la implementación de la aplicación web se utilizan") or text.startswith("Para la implementación del aplicación web se utilizan"):
        replace_paragraph_text(paragraph, "Para la implementación de la aplicación web se utilizan Laravel como framework de desarrollo backend, PHP como lenguaje de programación, MySQL como sistema gestor de base de datos, Vue.js para el desarrollo del frontend y Tailwind CSS para el diseño de la interfaz. También se utilizan Visual Studio Code y StarUML como herramientas de apoyo para el desarrollo y modelado del sistema.")
    elif text.startswith("Económicamente, el sistema permitía"):
        replace_paragraph_text(paragraph, text.replace("Bootstrap", "Tailwind CSS"))

# Replace the four specific objectives in place.
objectives = [
    "Diseñar e implementar los módulos de gestión de usuarios y productos que permitan registrar usuarios con sus respectivos roles y permisos, así como prendas de vestir con sus categorías, marcas, modelos, tallas, colores, precios e imágenes.",
    "Implementar un módulo de compras que permita gestionar proveedores y registrar la mercadería adquirida, las cantidades y los precios, actualizando automáticamente las entradas del inventario.",
    "Implementar un módulo de ventas que permita gestionar clientes, registrar las prendas vendidas, validar las existencias disponibles y actualizar automáticamente las salidas del inventario.",
    "Implementar los módulos de inventario y reportes que permitan consultar de manera precisa y actualizada las entradas, salidas y existencias de las prendas, además de generar reportes de compras y ventas por diferentes periodos para apoyar la toma de decisiones.",
]
old_objective_prefixes = ("Diseñar un módulo de gestión de productos", "Implementar un módulo de compras", "Implementar un módulo de ventas", "Implementar un módulo de inventario y reportes")
objective_index = 0
for paragraph in doc.paragraphs:
    if paragraph.text.strip().startswith(old_objective_prefixes) and objective_index < len(objectives):
        replace_paragraph_text(paragraph, objectives[objective_index])
        objective_index += 1

# Insert missing project concepts before section 2.3.
for paragraph in doc.paragraphs:
    if paragraph.text.strip().startswith("2.3 Herramientas de Análisis"):
        concepts = [
            ("2.2.7 Kardex", "El kardex es un registro que permite controlar los movimientos de entrada y salida de los productos, así como conocer las existencias disponibles después de cada operación realizada."),
            ("2.2.8 Entradas de inventario", "Las entradas de inventario representan el incremento de las existencias de un producto. En el sistema se generan principalmente cuando se registra una compra de mercadería."),
            ("2.2.9 Salidas de inventario", "Las salidas de inventario representan la disminución de las existencias de un producto. En el sistema se generan cuando se registra una venta."),
            ("2.2.10 Saldo de inventario", "El saldo de inventario es la cantidad disponible de un producto después de considerar sus entradas y salidas."),
            ("2.2.11 Stock mínimo", "El stock mínimo es la cantidad mínima de existencias que debe mantenerse para evitar que un producto quede agotado y facilitar la reposición oportuna de mercadería."),
        ]
        for heading, body in concepts:
            insert_before(paragraph, heading, "Heading 3")
            body_p = insert_before(paragraph, body)
            body_p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        break

# Add Tailwind CSS definition before the storage section.
for paragraph in doc.paragraphs:
    if paragraph.text.strip().startswith("2.5 Medios de almacenamiento"):
        insert_before(paragraph, "2.4.11 Tailwind CSS", "Heading 3")
        body_p = insert_before(paragraph, "Tailwind CSS es un framework de estilos basado en clases de utilidad que permite diseñar interfaces directamente desde la estructura HTML. En el proyecto se utiliza para definir colores, tamaños, márgenes, distribución, adaptación a diferentes pantallas y presentación visual de los componentes de la aplicación web.")
        body_p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        break

# Correct table content and identifiers.
for table_index, table in enumerate(doc.tables, start=1):
    for row in table.rows:
        for cell in row.cells:
            for paragraph in cell.paragraphs:
                updated = paragraph.text
                for old, new in contains_replacements:
                    updated = updated.replace(old, new)
                updated = updated.replace("gimenaquinaya4@gmail.com", "administradora@emmafashion.test")
                updated = updated.replace("gimenaquinaya@gmail.com", "usuario@emmafashion.test")
                updated = updated.replace("gimena123", "********")
                updated = updated.replace("HU-9", "HU-09")
                if updated != paragraph.text:
                    replace_paragraph_text(paragraph, updated)
    if table_index == 18:
        corrections = {
            4: ("Seleccionar un producto registrado y abrir el detalle de sus movimientos de salida.", "El sistema muestra las salidas del producto generadas por las ventas, indicando fecha, cantidad y origen del movimiento.", "Correcto"),
            5: ("Seleccionar un producto del inventario y presionar la opción Ver movimientos.", "El sistema muestra el historial de entradas y salidas del producto, indicando fecha, tipo de movimiento, cantidad y origen.", "Correcto"),
            6: ("Seleccionar un producto disponible y cambiar su estado de disponibilidad.", "El sistema actualiza la disponibilidad del producto sin modificar sus existencias.", "Correcto"),
        }
        for row_number, values in corrections.items():
            if row_number < len(table.rows):
                row = table.rows[row_number]
                for column, value in zip((2, 3, 4), values):
                    row.cells[column].text = value
    # Sprint 4 backlog codes.
    if table_index == 17:
        for row in table.rows[1:]:
            code = row.cells[0].text.strip()
            if code == "HU-07":
                row.cells[0].text = "HU-11"
            elif code == "HU-08":
                row.cells[0].text = "HU-12"

# Justify normal body paragraphs conservatively.
for paragraph in doc.paragraphs:
    if paragraph.text.strip() and paragraph.style.name not in {"Title", "Subtitle", "Heading 1", "Heading 2", "Heading 3", "Heading 4"}:
        paragraph.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

# Add the complete Chapter IV without removing existing material.
doc.add_page_break()
doc.add_heading("CAPÍTULO IV", level=1)
doc.add_heading("CONCLUSIONES Y RECOMENDACIONES", level=1)
doc.add_heading("4 CAPÍTULO IV CONCLUSIONES Y RECOMENDACIONES", level=1)
intro = doc.add_paragraph("El presente capítulo expone las conclusiones y recomendaciones del proyecto, resaltando que la implementación de los módulos de gestión de usuarios, productos, proveedores, compras, clientes, ventas, inventario y reportes permitió optimizar el control y la organización del sistema. Asimismo, se recomiendan algunos aspectos técnicos para el buen funcionamiento de la aplicación web en el ambiente de la tienda, como mantener actualizada la información, registrar adecuadamente las operaciones y respaldar los datos para asegurar un funcionamiento eficiente y confiable del negocio.")
intro.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
doc.add_heading("4.1 Conclusiones", level=2)
p = doc.add_paragraph("Se desarrolló una aplicación web que permitió mejorar el control de compras, ventas e inventario en la tienda de ropa Emma Fashion, ubicada en la localidad de Eterazama. Mediante el sistema se reemplazaron los registros manuales realizados en cuadernos por procesos digitales, organizados y confiables.")
p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
add_bullet(doc, "Se implementaron los módulos de gestión de usuarios y productos. El módulo de usuarios permite registrar usuarios y asignarles sus respectivos roles y permisos. El módulo de productos permite registrar prendas de vestir con sus respectivas categorías, marcas, modelos, tallas, colores, precios e imágenes, facilitando la organización, búsqueda y consulta de la información.")
add_bullet(doc, "Se implementó un módulo de compras que permite registrar los proveedores, los productos adquiridos, las cantidades y los precios. El sistema calcula automáticamente los subtotales y el total de la compra, además de actualizar las entradas de productos en el inventario.")
add_bullet(doc, "Se implementó un módulo de ventas que permite registrar los clientes y las prendas vendidas de manera rápida y confiable. Cada venta actualiza automáticamente las salidas del inventario, facilitando el control de las existencias y mejorando la atención brindada a los clientes.")
add_bullet(doc, "Se implementó un módulo de inventario y reportes que permite consultar de manera precisa y actualizada la disponibilidad, las entradas y las salidas de las prendas. Además, se pueden generar reportes de compras y ventas por diferentes periodos para apoyar el análisis y la toma de decisiones de la propietaria.")
doc.add_heading("4.2 Recomendaciones", level=2)
recommendations = [
    "Se recomienda contratar un servicio de hosting para alojar la aplicación web y su base de datos, permitiendo acceder al sistema mediante internet. En caso de utilizar la aplicación únicamente dentro de la computadora de la tienda, se debe comprobar que el servidor local y el servicio MySQL estén iniciados correctamente.",
    "Se recomienda asignar a cada empleado una cuenta personal con su respectivo rol y contraseña. Cada usuario debe acceder solamente a las funciones correspondientes a su trabajo, mantener su contraseña en reserva y cerrar la sesión después de utilizar el sistema.",
    "Se recomienda verificar cuidadosamente la información antes de guardarla. Los nombres, categorías, marcas, tallas, colores, precios y datos de clientes o proveedores deben registrarse correctamente para evitar información incompleta o duplicada.",
    "Se recomienda revisar periódicamente las existencias registradas en el inventario y compararlas con las prendas disponibles físicamente en la tienda. Esto permitirá identificar oportunamente diferencias producidas por operaciones no registradas o datos incorrectos.",
    "Se recomienda realizar talleres de capacitación continua a los empleados de la tienda para que puedan ingresar correctamente los datos de las prendas, compras y ventas, de manera que el sistema pueda emitir resultados precisos.",
    "Finalmente, es aconsejable realizar copias de seguridad periódicas de la base de datos para evitar la pérdida de información. También se recomienda efectuar mantenimiento preventivo al sistema e incorporar en el futuro nuevas funciones que permitan mejorar la administración de la tienda de ropa Emma Fashion.",
]
for recommendation in recommendations:
    p = doc.add_paragraph(recommendation)
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

doc.save(OUTPUT)
print(OUTPUT)
