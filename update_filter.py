import os

new_func = """        function filterCategory(cat) {
            activeCategory = cat;
            
            // Update button styles
            const buttons = document.querySelectorAll('.cat-filter-btn');
            buttons.forEach(btn => {
                if (btn.getAttribute('onclick') === `filterCategory('${cat}')`) {
                    // Make it active
                    btn.classList.add('active-cat', 'border-white', 'bg-white', 'text-black');
                    btn.classList.remove('border-zinc-800', 'text-zinc-400', 'hover:text-white', 'hover:border-zinc-600');
                } else {
                    // Make it inactive
                    btn.classList.remove('active-cat', 'border-white', 'bg-white', 'text-black');
                    btn.classList.add('border-zinc-800', 'text-zinc-400', 'hover:text-white', 'hover:border-zinc-600');
                }
            });
            
            renderProducts();
        }"""

for filepath in ['js/main.js', 'deploy_site/js/main.js']:
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            content = f.read()
            
        # replace the function
        # since it's just a small 4 line function right now:
        old_func = """        function filterCategory(cat) {
            activeCategory = cat;
            renderProducts();
        }"""
        
        if old_func in content:
            new_content = content.replace(old_func, new_func)
            with open(filepath, 'w') as f:
                f.write(new_content)
            print(f"Updated {filepath}")
        else:
            print(f"Could not find exact function signature in {filepath}")
