"""Derive the complete bilingual graphic set from the expressly selected B layout."""
from pathlib import Path
from PIL import Image,ImageDraw,ImageFont
import shutil,json,re
root=Path(__file__).resolve().parents[1];source=root/'assets-github/variants/B';out=root/'assets-github/approved';out.mkdir(parents=True,exist_ok=True)
bg='#20242b';muted='#d5d9df';font='/System/Library/Fonts/Supplemental/Arial.ttf'
for lang in ['en','de']:
 im=Image.open(source/f'banner-{lang}.png').convert('RGB');d=ImageDraw.Draw(im);d.rectangle((60,560,700,615),fill=bg);d.text((66,570),'deckerweb  ·  QuickNav',font=ImageFont.truetype(font,18),fill=muted);im.save(out/f'banner-{lang}.png')
 svg=(source/f'banner-{lang}.svg').read_text();svg=svg.replace(('Artwork proposal ' if lang=='en' else 'Grafikentwurf ')+'B','QuickNav').replace('Breakdance QuickNav B '+lang,'Breakdance QuickNav')
 (out/f'banner-{lang}.svg').write_text(svg)
 for w,h in [(772,250),(1544,500)]:
  scaled=im.resize((round(1280*h/640),h),Image.Resampling.LANCZOS)
  wide=Image.new('RGB',(w,h),bg);wide.paste(scaled,((w-scaled.width)//2,0));wide.save(out/f'wp-banner-{lang}-{w}x{h}.png')
 wide=1544/500*640;offset=(wide-1280)/2
 body=svg[svg.index('>')+1:svg.rindex('</svg>')];body=re.sub(r'<title>.*?</title>','',body)
 (out/f'wp-banner-{lang}.svg').write_text(f'<svg xmlns="http://www.w3.org/2000/svg" width="1544" height="500" viewBox="0 0 {wide} 640"><title>Breakdance QuickNav</title><rect width="{wide}" height="640" fill="{bg}"/><g transform="translate({offset} 0)">{body}</g></svg>\n')
for name in ['icon.svg','icon-128.png','icon-256.png']:shutil.copy(source/name,out/name)
brand=root/'assets/brand';brand.mkdir(exist_ok=True)
for name in ['icon.svg','icon-128.png','icon-256.png']:shutil.copy(out/name,brand/name)
for lang in ['en','de']:
 shutil.copy(out/f'banner-{lang}.png',brand/f'banner-{lang}.png')
 for w,h in [(772,250),(1544,500)]:shutil.copy(out/f'wp-banner-{lang}-{w}x{h}.png',brand/f'banner-{lang}-{w}x{h}.png')
# Keep legacy assets separately and refresh established public asset paths.
legacy=root/'assets-github/legacy';legacy.mkdir(exist_ok=True)
for name in ['icon.svg','icon-128x128.png','icon-256x256.png','banner-772x250.png','banner-1544x500.png']:
 p=root/'assets'/name
 if p.exists() and not (legacy/name).exists():shutil.copy(p,legacy/name)
shutil.copy(out/'icon.svg',root/'assets/icon.svg')
for size in [128,256]:shutil.copy(out/f'icon-{size}.png',root/f'assets/icon-{size}x{size}.png')
for w,h in [(772,250),(1544,500)]:
 shutil.copy(out/f'wp-banner-en-{w}x{h}.png',root/f'assets/banner-{w}x{h}.png')
 shutil.copy(out/f'wp-banner-de-{w}x{h}.png',root/f'assets/banner-de-{w}x{h}.png')
(root/'assets-github/design.json').write_text(json.dumps({'approved_variant':'B','approved_on':'2026-10-08','copyright':'2026 David Decker – DECKERWEB','license':'GPL-2.0-or-later','source':'Original vector artwork; alternative drafts and previous graphics retained','fonts':'System Arial references; no font binaries distributed','sizes':{'github':[1280,640],'wordpress':[[772,250],[1544,500]],'icons':[128,256]},'languages':['en','de']},indent=2)+'\n')
print('Selected B: complete bilingual PNG/SVG banners and 128/256 icons generated.')
