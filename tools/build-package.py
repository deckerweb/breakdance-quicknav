"""Build clean install/source archives without premium vendor or local test data."""
from pathlib import Path
import argparse,zipfile,hashlib,json
root=Path(__file__).resolve().parents[1]
a=argparse.ArgumentParser();a.add_argument('destination',type=Path);args=a.parse_args();args.destination.mkdir(parents=True,exist_ok=True)
version='2.0.0-beta.2'
runtime_dirs={'assets','images','includes','languages','docs'}
runtime_files={'breakdance-quicknav.php','uninstall.php','index.php','LICENSE','readme.txt','README.md','README-de.md','SECURITY.md'}
skip={'.DS_Store'}
paths=[p for p in root.rglob('*') if p.is_file() and '.git' not in p.parts and '.nova' not in p.parts and p.name not in skip and p.suffix not in {'.sqlite','.zip'} and p.name!='.htaccess']
results={}
for kind in ['install','source']:
 name=f'breakdance-quicknav-{version}'+('-source' if kind=='source' else '')+'.zip';dest=args.destination/name
 with zipfile.ZipFile(dest,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9) as z:
  for p in sorted(paths):
   relative=p.relative_to(root)
   if kind=='install':
    if relative.parts[0] not in runtime_dirs and str(relative) not in runtime_files:continue
    if p.name in {'dictionary.json','content.json','messages.json'} or p.suffix=='.scss':continue
    if relative.parts[0]=='assets' and str(relative) not in {'assets/quicknav.css','assets/dialog.js','assets/navigation.svg'} and relative.parts[:2] != ('assets','brand'):continue
    if relative.parts[0]=='docs' and p.name.startswith('Wiki-'):continue
   # Source archives include authoring dictionaries and draft artwork separately.
   info=zipfile.ZipInfo('breakdance-quicknav/'+str(relative),date_time=(2026,10,8,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.external_attr=0o100644<<16;z.writestr(info,p.read_bytes())
 results[name]=hashlib.sha256(dest.read_bytes()).hexdigest()
(args.destination/'SHA256SUMS.txt').write_text(''.join(h+'  '+n+'\n' for n,h in results.items()))
print(json.dumps(results,indent=2))
