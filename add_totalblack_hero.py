import os
import shutil

user_dir = '/home/ksh/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/'
dest_dir = '/home/ksh/Documentos/aura-luxury/'
deploy_dir = os.path.join(dest_dir, 'deploy_site/')

# 1. Copy image
src_path = os.path.join(user_dir, 'media_1790704203128.png')
dest_name = 'hero_essentials_totalblack.png'

shutil.copy(src_path, os.path.join(dest_dir, dest_name))
shutil.copy(src_path, os.path.join(deploy_dir, dest_name))

# 2. Update index.html
def update_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Add button
    old_button = """<button onclick="changeHeroColor('verde')" class="w-6 h-6 rounded-full bg-green-900 border border-zinc-600 focus:outline-none focus:ring-2 focus:ring-green-900 focus:ring-offset-2 focus:ring-offset-zinc-900"></button>"""
    new_button = """<button onclick="changeHeroColor('verde')" class="w-6 h-6 rounded-full bg-green-900 border border-zinc-600 focus:outline-none focus:ring-2 focus:ring-green-900 focus:ring-offset-2 focus:ring-offset-zinc-900"></button>
                            <button onclick="changeHeroColor('totalblack')" class="w-6 h-6 rounded-full bg-[#111111] border border-zinc-600 focus:outline-none focus:ring-2 focus:ring-[#111111] focus:ring-offset-2 focus:ring-offset-zinc-900"></button>"""
    
    if 'changeHeroColor(\'totalblack\')' not in content:
        content = content.replace(old_button, new_button)

        # Update script
        old_script = """'verde': 'hero_essentials_green.png'"""
        new_script = """'verde': 'hero_essentials_green.png',
                'totalblack': 'hero_essentials_totalblack.png'"""
        content = content.replace(old_script, new_script)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_html(os.path.join(dest_dir, 'index.html'))
update_html(os.path.join(deploy_dir, 'index.html'))
