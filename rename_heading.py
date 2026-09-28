import os

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            content = f.read()
            
        new_content = content.replace("'hoodies': 'Solo Sudaderas',", "'hoodies': 'Sudaderas Essentials',")
        
        with open(filepath, 'w') as f:
            f.write(new_content)
        print(f"Updated {filepath}")
