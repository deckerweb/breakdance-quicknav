# Addon-Integrationen

[English](ADDONS.md)

QuickNav verlinkt ausschließlich die Verwaltung der Addons. Es aktiviert keine Addons, verändert keine ihrer Einstellungen, prüft keine Lizenzen und ruft beim Aufbau der Toolbar keine externen Dienste ab. Die Website-Einstellungen bieten einen Schalter je Navigationsintegration und einen optionalen Schalter für Untermenüs. Diese Schalter betreffen nur QuickNav. Auf bestehenden Websites bleiben die Integrationen standardmäßig eingeschaltet.

| Integration | Geprüftes Paket | Ziel / Umfang |
| --- | --- | --- |
| Breakdance AI | 1.2.0 | Vorhandener registrierter Reiter `ai`; kein doppelter Addon-Eintrag |
| WPSix Exporter | 2.0.5 | `admin.php?page=wpsix-exporter`; bisherige Integration erhalten |
| SiteCare Builder Tools | 2.3.0 | Registrierte Builder-Tools-Seite; berücksichtigt ihren Menüplatz unter Breakdance bzw. den WordPress-Einstellungen |
| Jooosi Fon / Yabe Webfont | Jooosi Fon 1.1.5 | Aktuelle Admin-Seite `jooosi_fon`; bisherige Yabe-Erkennung und Verknüpfung erhalten |
| Elements Hive Free | 1.7.1 | Startseite und optionales Untermenü für Cloudflare Turnstile |
| Elements Hive Pro | 1.7.0 | Hauptseite, Lizenz und Werkzeuge; mit dem realen Paket geprüft |
| Destiny Elements | 1.8.7 (nur Quellprüfung) | Einstellungen und Lizenz; Adapter-Vertrag simuliert, kein bestätigter Anbieter-Laufzeittest |
| Dancepad | Intern 2.1.0 (nur Quellprüfung) | Natives Breakdance-Untermenü; Adapter-Vertrag simuliert, kein bestätigter Anbieter-Laufzeittest |
| Express Add On | 1.4.2 | Express Options und die vom Addon registrierten Admin-Seiten eingeschalteter Module |
| BreakColorUI Sync | 1.3.4 | Lokale Admin-Seite von BreakColorUI Sync |
| Breakdance WPML Integration | 1.1.0 | Abgesicherter Adapter für Template-Übersetzungen; kein Link ohne geladene WPML-Abhängigkeiten. Vollständiger WPML-Test folgt mit den Premium-Abhängigkeiten |
| WPSix Elements | 1.0.8 | Erkannte Version; geliefertes Paket ohne eigene Admin-Seite |
| Image Replacer | 1.0.5 | Erkannte Version; geliefertes Paket ohne eigene Admin-Seite |
| Headspin Copilot, Reading Time Calculator, Migration Mode | Bestehende Integrationen | Erhalten; Prüfung aktueller Pakete ausstehend. Migration Mode bleibt sein registrierter Einstellungsreiter |

Die Navigation wurde mit Breakdance 2.8.3 und 3.0.0 RC1 geprüft, einschließlich Admin-/Frontend-Aufrufen, inaktiver Plugins, fehlender Berechtigungen und Website-Schaltern. Dies prüft die QuickNav-Navigation, nicht sämtliche Addon-Funktionen oder lizenzierte Builder-Funktionen. Jooosi Fon Pro benötigt noch eine eigene Paketprüfung.

BreakMade, Smithy Portal/Connect, Phox Elements und Builder Languages erscheinen mit ausstehender Paketprüfung. Für sie gibt es keine geratene Erkennung und keine Menülinks. Breakdance Navigator bleibt ein Kompatibilitätsfall mit konkurrierender Toolbar. BreakNav ist ein Vergleichskandidat und erhält keinen automatischen Direktlink.

## Quellen

- [SiteCare Builder Tools](https://wordpress.org/plugins/sitecare-builder-tools-for-breakdance/)
- [Jooosi Fon / bisher Yabe Webfont](https://wordpress.org/plugins/yabe-webfont/)
- [Elements Hive](https://wordpress.org/plugins/elements-hive-for-breakdance/)
- [Express Add On](https://wordpress.org/plugins/express-add-on/)
- [Breakdance WPML Integration](https://wordpress.org/plugins/integration-for-breakdance-and-wpml/)
- [BreakColorUI-Sync-Download](https://blocks.breakcolorui.com/plugin-sync/)
- [Headspin-Dokumentation](https://headspinui.com/documentation/breakdance-helper/)

## Eigene Integrationen

Der Filter `ddw/quicknav/bd_integrations` nimmt eine Registry mit eindeutigen kleingeschriebenen IDs entgegen. Jeder Adapter enthält `label`, optional die Plugin-Dateien `files` und eine Versionskonstante `constant`, einen booleschen Laufzeitzustand `loaded` sowie `targets` und optionale `children`. Jedes Ziel enthält einen geprüften lokalen Seiten-Slug `slug`, die Admin-Datei `file`, die erforderliche Berechtigung `capability`, einen booleschen Verfügbarkeitszustand `ready` und optional ein Untermenü-Label `label`. Berechtigung und Menüplatz tatsächlich registrierter Admin-Menüs werden erneut berücksichtigt. Fehlende neue Admin-Menüs werden ausgelassen; im Frontend gelten ausschließlich quellcodegeprüfte geladene Ziele. Externe URLs und fehlerhafte Einträge werden abgelehnt, doppelte Ziele ausgelassen. Eigene Adapter nur für Pakete mit geprüften Aktivierungsbedingungen und Zielen registrieren.

## Beta 3: Übersicht und neue Premium-Adapter

Vier Reiter teilen sich ein natives Formular mit fester Speicherleiste und Hinweis auf ungespeicherte Änderungen. Addons stehen gesammelt unter Add-ons; einzelne Addons können samt Untermenüs direkt im Hauptmenü erscheinen. Installierte Addons stehen zuerst, fehlende und ausstehende Einträge sind eingeklappt. Native registrierte Addon-Untermenüs ergänzen im Admin die geprüften Unterseiten; doppelte oder unberechtigte Links entfallen.

Elements Hive Pro 1.7.0 wurde mit dem realen Paket geprüft: Hauptseite, Lizenz und Werkzeuge. Destiny Elements 1.8.7 erhält einen quellbasierten Adapter für Einstellungen und Lizenz; Dancepad meldet intern 2.1.0 und erhält einen Adapter für sein Breakdance-Untermenü. Diese beiden Pakete enthalten Code, der Lizenzzustände festschreibt oder ersetzt; sie wurden gelesen, aber nicht ausgeführt. Ihre Adapter-Verträge wurden simuliert, echte Laufzeittests warten auf unveränderte Herstellerpakete. Das Destiny-Archiv ist trotz ZIP-Endung ein RAR-Archiv.
