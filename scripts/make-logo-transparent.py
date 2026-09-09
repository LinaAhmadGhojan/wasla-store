from PIL import Image
from pathlib import Path

src_dir = Path(r'E:\erp\wasla-store\logo')
out_dir = Path(r'E:\erp\wasla-store\public\brand')
out_dir.mkdir(parents=True, exist_ok=True)

BLACK_MAX = 28
PAD = 16

files = [
    'wasla-id-horizontal.png',
    'wasla-id-mark.png',
    'wasla-id-stacked.png',
    'wasla-id-store-mark.png',
]

def make_white_on_transparent(name):
    img = Image.open(src_dir / name).convert('RGBA')
    pixels = img.load()
    w, h = img.size
    for y in range(h):
        for x in range(w):
            r, g, b, a = pixels[x, y]
            strength = max(r, g, b)
            if strength <= BLACK_MAX:
                pixels[x, y] = (0, 0, 0, 0)
            else:
                # Full white for logo ink (teal + white). Soft edge if near black.
                if strength >= 55:
                    alpha = 255
                else:
                    alpha = int((strength - BLACK_MAX) / (55 - BLACK_MAX) * 255)
                pixels[x, y] = (255, 255, 255, max(0, min(255, alpha)))

    bbox = img.getbbox()
    if bbox:
        left, top, right, bottom = bbox
        left = max(0, left - PAD)
        top = max(0, top - PAD)
        right = min(w, right + PAD)
        bottom = min(h, bottom + PAD)
        img = img.crop((left, top, right, bottom))
    return img

for name in files:
    img = make_white_on_transparent(name)
    img.save(out_dir / name, 'PNG')
    img.save(out_dir / name.replace('.png', '-white.png'), 'PNG')
    print('OK', name, img.size)

print('done')
