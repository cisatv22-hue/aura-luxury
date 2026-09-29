import os
import json

def update_html(filepath):
    with open('/tmp/essentials_images.json', 'r') as img_f:
        images = json.load(img_f)

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    html_block = f"""
        <!-- Unified Product View for Essentials Hoodies -->
        <div id="essentials-special-view" class="hidden mb-16">
            <div class="border border-zinc-800 rounded-2xl bg-[#0a0a0a] p-6 sm:p-12 shadow-2xl">
                <!-- Top Header -->
                <div class="text-center mb-10">
                    <h3 class="text-2xl sm:text-3xl font-black text-white tracking-widest uppercase">Sudadera Essentials</h3>
                    <p class="text-sm font-semibold text-white tracking-widest uppercase mt-2">— 6 Colores Disponibles</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">
                    <!-- Image Area -->
                    <div>
                        <span class="text-[10px] font-black text-zinc-400 tracking-[0.2em] uppercase mb-4 block">Fear of God Essentials</span>
                        <div class="rounded-xl overflow-hidden bg-neutral-900 border border-zinc-800 aspect-[4/5]">
                            <img id="unified-main-img" src="{images.get('cream', '')}" class="w-full h-full object-cover transition-opacity duration-300">
                        </div>
                    </div>

                    <!-- Details Area -->
                    <div class="flex flex-col justify-center">
                        <h4 class="text-2xl sm:text-3xl font-bold text-white uppercase tracking-wider mb-2">Sudadera Essentials</h4>
                        <p class="text-sm text-zinc-300 font-semibold mb-6">Hoodie de Algodón — Corte Relajado</p>
                        
                        <div class="text-4xl font-black text-amber-200 mb-2">$650 MXN</div>
                        <p class="text-xs font-bold text-white mb-8">IVA incluido • Envío gratis a partir de $1,200 MXN</p>
                        
                        <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest mb-3">Color — <span id="unified-color-name" class="text-white">Crema Beige</span></p>
                        <div class="flex flex-wrap gap-3 mb-4">
                            <!-- Color circles -->
                            <button onclick="selectUnifiedColor('cream', this)" class="unified-color-btn w-10 h-10 rounded-full bg-[#E5E0D8] border-2 border-white ring-2 ring-transparent transition-all focus:outline-none"></button>
                            <button onclick="selectUnifiedColor('white', this)" class="unified-color-btn w-10 h-10 rounded-full bg-[#FFFFFF] border-2 border-transparent transition-all focus:outline-none"></button>
                            <button onclick="selectUnifiedColor('grey', this)" class="unified-color-btn w-10 h-10 rounded-full bg-[#d4d4d4] border-2 border-transparent transition-all focus:outline-none"></button>
                            <button onclick="selectUnifiedColor('black', this)" class="unified-color-btn w-10 h-10 rounded-full bg-[#1a1a1a] border-2 border-transparent transition-all focus:outline-none"></button>
                            <button onclick="selectUnifiedColor('green', this)" class="unified-color-btn w-10 h-10 rounded-full bg-[#1B3E32] border-2 border-transparent transition-all focus:outline-none"></button>
                            <button onclick="selectUnifiedColor('total_black', this)" class="unified-color-btn w-10 h-10 rounded-full bg-[#111111] border-2 border-transparent transition-all focus:outline-none"></button>
                        </div>
                        <p class="text-[10px] font-bold text-zinc-400 mb-10 tracking-[0.15em] leading-relaxed">1 Crema • 2 Blanco • 3 Gris Claro • 4 Negro • 5 Verde Bosque • 6 Total Black</p>

                        <p class="text-xs font-bold text-zinc-400 uppercase tracking-widest mb-3">Talla</p>
                        <div class="flex gap-3 sm:gap-4 mb-8">
                            <button onclick="selectUnifiedSize('S', this)" class="unified-size-btn w-12 h-10 rounded flex items-center justify-center font-bold text-sm border border-amber-300 text-amber-300 transition-all">S</button>
                            <button onclick="selectUnifiedSize('M', this)" class="unified-size-btn w-12 h-10 rounded flex items-center justify-center font-bold text-sm border border-zinc-700 text-white hover:border-amber-300 hover:text-amber-300 transition-all">M</button>
                            <button onclick="selectUnifiedSize('L', this)" class="unified-size-btn w-12 h-10 rounded flex items-center justify-center font-bold text-sm border border-zinc-700 text-white hover:border-amber-300 hover:text-amber-300 transition-all">L</button>
                            <button onclick="selectUnifiedSize('XL', this)" class="unified-size-btn w-12 h-10 rounded flex items-center justify-center font-bold text-sm border border-zinc-700 text-white hover:border-amber-300 hover:text-amber-300 transition-all">XL</button>
                        </div>

                        <div class="flex gap-4 mb-6">
                            <div class="flex items-center justify-between border border-zinc-700 rounded px-4 py-2 w-32 bg-transparent">
                                <button onclick="updateUnifiedQty(-1)" class="text-zinc-400 hover:text-white font-bold text-lg leading-none">—</button>
                                <span id="unified-qty" class="text-white font-bold">1</span>
                                <button onclick="updateUnifiedQty(1)" class="text-zinc-400 hover:text-white font-bold text-lg leading-none">+</button>
                            </div>
                            <button onclick="addUnifiedToCart()" class="flex-1 bg-amber-200 text-black font-black text-sm uppercase tracking-wider rounded transition-all hover:bg-white active:scale-95 shadow-lg shadow-amber-200/20">Agregar al Carrito</button>
                        </div>
                        
                        <div class="flex items-center gap-2 mb-8">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <p class="text-xs font-bold text-zinc-300 tracking-wide">Pago seguro • Entregas 3-5 días • Devoluciones 30 días</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-zinc-800/50 flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-6 text-zinc-400">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        <p class="text-[10px] font-bold tracking-widest uppercase">Material: 100% algodón pesado 280g</p>
                    </div>
                    <div class="hidden sm:block w-1 h-1 bg-zinc-700 rounded-full"></div>
                    <p class="text-[10px] font-bold tracking-widest uppercase">Lavado a máquina</p>
                    <div class="hidden sm:block w-1 h-1 bg-zinc-700 rounded-full"></div>
                    <p class="text-[10px] font-bold tracking-widest uppercase">Hecho en México</p>
                </div>
            </div>
        </div>
"""

    if "essentials-special-view" not in content:
        content = content.replace('<!-- Product Cards Container -->', html_block + '\n        <!-- Product Cards Container -->')

    js_block = f"""
    <script>
        const unifiedImages = {json.dumps(images)};
        const unifiedColorNames = {{
            'cream': 'Crema Beige',
            'white': 'Blanco',
            'grey': 'Gris Claro',
            'black': 'Negro',
            'green': 'Verde Bosque',
            'total_black': 'Total Black'
        }};
        
        let currentUnifiedColor = 'Crema Beige';
        let currentUnifiedSize = 'S';
        let currentUnifiedQty = 1;

        function selectUnifiedColor(colorKey, btnElement) {{
            document.getElementById('unified-main-img').src = unifiedImages[colorKey];
            currentUnifiedColor = unifiedColorNames[colorKey];
            document.getElementById('unified-color-name').innerText = currentUnifiedColor;
            
            // Update active state of color buttons
            document.querySelectorAll('.unified-color-btn').forEach(btn => {{
                btn.classList.remove('border-white');
                btn.classList.add('border-transparent');
            }});
            btnElement.classList.remove('border-transparent');
            btnElement.classList.add('border-white');
        }}

        function selectUnifiedSize(size, btnElement) {{
            currentUnifiedSize = size;
            
            // Update active state of size buttons
            document.querySelectorAll('.unified-size-btn').forEach(btn => {{
                btn.classList.remove('border-amber-300', 'text-amber-300');
                btn.classList.add('border-zinc-700', 'text-white');
            }});
            btnElement.classList.remove('border-zinc-700', 'text-white');
            btnElement.classList.add('border-amber-300', 'text-amber-300');
        }}

        function updateUnifiedQty(delta) {{
            currentUnifiedQty += delta;
            if (currentUnifiedQty < 1) currentUnifiedQty = 1;
            document.getElementById('unified-qty').innerText = currentUnifiedQty;
        }}

        function addUnifiedToCart() {{
            const phoneNumber = "525636196042"; // Same as the standard checkout
            const message = `Hola, quiero comprar la Sudadera Essentials.\\n\\nColor: ${{currentUnifiedColor}}\\nTalla: ${{currentUnifiedSize}}\\nCantidad: ${{currentUnifiedQty}}\\nPrecio Total: $${{650 * currentUnifiedQty}} MXN`;
            const encodedMessage = encodeURIComponent(message);
            window.open(`https://wa.me/${{phoneNumber}}?text=${{encodedMessage}}`, '_blank');
        }}
    </script>
    """
    
    if "const unifiedImages =" not in content:
        content = content.replace('</body>', js_block + '\n</body>')

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_html('/home/ksh/Documentos/aura-luxury/index.html')
update_html('/home/ksh/Documentos/aura-luxury/deploy_site/index.html')
