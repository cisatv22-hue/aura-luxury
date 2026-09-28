import re
import json

with open('task-428-output.diff', 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

# Extract just the added lines that are inside the products array.
added_lines = []
in_hunk = False
for line in lines:
    if line.startswith('@@'):
        in_hunk = True
        continue
    if in_hunk:
        if line.startswith('+') and not line.startswith('+++'):
            added_lines.append(line[1:])

added_text = ''.join(added_lines)

# We want to extract IDs 101, 102, 109, 110.
# We can just extract them using regex, since we know their structure.
# They look like: { "id": 101, ... }
objects = []
matches = re.finditer(r'\{\s*"id":\s*(\d+).*?\}', added_text, re.DOTALL)
for match in matches:
    obj_str = match.group(0)
    # The regex might stop at the first '}' which is wrong if there's an array inside, like "options": [...]
    # So let's write a quick bracket matcher.
    start = match.start()
    brace_count = 0
    in_obj = False
    for i in range(start, len(added_text)):
        if added_text[i] == '{':
            in_obj = True
            brace_count += 1
        elif added_text[i] == '}':
            brace_count -= 1
            if brace_count == 0 and in_obj:
                obj_str = added_text[start:i+1]
                try:
                    obj = json.loads(obj_str)
                    objects.append(obj)
                except Exception as e:
                    pass
                break

with open('js/main.js', 'r', encoding='utf-8') as f:
    main_js = f.read()

match = re.search(r'const products = (\[.*?\]);', main_js, re.DOTALL)
if match:
    products = json.loads(match.group(1))
    existing_ids = {p['id'] for p in products}
    
    new_products = [o for o in objects if o['id'] not in existing_ids and o['id'] in [101, 102, 109, 110]]
    
    if new_products:
        products.extend(new_products)
        cat_order = {
            'essentials_hoodies': 1,
            'hoodies': 2,
            'streetwear': 3,
            'chains': 4,
            'caps': 5,
            'combos': 6
        }
        products.sort(key=lambda p: cat_order.get(p.get('category', ''), 99))
        
        new_json = json.dumps(products, indent=4)
        new_main_js = main_js[:match.start(1)] + new_json + main_js[match.end(1):]
        
        with open('js/main.js', 'w', encoding='utf-8') as f:
            f.write(new_main_js)
        
        with open('deploy_site/js/main.js', 'w', encoding='utf-8') as f:
            f.write(new_main_js)
        print(f"Added {len(new_products)} products back to main.js: {[p['id'] for p in new_products]}")
    else:
        print("No new products found to add.")
