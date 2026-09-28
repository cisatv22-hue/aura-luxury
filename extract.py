import json
import re
import base64
import os

with open('js/main.js', 'r') as f:
    content = f.read()

match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
if match:
    products = json.loads(match.group(1))
    
    os.makedirs('extracted_images', exist_ok=True)
    for p in products:
        b64 = p.get('image1', '')
        if b64.startswith('data:image/png;base64,'):
            b64_data = b64.replace('data:image/png;base64,', '')
            with open(f"extracted_images/{p['id']}_{p['name'].replace(' ', '_')}.png", 'wb') as img_f:
                img_f.write(base64.b64decode(b64_data))
    print("Images extracted!")
