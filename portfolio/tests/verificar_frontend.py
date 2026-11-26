"""Valida sintaxe PHP/JS e referências locais do frontend, sem banco de dados."""

from pathlib import Path
import re
import subprocess
import sys
from urllib.parse import unquote, urlsplit

ROOT = Path(__file__).resolve().parents[1]
PHP = [ROOT / 'index.php', *sorted((ROOT / 'php').rglob('*.php')), *sorted((ROOT / 'database').glob('*.php'))]
STYLES = re.compile(r'<style\b[^>]*>\s*\S', re.I)
LOCAL_ASSET = re.compile(r'<(?:link|script|img)\b[^>]*?\b(?:href|src)=["\']([^"\'<>]+)["\']', re.I)
CSS_URL = re.compile(r'url\(\s*["\']?([^"\')]+)', re.I)


def asset_path(source, reference):
    url = urlsplit(reference.strip())
    if url.scheme or url.netloc or not url.path:
        return None
    path = unquote(url.path)
    return (ROOT / path.lstrip('/') if path.startswith('/') else source.parent / path).resolve()


def check_asset(source, reference, errors):
    target = asset_path(source, reference)
    if target is not None and (not target.is_relative_to(ROOT) or not target.is_file()):
        errors.append(f'{source.relative_to(ROOT)}: recurso local ausente: {reference}')


def main():
    errors = []
    css = sorted((ROOT / 'assets/css').rglob('*.css'))
    js = sorted((ROOT / 'assets/js').rglob('*.js'))
    if not css or not js:
        errors.append('CSS e JS da aplicação devem existir em assets/css e assets/js.')

    for source in PHP:
        text = source.read_text(encoding='utf-8-sig')
        if STYLES.search(text):
            errors.append(f'{source.relative_to(ROOT)}: bloco de CSS embutido.')
        for reference in LOCAL_ASSET.findall(text):
            # O ícone do login já era absoluto na prática, mas usa ../../ no HTML.
            if source == ROOT / 'index.php' and reference == '../../img/logofood.png':
                reference = '/img/logofood.png'
            check_asset(source, reference, errors)

    for source in [*css, *sorted((ROOT / 'php').rglob('*.css'))]:
        for reference in CSS_URL.findall(source.read_text(encoding='utf-8')):
            check_asset(source, reference, errors)

    for command, files in ((('php', '-l'), PHP), (('node', '--check'), js)):
        for source in files:
            result = subprocess.run([*command, str(source)], capture_output=True, text=True)
            if result.returncode:
                errors.append(f'{source.relative_to(ROOT)}: {result.stderr.strip()}')

    if errors:
        print('\n'.join(errors), file=sys.stderr)
        return 1
    print(f'OK: {len(PHP)} arquivos PHP, {len(css)} folhas CSS e {len(js)} scripts JS; referências locais válidas.')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
