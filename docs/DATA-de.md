# Daten und Verbindungen

[English](DATA.md)

Website-Einstellungen: `ddw_bdqn_settings`. Persönliche Vorlieben: die Benutzeroption `ddw_bdqn_preferences` mit Website-Präfix. Keine eigenen Datenbanktabellen, Inhaltsänderungen oder Host-Cron-Aufgaben. Deaktivierung erhält Daten. Deinstallation entfernt immer den Updater-Cache dieses Hosts; die optionale Löschentscheidung entfernt nur die Einstellungen und persönlichen Vorlieben dieser Website. Netzwerk-Deinstallation folgt der Entscheidung jeder Website in begrenzten Gruppen. Breakdance-/Addon-Inhalte und Benutzerkonten bleiben erhalten.

Library 0.8.1 verwaltet gemeinsame Website-/Netzwerkoptionen, Katalog-Caches und Lebenszyklusdaten. Ihre Bereinigung beim letzten Host schützt auch inaktive installierte Host-Kopien. Die eigene Löschoption ist standardmäßig aus und entfernt niemals installierte Plugins oder deren Inhalte.

Der Updater ruft öffentliche Metadaten und Release-Pakete über HTTPS bei GitHub ab; WordPress kann bei Updates Plattform-/Versionsinformationen übertragen. Der Library-Online-Katalog ist standardmäßig aus. Nach Aktivierung ruft er öffentliche Katalogmetadaten und freigegebene Paketinformationen bei GitHub ab. Keine Telemetrie oder Analyse. Icons, CSS und JavaScript werden lokal mitgeliefert. Ressourcenlinks verbinden erst beim Aufruf mit dem Ziel; Newsletter-URLs enthalten keine personalisierten Kontoparameter.

Einzelne Integrationsschalter und die Untermenü-Vorliebe stehen in der vorhandenen Website-Option `ddw_bdqn_settings`. Die Erkennung ruft keine externen HTTP-Dienste ab und speichert keine Addon-Lizenzen oder Zugangsdaten. Aktivierung und Konfiguration der Addons werden nicht verändert.
