import json
import re

with open('js/main.js', 'r') as f:
    content = f.read()

match = re.search(r'const products = (\[.*?\]);', content, re.DOTALL)
if match:
    products = json.loads(match.group(1))
    id108 = next((p for p in products if p['id'] == 108), None)
    id109 = next((p for p in products if p['id'] == 109), None)
    
    if id108 and id109:
        if id108['image1'] == id109['image1']:
            print("ID 108 and 109 have the EXACT SAME base64 image!")
        else:
            print("ID 108 and 109 have DIFFERENT images.")
        
        print("ID 108 name:", id108['name'])
        print("ID 109 name:", id109['name'])
