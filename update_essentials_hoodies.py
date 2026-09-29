import os
import base64
import json
import re
import mimetypes

def encode_img(filename):
    path = os.path.join('/home/ksh/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/', filename)
    with open(path, 'rb') as f:
        mime = mimetypes.guess_type(path)[0] or 'image/png'
        return f"data:{mime};base64," + base64.b64encode(f.read()).decode('utf-8')

new_hoodies = [
    {
        "id": 101,
        "name": "Sudadera Essentials Cream",
        "category": "essentials_hoodies",
        "price": 650,
        "tag": "FEAR OF GOD ESSENTIALS",
        "image1": encode_img('media_1790703382929.png'),
        "desc": "Algodón Heavyweight 480g. Corte boxy oversized con logo en relieve.",
        "options": ["Chica", "Mediana", "Grande", "Extra Grande"]
    },
    {
        "id": 102,
        "name": "Sudadera Essentials White",
        "category": "essentials_hoodies",
        "price": 650,
        "tag": "FEAR OF GOD ESSENTIALS",
        "image1": encode_img('media_1790703394729.png'),
        "desc": "Algodón Heavyweight 480g. Corte boxy oversized con logo en relieve.",
        "options": ["Chica", "Mediana", "Grande", "Extra Grande"]
    },
    {
        "id": 103,
        "name": "Sudadera Essentials Light Grey",
        "category": "essentials_hoodies",
        "price": 650,
        "tag": "FEAR OF GOD ESSENTIALS",
        "image1": encode_img('media_1790703403646.png'),
        "desc": "Algodón Heavyweight 480g. Corte boxy oversized con logo en relieve.",
        "options": ["Chica", "Mediana", "Grande", "Extra Grande"]
    },
    {
        "id": 104,
        "name": "Sudadera Essentials Black",
        "category": "essentials_hoodies",
        "price": 650,
        "tag": "FEAR OF GOD ESSENTIALS",
        "image1": encode_img('media_1790703415757.png'),
        "desc": "Algodón Heavyweight 480g. Corte boxy oversized con logo en relieve.",
        "options": ["Chica", "Mediana", "Grande", "Extra Grande"]
    },
    {
        "id": 105,
        "name": "Sudadera Essentials Green",
        "category": "essentials_hoodies",
        "price": 650,
        "tag": "EXCLUSIVO",
        "tagClass": "badge-cream",
        "image1": encode_img('media_1790703441696.png'),
        "desc": "Algodón Heavyweight 480g. Corte boxy oversized con logo en relieve.",
        "options": ["Chica", "Mediana", "Grande", "Extra Grande"]
    }
]

def update_products(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
    if match:
        products = json.loads(match.group(1))
        
        # Remove old essentials_hoodies
        products = [p for p in products if p.get('category') != 'essentials_hoodies']
        
        # Add new ones at the beginning
        products = new_hoodies + products
            
        new_json = json.dumps(products, indent=4)
        new_content = content[:match.start(1)] + new_json + content[match.end(1):]
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)

update_products('js/main.js')
update_products('deploy_site/js/main.js')
