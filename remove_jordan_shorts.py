import json
import re

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
        if match:
            products = json.loads(match.group(1))
            
            # Filter out IDs 101 and 102
            products = [p for p in products if p['id'] not in [101, 102]]
            
            new_json = json.dumps(products, indent=4)
            new_content = content[:match.start(1)] + new_json + content[match.end(1):]
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Removed Jordan shorts from {filepath}")
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
