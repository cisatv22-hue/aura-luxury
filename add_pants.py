import os
import base64
import json
import re
import shutil

# 1. Update Hero Image
user_dir = os.path.expanduser('~/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/')
hero_src = os.path.join(user_dir, 'media_1790637726894.png')
shutil.copy(hero_src, 'hero_essentials.png')
shutil.copy(hero_src, 'deploy_site/hero_essentials.png')

# 2. Update index.html
def update_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Hero text
    content = content.replace('>APEX RACERS</h2>', '>FEAR OF GOD</h2>')
    content = content.replace('input_file_1.png', 'hero_essentials.png')
    content = content.replace('alt="Apex Racers Half-Zip"', 'alt="Essentials Fear of God"')
    content = content.replace('Apex Racers <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-zinc-500 to-zinc-300">Half-Zip</span>',
                              'Essentials <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-zinc-500 to-zinc-300">Fear of God</span>')
    content = content.replace('Sudadera deportiva de cuello alto con cierre metálico frontal en color negro profundo. Gráficos frontales exclusivos y parches urbanos termosellados.',
                              'Sudadera Essentials Fear of God. Calidad premium, diseño minimalista y comodidad inigualable para tu día a día.')
    content = content.replace('$480.00', '$650.00')
    
    # Add new category button
    caps_btn = """<button onclick="filterCategory('caps')" class="cat-filter-btn px-5 py-2.5 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-sm">
                    Gorras
                </button>"""
    pants_btn = """<button onclick="filterCategory('pants')" class="cat-filter-btn px-5 py-2.5 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-sm">
                    Pants Rompevientos
                </button>"""
    if 'Pants Rompevientos' not in content:
        content = content.replace(caps_btn, caps_btn + '\n                ' + pants_btn)
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_html('index.html')
update_html('deploy_site/index.html')

# 3. Add Pants to js/main.js
pants = [
    ('media_1790638187971.png', 'Pants Rompevientos Nike Negro'),
    ('media_1790638196205.png', 'Pants Rompevientos Nike Cream'),
    ('media_1790638201561.png', 'Pants Rompevientos Nike Gris')
]

new_products = []
for i, (filename, name) in enumerate(pants):
    path = os.path.join(user_dir, filename)
    with open(path, 'rb') as f:
        img_b64 = "data:image/png;base64," + base64.b64encode(f.read()).decode('utf-8')
    
    new_products.append({
        "id": 301 + i,
        "name": name,
        "category": "pants",
        "price": 450,
        "tag": "Nuevo",
        "tagClass": "badge-cream",
        "image1": img_b64,
        "desc": "Pants rompevientos Nike. Diseño urbano y ligero, ideal para destacar.",
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
                'chains': 5,
                'caps': 6,
                'combos': 7
            }
            products.sort(key=lambda p: cat_order.get(p.get('category', ''), 99))
            
            new_json = json.dumps(products, indent=4)
            new_content = content[:match.start(1)] + new_json + content[match.end(1):]
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
