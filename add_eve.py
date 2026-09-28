import os
import base64
import json

img_path = os.path.expanduser('~/.gemini/antigravity/brain/767ae79e-6ba4-4c9a-a798-24c65356bc8c/.user_uploaded/media_1790289528506.jpg')

with open(img_path, 'rb') as f:
    b64_data = base64.b64encode(f.read()).decode('utf-8')

img_data_uri = f"data:image/jpeg;base64,{b64_data}"

new_product = {
    "id": 100,
    "name": "Mensaje Especial: Te Extra\u00f1o Eve",
    "category": "accesorios",
    "price": 0,
    "tag": "Invaluable \u2764\ufe0f",
    "tagClass": "badge-cream",
    "image1": img_data_uri,
    "desc": "Un detalle muy especial y \u00fanico que no tiene precio.",
    "options": ["Para Eve"]
}

with open('deploy_site/js/main.js', 'r') as f:
    main_js = f.read()

new_item_json = json.dumps(new_product, indent=4)

products_end_idx = main_js.find('];\n\n        let activeCategory')
if products_end_idx != -1:
    new_main_js = main_js[:products_end_idx] + ",\n" + new_item_json + "\n    " + main_js[products_end_idx:]
    with open('deploy_site/js/main.js', 'w') as f:
        f.write(new_main_js)
    print("Added Eve image to main.js")
else:
    print("Could not find insertion point")
