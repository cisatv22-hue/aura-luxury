import os
import json
import re

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            content = f.read()
            
        # 1. Update the categoryNames map
        # Find: 'hoodies': 'Sudaderas Essentials',
        # Replace with: 'essentials_hoodies': 'Sudaderas Essentials',\n                'hoodies': 'Solo Sudaderas',
        content = content.replace("'hoodies': 'Sudaderas Essentials',", "'essentials_hoodies': 'Sudaderas Essentials',\n                'hoodies': 'Solo Sudaderas',")
        
        # 2. Update the products array
        match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
        if match:
            products = json.loads(match.group(1))
            for p in products:
                if 'Essentials' in p['name'] and 'Sudadera' in p['name']:
                    p['category'] = 'essentials_hoodies'
                    p['price'] = 650
            
            new_json = json.dumps(products, indent=4)
            content = content[:match.start(1)] + new_json + content[match.end(1):]
            
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated {filepath}")
