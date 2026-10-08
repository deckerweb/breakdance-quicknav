# Fragen nach Themen

[English](FAQ.md)

## Einstieg

### Wo sind die Einstellungen?

Unter Breakdance → QuickNav (ohne aktiven Builder: Einstellungen → Breakdance QuickNav) oder über Einstellungen neben Deaktivieren in der Pluginliste. Persönliche Vorlieben stehen im Benutzerprofil.

### Warum fehlt QuickNav?

Breakdance 2.x oder 3.x muss im Breakdance-Modus aktiv sein. Die WordPress-Toolbar muss sichtbar sein; Website-/Benutzereinstellungen und Berechtigungen müssen die Anzeige erlauben. Ein aktiver Breakdance Navigator unterdrückt doppelte QuickNav-Navigation.

## Alltag & Einstellungen

### Funktionieren bestehende Konstanten weiterhin?

Ja. BDQN_NAME_IN_ADMINBAR, BDQN_ICON, BDQN_NUMBER_TEMPLATES, BDQN_VIEW_CAPABILITY, BDQN_ENABLED_USERS und BDQN_DISABLE_FOOTER überschreiben gespeicherte Standardwerte. Eine leere bisherige Benutzerliste sperrt weiterhin alle Benutzer.

### Können Redakteure Entwürfe sehen?

Nur bei aktivierter Anzeige unveröffentlichter Inhalte sowie vorhandenen WordPress-Bearbeitungsrechten und Breakdance-Bearbeitungsrechten. Eine niedrigere Toolbar-Berechtigung gewährt keinen Builder-Zugriff.

### Welche Addons werden unterstützt?

Bestehende Integrationen bleiben erhalten. Die gelieferten Pakete sowie die frei verfügbaren SiteCare, Jooosi Fon, Elements Hive, Express Add On und BreakColorUI Sync sind für die Navigation geprüft. Weitere Premium-Pakete und WPML-Abhängigkeiten benötigen noch eigene Prüfungen. Siehe docs/ADDONS-de.md.

## Kompatibilität & Daten

### Funktioniert das Plugin in Multisite?

Website- und Netzwerkaktivierung werden unterstützt. Website-Einstellungen und persönliche Vorlieben gelten je Website. Es entsteht keine websiteübergreifende Toolbar. Gemeinsame Library-Daten bleiben geschützt, solange ein anderer Host installiert ist.

### Was passiert bei der Deinstallation, und gibt es ein Snippet?

Einstellungen und Vorlieben bleiben standardmäßig erhalten. Die Löschoption entfernt nur die QuickNav-Daten dieser Website; bei Netzwerk-Deinstallation gilt die Entscheidung jeder Website. Breakdance-Inhalte bleiben erhalten. Ein separat erzeugtes Snippet ausschließlich für Navigation ist optional; nicht gleichzeitig mit dem Plugin aktivieren.
