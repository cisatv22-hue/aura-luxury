import os
import json
import re

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            content = f.read()
            
        match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
        if match:
            products = json.loads(match.group(1))
            for p in products:
                # If it's the shorts, change its ID to 108
                if p['name'] == 'Short Essentials Fear of God Gris':
                    p['id'] = 108
            
            # replace the json
            new_json = json.dumps(products, indent=4)
            new_content = content[:match.start(1)] + new_json + content[match.end(1):]
            
            with open(filepath, 'w') as f:
                f.write(new_content)
            print(f"Updated {filepath}")
