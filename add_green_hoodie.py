import os
import base64
import json
import re

img_path = os.path.expanduser('~/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/media_1790636336518.png')

with open(img_path, 'rb') as f:
    img_b64 = "data:image/png;base64," + base64.b64encode(f.read()).decode('utf-8')

new_product = {
    "id": 112,
    "name": "Sudadera Essentials Fear of God Green",
    "category": "essentials_hoodies",
    "price": 650,
    "tag": "Exclusivo",
    "tagClass": "badge-cream",
    "image1": img_b64,
    "desc": "Sudadera Essentials Fear of God en color verde oscuro. Calidad premium, dise\u00f1o minimalista.",
    "options": ["Chica (S)", "Mediana (M)", "Grande (L)", "Extra Grande (XL)"]
}

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
        if match:
            products = json.loads(match.group(1))
            
            # Check if it already exists
            if not any(p['id'] == 112 for p in products):
                products.append(new_product)
                
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
                new_content = content[:match.start(1)] + new_json + content[match.end(1):]
                
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Added Sudadera Essentials Green to {filepath}")
            else:
                print(f"ID 112 already exists in {filepath}")
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
