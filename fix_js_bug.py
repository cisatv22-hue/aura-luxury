import os
import re

def fix_custom_action(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find and replace the card.innerHTML assignment in renderProducts
    # We will use regex to find the section
    old_card_pattern = r'const card = document\.createElement\(\'div\'\);\s+card\.className = "group bg-neutral-950 border border-zinc-800 rounded-lg overflow-hidden metallic-border transition-all duration-300 flex flex-col justify-between reveal-up";\s+card\.style\.transitionDelay = `\$\{\(index % 4\) \* 100\}ms`;\s+card\.innerHTML = `.*?container\.appendChild\(card\);'
    
    new_card_code = r'''const card = document.createElement('div');
            card.className = "group bg-neutral-950 border border-zinc-800 rounded-lg overflow-hidden metallic-border transition-all duration-300 flex flex-col justify-between reveal-up";
            card.style.transitionDelay = `${(index % 4) * 100}ms`;
            
            const action = product.custom_action ? product.custom_action : `openModal(${product.id})`;
            const buttonText = product.custom_action ? "Ver 6 Colores" : "+ Agregar al Carrito";
            const buttonClass = product.custom_action 
                ? "mt-5 w-full py-3 bg-white text-black text-[11px] uppercase font-black tracking-wider transition-all rounded hover:bg-zinc-200" 
                : "mt-5 w-full py-3 bg-zinc-900 border border-zinc-800 hover:border-zinc-500 text-zinc-200 hover:text-white text-[11px] uppercase font-black tracking-wider transition-all rounded";

            card.innerHTML = `
                <div class="img-container relative w-full h-80 bg-zinc-900/60 cursor-pointer flex items-center justify-center p-4" onclick="${action}">
                    <span class="absolute top-3 left-3 z-10 text-[9px] font-black uppercase tracking-widest ${product.tagClass} px-2.5 py-1 rounded-sm shadow-md">
                        ${product.tag}
                    </span>
                    
                    <img src="${product.image1}" alt="${product.name}" class="w-full h-full object-contain">
                    
                    <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-neutral-950 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex justify-center">
                        <span class="text-[9px] font-black uppercase tracking-widest text-white bg-black/90 border border-zinc-700 px-4 py-2 rounded-sm hover:bg-white hover:text-black transition-all">
                            Vista Rápida
                        </span>
                    </div>
                </div>

                <div class="p-5 flex-grow flex flex-col justify-between bg-zinc-900/40">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-tight text-white group-hover:text-zinc-300 transition-colors cursor-pointer" onclick="${action}">
                            ${product.name}
                        </h3>
                        <p class="text-base font-black text-amber-200 mt-2">$${product.price.toLocaleString('es-MX')}.00 MXN</p>
                    </div>

                    <button onclick="${product.custom_action ? action : `quickAddToCart(${product.id})`}" class="${buttonClass}">
                        ${buttonText}
                    </button>
                </div>
            `;
            container.appendChild(card);'''

    content = re.sub(old_card_pattern, new_card_code, content, count=1, flags=re.DOTALL)

    # I also should fix openModal just in case someone calls it with the custom product
    old_open_modal = r'function openModal\(id\) \{\s*const product = products\.find\(p => p\.id === id\);\s*if \(\!product\) return;'
    new_open_modal = r'''function openModal(id) {
            const product = products.find(p => p.id === id);
            if (!product) return;
            
            if (product.custom_action) {
                eval(product.custom_action);
                return;
            }'''
    content = re.sub(old_open_modal, new_open_modal, content, count=1)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

fix_custom_action('/home/ksh/Documentos/aura-luxury/js/main.js')
fix_custom_action('/home/ksh/Documentos/aura-luxury/deploy_site/js/main.js')
