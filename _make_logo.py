"""Gera a logo Dev.Stich em PNG 1080x1080 para foto de perfil do Instagram."""
from PIL import Image, ImageDraw, ImageFont
import os

SIZE = 1080
OUT = os.path.join(os.path.dirname(__file__), "devstich-logo-instagram.png")

# Paleta (mesma do site)
ACCENT  = (91, 91, 246)    # #5B5BF6
ACCENT2 = (139, 139, 255)  # #8B8BFF
WHITE   = (240, 240, 245)  # #F0F0F5
BG_DARK = (10, 10, 15)     # #0A0A0F

# --- Cria gradiente diagonal #8B8BFF -> #5B5BF6 ---
grad = Image.new("RGB", (SIZE, SIZE), ACCENT2)
gdraw = ImageDraw.Draw(grad)
for i in range(SIZE * 2):
    t = i / (SIZE * 2 - 1)
    r = int(ACCENT2[0] + (ACCENT[0] - ACCENT2[0]) * t)
    g = int(ACCENT2[1] + (ACCENT[1] - ACCENT2[1]) * t)
    b = int(ACCENT2[2] + (ACCENT[2] - ACCENT2[2]) * t)
    gdraw.line([(i, 0), (0, i)], fill=(r, g, b))

img = grad.copy()
draw = ImageDraw.Draw(img, "RGBA")

# --- Glow sutil atrás do mark ---
glow = Image.new("RGBA", (SIZE, SIZE), (0, 0, 0, 0))
gd = ImageDraw.Draw(glow)
cx, cy = SIZE // 2, SIZE // 2 - 40
for i, alpha in enumerate([8, 12, 18]):
    r = 380 - i * 60
    gd.ellipse((cx - r, cy - r, cx + r, cy + r), fill=(255, 255, 255, alpha))
from PIL import ImageFilter
glow = glow.filter(ImageFilter.GaussianBlur(40))
img.alpha_composite(glow) if img.mode == "RGBA" else img.paste(glow, (0, 0), glow)

draw = ImageDraw.Draw(img)

# --- Desenha o mark </> com linha diagonal stitch ---
# Mark centralizado, ocupa ~480px de largura
mx = SIZE // 2
my = SIZE // 2 - 40
half_w = 240   # metade da largura total do mark
half_h = 220   # metade da altura
stroke = 56    # espessura

def thick_line(d, p1, p2, w, color):
    """Linha grossa com cantos arredondados."""
    d.line([p1, p2], fill=color, width=w)
    d.ellipse((p1[0]-w//2, p1[1]-w//2, p1[0]+w//2, p1[1]+w//2), fill=color)
    d.ellipse((p2[0]-w//2, p2[1]-w//2, p2[0]+w//2, p2[1]+w//2), fill=color)

# Bracket esquerdo  <
left_top    = (mx - half_w + 60, my - half_h)
left_apex   = (mx - half_w - 60, my)
left_bot    = (mx - half_w + 60, my + half_h)
thick_line(draw, left_top, left_apex, stroke, WHITE)
thick_line(draw, left_apex, left_bot, stroke, WHITE)

# Bracket direito  >
right_top   = (mx + half_w - 60, my - half_h)
right_apex  = (mx + half_w + 60, my)
right_bot   = (mx + half_w - 60, my + half_h)
thick_line(draw, right_top, right_apex, stroke, WHITE)
thick_line(draw, right_apex, right_bot, stroke, WHITE)

# Linha diagonal "stitch" cruzando o centro
diag_top    = (mx + 70, my - half_h - 20)
diag_bot    = (mx - 70, my + half_h + 20)
thick_line(draw, diag_top, diag_bot, stroke, WHITE)

# --- Wordmark "Dev.Stich" abaixo ---
def load_font(size):
    # tenta algumas fontes do Windows que combinem com Space Grotesk
    candidates = [
        r"C:\Windows\Fonts\segoeuib.ttf",   # Segoe UI Bold
        r"C:\Windows\Fonts\seguibl.ttf",    # Segoe UI Black
        r"C:\Windows\Fonts\arialbd.ttf",    # Arial Bold
        r"C:\Windows\Fonts\verdanab.ttf",
    ]
    for p in candidates:
        if os.path.exists(p):
            return ImageFont.truetype(p, size)
    return ImageFont.load_default()

font = load_font(96)
text = "Dev.Stich"
bbox = draw.textbbox((0, 0), text, font=font)
tw = bbox[2] - bbox[0]
th = bbox[3] - bbox[1]
tx = (SIZE - tw) // 2
ty = my + half_h + 110
# leve sombra/contraste
draw.text((tx + 3, ty + 3), text, font=font, fill=(0, 0, 0, 80))
draw.text((tx, ty), text, font=font, fill=WHITE)

# --- Salva ---
img.save(OUT, "PNG", optimize=True)
print(f"OK: {OUT} ({SIZE}x{SIZE})")
