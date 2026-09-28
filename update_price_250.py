import json
import re

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
        if match:
            products = json.loads(match.group(1))
            updated = 0
            for p in products:
                if p.get('category') == 'streetwear':
                    p['price'] = 250
                    updated += 1
            
            new_json = json.dumps(products, indent=4)
            new_content = content[:match.start(1)] + new_json + content[match.end(1):]
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {updated} items to price 250 in {filepath}")
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
