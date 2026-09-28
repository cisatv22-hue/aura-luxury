import os
import base64
import json
import re

img1_path = 'extracted_images/109_Short_Essentials_Fear_of_God_Black.png'
img2_path = 'extracted_images/110_Short_Essentials_Fear_of_God_Grey.png'

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
    "price": 850,
    "tag": "Exclusivo",
    "tagClass": "badge-cream",
    "image1": get_b64(img2_path),
    "desc": "Short Essentials Fear of God en color gris. Calidad premium, dise\u00f1o minimalista.",
    "options": ["Chica (S)", "Mediana (M)", "Grande (L)", "Extra Grande (XL)"]
}

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if not os.path.exists(filepath):
        continue
    with open(filepath, 'r', encoding='utf-8') as f:
        main_js = f.read()

    match = re.search(r'const products = (\[.*?\]);', main_js, re.DOTALL)
    if match:
        products = json.loads(match.group(1))
        existing_ids = {p['id'] for p in products}
        
        new_products = []
        if 109 not in existing_ids:
            new_products.append(p1)
        if 110 not in existing_ids:
            new_products.append(p2)
            
        if new_products:
            products.extend(new_products)
            cat_order = {
                'essentials_hoodies': 1,
                'hoodies': 2,
                'streetwear': 3,
                'chains': 4,
                'caps': 5,
                'combos': 6
            }
            products.sort(key=lambda p: cat_order.get(p.get('category', ''), 99))
            
            new_json = json.dumps(products, indent=4)
            new_main_js = main_js[:match.start(1)] + new_json + main_js[match.end(1):]
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_main_js)
            print(f"Added {len(new_products)} products back to {filepath}: {[p['id'] for p in new_products]}")
        else:
            print(f"No new products to add for {filepath}.")

