import re
with open('deploy_site/js/main.js', 'r') as f:
    main_js = f.read()

# Find the object with "Sudadera Apex Racers Half-Zip"
match = re.search(r'"name":\s*"Sudadera Apex Racers Half-Zip",.*?"image1":\s*"([^"]+)"', main_js, re.DOTALL)
if match:
    b64 = match.group(1)
    
    with open('deploy_site/index.html', 'r') as f:
        html = f.read()
    
    html = html.replace('src="input_file_1.png"', f'src="{b64}"')
    
    with open('deploy_site/index.html', 'w') as f:
        f.write(html)
    print("Replaced successfully")
else:
    print("Not found")
