"""Build a runtime-only WordPress ZIP with a stable root folder."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
output = root / 'dist' / 'codeixe-wp-seo-connector.zip'
output.parent.mkdir(exist_ok=True)
files = ['codeixe-wp-seo-connector.php', 'uninstall.php', 'readme.txt', 'README.md', 'LICENSE', 'CHANGELOG.md']
for folder in ['includes', 'assets', 'docs', 'languages']:
    files.extend(str(p.relative_to(root)) for p in (root / folder).rglob('*') if p.is_file())
with ZipFile(output, 'w', ZIP_DEFLATED) as archive:
    for filename in sorted(files):
        archive.write(root / filename, 'codeixe-wp-seo-connector/' + filename)
print(output)
