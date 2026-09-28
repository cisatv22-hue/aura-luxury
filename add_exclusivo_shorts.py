import os
import base64
import json

img1_path = os.path.expanduser('~/.gemini/antigravity/brain/b995aa68-baeb-4a59-915a-fa1d5eb1a5e5/.user_uploaded/media_1790634250860.png')
img2_path = os.path.expanduser('~/.gemini/antigravity/brain/b995aa68-baeb-4a59-915a-fa1d5eb1a5e5/.user_uploaded/media_1790634260445.png')

def get_b64(path):
    with open(path, 'rb') as f:
        return "data:image/png;base64," + base64.b64encode(f.read()).decode('utf-8')

p1 = {
    "id": 109,
    "name": "Short Essentials Fear of God Black",
    "category": "streetwear",
    "price": 850,
    "tag": "Exclusivo",
    "tagClass": "badge-cream",
    "image1": get_b64(img1_path),
    "desc": "Short Essentials Fear of God en color negro. Calidad premium, dise\u00f1o minimalista.",
    "options": ["Chica (S)", "Mediana (M)", "Grande (L)", "Extra Grande (XL)"]
}

p2 = {
    "id": 110,
    "name": "Short Essentials Fear of God Grey",
    "category": "streetwear",
    "price": 250,
    "tag": "Exclusivo",
    "tagClass": "badge-cream",
    "image1": get_b64(img2_path),
    "desc": "Short Essentials Fear of God en color gris. Calidad premium, dise\u00f1o minimalista.",
    "options": ["Chica (S)", "Mediana (M)", "Grande (L)", "Extra Grande (XL)"]
}

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if not os.path.exists(filepath):
        continue
    with open(filepath, 'r') as f:
        main_js = f.read()

    new_items = ",\n" + json.dumps(p1, indent=4) + ",\n" + json.dumps(p2, indent=4)

    products_end_idx = main_js.find('];\n\n        let activeCategory')
    if products_end_idx != -1:
        new_main_js = main_js[:products_end_idx] + new_items + "\n    " + main_js[products_end_idx:]
        with open(filepath, 'w') as f:
            f.write(new_main_js)
        print(f"Added 2 shorts to {filepath}")
    else:
        print(f"Could not find insertion point in {filepath}")
