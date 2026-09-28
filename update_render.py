import os
import re

new_render_function = """        function renderProducts() {
            const container = document.getElementById('product-grid');
            container.innerHTML = '';

            let filtered = products.filter(p => {
                const matchesCat = activeCategory === 'all' || p.category === activeCategory;
                const matchesSearch = p.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                                      p.desc.toLowerCase().includes(searchQuery.toLowerCase());
                return matchesCat && matchesSearch;
            });

            document.getElementById('product-count').textContent = filtered.length;

            if (filtered.length === 0) {
                container.innerHTML = `
                    <div class="col-span-full text-center py-20 text-zinc-500">
                        <i class="fa-solid fa-magnifying-glass text-4xl mb-4 text-zinc-700"></i>
                        <p class="text-xs uppercase tracking-widest font-bold">No se encontraron productos para esta búsqueda.</p>
                    </div>
                `;
                return;
            }

            const categoryNames = {
                'hoodies': 'Solo Sudaderas',
                'chains': 'Solo Plata .925',
                'caps': 'Solo Gorras',
                'combos': 'Combos Especiales',
                'streetwear': 'Streetwear Exclusivo'
            };

            let currentCategory = null;

            filtered.forEach((product, index) => {
                // If showing all products (or searching), group by category
                if (activeCategory === 'all' && product.category !== currentCategory) {
                    currentCategory = product.category;
                    const catTitle = document.createElement('div');
                    catTitle.className = 'col-span-full mt-10 mb-2 border-b border-zinc-800 pb-2 reveal-up';
                    catTitle.innerHTML = `<h3 class="text-xl font-black uppercase tracking-widest text-amber-50">${categoryNames[currentCategory] || currentCategory}</h3>`;
                    container.appendChild(catTitle);
                }

                const card = document.createElement('div');
                card.className = "group bg-neutral-950 border border-zinc-800 rounded-lg overflow-hidden metallic-border transition-all duration-300 flex flex-col justify-between reveal-up";
                card.style.transitionDelay = `${(index % 4) * 100}ms`;
                
                card.innerHTML = `
                    <div class="img-container relative w-full h-80 bg-zinc-900/60 cursor-pointer flex items-center justify-center p-4" onclick="openModal(${product.id})">
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
                            <h3 class="text-xs font-black uppercase tracking-tight text-white group-hover:text-zinc-300 transition-colors cursor-pointer" onclick="openModal(${product.id})">
                                ${product.name}
                            </h3>
                            <p class="text-base font-black text-amber-200 mt-2">$${product.price.toLocaleString('es-MX')}.00 MXN</p>
                        </div>

                        <button onclick="quickAddToCart(${product.id})" class="mt-5 w-full py-3 bg-zinc-900 border border-zinc-800 hover:border-zinc-500 text-zinc-200 hover:text-white text-[11px] uppercase font-black tracking-wider transition-all rounded">
                            + Agregar al Carrito
                        </button>
                    </div>
                `;
                container.appendChild(card);
                if (window.scrollObserver) {
                    window.scrollObserver.observe(card);
                }
            });
        }"""

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            content = f.read()
            
        # find the function renderProducts() { ... }
        # Since it might be tricky to regex match nested braces, we can use a simpler approach:
        # We know it starts at "function renderProducts() {" and ends before "function filterCategory(cat) {"
        start_idx = content.find('function renderProducts() {')
        end_idx = content.find('function filterCategory(cat) {')
        
        if start_idx != -1 and end_idx != -1:
            new_content = content[:start_idx] + new_render_function + "\n\n        " + content[end_idx:]
            with open(filepath, 'w') as f:
                f.write(new_content)
            print(f"Updated {filepath}")
        else:
            print(f"Failed to find function boundaries in {filepath}")
