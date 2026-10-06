with open('deploy_site/js/main.js', 'r') as f:
    js = f.read()

js = js.replace("window.open(`https://wa.me/?text=${encoded}`, '_blank');", "window.open(`https://wa.me/525516069816?text=${encoded}`, '_blank');")

with open('deploy_site/js/main.js', 'w') as f:
    f.write(js)
print("Replaced")
