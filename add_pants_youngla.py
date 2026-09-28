import os
import base64
import json
import re
import mimetypes

# 1. Update index.html
def update_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    pants_btn = """<button onclick="filterCategory('pants')" class="cat-filter-btn px-5 py-2.5 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-sm">
                    Pants Rompevientos
                </button>"""
    youngla_btn = """<button onclick="filterCategory('pants_youngla')" class="cat-filter-btn px-5 py-2.5 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-sm">
                    Pants Youngla
                </button>"""
    
    if 'Pants Youngla' not in content:
        content = content.replace(pants_btn, pants_btn + '\n                ' + youngla_btn)
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_html('index.html')
update_html('deploy_site/index.html')

# 2. Add Pants to js/main.js
user_dir = os.path.expanduser('~/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/')
pants = [
    ('media_1790639061728.png', 'Pants Youngla Skulls Black'),
    ('media_1790639067041.jpg', 'Pants Youngla Skulls Grey'),
    ('media_1790639072105.png', 'Pants Youngla Black')
]

new_products = []
for i, (filename, name) in enumerate(pants):
    path = os.path.join(user_dir, filename)
    with open(path, 'rb') as f:
        mime = mimetypes.guess_type(path)[0] or 'image/png'
        img_b64 = f"data:{mime};base64," + base64.b64encode(f.read()).decode('utf-8')
    
    new_products.append({
        "id": 401 + i,
        "name": name,
        "category": "pants_youngla",
        "price": 250,
        "tag": "Exclusivo",
        "tagClass": "badge-cream",
        "image1": img_b64,
        "desc": "Pants Youngla edición exclusiva. Diseño llamativo y ajuste perfecto.",
        "options": ["Chico (S)", "Mediano (M)", "Grande (L)"]
    })

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
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
                'pants': 4,
                'pants_youngla': 5,
                'chains': 6,
                'caps': 7,
                'combos': 8
            }
            products.sort(key=lambda p: cat_order.get(p.get('category', ''), 99))
            
            new_json = json.dumps(products, indent=4)
            new_content = content[:match.start(1)] + new_json + content[match.end(1):]
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
