import os

def remove_animation(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    old_script = """                img.style.opacity = '0';
                img.style.transform = 'translateY(10px) scale(0.95)';
                setTimeout(() => {
                    img.src = images[color];
                    img.style.opacity = '1';
                    img.style.transform = 'translateY(0) scale(1)';
                }, 300);"""
    
    new_script = """                img.src = images[color];"""

    content = content.replace(old_script, new_script)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

remove_animation('index.html')
remove_animation('deploy_site/index.html')
