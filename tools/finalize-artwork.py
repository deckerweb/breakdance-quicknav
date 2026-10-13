"""Restore the approved graphic revision from its preserved SVG/PNG originals."""
from pathlib import Path
import shutil
root=Path(__file__).resolve().parents[1]
source=root/'assets-github/revisions/2026-10-13'
for p in source.iterdir():
 if p.is_file():shutil.copyfile(p,root/'assets-github/approved'/p.name)
print('Approved artwork revision 2026-10-13 restored. Runtime copies are maintained alongside it.')
