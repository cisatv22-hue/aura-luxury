import os

def update_combo_html(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replacements
    # 1. Update the combo title
    content = content.replace(
        '<h3 class="font-display text-2xl sm:text-3xl font-black text-white uppercase mt-2">Sudadera Crema + Cadena Cubana</h3>',
        '<h3 id="combo-title" class="font-display text-2xl sm:text-3xl font-black text-white uppercase mt-2">Combo Essentials Gris + Pulsera</h3>'
    )
    
    # 2. Update the combo desc
    content = content.replace(
        '<p class="text-xs text-zinc-300 mt-1">El combo de lujo: Sudadera Heavyweight Cream ($890) + Cadena Cubana Plata ($1,500)</p>',
        '<p id="combo-desc" class="text-xs text-zinc-300 mt-1">El combo de lujo: Sudadera y Short Essentials Gris + Pulsera de Plata</p>'
    )
    
    # 3. Update the prices
    content = content.replace(
        '<span class="text-2xl font-black text-white">$2,190 MXN</span>',
        '<span class="text-2xl font-black text-white">$1,100 MXN</span>'
    )
    content = content.replace(
        '<span class="block text-xs text-zinc-500 line-through">$2,390 MXN</span>',
        '<span class="block text-xs text-zinc-500 line-through">$1,350 MXN</span>'
    )
    
    # 4. Replace items and add IDs
    old_items_start = '<div class="space-y-4">'
    old_items_end = '<!-- Included Item 2 -->'
    
    # We will just replace the whole space-y-4 div content
    import re
    # Match the space-y-4 div and its contents up to the closing div before the button
    pattern = re.compile(r'<div class="space-y-4">.*?</div>\s*</div>\s*<button onclick="addBundleToCart', re.DOTALL)
    
    new_items = '''<div class="space-y-4">
                            <!-- Included Item 1 -->
                            <div class="flex items-center gap-4 bg-neutral-950 p-3.5 border border-zinc-800 rounded">
                                <img id="combo-item1-img" src="essentials_hoodie.png" class="w-16 h-16 object-cover rounded border border-zinc-800" alt="Conjunto Essentials">
                                <div class="text-xs">
                                    <h5 id="combo-item1-title" class="text-white font-bold uppercase">Conjunto Essentials Gris</h5>
                                    <p class="text-zinc-400 mt-0.5">Sudadera + Short Algodón Heavyweight | Talla Chica-XL</p>
                                    <p class="text-amber-200 font-bold mt-1">$900.00 MXN</p>
                                </div>
                            </div>

                            <!-- Included Item 2 -->
                            <div class="flex items-center gap-4 bg-neutral-950 p-3.5 border border-zinc-800 rounded">
                                <img src="https://images.unsplash.com/photo-1611591437281-460bfbe1220a?q=80&w=300&auto=format&fit=crop" class="w-16 h-16 object-cover rounded border border-zinc-800" alt="Pulsera Plata">
                                <div class="text-xs">
                                    <h5 class="text-white font-bold uppercase">Pulsera Plata Ley .925</h5>
                                    <p class="text-zinc-400 mt-0.5">Plata Fina Maciza</p>
                                    <p class="text-amber-200 font-bold mt-1">$450.00 MXN</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button onclick="addBundleToCart(1, 3)" class='''
    
    content = pattern.sub(new_items, content)
    
    # 5. Update the color buttons to pass the color name
    content = content.replace("changeComboColor('combo_grey.png')", "changeComboColor('combo_grey.png', 'Gris')")
    content = content.replace("changeComboColor('combo_white.jpg')", "changeComboColor('combo_white.jpg', 'Blanco')")
    content = content.replace("changeComboColor('combo_black.jpg')", "changeComboColor('combo_black.jpg', 'Negro')")
    content = content.replace("changeComboColor('combo_green.jpg')", "changeComboColor('combo_green.jpg', 'Verde')")
    content = content.replace("changeComboColor('combo_beige.png')", "changeComboColor('combo_beige.png', 'Beige')")
    
    # 6. Update JS function
    old_js = '''<script>
        function changeComboColor(imgSrc) {
            document.getElementById('combo-image').src = imgSrc;
        }
    </script>'''
    
    new_js = '''<script>
        function changeComboColor(imgSrc, colorName) {
            document.getElementById('combo-image').src = imgSrc;
            document.getElementById('combo-title').innerText = 'Combo Essentials ' + colorName + ' + Pulsera';
            document.getElementById('combo-desc').innerText = 'El combo de lujo: Sudadera y Short Essentials ' + colorName + ' + Pulsera de Plata';
            document.getElementById('combo-item1-title').innerText = 'Conjunto Essentials ' + colorName;
        }
    </script>'''
    
    content = content.replace(old_js, new_js)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_combo_html('/home/ksh/Documentos/aura-luxury/index.html')
update_combo_html('/home/ksh/Documentos/aura-luxury/deploy_site/index.html')
