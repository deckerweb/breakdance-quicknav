"""Build the expressly authorized navigation-only snippet from the current host."""
from pathlib import Path
import re,json,base64
root=Path(__file__).resolve().parents[1]
parts=[]
for name in ['class-settings.php','class-builder.php','class-integrations.php','class-plugin.php']:
 s=(root/'includes'/name).read_text().removeprefix('<?php')
 s=s.replace('namespace Deckerweb\\BreakdanceQuickNav;', '')
 for a,b in [('final class Settings','final class SnippetSettings'),('final class Builder','final class SnippetBuilder'),('final class Plugin','final class SnippetPlugin'),('Settings::','SnippetSettings::'),('Builder::','SnippetBuilder::'),('final class Integrations','final class SnippetIntegrations'),('Integrations::','SnippetIntegrations::')]:s=s.replace(a,b)
 if name=='class-settings.php':
  a=s.index('\t\t$saved = get_option');b=s.index('\t\t$map = self::constant_map();',a)
  s=s[:a]+"\t\t$out = self::defaults();\n"+s[b:]
 if name=='class-plugin.php':
  keep=['__construct','visible','assets','toolbar','resources','addon_url','content_group','addon_nodes','form_nodes'];blocks=[]
  for m in re.finditer(r'\n\t/\*\*',s):
   a=m.start();b=s.find('\n\t/**',a+1)
   if b<0:b=s.rfind('\n}')
   block=s[a:b];f=re.search(r'function (\w+)\(',block)
   if not f or f.group(1) not in keep:continue
   name=f.group(1)
   if name=='__construct':block="\n\t/** @return void Registers navigation and toolbar styles only. */\n\tpublic function __construct() { add_action( 'admin_bar_menu', array( $this, 'toolbar' ), 999 ); add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) ); add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) ); }\n"
   if name=='assets':block="\n\t/** @param string $hook Admin screen hook. @return void Adds minimal toolbar-only styling. */\n\tpublic function assets( $hook = '' ) { if ( SnippetBuilder::active() && $this->visible( SnippetSettings::get() ) ) { wp_add_inline_style( 'admin-bar', '#wpadminbar .bdqn-icon{width:16px;height:16px;vertical-align:middle;margin-right:6px}#wpadminbar .bdqn-status-heading>.ab-empty-item,#wpadminbar .bdqn-status-label{font-size:11px;color:#a7aaad}#wpadminbar .bdqn-status-heading>.ab-empty-item{cursor:default}' ); } }\n"
   if name=='toolbar':
    block=block.replace("'href' => current_user_can( 'manage_options' ) ? self::url() : '#'", "'href' => '#'")
    block='\n'.join(line for line in block.split('\n') if "'bdqn-own-settings'" not in line)
   blocks.append(block)
  s='\n/** Navigation-only coordinator without settings UI or shared components. */\nfinal class SnippetPlugin {'+''.join(blocks)+'\n}\n'
 parts.append(s)
body='''<?php
/** Breakdance QuickNav navigation-only snippet, GPL-2.0-or-later.
 * © 2025–2026 David Decker – DECKERWEB.
 * Origin: Peter Kulcsár, Breakdance Navigator, © 2024, GPL v2 or later.
 * https://github.com/beamkiller/breakdance-navigator
 * Do not run beside the plugin. No settings UI, updater or Library.
 */
namespace Deckerweb\\BreakdanceQuickNavSnippet;
if ( ! defined( 'ABSPATH' ) ) { return; }
if ( defined( 'DDW_BDQN_VERSION' ) || class_exists( 'DDW_Breakdance_QuickNav' ) || class_exists( __NAMESPACE__ . '\\SnippetPlugin', false ) ) { return; }
'''+"\nif ( ! class_exists( __NAMESPACE__ . '\\SnippetPlugin', false ) ) {\n"+''.join(parts)
body=body.replace("plugins_url( 'images/breakdance-icon.png', DDW_BDQN_FILE )", "'data:image/png;base64,"+base64.b64encode((root/'images/breakdance-icon.png').read_bytes()).decode()+"'")
body=body.replace("esc_url( $icon )", "esc_url( $icon, array( 'http', 'https', 'data' ) )")
body+='''
/** @return void Registers one navigation instance unless the full plugin is active. */
$boot = static function () { static $instance; if ( ! $instance && ! defined( 'DDW_BDQN_VERSION' ) && ! class_exists( 'DDW_Breakdance_QuickNav' ) ) { $instance = new SnippetPlugin(); } };
if ( did_action( 'init' ) ) { $boot(); } else { add_action( 'init', $boot, 20 ); }
'''
body+='\n}\n'
out=root/'snippet';out.mkdir(exist_ok=True)
(out/'breakdance-quicknav-navigation.php').write_text(body)
(out/'breakdance-quicknav.code-snippets.json').write_text(json.dumps({'generator':'Code Snippets','date_created':'2026-10-08','snippets':[{'name':'Breakdance QuickNav Navigation','desc':'Navigation only. Do not run beside the plugin.','code':body.removeprefix('<?php'),'scope':'global','active':False}]},ensure_ascii=False,indent=2)+'\n')
print('Navigation-only snippet generated.')
