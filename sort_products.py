import os
import json
import re

cat_order = {
    'essentials_hoodies': 1,
    'hoodies': 2,
    'streetwear': 3,
    'chains': 4,
    'caps': 5,
    'combos': 6
}

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            content = f.read()
            
        match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
        if match:
            products = json.loads(match.group(1))
            
            # Sort products by category
            products.sort(key=lambda p: cat_order.get(p.get('category', ''), 99))
            
            new_json = json.dumps(products, indent=4)
            content = content[:match.start(1)] + new_json + content[match.end(1):]
            
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated {filepath}")
