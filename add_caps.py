import os
import base64
import json
import re

dir_path = os.path.expanduser('~/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/')
images = [
    ('media_1790636865505.png', 'Gorra Negra M Wings', 'Gorra negra con dise\u00f1o de alas y letra M.'),
    ('media_1790636893503.png', 'Gorra H Dinosaur', 'Gorra negra con dise\u00f1o de dinosaurio y letra H.'),
    ('media_1790636921372.png', 'Gorra Pink LA LA LA', 'Gorra rosa con blanco, dise\u00f1o LA LA LA.'),
    ('media_1790637018763.png', 'Gorra Scream Ghostface', 'Gorra negra con dise\u00f1o de Scream Ghostface.'),
    ('media_1790637052710.png', 'Gorra Undisputed Canelo', 'Gorra negra Undisputed Canelo con banderas.')
]

new_products = []
for i, (filename, name, desc) in enumerate(images):
    path = os.path.join(dir_path, filename)
    with open(path, 'rb') as f:
        img_b64 = "data:image/png;base64," + base64.b64encode(f.read()).decode('utf-8')
    
    new_products.append({
        "id": 201 + i,
        "name": name,
        "category": "caps",
        "price": 400,
        "tag": "Nuevo",
        "tagClass": "badge-cream",
        "image1": img_b64,
        "desc": desc,
        "options": ["Unitalla"]
    })

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
        if match:
            products = json.loads(match.group(1))
            
            # Avoid duplicates
            existing_ids = {p['id'] for p in products}
            to_add = [p for p in new_products if p['id'] not in existing_ids]
            
            if to_add:
                products.extend(to_add)
                
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
                print(f"Added {len(to_add)} caps to {filepath}")
            else:
                print(f"Caps already exist in {filepath}")
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
