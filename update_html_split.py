import os
import re

def update_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find the old block and replace
    old_html_pattern = re.compile(r'<!-- Unified Product View for Essentials Hoodies -->.*?</div>\s*</div>\s*</div>', re.DOTALL)
    
    new_html = r'''<!-- Unified Product View for Essentials Hoodies -->
        <div id="essentials-special-view" class="hidden mb-16">
            <div class="grid grid-cols-1 lg:grid-cols-2 bg-[#0a0a0a] rounded-xl overflow-hidden shadow-2xl">
                <!-- Left side: Model Image -->
                <div class="h-full relative bg-black">
                    <img id="unified-model-img" src="model_crema.png" class="w-full h-[500px] lg:h-full object-cover object-top">
                </div>
                <!-- Right side: Details -->
                <div class="p-8 lg:p-12 xl:p-16 flex flex-col justify-center border-l border-zinc-900">
                    <div class="text-right text-[9px] font-black text-zinc-500 tracking-[0.2em] uppercase mb-8">ESSENTIALS • FEAR OF GOD</div>
                    
                    <!-- Flat Image Box -->
                    <div class="border border-[#3d1a1a] rounded-lg p-1 mb-8 bg-[#050505] w-full max-w-sm transition-all hover:border-red-900">
                        <img id="unified-flat-img" src="" class="w-full h-40 object-cover rounded">
                    </div>

                    <h4 class="text-xl sm:text-2xl font-black text-white uppercase tracking-widest mb-4">SUDADERA ESSENTIALS FEAR OF GOD</h4>
                    <div class="text-xl font-bold text-white mb-10">$650 MXN</div>

                    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-4">COLOR — <span id="unified-color-name" class="text-white">CREMA</span></p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-10">
                        <!-- Color Pills -->
                        <button onclick="selectUnifiedColor('cream', this)" class="unified-color-btn flex items-center gap-3 px-4 py-2.5 rounded-full border border-white text-white transition-all focus:outline-none">
                            <span class="w-4 h-4 rounded-full bg-[#a39480]"></span>
                            <span class="text-[10px] font-bold tracking-widest uppercase">CREMA</span>
                        </button>
                        <button onclick="selectUnifiedColor('white', this)" class="unified-color-btn flex items-center gap-3 px-4 py-2.5 rounded-full border border-zinc-800 text-zinc-400 hover:border-zinc-500 hover:text-white transition-all focus:outline-none">
                            <span class="w-4 h-4 rounded-full bg-[#FFFFFF]"></span>
                            <span class="text-[10px] font-bold tracking-widest uppercase">BLANCO</span>
                        </button>
                        <button onclick="selectUnifiedColor('grey', this)" class="unified-color-btn flex items-center gap-3 px-4 py-2.5 rounded-full border border-zinc-800 text-zinc-400 hover:border-zinc-500 hover:text-white transition-all focus:outline-none">
                            <span class="w-4 h-4 rounded-full bg-[#9f9e9e]"></span>
                            <span class="text-[10px] font-bold tracking-widest uppercase">GRIS</span>
                        </button>
                        <button onclick="selectUnifiedColor('black', this)" class="unified-color-btn flex items-center gap-3 px-4 py-2.5 rounded-full border border-zinc-800 text-zinc-400 hover:border-zinc-500 hover:text-white transition-all focus:outline-none">
                            <span class="w-4 h-4 rounded-full bg-[#1a1a1a]"></span>
                            <span class="text-[10px] font-bold tracking-widest uppercase">NEGRO</span>
                        </button>
                        <button onclick="selectUnifiedColor('green', this)" class="unified-color-btn flex items-center gap-3 px-4 py-2.5 rounded-full border border-zinc-800 text-zinc-400 hover:border-zinc-500 hover:text-white transition-all focus:outline-none">
                            <span class="w-4 h-4 rounded-full bg-[#1B3E32]"></span>
                            <span class="text-[10px] font-bold tracking-widest uppercase">VERDE</span>
                        </button>
                        <button onclick="selectUnifiedColor('total_black', this)" class="unified-color-btn flex items-center gap-3 px-4 py-2.5 rounded-full border border-zinc-800 text-zinc-400 hover:border-zinc-500 hover:text-white transition-all focus:outline-none">
                            <span class="w-4 h-4 rounded-full bg-[#050505]"></span>
                            <span class="text-[10px] font-bold tracking-widest uppercase">TOTAL BLACK</span>
                        </button>
                    </div>

                    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-4">TALLA — <span id="unified-size-name" class="text-white">L</span></p>
                    <div class="flex gap-3 mb-12">
                        <button onclick="selectUnifiedSize('S', this)" class="unified-size-btn w-14 h-10 rounded-lg flex items-center justify-center font-bold text-sm border border-zinc-800 text-zinc-400 hover:border-zinc-500 hover:text-white transition-all focus:outline-none">S</button>
                        <button onclick="selectUnifiedSize('M', this)" class="unified-size-btn w-14 h-10 rounded-lg flex items-center justify-center font-bold text-sm border border-zinc-800 text-zinc-400 hover:border-zinc-500 hover:text-white transition-all focus:outline-none">M</button>
                        <button onclick="selectUnifiedSize('L', this)" class="unified-size-btn w-14 h-10 rounded-lg flex items-center justify-center font-bold text-sm border border-white text-white transition-all focus:outline-none">L</button>
                        <button onclick="selectUnifiedSize('XL', this)" class="unified-size-btn w-14 h-10 rounded-lg flex items-center justify-center font-bold text-sm border border-zinc-800 text-zinc-400 hover:border-zinc-500 hover:text-white transition-all focus:outline-none">XL</button>
                    </div>

                    <!-- Add to cart -->
                    <button onclick="addUnifiedToCart()" class="w-full bg-white text-black font-extrabold text-sm uppercase tracking-widest rounded-lg py-4 transition-all hover:bg-zinc-200 active:scale-[0.98] mb-8 flex items-center justify-center gap-3">
                        ADD TO CART <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                    
                    <p class="text-[8px] font-bold text-zinc-600 text-center tracking-widest uppercase leading-relaxed max-w-sm mx-auto">
                        Free shipping en pedidos +$1200 MXN • Devoluciones 30 días • Fabricado en 100% algodón pesado 320gsm
                    </p>
                </div>
            </div>
        </div>'''
    
    # We replace the HTML block
    content = old_html_pattern.sub(new_html, content, count=1)

    # Now we need to update the JS block.
    # The current JS block uses `unifiedImages`. We will add `unifiedModelImages`.
    js_old_pattern = re.compile(r'<script>\s*const unifiedImages =.*?</script>', re.DOTALL)
    
    js_new = r'''<script>
        // Use the base64 flat images generated before
        // The script in `js/main.js` might have injected unifiedImages, but we can rely on what was previously there or we rewrite it.
        // Wait, since I'm doing regex, let's keep `const unifiedImages = {...}` as it was, but we just re-declare it by reading from the old block or we reconstruct it.
        // Actually, we can fetch the old `unifiedImages` string by capturing it.
        // Wait, I'll let python replace the whole block since I know the exact logic needed.
'''

    with open('/tmp/essentials_images.json', 'r') as img_f:
        import json
        images = json.load(img_f)

    js_new = f'''<script>
        const unifiedImages = {json.dumps(images)};
        
        const unifiedModelImages = {{
            'cream': 'model_crema.png',
            'white': 'model_blanco.png',
            'grey': 'model_gris.png',
            'black': 'model_negro.png',
            'green': 'model_negro.png', // Temporary
            'total_black': 'model_negro.png' // Temporary
        }};

        const unifiedColorNames = {{
            'cream': 'CREMA',
            'white': 'BLANCO',
            'grey': 'GRIS',
            'black': 'NEGRO',
            'green': 'VERDE',
            'total_black': 'TOTAL BLACK'
        }};
        
        let currentUnifiedColor = 'CREMA';
        let currentUnifiedSize = 'L'; // Default

        // Initialize flat image on load
        window.addEventListener('DOMContentLoaded', () => {{
            const flatImg = document.getElementById('unified-flat-img');
            if (flatImg) flatImg.src = unifiedImages['cream'];
        }});

        function selectUnifiedColor(colorKey, btnElement) {{
            document.getElementById('unified-model-img').src = unifiedModelImages[colorKey];
            document.getElementById('unified-flat-img').src = unifiedImages[colorKey];
            
            currentUnifiedColor = unifiedColorNames[colorKey];
            document.getElementById('unified-color-name').innerText = currentUnifiedColor;
            
            // Update active state of color buttons
            document.querySelectorAll('.unified-color-btn').forEach(btn => {{
                btn.classList.remove('border-white', 'text-white');
                btn.classList.add('border-zinc-800', 'text-zinc-400');
            }});
            btnElement.classList.remove('border-zinc-800', 'text-zinc-400');
            btnElement.classList.add('border-white', 'text-white');
        }}

        function selectUnifiedSize(size, btnElement) {{
            currentUnifiedSize = size;
            document.getElementById('unified-size-name').innerText = size;
            
            // Update active state of size buttons
            document.querySelectorAll('.unified-size-btn').forEach(btn => {{
                btn.classList.remove('border-white', 'text-white');
                btn.classList.add('border-zinc-800', 'text-zinc-400');
            }});
            btnElement.classList.remove('border-zinc-800', 'text-zinc-400');
            btnElement.classList.add('border-white', 'text-white');
        }}

        function addUnifiedToCart() {{
            const phoneNumber = "525516069816"; // Same as the standard checkout
            const message = `Hola, quiero comprar la Sudadera Essentials Fear of God.\\n\\nColor: ${{currentUnifiedColor}}\\nTalla: ${{currentUnifiedSize}}\\nPrecio Total: $650 MXN`;
            const encodedMessage = encodeURIComponent(message);
            window.open(`https://wa.me/${{phoneNumber}}?text=${{encodedMessage}}`, '_blank');
        }}
    </script>'''

    content = js_old_pattern.sub(js_new, content, count=1)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_html('/home/ksh/Documentos/aura-luxury/index.html')
update_html('/home/ksh/Documentos/aura-luxury/deploy_site/index.html')
