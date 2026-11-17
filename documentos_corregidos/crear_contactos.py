from pathlib import Path
from PIL import Image, ImageDraw


source = Path(r"C:\laragon\www\control_emmafashion\documentos_corregidos\render_final")
output = source / "contactos"
output.mkdir(exist_ok=True)
pages = sorted(source.glob("page-*.png"), key=lambda p: int(p.stem.split("-")[-1]))

per_sheet = 8
thumb_w = 360
thumb_h = 510
margin = 24
label_h = 34

for sheet_index in range(0, len(pages), per_sheet):
    group = pages[sheet_index : sheet_index + per_sheet]
    canvas = Image.new("RGB", (4 * (thumb_w + margin) + margin, 2 * (thumb_h + label_h + margin) + margin), "white")
    draw = ImageDraw.Draw(canvas)
    for offset, path in enumerate(group):
        image = Image.open(path).convert("RGB")
        image.thumbnail((thumb_w, thumb_h))
        col = offset % 4
        row = offset // 4
        x = margin + col * (thumb_w + margin) + (thumb_w - image.width) // 2
        y = margin + row * (thumb_h + label_h + margin)
        canvas.paste(image, (x, y))
        page_number = int(path.stem.split("-")[-1])
        draw.text((margin + col * (thumb_w + margin), y + thumb_h + 5), f"Página PDF {page_number}", fill="black")
    destination = output / f"contacto-{sheet_index // per_sheet + 1:02d}.png"
    canvas.save(destination)
    print(destination)
