import os
import base64
import json

img_path = os.path.expanduser('~/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/media_1790631457394.png')

with open(img_path, 'rb') as f:
    img_b64 = "data:image/png;base64," + base64.b64encode(f.read()).decode('utf-8')

product = {
    "id": 104,
    "name": "Short Essentials Fear of God Gris",
    "category": "streetwear",
    "price": 250,
    "tag": "Essentials",
    "tagClass": "badge-cream",
    "image1": img_b64,
    "desc": "Short deportivo de la marca Fear of God Essentials en color gris. Ideal para streetwear.",
    "options": ["Chica (S)", "Mediana (M)", "Grande (L)", "Extra Grande (XL)"]
}

new_item = ",\n" + json.dumps(product, indent=4)

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            content = f.read()
            
        insertion_point = content.find('];\n\n        let activeCategory')
        if insertion_point != -1:
            new_content = content[:insertion_point] + new_item + "\n    " + content[insertion_point:]
            with open(filepath, 'w') as f:
                f.write(new_content)
            print(f"Successfully added to {filepath}")
        else:
            print(f"Could not find insertion point in {filepath}")
    else:
        print(f"File {filepath} not found")
