import os
import shutil

# 1. Copy images
user_dir = '/home/ksh/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/'
dest_dir = '/home/ksh/Documentos/aura-luxury/'
deploy_dir = os.path.join(dest_dir, 'deploy_site/')

copies = [
    ('media_1790699394760.jpg', 'combo_white.jpg'),
    ('media_1790699403776.jpg', 'combo_black.jpg'),
    ('media_1790699427566.jpg', 'combo_green.jpg'),
    ('media_1790699507934.png', 'combo_beige.png'),
    ('media_1790697441294.png', 'combo_grey.png') # the one from before
]

for src, dst in copies:
    src_path = os.path.join(user_dir, src)
    shutil.copy(src_path, os.path.join(dest_dir, dst))
    shutil.copy(src_path, os.path.join(deploy_dir, dst))

# 2. Update index.html
def update_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Add id to image
    content = content.replace('<img src="combo_3pieces.png"', '<img id="combo-image" src="combo_3pieces.png"')

    # Add color selector
    old_text = '<p class="text-xs text-zinc-300 mt-1">El combo de lujo: Sudadera Heavyweight Cream ($890) + Cadena Cubana Plata ($1,500)</p>'
    new_text = old_text + '''
                        <div class="flex items-center gap-3 mt-4">
                            <button onclick="changeComboColor('combo_grey.png')" class="w-6 h-6 rounded-full bg-zinc-400 border border-zinc-600 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 focus:ring-offset-zinc-900"></button>
                            <button onclick="changeComboColor('combo_white.jpg')" class="w-6 h-6 rounded-full bg-white border border-zinc-600 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-zinc-900"></button>
                            <button onclick="changeComboColor('combo_black.jpg')" class="w-6 h-6 rounded-full bg-black border border-zinc-600 focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 focus:ring-offset-zinc-900"></button>
                            <button onclick="changeComboColor('combo_green.jpg')" class="w-6 h-6 rounded-full bg-green-900 border border-zinc-600 focus:outline-none focus:ring-2 focus:ring-green-900 focus:ring-offset-2 focus:ring-offset-zinc-900"></button>
                            <button onclick="changeComboColor('combo_beige.png')" class="w-6 h-6 rounded-full bg-[#E5E0D8] border border-zinc-600 focus:outline-none focus:ring-2 focus:ring-[#E5E0D8] focus:ring-offset-2 focus:ring-offset-zinc-900"></button>
                        </div>'''
    
    if 'changeComboColor' not in content:
        content = content.replace(old_text, new_text)

        # Add JS script for changeComboColor at the end of the body
        script = '''
    <script>
        function changeComboColor(imgSrc) {
            document.getElementById('combo-image').src = imgSrc;
        }
    </script>
'''
        content = content.replace('</body>', script + '</body>')

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_html(os.path.join(dest_dir, 'index.html'))
update_html(os.path.join(deploy_dir, 'index.html'))
