import os

for filepath in ['index.html', 'deploy_site/index.html']:
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            content = f.read()
            
        # Add to main cat filter buttons
        button_html = """                <button onclick="filterCategory('essentials_hoodies')" class="cat-filter-btn px-5 py-2.5 text-xs font-extrabold uppercase tracking-wider border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-600 transition-all rounded-sm">
                    Sudaderas Essentials
                </button>
"""
        content = content.replace(
            """<button onclick="filterCategory('hoodies')\"""",
            button_html + """                <button onclick="filterCategory('hoodies')\""""
        )
        
        # Add to desktop nav
        nav_desktop = """<a href="#catalogo" onclick="filterCategory('essentials_hoodies')" class="hover:text-white transition-colors py-2 border-b-2 border-transparent hover:border-white">Essentials</a>
                    """
        content = content.replace(
            """<a href="#catalogo" onclick="filterCategory('hoodies')" class="hover:text-white transition-colors py-2 border-b-2 border-transparent hover:border-white">Sudaderas</a>""",
            nav_desktop + """<a href="#catalogo" onclick="filterCategory('hoodies')" class="hover:text-white transition-colors py-2 border-b-2 border-transparent hover:border-white">Sudaderas</a>"""
        )
        
        # Add to mobile nav
        nav_mobile = """<a href="#catalogo" onclick="filterCategory('essentials_hoodies'); toggleMobileMenu()" class="block text-xs font-extrabold tracking-widest uppercase text-slate-200">Essentials</a>
            """
        content = content.replace(
            """<a href="#catalogo" onclick="filterCategory('hoodies'); toggleMobileMenu()" class="block text-xs font-extrabold tracking-widest uppercase text-slate-200">Sudaderas</a>""",
            nav_mobile + """<a href="#catalogo" onclick="filterCategory('hoodies'); toggleMobileMenu()" class="block text-xs font-extrabold tracking-widest uppercase text-slate-200">Sudaderas</a>"""
        )
        
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated {filepath}")
