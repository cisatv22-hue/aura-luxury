import re

with open('index.html', 'r', encoding='utf-8') as f:
    content = f.read()

target = """        const unifiedModelImages = {
            'cream': 'model_crema.png',
            'white': 'model_blanco.png',
            'grey': 'model_gris.png',
            'black': 'model_negro.png',
            'green': 'model_verde.png',
            'total_black': 'model_total_black.png'
        };"""

replacement = """        const unifiedModelImages = {
            'cream': 'model_crema.png',
            'white': 'model_blanco.png',
            'grey': 'model_gris.png',
            'black': 'model_negro.png',
            'green': 'model_verde.png',
            'total_black': 'model_total_black.png'
        };

        // Preload model images so color change is instant
        Object.values(unifiedModelImages).forEach(src => {
            const img = new Image();
            img.src = src;
        });"""

if target in content:
    content = content.replace(target, replacement)
    with open('index.html', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Successfully replaced.")
else:
    print("Target not found.")
