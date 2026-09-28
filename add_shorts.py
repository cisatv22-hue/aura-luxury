import os
import base64
import json

img1_path = os.path.expanduser('~/.gemini/antigravity/brain/767ae79e-6ba4-4c9a-a798-24c65356bc8c/.user_uploaded/media_1790289895415.png')
img2_path = os.path.expanduser('~/.gemini/antigravity/brain/767ae79e-6ba4-4c9a-a798-24c65356bc8c/.user_uploaded/media_1790289905738.png')

def get_b64(path):
    with open(path, 'rb') as f:
        return "data:image/png;base64," + base64.b64encode(f.read()).decode('utf-8')

p1 = {
    "id": 101,
    "name": "Short Jordan Tie-Dye Gris",
    "category": "streetwear",
    "price": 250,
    "tag": "Nuevo",
    "tagClass": "badge-cream",
    "image1": get_b64(img1_path),
    "desc": "Short Jordan deportivo con dise\u00f1o tie-dye en tonos grises. Material fresco y c\u00f3modo.",
    "options": ["Chica (S)", "Mediana (M)", "Grande (L)", "Extra Grande (XL)"]
}

p2 = {
    "id": 102,
    "name": "Short Jordan Negro",
    "category": "streetwear",
    "price": 250,
    "tag": "Cl\u00e1sico",
    "tagClass": "badge-cream",
    "image1": get_b64(img2_path),
    "desc": "Short Jordan deportivo cl\u00e1sico en color negro. B\u00e1sico e indispensable.",
    "options": ["Chica (S)", "Mediana (M)", "Grande (L)", "Extra Grande (XL)"]
}

with open('deploy_site/js/main.js', 'r') as f:
    main_js = f.read()

new_items = ",\n" + json.dumps(p1, indent=4) + ",\n" + json.dumps(p2, indent=4)

products_end_idx = main_js.find('];\n\n        let activeCategory')
if products_end_idx != -1:
    new_main_js = main_js[:products_end_idx] + new_items + "\n    " + main_js[products_end_idx:]
    with open('deploy_site/js/main.js', 'w') as f:
        f.write(new_main_js)
    print("Added 2 shorts to main.js")
else:
    print("Could not find insertion point")
