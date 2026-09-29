import os
import re

def update_header(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    old_pattern = re.compile(r'<!-- Header & Category Filters -->.*?</div>\s*</div>', re.DOTALL)
    
    new_header = """<!-- Header & Category Filters -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-12 pb-6 border-b border-zinc-800 gap-8">
            <div class="shrink-0 lg:mr-8">
                <h2 class="font-display text-4xl sm:text-5xl font-black uppercase tracking-tight text-white leading-none">Catálogo<br>Completo</h2>
                <span class="text-xs sm:text-sm font-semibold uppercase tracking-[0.1em] text-zinc-400 mt-2 block">Colección Disponible</span>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-3 overflow-x-auto pb-4 scrollbar-hide w-full" style="-ms-overflow-style: none; scrollbar-width: none;">
                <button onclick="filterCategory('all')" class="cat-filter-btn active-cat px-6 py-2 text-xs font-extrabold uppercase tracking-wider border border-white bg-white text-black transition-all rounded-full whitespace-nowrap shrink-0">
                    Todos
                </button>
                <button onclick="filterCategory('essentials_hoodies')" class="cat-filter-btn px-6 py-2 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-full whitespace-nowrap shrink-0">
                    Sudaderas Essentials
                </button>
                <button onclick="filterCategory('hoodies')" class="cat-filter-btn px-6 py-2 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-full whitespace-nowrap shrink-0">
                    Sudaderas
                </button>
                <button onclick="filterCategory('chains')" class="cat-filter-btn px-6 py-2 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-full whitespace-nowrap shrink-0">
                    Cadenas Plata .925
                </button>
                <button onclick="filterCategory('caps')" class="cat-filter-btn px-6 py-2 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-full whitespace-nowrap shrink-0">
                    Gorras
                </button>
                <button onclick="filterCategory('pants')" class="cat-filter-btn px-6 py-2 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-full whitespace-nowrap shrink-0">
                    Pants Rompevientos
                </button>
                <button onclick="filterCategory('pants_youngla')" class="cat-filter-btn px-6 py-2 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-full whitespace-nowrap shrink-0">
                    Pants Youngla
                </button>
                <button onclick="filterCategory('jackets_afelpadas')" class="cat-filter-btn px-6 py-2 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-full whitespace-nowrap shrink-0">
                    Hoodies & Jackets Afelpadas
                </button>
                <button onclick="filterCategory('combos')" class="cat-filter-btn px-6 py-2 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-full whitespace-nowrap shrink-0">
                    Combos Especiales
                </button>
            </div>
            <style>
                .scrollbar-hide::-webkit-scrollbar {
                    display: none;
                }
            </style>
        </div>"""
        
    content = old_pattern.sub(new_header, content, count=1)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_header('/home/ksh/Documentos/aura-luxury/index.html')
update_header('/home/ksh/Documentos/aura-luxury/deploy_site/index.html')
