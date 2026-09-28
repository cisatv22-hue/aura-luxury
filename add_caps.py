import os
import base64
import json

img1_path = os.path.expanduser('~/.gemini/antigravity/brain/767ae79e-6ba4-4c9a-a798-24c65356bc8c/.user_uploaded/media_1790290617675.jpg')
img2_path = os.path.expanduser('~/.gemini/antigravity/brain/767ae79e-6ba4-4c9a-a798-24c65356bc8c/.user_uploaded/media_1790290632084.jpg')
img3_path = os.path.expanduser('~/.gemini/antigravity/brain/767ae79e-6ba4-4c9a-a798-24c65356bc8c/.user_uploaded/media_1790290660938.jpg')
img4_path = os.path.expanduser('~/.gemini/antigravity/brain/767ae79e-6ba4-4c9a-a798-24c65356bc8c/.user_uploaded/media_1790290667517.png')
img5_path = os.path.expanduser('~/.gemini/antigravity/brain/767ae79e-6ba4-4c9a-a798-24c65356bc8c/.user_uploaded/media_1790290676193.png')

def get_b64(path, ext):
    with open(path, 'rb') as f:
        return f"data:image/{ext};base64," + base64.b64encode(f.read()).decode('utf-8')

p1 = {
    "id": 103,
    "name": "Gorra Scream Flamas Negras",
    "category": "caps",
    "price": 400,
    "tag": "Exclusivo",
    "tagClass": "badge-cream",
    "image1": get_b64(img1_path, 'jpeg'),
    "desc": "Gorra con dise\u00f1o de Scream y flamas bordadas. Estilo \u00fanico y atrevido.",
    "options": ["Unitalla"]
}

p2 = {
    "id": 104,
    "name": "Gorra LALALA Rosa",
    "category": "caps",
    "price": 400,
    "tag": "Nuevo",
    "tagClass": "badge-cream",
    "image1": get_b64(img2_path, 'jpeg'),
    "desc": "Gorra estilo trucker color rosa con dise\u00f1o LALALA y detalles bordados en la malla.",
    "options": ["Unitalla"]
}

p3 = {
    "id": 105,
    "name": "Gorra Canelo Undisputed",
    "category": "caps",
    "price": 400,
    "tag": "Premium",
    "tagClass": "badge-cream",
    "image1": get_b64(img3_path, 'jpeg'),
    "desc": "Gorra edici\u00f3n especial Undisputed CA, color negro con detalles de banderas.",
    "options": ["Unitalla"]
}

p4 = {
    "id": 106,
    "name": "Gorra Houston Dandy Nubes",
    "category": "caps",
    "price": 400,
    "tag": "Trending",
    "tagClass": "badge-cream",
    "image1": get_b64(img4_path, 'png'),
    "desc": "Gorra color negro con dise\u00f1o de nubes, letra H frontal y detalle lateral.",
    "options": ["Unitalla"]
}

p5 = {
    "id": 107,
    "name": "Gorra LA Azul Flamas",
    "category": "caps",
    "price": 400,
    "tag": "Streetwear",
    "tagClass": "badge-cream",
    "image1": get_b64(img5_path, 'png'),
    "desc": "Gorra negra con detalles y logo LA en color azul estilo flamas y rhinestones.",
    "options": ["Unitalla"]
}

with open('js/main.js', 'r') as f:
    main_js = f.read()

new_items = ",\n" + json.dumps(p1, indent=4) + ",\n" + json.dumps(p2, indent=4) + ",\n" + json.dumps(p3, indent=4) + ",\n" + json.dumps(p4, indent=4) + ",\n" + json.dumps(p5, indent=4)

products_end_idx = main_js.find('];\n\n        let activeCategory')
if products_end_idx != -1:
    new_main_js = main_js[:products_end_idx] + new_items + "\n    " + main_js[products_end_idx:]
    with open('js/main.js', 'w') as f:
        f.write(new_main_js)
    print("Added 5 caps to main.js")
else:
    print("Could not find insertion point")
