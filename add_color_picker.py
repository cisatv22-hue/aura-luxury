import os
import shutil
import re

# 1. Copy images
user_dir = '/home/ksh/.gemini/antigravity/brain/960f2a69-e48c-4fd2-8fa7-a4ec5c34635b/.user_uploaded/'
dest_dir = '/home/ksh/Documentos/aura-luxury/'
deploy_dir = os.path.join(dest_dir, 'deploy_site/')

images = {
    'black': 'media_1790640257687.png',
    'green': 'media_1790640267208.png',
    'grey': 'media_1790640290720.png',
    'beige': 'media_1790640338205.png'
}

for color, filename in images.items():
    src = os.path.join(user_dir, filename)
    dest_name = f'hero_essentials_{color}.png'
    shutil.copy(src, os.path.join(dest_dir, dest_name))
    shutil.copy(src, os.path.join(deploy_dir, dest_name))

# 2. Update index.html
def update_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Add id to hero image if not exists
    if 'id="hero-img"' not in content:
        content = content.replace(
            '<img src="hero_essentials.png" alt="Essentials Fear of God"',
            '<img id="hero-img" src="hero_essentials.png" alt="Essentials Fear of God"'
        )

    # UI for color picker
    color_picker_html = """
                    <!-- Color Picker -->
                    <div class="mt-8">
                        <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-3">Selecciona un color</p>
                        <div class="flex gap-4">
                            <!-- Blanco -->
                            <button onclick="changeHeroColor('blanco')" class="w-8 h-8 rounded-full bg-[#E5E5E5] border-2 border-transparent hover:border-white focus:border-white transition-all shadow-[0_0_10px_rgba(255,255,255,0.1)]"></button>
                            <!-- Negro -->
                            <button onclick="changeHeroColor('negro')" class="w-8 h-8 rounded-full bg-[#1A1A1A] border-2 border-transparent hover:border-zinc-400 focus:border-zinc-400 transition-all shadow-[0_0_10px_rgba(0,0,0,0.5)]"></button>
                            <!-- Beige -->
                            <button onclick="changeHeroColor('beige')" class="w-8 h-8 rounded-full bg-[#D5CDBE] border-2 border-transparent hover:border-white focus:border-white transition-all shadow-[0_0_10px_rgba(213,205,190,0.2)]"></button>
                            <!-- Gris -->
                            <button onclick="changeHeroColor('gris')" class="w-8 h-8 rounded-full bg-[#8A8A8A] border-2 border-transparent hover:border-white focus:border-white transition-all shadow-[0_0_10px_rgba(138,138,138,0.2)]"></button>
                            <!-- Verde -->
                            <button onclick="changeHeroColor('verde')" class="w-8 h-8 rounded-full bg-[#1B3E32] border-2 border-transparent hover:border-white focus:border-white transition-all shadow-[0_0_10px_rgba(27,62,50,0.3)]"></button>
                        </div>
                    </div>
                    """

    button_html = """<button onclick="quickAddToCart(2)" class="glow-button w-full sm:w-auto px-10 py-5 bg-white text-black font-black text-xs uppercase tracking-[0.2em] rounded-sm flex items-center justify-center gap-3">
                        <i class="fa-solid fa-bolt text-amber-500"></i> Comprar Ahora
                    </button>"""
    
    if 'changeHeroColor' not in content:
        content = content.replace(button_html, button_html + color_picker_html)

    # JS for color picker
    js_func = """
    <script>
        function changeHeroColor(color) {
            const img = document.getElementById('hero-img');
            const images = {
                'blanco': 'hero_essentials.png',
                'negro': 'hero_essentials_black.png',
                'beige': 'hero_essentials_beige.png',
                'gris': 'hero_essentials_grey.png',
                'verde': 'hero_essentials_green.png'
            };
            if (images[color]) {
                img.style.opacity = '0';
                img.style.transform = 'translateY(10px) scale(0.95)';
                setTimeout(() => {
                    img.src = images[color];
                    img.style.opacity = '1';
                    img.style.transform = 'translateY(0) scale(1)';
                }, 300);
            }
        }
    </script>
</body>"""

    if 'function changeHeroColor' not in content:
        content = content.replace('</body>', js_func)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_html(os.path.join(dest_dir, 'index.html'))
update_html(os.path.join(deploy_dir, 'index.html'))
