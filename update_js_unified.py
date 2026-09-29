import os
import json
import re

def update_products_js(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
    if not match:
        return

    products = json.loads(match.group(1))

    # Save the base64 images to use them later
    images = {}
    for p in products:
        if p.get('category') == 'essentials_hoodies':
            if 'Cream' in p.get('name', ''): images['cream'] = p['image1']
            if 'White' in p.get('name', ''): images['white'] = p['image1']
            if 'Light Grey' in p.get('name', ''): images['grey'] = p['image1']
            if 'Black' in p.get('name', '') and 'Total' not in p.get('name', ''): images['black'] = p['image1']
            if 'Green' in p.get('name', ''): images['green'] = p['image1']
            if 'Total Black' in p.get('name', ''): images['total_black'] = p['image1']

    # Remove all essentials_hoodies
    products = [p for p in products if p.get('category') != 'essentials_hoodies']

    # Add a representative product to appear in 'Todos' and 'Sudaderas'
    rep_product = {
        "id": 100,
        "name": "Sudadera Essentials",
        "category": "all", # We will manually handle this or give it a custom action
        "price": 650,
        "tag": "6 COLORES",
        "image1": images.get('cream', ''),
        "desc": "Hoodie de Algodón - Corte Relajado. Selecciona para ver colores.",
        "options": ["Ver Opciones"],
        "custom_action": "filterCategory('essentials_hoodies')"
    }
    
    # Insert at beginning
    products.insert(0, rep_product)

    new_json = json.dumps(products, indent=4)
    new_content = content[:match.start(1)] + new_json + content[match.end(1):]

    # Now we need to update renderProducts to handle custom_action
    # Find renderProducts function
    # Search for: `<button onclick="addToCart`
    old_button = r'<button onclick="addToCart\(\$\{product\.id\}\)" class="mt-4 w-full bg-zinc-900 border border-zinc-700 hover:border-amber-300 hover:text-amber-300 text-white font-bold py-2 px-4 rounded text-xs transition-colors uppercase tracking-wider"\>'
    new_button = r'''${product.custom_action 
                                ? `<button onclick="${product.custom_action}" class="mt-4 w-full bg-amber-200 text-black font-bold py-2 px-4 rounded text-xs hover:bg-white transition-colors uppercase tracking-wider">Ver 6 Colores</button>`
                                : `<button onclick="addToCart(${product.id})" class="mt-4 w-full bg-zinc-900 border border-zinc-700 hover:border-amber-300 hover:text-amber-300 text-white font-bold py-2 px-4 rounded text-xs transition-colors uppercase tracking-wider">`
                            }
                            ${!product.custom_action ? '+ Agregar al carrito</button>' : ''}'''
    
    # Since the button string contains `+ Agregar al carrito</button>`, we replace the whole block
    old_button_block = r'<button onclick="addToCart\(\$\{product\.id\}\)" class="mt-4 w-full bg-zinc-900 border border-zinc-700 hover:border-amber-300 hover:text-amber-300 text-white font-bold py-2 px-4 rounded text-xs transition-colors uppercase tracking-wider"\>\s*\+\s*AGREGAR AL CARRITO\s*\<\/button\>'
    
    new_button_block = r'''${product.custom_action 
                                ? `<button onclick="${product.custom_action}" class="mt-4 w-full bg-amber-200 text-black font-extrabold py-2 px-4 rounded-sm text-xs hover:bg-white transition-colors uppercase tracking-wider">Ver 6 Colores</button>`
                                : `<button onclick="addToCart(${product.id})" class="mt-4 w-full bg-zinc-900 border border-zinc-700 hover:border-amber-300 hover:text-amber-300 text-white font-bold py-2 px-4 rounded-sm text-xs transition-colors uppercase tracking-wider">+ AGREGAR AL CARRITO</button>`
                            }'''

    new_content = re.sub(old_button_block, new_button_block, new_content)
    
    # Also update the logic in filterCategory to toggle grid vs special view
    filter_logic_old = r'function filterCategory\(cat\) \{'
    filter_logic_new = r'''function filterCategory(cat) {
            const grid = document.getElementById('product-grid');
            const specialView = document.getElementById('essentials-special-view');
            
            if (cat === 'essentials_hoodies') {
                if(grid) grid.style.display = 'none';
                if(specialView) specialView.style.display = 'block';
            } else {
                if(grid) grid.style.display = 'grid';
                if(specialView) specialView.style.display = 'none';
            }
'''
    if "essentials-special-view" not in new_content:
        new_content = new_content.replace('function filterCategory(cat) {', filter_logic_new)

    # Save images mapping somewhere in index.html (we will inject a script for this)
    # We will write the images mapping to a temp JSON file to inject into HTML later
    with open('/tmp/essentials_images.json', 'w') as img_f:
        json.dump(images, img_f)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)

update_products_js('/home/ksh/Documentos/aura-luxury/js/main.js')
update_products_js('/home/ksh/Documentos/aura-luxury/deploy_site/js/main.js')
