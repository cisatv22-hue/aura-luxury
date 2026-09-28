import os
import base64
import json
import re
from datetime import datetime

upload_dir = os.path.expanduser('~/.gemini/antigravity/brain/767ae79e-6ba4-4c9a-a798-24c65356bc8c/.user_uploaded/')
images = []
for f in os.listdir(upload_dir):
    if f.endswith('.png') or f.endswith('.jpg'):
        path = os.path.join(upload_dir, f)
        # Check if modified yesterday (Sep 23)
        mtime = datetime.fromtimestamp(os.path.getmtime(path))
        if mtime.day == 23:
            images.append(path)

# Let's read existing base64 strings to avoid duplicates
with open('deploy_site/js/main.js', 'r') as f:
    main_js = f.read()

existing_b64 = []
for match in re.finditer(r'"image1":\s*"data:image/[^;]+;base64,([^"]+)"', main_js):
    # we just take a snippet of the base64 to check
    existing_b64.append(match.group(1)[:100])

new_products = []
product_id = 10 # starting ID
for img_path in images:
    with open(img_path, 'rb') as f:
        b64_data = base64.b64encode(f.read()).decode('utf-8')
    
    # Check if this image is already in main_js
    snippet = b64_data[:100]
    if snippet in existing_b64:
        continue
    
    ext = img_path.split('.')[-1]
    img_data_uri = f"data:image/{ext};base64,{b64_data}"
    
    new_products.append({
        "id": product_id,
        "name": f"Aura Luxury Exclusivo {product_id}",
        "category": "hoodies",
        "price": 1290,
        "tag": "New Arrival",
        "tagClass": "badge-black",
        "image1": img_data_uri,
        "desc": "Nueva colecci\u00f3n exclusiva de Aura Luxury. A\u00f1adido recientemente a tu cat\u00e1logo.",
        "options": ["Chica (S)", "Mediana (M)", "Grande (L)"]
    })
    product_id += 1

print(f"Found {len(new_products)} new images to add.")

if new_products:
    # Format the new products as JSON but without the outer brackets
    new_items_json = json.dumps(new_products, indent=4)
    # Strip [ and ]
    new_items_str = new_items_json[2:-2]
    
    # Insert before the last bracket of the products array
    # Find the products array end
    products_end_idx = main_js.find('];\n\n        let activeCategory')
    if products_end_idx != -1:
        new_main_js = main_js[:products_end_idx] + ",\n" + new_items_str + "\n    " + main_js[products_end_idx:]
        with open('deploy_site/js/main.js', 'w') as f:
            f.write(new_main_js)
        print("Updated main.js")
    else:
        print("Could not find insertion point")
