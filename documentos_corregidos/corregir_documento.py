from pathlib import Path

from docx import Document
from docx.oxml.ns import qn


SOURCE = Path(r"C:\Users\HP\Documents\PERFIL_TESIS_GRADO-Quinaya4.docx")
OUTPUT = Path(r"C:\laragon\www\control_emmafashion\documentos_corregidos\PERFIL_TESIS_GRADO-Quinaya4_CORREGIDO.docx")


def replace_paragraph(paragraph, text: str) -> None:
    """Replace text while retaining the paragraph and first-run formatting."""
    if paragraph.runs:
        paragraph.runs[0].text = text
        for run in paragraph.runs[1:]:
            run.text = ""
    else:
        paragraph.add_run(text)


document = Document(SOURCE)

replacements = {
    210: (
        "La tienda de ropa Emma Fashion, ubicada en la localidad de Eterazama, registra las compras y "
        "ventas en cuadernos y controla las existencias mediante la revisión física de las prendas. "
        "Estos procedimientos dificultan la consulta de la información, el conocimiento preciso del "
        "stock y la obtención del total diario de ventas, además de aumentar la posibilidad de errores "
        "y pérdida de datos."
    ),
    211: (
        "Para atender esta situación, se desarrolla una aplicación web para la gestión de compras, "
        "ventas e inventario mediante la metodología Scrum y las tecnologías Laravel, Vue.js, Inertia.js "
        "y MySQL. La aplicación permite administrar usuarios, clientes, proveedores, productos y sus "
        "variantes; registrar compras y ventas; actualizar automáticamente las entradas y salidas del "
        "inventario; y generar reportes por rangos de fechas. También incorpora un catálogo para que los "
        "clientes consulten las prendas disponibles."
    ),
    212: (
        "Las pruebas funcionales ejecutadas en cada sprint verifican el registro de las operaciones, el "
        "cálculo automático de los montos y la actualización del stock por talla y color. En consecuencia, "
        "la aplicación organiza la información de la tienda y facilita la consulta de existencias, compras "
        "y ventas para apoyar la toma de decisiones."
    ),
    219: (
        "La gestión de compras, ventas e inventario es necesaria para organizar las operaciones de una "
        "tienda de ropa y conocer la disponibilidad de cada prenda. Una aplicación web permite registrar "
        "productos, proveedores, clientes, compras y ventas, además de controlar las entradas y salidas "
        "del stock. De esta forma, la información se mantiene centralizada y puede consultarse cuando se "
        "requiere."
    ),
    220: (
        "El proyecto proporciona a la tienda Emma Fashion una aplicación web que registra las compras y "
        "ventas, calcula sus totales, actualiza las existencias y genera reportes por rangos de fechas. "
        "Asimismo, presenta un catálogo en línea para la consulta de las prendas disponibles. Estas "
        "funciones favorecen el control de las operaciones y reducen el tiempo empleado en revisar "
        "cuadernos y contar físicamente la mercadería."
    ),
    225: (
        "Este capítulo presenta el diagnóstico de la situación actual de la tienda Emma Fashion, la "
        "justificación del proyecto, la descripción y formulación del problema, los objetivos y el enfoque "
        "metodológico. El diagnóstico permite reconocer las dificultades existentes en el registro de "
        "compras y ventas, el control de las prendas disponibles y el cálculo del total diario."
    ),
    228: (
        "La tienda Emma Fashion es un establecimiento dedicado a la venta de prendas de vestir para damas, "
        "caballeros, niños y niñas. Se encuentra en la plaza principal de la localidad de Eterazama, "
        "municipio de Villa Tunari, departamento de Cochabamba. Desarrolla sus actividades comerciales "
        "desde hace aproximadamente siete años y ofrece prendas para distintas edades y ocasiones."
    ),
    229: (
        "La tienda comercializa poleras, pantalones, vestidos, blusas, chaquetas y otras prendas de "
        "diferentes marcas, tallas y colores. La propietaria y la vendedora distribuyen la mercadería en "
        "estantes, maniquíes y depósito para exhibirla y conservarla hasta su venta."
    ),
    230: (
        "La compra de mercadería es una actividad permanente. Los productos llegan mediante proveedores o "
        "compras directas efectuadas por la propietaria o la persona encargada. Al recibirlos, se revisan el "
        "tipo de prenda, la marca, la talla, el color y el estado; después se organizan para exhibición o "
        "almacenamiento. La cantidad recibida, el precio y el monto total se anotan en cuadernos."
    ),
    231: (
        "La atención de una venta comienza cuando el cliente consulta por una prenda. La persona encargada "
        "revisa físicamente las tallas, colores y marcas disponibles, muestra las opciones y, una vez elegida "
        "la prenda, registra en un cuaderno su descripción y precio. Al finalizar la jornada, los importes "
        "anotados se utilizan para calcular el total vendido."
    ),
    232: (
        "El control de existencias se realiza mediante la observación y el conteo de las prendas ubicadas en "
        "estantes, maniquíes y depósito. Cuando se consulta la disponibilidad, la persona encargada revisa "
        "directamente la mercadería y, en algunos casos, recurre a su memoria. Este procedimiento no permite "
        "conocer de manera inmediata la cantidad disponible por talla y color."
    ),
    233: (
        "La propietaria efectúa el cierre diario mediante la revisión del cuaderno y la suma de los montos "
        "registrados. Luego compara el resultado con el dinero en efectivo para comprobar el total de las "
        "ventas de la jornada."
    ),
    239: (
        "El proyecto es técnicamente viable porque utiliza Laravel, Vue.js, Inertia.js, MySQL y Tailwind CSS, "
        "tecnologías compatibles con el desarrollo de una aplicación web. La solución contempla módulos de "
        "usuarios, productos, proveedores, compras, clientes, ventas, inventario y reportes. Estos componentes "
        "permiten centralizar los registros y actualizar las existencias a partir de las entradas y salidas."
    ),
    241: (
        "El proyecto beneficia a la propietaria, a la vendedora y a los clientes de Emma Fashion. La "
        "aplicación facilita el registro y la consulta de compras, ventas y existencias, mientras que el "
        "catálogo permite revisar las características y disponibilidad de las prendas. Con ello se favorece "
        "una atención más ordenada y oportuna."
    ),
    243: (
        "La aplicación reduce el tiempo destinado a revisar registros y realizar cálculos manuales. Además, "
        "emplea tecnologías de software libre y de código abierto, como Laravel, Vue.js, Tailwind CSS, MySQL "
        "y Visual Studio Code, por lo que no requiere adquirir licencias para su desarrollo."
    ),
    245: (
        "El proyecto permite aplicar conocimientos de análisis y diseño de sistemas, programación, bases de "
        "datos, ingeniería de software y desarrollo web adquiridos en la carrera de Sistemas Informáticos del "
        "Instituto Tecnológico Eterazama. Asimismo, constituye una experiencia de investigación aplicada a una "
        "necesidad del entorno comercial de la localidad."
    ),
    248: (
        "Los datos de las prendas se consultan mediante la revisión directa de los productos distribuidos en "
        "estantes, maniquíes y depósito. La ausencia de un registro centralizado por categoría, marca, talla y "
        "color ocasiona demoras al buscar una prenda y comprobar su disponibilidad."
    ),
    249: (
        "Todas las compras de mercadería se anotan en cuadernos, incluyendo las prendas recibidas, cantidades, "
        "precios y montos totales. La dispersión de estos registros dificulta controlar las entradas y aumenta "
        "el riesgo de pérdida de datos y errores en los costos."
    ),
    250: (
        "Las ventas se registran en un cuaderno con la descripción de la prenda y su precio. La falta de datos "
        "sobre el cliente y la variante vendida dificulta revisar las ventas efectuadas y determinar las "
        "salidas correspondientes por talla y color."
    ),
    251: (
        "Las existencias se determinan mediante el conteo físico de las prendas y, en algunos casos, con base "
        "en la memoria de la persona encargada. Este procedimiento dificulta conocer el stock exacto y demora "
        "la atención al cliente. Además, el total diario se obtiene sumando los montos del cuaderno, lo que "
        "requiere tiempo y aumenta la posibilidad de errores al compararlo con el dinero en efectivo."
    ),
    253: (
        "¿Cómo mejorar la gestión de compras, ventas e inventario en la tienda de ropa Emma Fashion, ubicada "
        "en la localidad de Eterazama, durante la gestión 2026?"
    ),
    256: (
        "Desarrollar una aplicación web para la gestión de compras, ventas e inventario en la tienda de ropa "
        "Emma Fashion, ubicada en la localidad de Eterazama, durante la gestión 2026."
    ),
    258: (
        "Implementar los módulos de productos, catálogo y usuarios para registrar las prendas por categoría, "
        "marca, talla y color, visualizar su disponibilidad y asignar roles y permisos."
    ),
    259: (
        "Implementar los módulos de proveedores y compras para registrar la mercadería adquirida, sus "
        "cantidades y precios, y actualizar las entradas del inventario."
    ),
    260: (
        "Implementar los módulos de clientes y ventas para registrar las prendas vendidas por talla y color, "
        "validar el stock, calcular el total y actualizar las salidas del inventario."
    ),
    261: (
        "Implementar los módulos de inventario y reportes para consultar las existencias, entradas y salidas, "
        "y generar reportes de compras y ventas por rangos de fechas con exportación en PDF."
    ),
    264: (
        "Para recolectar la información se emplea la entrevista, apoyada por una guía de preguntas dirigida a "
        "la propietaria. Esta técnica permite conocer el funcionamiento de los procesos de compras, ventas y "
        "control de existencias de la tienda."
    ),
    265: (
        "El desarrollo se organiza mediante Scrum, marco de trabajo que divide las funcionalidades en sprints "
        "y permite revisar progresivamente los resultados con la propietaria. También se utiliza UML para "
        "representar los casos de uso, las clases y los componentes de la aplicación."
    ),
    266: (
        "La aplicación se desarrolla con Laravel y PHP en el servidor, Vue.js e Inertia.js para la interfaz y "
        "su comunicación con el backend, MySQL para el almacenamiento de datos y Tailwind CSS para el diseño "
        "visual. Visual Studio Code se utiliza para la edición del código y draw.io para elaborar diagramas."
    ),
    271: "",
    272: (
        "Este capítulo presenta los antecedentes y conceptos relacionados con las compras, ventas y el control "
        "de existencias. Asimismo, desarrolla la metodología Scrum y las tecnologías utilizadas en la "
        "construcción de la aplicación web Emma Fashion."
    ),
    280: "Ferrero Manauta (2022) plantea:",
    281: (
        "Una aplicación web es una herramienta a la que los usuarios acceden mediante un navegador conectado "
        "a Internet o a una intranet. Su ejecución se apoya en tecnologías interpretadas por el navegador y "
        "en servicios proporcionados por un servidor web (p. 2)."
    ),
    287: "Compra.",
    288: (
        "Ricardo (2025) define la compra como una transacción en la que el comprador entrega dinero u otro "
        "valor y recibe, a cambio, un bien o servicio proporcionado por el vendedor (p. 1)."
    ),
    290: (
        "López (2021) define la venta como un proceso de intercambio en el que el vendedor entrega un bien, "
        "servicio o activo y el comprador proporciona dinero o crédito a cambio (p. 1)."
    ),
    292: (
        "Rubio (2025) define el inventario como el conjunto de bienes que una empresa mantiene para la venta, "
        "incluidas las materias primas, los productos en proceso o las mercancías terminadas (p. 1)."
    ),
    294: (
        "Pérez y Gardey (2020) definen el control como la función administrativa que permite supervisar, "
        "verificar y evaluar las actividades de una organización para comprobar el cumplimiento de los "
        "objetivos, las normas y los planes establecidos (p. 1)."
    ),
    296: (
        "Bustamante (2026) define el catálogo como un registro ordenado de elementos, productos o servicios "
        "que facilita su búsqueda, consulta y clasificación (p. 1)."
    ),
    297: "Metodología de desarrollo de software",
    302: "",
    306: "",
    310: "",
}

for index, text in replacements.items():
    replace_paragraph(document.paragraphs[index], text)

# Remove inherited gray shading from the corrected opening sections.
for paragraph in document.paragraphs[209:311]:
    properties = paragraph._p.pPr
    if properties is not None:
        for shading in list(properties.findall(qn("w:shd"))):
            properties.remove(shading)
    for run in paragraph.runs:
        run_properties = run._r.rPr
        if run_properties is not None:
            for shading in list(run_properties.findall(qn("w:shd"))):
                run_properties.remove(shading)

# Correct visible heading spelling and remove punctuation discouraged by the reviewer.
for paragraph in document.paragraphs:
    if paragraph.text.strip() == "CAPÍTULO II :MARCO TEORICO CONCEPTUAL":
        replace_paragraph(paragraph, "")
    if paragraph.text.strip() == "CAPITULO II :MARCO TEORICO CONCEPTUAL":
        replace_paragraph(paragraph, "")

OUTPUT.parent.mkdir(parents=True, exist_ok=True)
document.save(OUTPUT)
print(OUTPUT)
