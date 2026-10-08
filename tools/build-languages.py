"""Extract literal host strings and compile deterministic German GNU MO catalogs."""
from pathlib import Path
import re,json,struct
root=Path(__file__).resolve().parents[1]
dictionary=json.loads((root/'languages/dictionary.json').read_text())
strings=set()
for p in [root/'breakdance-quicknav.php',*list((root/'includes').glob('*.php'))]:
 strings.update(x.replace("\\'", "'") for x in re.findall(r"(?:__|esc_html__|esc_attr__)\(\s*'((?:\\'|[^'])*)',\s*'breakdance-quicknav'",p.read_text()))
# Shared Library strings compile into the same host domain; host values take precedence.
messages=json.loads((root/'includes/deckerweb-plugin-library/messages.json').read_text())
strings.update(messages)
for lang,mapping in dictionary.items():
 for message,translations in messages.items():mapping.setdefault(message,translations[lang])
header='Project-Id-Version: Breakdance QuickNav 2.0.0-beta.1\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\nMIME-Version: 1.0\nPO-Revision-Date: 2026-10-08 10:00+0200\nLast-Translator: David Decker – DECKERWEB\nLanguage-Team: German\n'
quote=lambda x:json.dumps(x,ensure_ascii=False)
def mo(entries):
 keys=sorted(entries);n=len(keys);offset=28+16*n
 originals=b'';translations=b'';ot=[];tt=[]
 for k in keys:
  b=k.encode();ot.append((len(b),offset+len(originals)));originals+=b+b'\0'
 offset+=len(originals)
 for k in keys:
  b=entries[k].encode();tt.append((len(b),offset+len(translations)));translations+=b+b'\0'
 return struct.pack('<7I',0x950412de,0,n,28,28+8*n,0,0)+b''.join(struct.pack('<2I',*x) for x in ot+tt)+originals+translations
for lang,mapping in dictionary.items():
 missing=strings-set(mapping)
 if missing:raise SystemExit('Missing '+lang+': '+repr(sorted(missing)))
 catalog={s:mapping[s] for s in strings};catalog['']=header+'Language: '+lang+'\nPlural-Forms: nplurals=2; plural=(n != 1);\n'
 path=root/'languages'/('breakdance-quicknav-'+lang)
 path.with_suffix('.po').write_text('\n\n'.join('msgid '+quote(k)+'\nmsgstr '+quote(v) for k,v in sorted(catalog.items()))+'\n')
 path.with_suffix('.mo').write_bytes(mo(catalog))
(root/'languages/breakdance-quicknav.pot').write_text('msgid ""\nmsgstr '+quote(header)+'\n\n'+'\n\n'.join('msgid '+quote(s)+'\nmsgstr ""' for s in sorted(strings))+'\n')
print(str(len(strings))+' literal host messages compiled for Du and Sie.')
