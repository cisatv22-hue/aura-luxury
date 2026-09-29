import os
import shutil

# 1. Copy image
user_dir = '/home/ksh/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/'
dest_dir = '/home/ksh/Documentos/aura-luxury/'
deploy_dir = os.path.join(dest_dir, 'deploy_site/')

src_image = os.path.join(user_dir, 'media_1790697441294.png')
dest_name = 'combo_3pieces.png'

shutil.copy(src_image, os.path.join(dest_dir, dest_name))
shutil.copy(src_image, os.path.join(deploy_dir, dest_name))

# 2. Update index.html
def update_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    old_url = 'https://images.unsplash.com/photo-1552062407-291eccc2b5f1?q=80&w=1200&auto=format&fit=crop'
    new_url = 'combo_3pieces.png'
    
    content = content.replace(old_url, new_url)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_html(os.path.join(dest_dir, 'index.html'))
update_html(os.path.join(deploy_dir, 'index.html'))
