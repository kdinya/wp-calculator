#!/usr/bin/env python3
import os, sys, glob, subprocess, re, shutil

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

def run_check(desc, fn):
    print(f"[*] {desc}...", end=" ")
    try:
        fn()
        print("OK")
    except Exception as e:
        print(f"FAILED: {e}")
        sys.exit(1)

def check_php_syntax():
    php_files = glob.glob(os.path.join(ROOT, '**', '*.php'), recursive=True)
    if not php_files:
        raise Exception("No PHP files found")
    php_bin = shutil.which('php')
    if php_bin:
        for f in php_files:
            res = subprocess.run([php_bin, '-l', f], capture_output=True, text=True)
            if res.returncode != 0:
                raise Exception(f"Syntax error in {f}: {res.stderr or res.stdout}")
    else:
        # Fallback PHP sanity check: verify balanced brackets, braces, and no raw errors
        for f in php_files:
            with open(f, 'r', encoding='utf-8') as fp:
                code = fp.read()
            if not code.startswith('<?php'):
                raise Exception(f"File {f} must start with <?php")
            if code.count('{') != code.count('}'):
                raise Exception(f"Mismatched curly braces in {f}")
            if code.count('(') != code.count(')'):
                raise Exception(f"Mismatched parentheses in {f}")

def check_js_syntax():
    js_files = glob.glob(os.path.join(ROOT, 'assets', 'js', '*.js'))
    for f in js_files:
        res = subprocess.run(['node', '--check', f], capture_output=True, text=True)
        if res.returncode != 0:
            raise Exception(f"JS syntax error in {f}: {res.stderr}")
        with open(f, 'r', encoding='utf-8') as fp:
            content = fp.read()
            if '<?php' in content or '<?=' in content:
                raise Exception(f"Unparsed PHP tag found inside JS file {f}")

def check_single_menu():
    php_files = glob.glob(os.path.join(ROOT, '**', '*.php'), recursive=True)
    menu_count = 0
    menu_matches = []
    for f in php_files:
        with open(f, 'r', encoding='utf-8') as fp:
            lines = fp.readlines()
            for i, line in enumerate(lines):
                if 'add_menu_page(' in line:
                    menu_count += 1
                    menu_matches.append(f"{f}:{i+1}")
    if menu_count != 1:
        raise Exception(f"Expected exactly 1 add_menu_page call, found {menu_count}: {menu_matches}")

def check_no_wood_icon():
    files_to_check = [
        os.path.join(ROOT, 'includes', 'class-wp-calculator-admin.php'),
        os.path.join(ROOT, 'assets', 'js', 'admin.js')
    ]
    for f in files_to_check:
        with open(f, 'r', encoding='utf-8') as fp:
            c = fp.read()
            if '🪵' in c:
                raise Exception(f"Found wood icon 🪵 in {f}")

def check_backup_in_settings():
    admin_php = os.path.join(ROOT, 'includes', 'class-wp-calculator-admin.php')
    with open(admin_php, 'r', encoding='utf-8') as fp:
        c = fp.read()
    if 'wc-tab-pane-settings' not in c:
        raise Exception("Settings tab missing")
    settings_idx = c.find('id="wc-tab-pane-settings"')
    backup_idx = c.find('downloadDataBackup()')
    if backup_idx < settings_idx:
        raise Exception("downloadDataBackup button should be located inside settings tab")

def check_semver():
    main_file = os.path.join(ROOT, 'wp-calculator.php')
    with open(main_file, 'r', encoding='utf-8') as fp:
        c = fp.read()
    m_head = re.search(r'Version:\s*([0-9]+\.[0-9]+\.[0-9]+)', c)
    m_const = re.search(r"define\('WP_CALCULATOR_VERSION',\s*'([0-9]+\.[0-9]+\.[0-9]+)'\)", c)
    if not m_head or not m_const:
        raise Exception("SemVer version missing in header or constant")
    if m_head.group(1) != m_const.group(1):
        raise Exception(f"Version mismatch: header={m_head.group(1)} vs const={m_const.group(1)}")

def check_bilingual_readme():
    readme_path = os.path.join(ROOT, 'README.md')
    with open(readme_path, 'r', encoding='utf-8') as fp:
        c = fp.read()
    if '## English' not in c:
        raise Exception("README.md must have ## English section")
    if '## Українська версія' not in c:
        raise Exception("README.md must have ## Українська версія section")

if __name__ == '__main__':
    run_check("PHP syntax & bracket balance", check_php_syntax)
    run_check("JS syntax (node --check, no PHP tags)", check_js_syntax)
    run_check("Single admin menu page", check_single_menu)
    run_check("Universal materials icon (no 🪵)", check_no_wood_icon)
    run_check("Backup button in settings tab", check_backup_in_settings)
    run_check("SemVer consistency", check_semver)
    run_check("Bilingual README structure", check_bilingual_readme)
    print("\n[SUCCESS] All regression & validation checks passed perfectly!")
