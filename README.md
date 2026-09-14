# FF Reichenau – Website

Offizielle Webpräsenz der **Freiwilligen Feuerwehr Reichenau** (Innsbruck, Tirol).  
Nachbau und Erweiterung der bestehenden Website [ffr.at](https://ffr.at).

---

## Inhaltsverzeichnis

- [Überblick](#überblick)
- [Technologie-Stack](#technologie-stack)
- [Projektstruktur](#projektstruktur)
- [Installation & Start](#installation--start)
- [Admin-Bereich](#admin-bereich)
- [Mitglieder-System](#mitglieder-system)
- [Dienstgrade (Ränge)](#dienstgrade-ränge)
- [Seiten](#seiten)
- [Datenbank](#datenbank)
- [Bilder & Assets](#bilder--assets)

---

## Überblick

Die Website bildet die Freiwillige Feuerwehr Reichenau ab mit:

- **Öffentlicher Bereich**: Startseite, Über Uns (Mannschaft, Kommando, Ausschuss, Ehrenmitglieder, Jugend, Geschichte, Organigramm, Schutzbereich), Ausrüstung & Fahrzeuge, Jugendfeuerwehr, Berichte/Einsätze, Termine, Kontakt, Datenschutzerklärung
- **Admin-Bereich**: Vollständiges CRUD für Mitglieder und Berichte, Dashboard mit Statistiken, Mitglieder-Filterung und Suche
- **70 Mitglieder** mit Fotos, Dienstgraden und Multi-Funktions-System
- **15 Einsatzberichte** mit 71 Bildern
- **7 Fahrzeuge** mit 27 Bildern
- **30 offizielle Dienstgrad-Abzeichen** (PNG) des Tiroler Feuerwehrverbands

---

## Technologie-Stack

| Komponente     | Technologie                                  |
|----------------|----------------------------------------------|
| **Backend**    | PHP 8.4 (ohne Framework, Plain PHP)          |
| **Datenbank**  | MariaDB/MySQL                                |
| **Frontend**   | HTML5, CSS3 (Custom Properties), Vanilla JS  |
| **Schrift**    | Inter (Google Fonts)                         |
| **Icons**      | Font Awesome 6.5.1 (CDN)                    |
| **Webserver**  | PHP Built-in Development Server              |

---

## Projektstruktur

```
FFR/
├── index.php                  # Router (Whitelist-basiert)
├── .htaccess                  # Apache URL-Rewriting
├── start.bat                  # Dev-Server starten (localhost:8000)
├── autocommit.bat             # Git Auto-Commit alle 30 Sek.
│
├── config/
│   ├── database.php           # DB-Verbindung (MySQL), Schema, Uploads
│   ├── migrations.php         # Automatische DB-Migrationen (laufen bei jedem Request)
│   ├── ranks.php              # Tiroler Dienstgrad-Definitionen
│   ├── badges.php             # Verwendungs-/Funktionsabzeichen
│   ├── env.php                # .env-Loader
│   ├── gate.php                # Seitensperre (Zugangspasswort)
│   ├── seed.php               # Datenbank-Seeding
│   └── reseed.php             # Re-Seeding
│
├── includes/
│   ├── header.php             # HTML-Head, Navigation (öffentlich)
│   ├── nav.php                # Navigationsmenü
│   └── footer.php             # Footer mit Links & Copyright
│
├── pages/
│   ├── home.php               # Startseite
│   ├── ueber-uns.php          # Über Uns (Mannschaft, Kommando, Ausschuss, etc.)
│   ├── ausruestung.php        # Fahrzeuge & Ausrüstung
│   ├── jugend.php             # Jugendfeuerwehr
│   ├── berichte.php           # Einsatzberichte mit Bildern
│   ├── termine.php            # Termine & Veranstaltungen
│   ├── kontakt.php            # Kontaktseite
│   └── datenschutz.php        # Datenschutzerklärung (DSGVO)
│
├── admin/
│   ├── index.php              # Dashboard
│   ├── login.php              # Login-Seite
│   ├── logout.php             # Logout
│   ├── auth.php               # Session-Verwaltung, CSRF, Helfer
│   ├── members.php            # Mitglieder-Liste (Filter, Suche, Löschen)
│   ├── member-edit.php        # Mitglied anlegen/bearbeiten
│   ├── reports.php            # Berichte-Liste
│   ├── report-edit.php        # Bericht anlegen/bearbeiten
│   ├── settings.php           # Einstellungen
│   ├── admin-style.css        # Admin-spezifisches CSS
│   └── includes/
│       ├── admin-header.php   # Admin-Layout (Sidebar, Header)
│       └── admin-footer.php   # Admin-Footer
│
├── assets/
│   ├── css/
│   │   └── style.css          # Haupt-Stylesheet (öffentlich)
│   ├── js/
│   │   └── main.js            # Öffentliches JavaScript
│   └── images/
│       ├── ranks/             # 30 Dienstgrad-Abzeichen (PNG)
│       ├── vehicles/          # Fahrzeugbilder
│       ├── sponsors/          # Sponsorenlogos
│       └── ...                # Weitere Bilder (Geschichte, Schutzgebiet, etc.)
│
└── uploads/
    ├── members/               # Mitglieder-Fotos
    └── reports/               # Berichts-Bilder
```

---

## Installation & Start

### Voraussetzungen

- **PHP 8.0+** (getestet mit PHP 8.4.20) mit aktivierter `pdo_mysql`-Erweiterung
- Laufender MySQL/MariaDB-Server (z.B. über XAMPP) mit den Zugangsdaten aus `.env`
- Keine weiteren Abhängigkeiten (kein Composer, kein Node.js)

### Schnellstart

```bash
# Repository klonen
git clone <repo-url> FFR
cd FFR

# PHP Dev-Server starten
php -S localhost:8000

# Oder unter Windows:
start.bat
```

Die Website ist dann erreichbar unter: **http://localhost:8000**

### Umgebungsvariablen (.env)

Datenbank- und SMTP-Zugangsdaten werden aus einer `.env`-Datei im Projektroot gelesen (siehe `.env.example`). Die `.env` selbst ist in `.gitignore` und wird nie eingecheckt.

```bash
cp .env.example .env
# .env öffnen und Zugangsdaten eintragen
```

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ffrdb
DB_USER=ffr
DB_PASSWORD=...

SMTP_HOST=...
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=...
SMTP_PASSWORD=...
SMTP_FROM_EMAIL=...
SMTP_TO_EMAIL=reichenau@feuerwehr.tirol
```

### Datenbank & Migrationen

Tabellen und der Standard-Admin werden bei jedem Seitenaufruf automatisch durch `config/database.php` → `initDatabase()` angelegt, falls sie noch fehlen. Direkt danach prüft `runMigrations()` (siehe `config/migrations.php`), ob es noch nicht angewendete Schema- oder Datenänderungen gibt, und wendet nur die fehlenden an – erledigt in Millisekunden, wenn ohnehin schon alles aktuell ist. Eine `migrations`-Tabelle merkt sich, was bereits gelaufen ist.

Das bedeutet: Nach dem Hochladen neuer Dateien per FTP reicht ein einziger Seitenaufruf, damit sich die Datenbank automatisch aktualisiert – kein manueller SQL-Import über phpMyAdmin nötig. Neue Änderungen werden einfach als neuer Eintrag mit eindeutiger ID in `getMigrations()` angehängt.

---

## Admin-Bereich

Erreichbar unter: **http://localhost:8000/admin/**

### Standard-Zugangsdaten

| Feld       | Wert         |
|------------|--------------|
| Benutzername | `admin`     |
| Passwort   | `admin2024`  |

### Funktionen

- **Dashboard**: Statistiken (Mitglieder, Berichte, Bilder)
- **Mitglieder**: Anlegen, Bearbeiten, Löschen, Foto-Upload, Dienstgrad-Zuweisung, Multi-Funktions-System, Filter nach Gruppen, Textsuche
- **Berichte**: CRUD mit Mehrfach-Bildupload, Kategorien (Einsatz, Übung, Jugend, Sonstige)
- **Sicherheit**: CSRF-Schutz, Session-basierte Authentifizierung, sichere Datei-Uploads (MIME-Check, zufällige Dateinamen)

---

## Mitglieder-System

### Grundgruppen

Jedes Mitglied gehört zu genau **einer Grundgruppe**:

| Grundgruppe      | Beschreibung                        |
|------------------|-------------------------------------|
| **Mannschaft**   | Aktive Einsatzmannschaft (Standard) |
| **Ehrenmitglieder** | Ehemalige verdiente Mitglieder   |
| **Jugend**       | Jugendfeuerwehr                     |

### Multi-Funktions-System

Zusätzlich zur Grundgruppe können Mitgliedern **beliebig viele Funktionen** zugewiesen werden. Jede Funktion hat:

- **Sektion**: In welchem Bereich das Mitglied auf der Website angezeigt wird (Kommando, Ausschuss, Beauftragter)
- **Rolle**: Die konkrete Bezeichnung (z.B. Kommandant, Kassier, Gerätewart)

Funktionen werden als **JSON** in der Spalte `functions` gespeichert:

```json
[
  {"section": "Kommando", "role": "Kommandant"},
  {"section": "Ausschuss", "role": "Stv-Kdt. & Bezirkskommandant"}
]
```

Ein Mitglied kann also z.B. gleichzeitig im Kommando **und** im Ausschuss aufscheinen — mit jeweils unterschiedlicher Rolle — obwohl es nur **einen einzigen Datensatz** gibt.

### Admin-Formular

Im Mitglied-Bearbeitungsformular:
1. **Dienstgrad**: Dropdown mit allen 30+ Tiroler Rängen (mit Badge-Vorschau)
2. **Grundgruppe**: Mannschaft / Ehrenmitglieder / Jugend
3. **Funktionen**: Dynamisch hinzufügbare Zeilen mit Sektion-Dropdown + Rollen-Textfeld
4. **Details**: Eintrittsdatum, Telefon, E-Mail, Bio
5. **Foto**: Upload mit Vorschau

### Filterung im Admin

- **Gruppen-Filter**: Alle, Kommando, Ausschuss, Beauftragter, Mannschaft, Ehrenmitglieder, Jugend
- **Live-Textsuche**: Filtert sofort nach Name, Rang oder Funktion

---

## Dienstgrade (Ränge)

30+ offizielle Dienstgrade des Tiroler Landesfeuerwehrverbands in `config/ranks.php`:

| Kategorie        | Ränge                                                              |
|------------------|--------------------------------------------------------------------|
| **Mannschaft**   | JFM, PFM, FM, OFM, HFM                                           |
| **Chargen**      | LM, OLM, HLM, BM, OBM, HBM                                      |
| **Offiziere**    | V, OV, HV, BI, OBI, HBI, ABI, BR, OBR, LBD-Stv, LBD            |
| **Sonderränge**  | FArzt, FKur                                                       |
| **Ehrenränge**   | E-FM, E-OFM, E-HFM, E-BM, E-V, E-HBI                           |

Jeder Rang hat ein **PNG-Abzeichen** in `assets/images/ranks/` (heruntergeladen von der FF Kufstein).

---

## Seiten

| Route           | Datei                  | Beschreibung                                           |
|-----------------|------------------------|--------------------------------------------------------|
| `/`             | `pages/home.php`       | Startseite mit Hero, Features, aktuelle Berichte       |
| `/ueber-uns`    | `pages/ueber-uns.php`  | Mannschaft, Kommando, Ausschuss, Geschichte, Organigramm |
| `/ausruestung`  | `pages/ausruestung.php`| 7 Fahrzeuge mit Bildern und Beschreibungen             |
| `/jugend`       | `pages/jugend.php`     | Jugendfeuerwehr, Übungsplan, Betreuer                  |
| `/berichte`     | `pages/berichte.php`   | Einsatzberichte mit Bildergalerien                     |
| `/termine`      | `pages/termine.php`    | Kommende Termine und Veranstaltungen                   |
| `/kontakt`      | `pages/kontakt.php`    | Kontaktdaten, Anfahrt                                  |
| `/datenschutz`  | `pages/datenschutz.php`| Datenschutzerklärung (DSGVO-konform, Österreich)       |

---

## Datenbank

### Tabellen

| Tabelle          | Beschreibung                                    |
|------------------|-------------------------------------------------|
| `users`          | Admin-Benutzer (username, password-hash, name)  |
| `members`        | Mitglieder mit Rang, Funktionen, Foto, Details  |
| `reports`        | Einsatzberichte mit Datum, Kategorie, Inhalt    |
| `report_images`  | Bilder zu Berichten (1:n zu reports)             |

### Wichtige Spalten (members)

| Spalte       | Typ    | Beschreibung                                          |
|--------------|--------|-------------------------------------------------------|
| `rank`       | TEXT   | Dienstgrad-Kürzel (z.B. "OBI", "HFM")               |
| `functions`  | TEXT   | JSON-Array mit Sektions-Rollen (Kommando, Ausschuss) |
| `group_name` | TEXT   | Grundgruppe (Mannschaft/Ehrenmitglieder/Jugend)      |
| `photo`      | TEXT   | Pfad zum Foto in uploads/members/                    |
| `sort_order` | INT    | Sortierreihenfolge innerhalb der Gruppe              |

---

## Bilder & Assets

- **Mitglieder-Fotos**: `uploads/members/` — 52 Porträtbilder
- **Berichts-Bilder**: `uploads/reports/` — 71 Einsatzbilder
- **Fahrzeuge**: `assets/images/vehicles/` — 27 Fahrzeugbilder
- **Dienstgrad-Abzeichen**: `assets/images/ranks/` — 30 PNG-Badges
- **Sponsoren**: `assets/images/sponsors/` — Partnerlogos
- **Sonstiges**: Geschichte, Organigramm, Schutzgebietskarte

---

## Lizenz

Dieses Projekt ist für die Freiwillige Feuerwehr Reichenau, Innsbruck, Tirol.  
Bilder und Inhalte unterliegen dem Copyright der jeweiligen Rechteinhaber.

---

## Kontakt

**Freiwillige Feuerwehr Reichenau**  
Andechsstraße 83, 6020 Innsbruck  
[office@ffr.at](mailto:office@ffr.at)  
[www.ffr.at](https://ffr.at)
