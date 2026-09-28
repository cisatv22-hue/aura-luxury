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
            
            # Filter out the caps we want to remove
            names_to_remove = [
                'Gorra Scream Flamas Negras',
                'Gorra LALALA Rosa',
                'Gorra Canelo Undisputed',
                'Gorra Houston Dandy Nubes',
                'Gorra LA Azul Flamas'
            ]
            
            new_products = [p for p in products if p['name'] not in names_to_remove]
            
            # replace the json
            new_json = json.dumps(new_products, indent=4)
            new_content = content[:match.start(1)] + new_json + content[match.end(1):]
            
            with open(filepath, 'w') as f:
                f.write(new_content)
            print(f"Updated {filepath}")
