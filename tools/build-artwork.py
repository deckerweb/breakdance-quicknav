"""Create three editable vector artwork proposals and matching PNG previews."""
from pathlib import Path
from PIL import Image,ImageDraw,ImageFont
from html import escape
root=Path(__file__).resolve().parents[1]/'assets-github/variants'
font_path='/System/Library/Fonts/Supplemental/Arial.ttf'
bold_path='/System/Library/Fonts/Supplemental/Arial Bold.ttf'
variants={'A':{'bg':'#fff9e9','fg':'#302e22','accent':'#8c6100','card':'#ffffff','muted':'#635b42','tag':'Clear / Klar'},'B':{'bg':'#20242b','fg':'#ffffff','accent':'#f3c94e','card':'#2c333d','muted':'#d5d9df','tag':'Focus / Fokus'},'C':{'bg':'#f8f5ef','fg':'#293b42','accent':'#22786b','card':'#ffffff','muted':'#526367','tag':'Calm / Ruhe'}}
for variant,c in variants.items():
 dest=root/variant;dest.mkdir(parents=True,exist_ok=True)
 for lang in ['en','de']:
  img=Image.new('RGB',(1280,640),c['bg']);draw=ImageDraw.Draw(img);elements=[]
  def rect(box,fill,r=0,outline=None):
   draw.rounded_rectangle(box,radius=r,fill=fill,outline=outline,width=2)
   x,y,x2,y2=box;elements.append(f'<rect x="{x}" y="{y}" width="{x2-x}" height="{y2-y}" rx="{r}" fill="{fill}"'+(f' stroke="{outline}" stroke-width="2"' if outline else '')+'/>')
  def text(x,y,s,size=24,color=None,bold=False):
   color=color or c['fg'];font=ImageFont.truetype(bold_path if bold else font_path,size)
   draw.text((x,y),s,font=font,fill=color)
   elements.append(f'<text x="{x}" y="{y+size*.91:.1f}" fill="{color}" font-family="Arial, sans-serif" font-size="{size}" font-weight="{700 if bold else 400}">{escape(s)}</text>')
  rect((62,64,202,98),c['accent'],17);text(80,70,'QUICKNAV',18,c['bg'],True)
  text(62,145,'Breakdance',72,bold=True);text(62,222,'QuickNav',72,bold=True)
  text(66,333,'Breakdance. Within reach.' if lang=='en' else 'Breakdance. Direkt erreichbar.',31,bold=True)
  text(66,389,'Templates, settings and add-ons.' if lang=='en' else 'Templates, Einstellungen und Addons.',23,c['muted'])
  text(66,486,'Breakdance 2.x  /  3.x',22,c['accent'],True)
  text(66,570,'deckerweb  ·  '+('Artwork proposal ' if lang=='en' else 'Grafikentwurf ')+variant,18,c['muted'])
  rect((755,91,1212,554),c['card'],24)
  rect((778,115,1189,166),c['accent'],10)
  text(805,128,'BD',24,c['bg'],True)
  labels=['Pages','Templates','Global Blocks','Breakdance Settings','QuickNav Settings'] if lang=='en' else ['Seiten','Templates','Global Blocks','Breakdance-Einstellungen','QuickNav-Einstellungen']
  for i,label in enumerate(labels):
   y=194+i*57
   if i==1:rect((784,y-7,1181,y+39),c['bg'],8)
   text(803,y,label,21,c['fg'],i==1)
   text(1145,y,'›',26,c['accent'],True)
  text(803,509,'Breakdance 2 & 3',18,c['muted'])
  svg='<svg xmlns="http://www.w3.org/2000/svg" width="1280" height="640" viewBox="0 0 1280 640"><title>Breakdance QuickNav '+variant+' '+lang+'</title><rect width="1280" height="640" fill="'+c['bg']+'"/>'+''.join(elements)+'</svg>\n'
  (dest/f'banner-{lang}.svg').write_text(svg);img.save(dest/f'banner-{lang}.png')
 # Shared text-free navigation icon, no third-party logo.
 icon=Image.new('RGB',(256,256),c['bg']);d=ImageDraw.Draw(icon)
 d.rounded_rectangle((35,40,221,216),radius=28,fill=c['card'],outline=c['accent'],width=10)
 d.line((40,90,216,90),fill=c['accent'],width=8)
 for x in [61,85,109]:d.ellipse((x,60,x+9,69),fill=c['accent'])
 d.polygon([(119,117),(181,166),(150,174),(136,205)],fill=c['accent'])
 d.line((65,123,94,123),fill=c['muted'],width=8);d.line((65,151,95,151),fill=c['muted'],width=8)
 icon.save(dest/'icon-256.png');icon.resize((128,128),Image.Resampling.LANCZOS).save(dest/'icon-128.png')
 (dest/'icon.svg').write_text(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" rx="40" fill="{c["bg"]}"/><rect x="35" y="40" width="186" height="176" rx="28" fill="{c["card"]}" stroke="{c["accent"]}" stroke-width="10"/><path d="M40 90h176M61 64h9m15 0h9m15 0h9" stroke="{c["accent"]}" stroke-width="8" stroke-linecap="round"/><path d="m119 117 62 49-31 8-14 31Z" fill="{c["accent"]}"/><path d="M65 123h29m-29 28h30" stroke="{c["muted"]}" stroke-width="8" stroke-linecap="round"/></svg>')
(root/'design.json').write_text(__import__('json').dumps({'status':'Three proposals; final selection pending','variants':variants,'dimensions':[1280,640],'font':'System Arial; font files are not distributed','artwork':'Original vector composition; no copied vendor graphics'},indent=2))
contact=Image.new('RGB',(1280,1020),'#e2e8f0')
for i,v in enumerate(variants):
 preview=Image.open(root/v/'banner-de.png').resize((640,320),Image.Resampling.LANCZOS)
 preview_en=Image.open(root/v/'banner-en.png').resize((640,320),Image.Resampling.LANCZOS)
 contact.paste(preview,(0,i*340));contact.paste(preview_en,(640,i*340))
(root/'overview.png').parent.mkdir(exist_ok=True);contact.save(root/'overview.png')
print('Three bilingual 1280 × 640 PNG/SVG proposals and 128/256 icons generated.')
