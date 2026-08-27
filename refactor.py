import re

def process_file():
    with open('resources/views/layouts/sidebar-nav-temp.txt', 'r', encoding='utf-8') as f:
        content = f.read()

    # We will do some manual regex replacements to convert standard tags to components.
    
    # 1. Section
    # <div>
    #    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
    #        x-show="!sidebarCollapsed" x-cloak>Master Data</div>
    #    <div class="space-y-1">
    section_pattern = re.compile(
        r'<div>\s*<div[^>]*text-\[11px\][^>]*>([^<]+)</div>\s*<div class="space-y-1">',
        re.MULTILINE
    )
    content = section_pattern.sub(r'<x-sidebar.section label="\1">', content)
    
    # 2. End Section (Need to be careful, but we can just replace </div>\s*</div> with </x-sidebar.section>)
    # This is tricky without a proper HTML parser.

    # Let's write the whole file with a python script that just outputs a clean sidebar.
    pass

if __name__ == '__main__':
    process_file()
