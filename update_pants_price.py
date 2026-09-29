import json
import re

def update_pants_price(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
    if match:
        products = json.loads(match.group(1))
        
        for p in products:
            if p.get('category') == 'pants':
                p['price'] = 250
                
        new_json = json.dumps(products, indent=4)
        new_content = content[:match.start(1)] + new_json + content[match.end(1):]
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)

update_pants_price('js/main.js')
update_pants_price('deploy_site/js/main.js')
