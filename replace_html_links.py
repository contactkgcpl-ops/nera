import re
import os

files = [
    'index.html',
    'about.html',
    'contact.html',
    'industries.html',
    'products.html',
    'supply-chain.html',
    'script.js'
]

html_pattern = re.compile(r'href="([^"]+)\.html(#?[^"]*)"')
js_pattern = re.compile(r'products\.html')

for filename in files:
    if not os.path.exists(filename):
        print(f"Skipping {filename} (not found)")
        continue
        
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()
        
    if filename.endswith('.js'):
        new_content = js_pattern.sub('products', content)
    else:
        new_content = html_pattern.sub(r'href="\1\2"', content)
        
    if new_content != content:
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated links in {filename}")
    else:
        print(f"No changes in {filename}")
