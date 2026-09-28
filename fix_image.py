import os
import base64
import json

img_path = 'essentials_grey.png'

with open(img_path, 'rb') as f:
    img_b64 = "data:image/png;base64," + base64.b64encode(f.read()).decode('utf-8')

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            content = f.read()
            
        import re
        match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
        if match:
            products = json.loads(match.group(1))
            for p in products:
                if p['id'] == 104:
                    p['image1'] = img_b64
            
            # replace the json
            new_json = json.dumps(products, indent=4)
            new_content = content[:match.start(1)] + new_json + content[match.end(1):]
            
            with open(filepath, 'w') as f:
                f.write(new_content)
            print(f"Updated {filepath}")
