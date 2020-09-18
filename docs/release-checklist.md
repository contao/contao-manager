# Checkliste neue Contao Manager Version


## 1. Installation

- [ ] Contao Webhoster
    - Contao Version getestet
        - [ ] 4.4 (LTS)
        - [ ] 4.9 (LTS)
        - [ ] 4.10
    - Webhoster getestet
        - [ ] 1und1 IONOS
        - [ ] All-Inkl
        - [ ] cyon
        - [ ] DomainFactory
        - [ ] Hetzner
        - [ ] Hostingwerk
        - [ ] Metanet
        - [ ] Novatrend
        - [ ] Strato
        - [ ] Webgo
- [ ] Contao Lokal
    - [ ] Mac
        - [ ] Laragon
        - [ ] MAMP
        - [ ] XAMPP
    - [ ] Windows
        - [ ] Laragon
        - [ ] MAMP
        - [ ] XAMPP
    - Contao Version getestet
        - [ ] 4.4 (LTS)
        - [ ] 4.9 (LTS)
        - [ ]	4.10
- [ ] Contao nur Core-Module
    - Contao Version getestet
        - [ ] 4.4 (LTS)
        - [ ] 4.9 (LTS)
        - [ ]	4.10
- [ ] Contao Manager bei bestehender Installation hinzufügen
- [ ] Unterschiedliche PHP-Versionen, auch inkompatible testen
    - [ ] PHP 7.1
    - [ ] PHP 7.2
    - [ ] PHP 7.3
    - [ ] PHP 7.4
    - [ ] PHP 8.0
- [ ] Contao aus Backup wiederherstellen mit vorhandener `composer.lock` und `composer.json`


## 2. Fragen zum Installationsprozess

- [ ] Contao Manager außerhalb WEB-Verzeichnis
- [ ] Contao Manager Self-update
- [ ] Wird bestehende `users.json` und `config.json` erkannt
- [ ] Funktioniert die Systemprüfung (PHP-Version und Pfad erkannt)
- [ ] Composer Resolver Cloud hat geklappt
- [ ] Installation läuft fehlerfrei durch?


## 3. Pakete verwalten
- [ ] Suche nach Paketen funktioniert
- [ ] Alle Pakete aktualisieren
- [ ] Neue Erweiterung installieren (z. B. [contao-easy_themes](https://packagist.org/packages/terminal42/contao-easy_themes), [notification_center](https://packagist.org/packages/terminal42/notification_center))
- [ ] Mehrere Pakete gleichzeitig installieren
- [ ] Core Paket entfernen (z. B. [faq-bundle](https://packagist.org/packages/contao/faq-bundle))
- [ ] Ein Paket entfernen
- [ ] Mehrere Pakete gleichzeitig entfernen
- [ ] Version bei Paketen ändern
- [ ] Private Pakete hochladen
- [ ] Manuelle Änderungen in `composer.json` werden erkannt
- [ ] Empfohlene Pakete installieren (z. B. [contao-leads](https://packagist.org/packages/terminal42/contao-leads) -> [phpspreadsheet](https://packagist.org/packages/phpoffice/phpspreadsheet))


## 4. Wartungsarbeiten
- [ ] Debug-Modus
- [ ] Cache leeren
- [ ] Installtool entsperren
- [ ] Debug-Modus aktivieren
- [ ] Composer Class Loader
- [ ] Opcode-Cache leeren
- [ ] Composer-Abhängigkeiten


## 5. Tools
- [ ] PHP infos auslesen
- [ ] Anbindung externer Dienste (trakked) fehlerfrei
- [ ] Systemprüfung aufrufen und Serverkonfiguration ändern möglich


## 6. Fragen zur GUI und Darstellung
- [ ] Allgemeine Darstellungsfehler auf dem Desktop
    - [ ] Google Chrome
    - [ ] Microsoft Edge
    - [ ] Mozilla Firefox
    - [ ] Opera
    - [ ] Safari
- [ ] Allgemeine Darstellungsfehler auf mobilen Geräten
    - [ ] Google Chrome
    - [ ] Microsoft Edge
    - [ ] Mozilla Firefox
    - [ ] Opera
    - [ ] Safari
- [ ] Übersetzungsfehler
- [ ] Automatischer Timeout testen
- [ ] Ausgabe Console anzeigen und log kopieren
