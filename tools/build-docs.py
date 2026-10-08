"""Generate bilingual user documentation from one maintained source."""
from pathlib import Path
import json
root=Path(__file__).resolve().parents[1]
data=json.loads((root/'docs/content.json').read_text());history=json.loads((root/'includes/history.json').read_text())
for lang,d in data.items():
 de=lang=='de';other='README.md' if de else 'README-de.md';labels={'new':'Neu' if de else 'New','improved':'Verbessert' if de else 'Improved','fixed':'Behoben' if de else 'Fixed','misc':'Sonstiges' if de else 'Misc'}
 change='\n\n'.join('### '+r['version']+' · '+r['date'][lang]+'\n\n'+'\n'.join('- **'+labels[i['category']]+':** '+i[lang] for i in r['items']) for r in history)
 t=d['titles'];faq='\n\n'.join('### '+q+'\n\n'+a for q,a in d['faq'])
 full=('docs/FAQ-de.md' if de else 'docs/FAQ.md')
 md='# Breakdance QuickNav\n\n'+('[English]' if de else '[Deutsch]')+'('+other+')\n\n'+('![Breakdance QuickNav](assets/brand/banner-'+lang+'.png)\n\n')+'## '+t[0]+'\n\n'+d['about']+'\n\n'+d['requirements']+'\n\n[FAQ](#faq) · ['+t[5]+'](#'+('änderungsverlauf' if de else 'changelog')+') · [GitHub](https://github.com/deckerweb/breakdance-quicknav)\n\n'+('**Inhalt:** ' if de else '**Contents:** ')+' · '.join('['+x+'](#'+x.lower().replace(' & ','--').replace(' ','-')+')' for x in t)+'\n\n## '+t[1]+'\n\n'+'\n'.join('- '+s for s in d['features'])+'\n\n## '+t[2]+'\n\n'+'\n'.join(str(i+1)+'. '+s for i,s in enumerate(d['install']))+'\n\n## '+t[3]+'\n\n'+('\n\n'.join('### '+s.split(';')[0]+'\n\n'+s.split(';',1)[1].strip() for s in d['features']))+'\n\n## FAQ\n\n'+faq+'\n\n'+('[Fragen nach Themen]' if de else '[FAQ by topic]')+'('+full+')\n\n## '+t[5]+'\n\n'+change+'\n\n## '+t[6]+'\n\nDavid Decker – DECKERWEB. QuickNav.\n\n[Issues](https://github.com/deckerweb/breakdance-quicknav/issues) · [Security](SECURITY.md) · [Ko-fi](https://ko-fi.com/deckerweb) · [Buy Me a Coffee](https://buymeacoffee.com/daveshine) · [PayPal](https://paypal.me/deckerweb)\n\n'+('Das Plugin entstand als Fork von ' if de else 'This plugin originated as a fork of ')+'[Breakdance Navigator](https://github.com/beamkiller/breakdance-navigator), Peter Kulcsár, © 2024, GPL v2 or later.\n\nCopyright © 2025–2026 David Decker – DECKERWEB. GPL v2 or later. '+('Das bestehende Breakdance-Logo gehört Soflyy. Das neue Navigationssymbol ist eine eigene Vektorgrafik. ' if de else 'The existing Breakdance logo belongs to Soflyy. The navigation symbol is original vector artwork. ')+'deckerweb Updater 2.1.0 '+('und' if de else 'and')+' Library 0.8.1: © 2026 David Decker – DECKERWEB, GPL-2.0-or-later.\n'
 md += ('\nHistorische Grafiken enthalten Symbole von [Remix Icon](https://remixicon.com/), © Remix Icon. Die aktuellen Grafiken sind eigene Vektorgrafiken.\n' if de else '\nRetained legacy graphics contain [Remix Icon](https://remixicon.com/) symbols, © Remix Icon. The current artwork is original vector artwork.\n')
 md += ('\nEinstellungsoberfläche und Host-Anbindung verwenden GPL-2.0-or-later-Code aus [Oxygen QuickNav 2.0.0](https://github.com/deckerweb/oxygen-quicknav/tree/v2.0.0), © 2025–2026 David Decker – DECKERWEB.\n' if de else '\nThe settings interface and host integration reuse GPL-2.0-or-later code from [Oxygen QuickNav 2.0.0](https://github.com/deckerweb/oxygen-quicknav/tree/v2.0.0), © 2025–2026 David Decker – DECKERWEB.\n')
 (root/('README-de.md' if de else 'README.md')).write_text(md)
 grouped='\n\n'.join('## '+heading+'\n\n'+'\n\n'.join('### '+q+'\n\n'+a for q,a in d['faq'][start:end]) for heading,start,end in [(('Einstieg' if de else 'Getting started'),0,2),(('Alltag & Einstellungen' if de else 'Everyday use & settings'),2,5),(('Kompatibilität & Daten' if de else 'Compatibility & data'),5,7)])
 (root/'docs'/('FAQ-de.md' if de else 'FAQ.md')).write_text('# '+('Fragen nach Themen' if de else 'FAQ by topic')+'\n\n'+('[English](FAQ.md)' if de else '[Deutsch](FAQ-de.md)')+'\n\n'+grouped+'\n')
 (root/'docs'/('CHANGELOG-de.md' if de else 'CHANGELOG.md')).write_text('# '+t[5]+'\n\n'+('[English](CHANGELOG.md)' if de else '[Deutsch](CHANGELOG-de.md)')+'\n\n'+change+'\n')
# Directory-format source can be generated, but this GitHub build contains external updaters.
d=data['en']
readme='=== Breakdance QuickNav ===\nContributors: daveshine\nTags: breakdance, toolbar, navigation\nRequires at least: 6.7\nTested up to: 7.1.3\nRequires PHP: 7.4\nStable tag: 2.0.0-beta.3\nLicense: GPLv2 or later\nLicense URI: https://www.gnu.org/licenses/gpl-2.0.html\n\n'+d['about']+'\n\n== Description ==\n\n'+d['requirements']+'\n\n'+'\n'.join('* '+s for s in d['features'])+'\n\n== Installation ==\n\n'+'\n'.join(str(i+1)+'. '+s for i,s in enumerate(d['install']))+'\n\n== Frequently Asked Questions ==\n\n'+'\n\n'.join('= '+q+' =\n\n'+a for q,a in d['faq'])+'\n\n== Changelog ==\n\n'+'\n\n'.join('= '+r['version']+' =\n\n'+'\n'.join('* '+{'new':'New','improved':'Improved','fixed':'Fixed','misc':'Misc'}[i['category']]+': '+i['en'] for i in r['items']) for r in history)+'\n'
(root/'readme.txt').write_text(readme)

# Wiki text sources are staged locally; this generator never publishes.
wiki=root/'wiki';wiki.mkdir(exist_ok=True)
for lang in ['en','de']:
 de=lang=='de';suffix='-de' if de else ''
 home=(root/('README-de.md' if de else 'README.md')).read_text().replace('(README.md)','(Home)').replace('(README-de.md)','(Startseite)')
 home=home.replace('(docs/FAQ.md)','(FAQ-by-topic)').replace('(docs/FAQ-de.md)','(Fragen-nach-Themen)').replace('(SECURITY.md)','(https://github.com/deckerweb/breakdance-quicknav/security/advisories/new)')
 home=home.replace('(assets/brand/banner-'+lang+'.png)','(https://raw.githubusercontent.com/deckerweb/breakdance-quicknav/main/assets-github/approved/banner-'+lang+'.png)')
 (wiki/('Startseite.md' if de else 'Home.md')).write_text(home)
 for source,target in [('FAQ','Fragen-nach-Themen' if de else 'FAQ-by-topic'),('CHANGELOG','Aenderungsverlauf' if de else 'Changelog'),('DATA','Daten' if de else 'Data'),('ADDONS','Addons-de' if de else 'Addons')]:
  (wiki/(target+'.md')).write_text((root/'docs'/(source+suffix+'.md')).read_text())
