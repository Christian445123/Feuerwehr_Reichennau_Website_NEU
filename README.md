# FF Reichenau – Website

Offizielle Webpräsenz der **Freiwilligen Feuerwehr Reichenau** (Innsbruck Stadt, Tirol).

---

## Inhaltsverzeichnis

- [Überblick](#überblick)
- [Technologie-Stack](#technologie-stack)
- [Projektstruktur](#projektstruktur)
- [Installation & Start](#installation--start)
- [Sicherheit](#sicherheit)
- [Wartungsmodus / Zugangssperre](#wartungsmodus--zugangssperre)
- [Admin-Bereich](#admin-bereich)
- [Protokolle & Discord-Benachrichtigungen](#protokolle--discord-benachrichtigungen)
- [Mitglieder-System](#mitglieder-system)
- [Organigramm](#organigramm)
- [Dienstgrade (Ränge)](#dienstgrade-ränge)
- [Fahrzeug- & Wache-Fotos](#fahrzeug--wache-fotos)
- [Berichte & Bilder-Lightbox](#berichte--bilder-lightbox)
- [Kontaktformulare](#kontaktformulare)
- [Google Analytics](#google-analytics)
- [Seiten](#seiten)
- [Datenbank](#datenbank)
- [Bilder & Assets](#bilder--assets)
- [Änderungsprotokoll](#änderungsprotokoll)

---

## Überblick

Die Website bildet die Freiwillige Feuerwehr Reichenau vollständig ab:

- **Öffentlicher Bereich**: Startseite, Über Uns (Mannschaft, Kommando, Ausschuss, Ehrenmitglieder, Jugend, Geschichte, Organigramm, Schutzbereich), Ausrüstung & Fahrzeuge, Jugendfeuerwehr (mit eigenem Kontaktformular), Einsatzberichte mit Bilder-Lightbox, Termine, Alarmierungen (eigene Statistik + Live-Übersicht des Landes-Feuerwehrverbands Tirol), Sicherheitstipps, Kontakt & Impressum, Datenschutzerklärung
- **Admin-Bereich**: Vollständiges CRUD für Mitglieder, Berichte, Dienstgrade, Organigramm und Benutzer, granulares Berechtigungssystem, Aktivitäts-/Login-Protokolle mit Discord-Anbindung, Rate-Limiting mit IP-Sperren, austauschbare Fahrzeug-/Wache-Fotos ohne Code-Änderung
- **Sicherheit**: Verschlüsselte Zugangsdaten in der `.env`, gehärtete `.htaccess` gegen den Abruf sensibler Dateien, Rate-Limiting + automatische IP-Sperren gegen Missbrauch, CSRF-Schutz auf allen Formularen
- **Wartungsmodus**: Ein-/ausschaltbare Zugangssperre mit eigener Wartungsseite statt einer nackten Passwortabfrage

---

## Technologie-Stack

| Komponente     | Technologie                                            |
|----------------|---------------------------------------------------------|
| **Backend**    | PHP 8.x (ohne Framework, Plain PHP)                     |
| **Datenbank**  | MariaDB/MySQL (PDO)                                     |
| **Frontend**   | HTML5, CSS3 (Custom Properties), Vanilla JS             |
| **Schriften**  | Poppins & Barlow Condensed (öffentlich), Inter (Admin)  |
| **Icons**      | Font Awesome 6.5.1 (CDN)                                |
| **Externe Dienste** | Google Analytics 4 (optional, Cookie-Consent), Discord-Webhooks (optional, für Logs), Alarmierungsübersicht Landes-Feuerwehrverband Tirol (Iframe-Embed) |
| **Webserver**  | Apache (`.htaccess`) oder PHP Built-in Development Server |

---

## Projektstruktur

```
FFR/
├── index.php                  # Router (Whitelist-basiert)
├── zugang.php                 # Wartungsseite / Zugangssperre
├── .htaccess                  # Apache: URL-Rewriting + Schutz vor Dotfile-/DB-Zugriff
├── .env / .env.example        # Zugangsdaten (nie in Git, siehe .gitignore)
├── start.bat                  # Dev-Server starten
├── autocommit.bat             # Optionales Git Auto-Commit-Skript
│
├── config/
│   ├── database.php           # DB-Verbindung, Schema, Upload-Helfer
│   ├── migrations.php         # Automatische DB-Migrationen (laufen bei jedem Request)
│   ├── env.php                # .env-Loader
│   ├── crypto.php             # AES-256-Verschlüsselung für DB-/SMTP-Passwort
│   ├── encrypt-tool.php       # CLI-Werkzeug: Klartext-Passwort -> "enc:"-Wert
│   ├── gate.php                # Wartungsmodus / Zugangssperre
│   ├── logging.php            # Aktivitäts-/Login-Log, Rate-Limiting, IP-Sperren, Discord
│   ├── media.php              # Austauschbare Fahrzeug-/Wache-Fotos
│   ├── mail.php               # Mailversand (SMTP-Socket oder PHP mail(), absturzsicher)
│   ├── stats.php              # Einsatz-Statistik-Zähler (1. Freitag im März, manuell zurücksetzbar)
│   ├── analytics.php          # Google-Analytics-Measurement-ID (site_settings)
│   ├── ranks.php              # Tiroler Dienstgrad-Definitionen
│   ├── badges.php             # Verwendungs-/Funktionsabzeichen
│   ├── seed.php / reseed.php  # Datenbank-Seeding
│   └── secret_key.php         # Auto-generierter Verschlüsselungsschlüssel (nie in Git)
│
├── includes/
│   ├── header.php             # HTML-Head, Cache-Busting für CSS
│   ├── nav.php                # Navigationsmenü (inkl. mobilem Hamburger-Menü)
│   └── footer.php             # Footer, Cookie-Consent-Banner, Google Analytics
│
├── pages/
│   ├── home.php               # Startseite (Hero, Statistik, Einsatzgebiete, aktuelle Berichte, Social Media)
│   ├── ueber-uns.php          # Mannschaft, Kommando, Ausschuss, Geschichte, Organigramm, Schutzbereich
│   ├── ausruestung.php        # 7 Fahrzeuge (DB-Fotos) + Wache
│   ├── jugend.php             # Jugendfeuerwehr mit eigenem Kontaktformular
│   ├── berichte.php           # Einsatzberichte mit Kategorie-Filter und Bildergalerien
│   ├── termine.php            # Termine & Veranstaltungen
│   ├── alarmierungen.php      # Eigene Einsatzstatistik + Live-Übersicht Tirol
│   ├── sicherheitstipps.php   # Sicherheitstipps für die Bevölkerung
│   ├── kontakt.php            # Kontaktformular, Kontaktdaten, Impressum, Allgemeine Hinweise
│   └── datenschutz.php        # Datenschutzerklärung (DSGVO-konform, Österreich)
│
├── admin/
│   ├── index.php              # Dashboard
│   ├── login.php / logout.php / auth.php  # Login (rate-limitiert), Session, CSRF
│   ├── permissions.php        # Berechtigungssystem
│   ├── members.php / member-edit.php      # Mitglieder-CRUD
│   ├── ranks.php / rank-edit.php          # Dienstgrade-CRUD
│   ├── reports.php / report-edit.php      # Berichte-CRUD mit Bild-Upload
│   ├── orgchart.php           # Organigramm-Namen pflegen
│   ├── vehicle-photos.php     # Fahrzeug-/Wache-Fotos austauschen
│   ├── users.php / user-edit.php          # Benutzer & Berechtigungen
│   ├── logs.php               # Aktivitäts-Log
│   ├── login-log.php          # Login-Log (Admin + Zugangssperre)
│   ├── rate-limit.php         # Rate-Limit-Ereignisse + IP-Sperren verwalten
│   ├── settings.php           # Zugangspasswort, Wartungsmodus, Statistik-Reset, Google Analytics
│   ├── deploy.php             # Git-Pull-Button (Server auf GitHub-Stand bringen)
│   └── includes/               # Admin-Layout (Sidebar, Header, Footer)
│
├── assets/
│   ├── css/style.css          # Haupt-Stylesheet (öffentlich, inkl. Lightbox, Mobile-Menü)
│   ├── js/main.js             # Öffentliches JavaScript (Lightbox, mobiles Menü, Zähler-Animation)
│   └── images/                # Statische Bilder (Ränge, Logos, Geschichte, Einsatzgebiete, ...)
│
└── uploads/
    ├── members/                # Mitglieder-Fotos
    ├── reports/                 # Berichts-Bilder
    ├── vehicles/                # Über Admin hochgeladene Fahrzeugfotos
    └── wache/                   # Über Admin hochgeladene Wache-Fotos
```

---

## Installation & Start

### Voraussetzungen

- **PHP 8.0+** mit aktivierter `pdo_mysql`- und `curl`-Erweiterung
- Laufender MySQL/MariaDB-Server (z.B. über XAMPP) mit den Zugangsdaten aus `.env`
- Keine weiteren Abhängigkeiten (kein Composer, kein Node.js)

### Schnellstart

```bash
git clone <repo-url> FFR
cd FFR
php -S localhost:8000
# oder unter Windows: start.bat
```

Die Website ist dann erreichbar unter **http://localhost:8000**. Der Admin-Bereich unter **http://localhost:8000/admin/**.

### Umgebungsvariablen (.env)

```bash
cp .env.example .env
# .env öffnen und Zugangsdaten eintragen
```

```env
# Datenbank
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ffrdb
DB_USER=ffr
DB_PASSWORD=enc:...          # siehe Abschnitt "Sicherheit"

# SMTP (für Kontaktformulare; leer lassen = Fallback auf PHP mail())
SMTP_HOST=
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=
SMTP_PASSWORD=enc:...
SMTP_FROM_EMAIL=
SMTP_FROM_NAME=FF Reichenau Website
SMTP_TO_EMAIL=reichenau@feuerwehr.tirol

# Discord-Webhooks für Logs (optional, leer = deaktiviert)
DISCORD_WEBHOOK_ACTIVITY=
DISCORD_WEBHOOK_LOGIN=
DISCORD_WEBHOOK_RATE_LIMIT=
```

Die `.env`-Datei selbst ist in `.gitignore` und wird **nie** eingecheckt (das Repository ist öffentlich auf GitHub).

### Datenbank & Migrationen

Tabellen und der Standard-Admin werden bei jedem Seitenaufruf automatisch durch `config/database.php` → `initDatabase()` angelegt, falls sie fehlen. Direkt danach prüft `runMigrations()` (siehe `config/migrations.php`), ob es noch nicht angewendete Schema- oder Datenänderungen gibt, und wendet nur die fehlenden an. Eine `migrations`-Tabelle merkt sich, was bereits gelaufen ist.

Das bedeutet: Nach dem Hochladen neuer Dateien per FTP/Git reicht ein einziger Seitenaufruf, damit sich die Datenbank automatisch aktualisiert – kein manueller SQL-Import nötig. Neue Änderungen werden als neuer Eintrag mit eindeutiger, unveränderlicher ID in `getMigrations()` angehängt; bestehende Einträge werden nie nachträglich verändert.

---

## Sicherheit

- **Verschlüsselte Zugangsdaten**: `DB_PASSWORD` und `SMTP_PASSWORD` werden mit AES-256-CBC verschlüsselt in der `.env` gespeichert (Präfix `enc:`), der Schlüssel liegt in der gitignoreten `config/secret_key.php` – nicht in der `.env` selbst. Verschlüsselten Wert erzeugen: `php config/encrypt-tool.php "mein-passwort"`.
- **Gehärtete `.htaccess`**: blockiert den direkten Web-Zugriff auf Dotfiles (`.env`, `.git`, ...) sowie `.db`/`.sql`/`.log`-Dateien, kompatibel mit Apache 2.2 und 2.4+.
- **Rate-Limiting + automatische IP-Sperren**: Admin-Login, Zugangssperre und beide Kontaktformulare (Haupt- und Jugendformular) sind rate-limitiert. Bei deutlicher Überschreitung wird die IP automatisch gesperrt (siehe [Protokolle](#protokolle--discord-benachrichtigungen)).
- **CSRF-Schutz** auf allen Formularen im Admin-Bereich sowie den öffentlichen Kontaktformularen, zusätzlich Honeypot-Felder gegen Bots.
- **Absturzsicherer Mailversand**: Viele Hoster deaktivieren PHP's `mail()`-Funktion serverseitig gegen Spam-Missbrauch. Ein Aufruf einer deaktivierten Funktion ist ein fataler Fehler, den `@` nicht unterdrückt – `config/mail.php` prüft das vorab, und `pages/kontakt.php`/`pages/jugend.php` fangen zusätzlich jeden unerwarteten Fehler beim Mailversand ab, damit nie eine leere weiße Seite statt einer Fehlermeldung erscheint.
- **Sichere Datei-Uploads**: MIME-Type-Prüfung per `finfo`, zufällige Dateinamen, Upload-Verzeichnisse außerhalb der direkten Code-Struktur.

---

## Wartungsmodus / Zugangssperre

Solange die Website nicht offiziell ist, kann der Zugriff über **Admin → Einstellungen** komplett gesperrt werden:

- **Aktiv**: Besucher sehen eine Wartungsseite (`zugang.php`) mit drehendem Zahnrad-Icon im Feuerwehr-Look ("Die Website ist in Wartung. Solltest du dennoch darauf zugreifen wollen, bitte Passwort eingeben.") und müssen das Zugangspasswort eingeben.
- **Deaktiviert**: Die Website ist für alle frei zugänglich, keine Passwortabfrage.

Jeder Anmeldeversuch (Erfolg wie Fehlschlag) wird protokolliert und ist rate-limitiert.

---

## Admin-Bereich

Erreichbar unter **/admin/**. Das Hauptkonto `admin` hat immer und unveränderlich Vollzugriff (kann sich nie selbst aussperren). Weitere Benutzer erhalten granulare Berechtigungen:

| Berechtigung          | Beschreibung                                                        |
|-----------------------|-----------------------------------------------------------------------|
| `reports.manage`      | Berichte verwalten (erstellen, bearbeiten, löschen, veröffentlichen) |
| `members.manage`      | Mitglieder verwalten (erstellen, bearbeiten, löschen, Fotos)         |
| `members.functions`   | Funktionen & Abzeichen zuweisen (Kommando, Ausschuss, Dienstgrad)    |
| `ranks.manage`        | Dienstgrade verwalten                                                 |
| `orgchart.manage`     | Organigramm verwalten (Namen den Positionen zuordnen)                |
| `media.manage`        | Fahrzeug- und Wache-Fotos austauschen                                 |
| `settings.manage`     | Zugangspasswort, Wartungsmodus, Statistik-Reset, Google Analytics    |
| `users.manage`        | Benutzer & Rechte verwalten                                           |
| `logs.manage`         | Aktivitäts-Log, Login-Log, Rate-Limit & IP-Sperren einsehen/verwalten|
| `deploy.manage`       | Deployment: neuesten Stand von GitHub holen (`git pull`)              |

---

## Protokolle & Discord-Benachrichtigungen

Drei getrennte Protokolle, jeweils in der Datenbank gespeichert und im Admin-Bereich einsehbar (Berechtigung `logs.manage`):

1. **Aktivitäts-Log** (`admin/logs.php`): erfasst sicherheitsrelevante Admin-Aktionen – Login/Logout, Berichte/Mitglieder/Dienstgrade/Benutzer anlegen/ändern/löschen, Organigramm-Änderungen, Einstellungsänderungen, Fahrzeugfoto-Änderungen, Deployments. Filterbar nach Benutzer und Aktion.
2. **Login-Log** (`admin/login-log.php`): jeder Anmeldeversuch am Admin-Bereich *und* an der Website-Zugangssperre, mit IP-Adresse, User-Agent und Erfolg/Fehlschlag. Direkter "IP sperren"-Button pro Eintrag.
3. **Rate-Limit & IP-Sperren** (`admin/rate-limit.php`): Übersicht aller Rate-Limit-Überschreitungen sowie manuell/automatisch gesperrter IP-Adressen, mit Formular zum manuellen Sperren (befristet oder dauerhaft) und Entsperren.

Alle drei Protokolle können zusätzlich **live an Discord gemeldet** werden: In der `.env` je einen Webhook für `DISCORD_WEBHOOK_ACTIVITY`, `DISCORD_WEBHOOK_LOGIN` und `DISCORD_WEBHOOK_RATE_LIMIT` eintragen. Ohne eingetragenen Webhook passiert einfach nichts – ein Discord-Ausfall blockiert nie die Website selbst (kurzes Timeout, Fehler werden verschluckt).

---

## Mitglieder-System

### Grundgruppen

Jedes Mitglied gehört zu genau **einer Grundgruppe**: Mannschaft (Standard), Ehrenmitglieder oder Jugend.

### Multi-Funktions-System

Zusätzlich zur Grundgruppe können Mitgliedern beliebig viele Funktionen zugewiesen werden, gespeichert als JSON in der Spalte `functions`:

```json
[
  {"section": "Kommando", "role": "Kommandant"},
  {"section": "Ausschuss", "role": "Ausbildung"}
]
```

Ein Mitglied kann so z.B. gleichzeitig im Kommando **und** im Ausschuss aufscheinen – mit jeweils unterschiedlicher Rolle – obwohl es nur einen einzigen Datensatz gibt. Die Foto-Kacheln unter "Über Uns" gruppieren Mitglieder automatisch nach der jeweiligen `section`.

---

## Organigramm

Das Organigramm (`pages/ueber-uns.php`, Abschnitt "Organigramm") ist in drei Sektionen gegliedert – **Kommando**, **Gruppen**, **Beauftragte** – im dunklen Layout mit gelben Namens-Kacheln. Die Struktur (welche Positionen es gibt) ist fest im Code hinterlegt, nur die **Namen** je Position werden über **Admin → Organigramm** gepflegt (`org_chart_positions`-Tabelle). Ist eine Position frei, erscheint automatisch "derzeit nicht besetzt". Wird ein Name im Organigramm gefunden, der auch ein Mitglied mit hinterlegtem Dienstgrad ist, erscheint automatisch das passende Rang-Abzeichen.

---

## Dienstgrade (Ränge)

30+ offizielle Dienstgrade des Tiroler Landesfeuerwehrverbands in `config/ranks.php`, jeweils mit PNG-Abzeichen in `assets/images/ranks/`. Verwaltung über **Admin → Dienstgrade**.

---

## Fahrzeug- & Wache-Fotos

Fotos der 7 Fahrzeuge (Fuhrpark) und der 4 Wache-Bereiche lassen sich über **Admin → Fahrzeug- & Wache-Fotos** austauschen, ganz ohne Code-Änderung:

- **Fahrzeuge**: jedes Fahrzeug hat eine Foto-Galerie (`vehicle_photos`-Tabelle) – das erste Foto ist automatisch das Hauptbild, alle weiteren erscheinen als Vorschaubilder zum Durchklicken. Fotos können hinzugefügt oder gelöscht werden.
- **Wache**: vier feste Slots (Umkleideraum, Umkleidebereich, Atemschutz-Arbeitsplatz, Funkkabine, `media_slots`-Tabelle) mit unveränderlichem Label – jeweils per Upload austauschbar.

Alle Fahrzeug- und Wache-Bilder sind zusätzlich über die Bilder-Lightbox anklickbar.

---

## Berichte & Bilder-Lightbox

Einsatzberichte (`pages/berichte.php`) unterstützen Kategorien (Einsatz, Übung, Jugend, Veranstaltungen, Sonstige) mit Unterkategorien für Einsatz/Übung (Brand, Technisch, ABC, bei Einsätzen zusätzlich Unterstützung), mehreren Bildern pro Bericht sowie einem optionalen Link zu einem Instagram-Beitrag oder einer externen Website. Ältere Berichte (> 2 Jahre) werden automatisch mit einem "Archiviert"-Badge gekennzeichnet – die Archivierung ist rein kosmetisch, unter "Alle" und den Kategorie-Filtern bleiben sie weiterhin sichtbar.

**Bilder-Lightbox**: Alle inhaltlichen Fotos der Website (Berichte, Geschichte, Schutzbereich, Fahrzeuge, Wache, Jugend, Einsatzgebiete auf der Startseite) lassen sich anklicken und vergrößert ansehen, bei mehreren Bildern mit Pfeilen zum Durchblättern und X zum Schließen. Logos, Sponsoren-Logos und Rang-/Funktionsabzeichen sind bewusst ausgenommen (Icons/Branding, keine Foto-Inhalte).

---

## Kontaktformulare

Es gibt zwei getrennte Kontaktformulare, die beide denselben absturzsicheren Mailversand (`config/mail.php`) nutzen:

- **Hauptformular** (`pages/kontakt.php`): allgemeine Anfragen, sendet an `SMTP_TO_EMAIL`.
- **Jugend-Formular** (`pages/jugend.php`, Abschnitt "Kontakt"): eigenes, kompaktes Formular (Vorname/Nachname/E-Mail/Nachricht) speziell für Interessent:innen der Jugendfeuerwehr.

Beide sind CSRF-geschützt, haben ein Honeypot-Feld gegen Bots und sind rate-limitiert (5 Anfragen/Stunde je IP). Ist noch kein SMTP hinterlegt, wird automatisch auf PHP's `mail()` zurückgegriffen; ist auch das serverseitig deaktiviert, erscheint eine verständliche Fehlermeldung statt eines Absturzes.

---

## Google Analytics

Optional über **Admin → Einstellungen** einrichtbar (GA4-Measurement-ID, Format `G-XXXXXXXXXX`). Wird öffentlich erst nach Zustimmung im Cookie-Consent-Banner geladen (`anonymize_ip: true`); ohne Einwilligung wird nichts nachgeladen. Die Einwilligung lässt sich auf der Datenschutzseite jederzeit widerrufen.

---

## Seiten

| Route              | Datei                       | Beschreibung                                                       |
|--------------------|------------------------------|---------------------------------------------------------------------|
| `home`             | `pages/home.php`             | Startseite: Hero, Statistik, Einsatzgebiete, aktuelle Berichte, Alarmierungen, Social Media, Schnellzugriff, Sponsoren |
| `ueber-uns`        | `pages/ueber-uns.php`        | Mannschaft, Kommando, Ausschuss (mit Gruppenfoto), Jugend, Geschichte, Organigramm, Schutzbereich |
| `ausruestung`      | `pages/ausruestung.php`      | 7 Fahrzeuge (austauschbare Fotos) + Wache                            |
| `jugend`           | `pages/jugend.php`           | Jugendfeuerwehr mit eigenem Kontaktformular                          |
| `berichte`         | `pages/berichte.php`         | Einsatzberichte mit Kategorie-Filter und Bildergalerien              |
| `termine`          | `pages/termine.php`          | Kommende Termine und Veranstaltungen                                 |
| `alarmierungen`    | `pages/alarmierungen.php`    | Eigene Einsatzstatistik + Live-Übersicht Landes-Feuerwehrverband Tirol |
| `sicherheitstipps` | `pages/sicherheitstipps.php` | Sicherheitstipps für die Bevölkerung                                 |
| `kontakt`          | `pages/kontakt.php`          | Kontaktformular, Kontaktdaten, Bankverbindung, Impressum, Allgemeine Hinweise |
| `datenschutz`      | `pages/datenschutz.php`      | Datenschutzerklärung (DSGVO-konform, Österreich)                     |

---

## Datenbank

### Wichtige Tabellen

| Tabelle                | Beschreibung                                                    |
|-------------------------|------------------------------------------------------------------|
| `users`                | Admin-Benutzer (username, password-hash, name, permissions)     |
| `members`              | Mitglieder mit Rang, Funktionen (JSON), Foto, Details            |
| `reports` / `report_images` | Einsatzberichte mit Kategorie/Unterkategorie und Bildern (1:n) |
| `org_chart_positions`  | Organigramm-Positionen (Schlüssel, Label, aktueller Name)        |
| `ranks`                | Dienstgrade mit Abzeichen-Pfad                                    |
| `vehicle_photos`       | Fahrzeug-Foto-Galerien (vehicle_key, Pfad, Sortierung)            |
| `media_slots`          | Feste Einzelbild-Slots (z.B. Wache-Fotos)                         |
| `site_settings`        | Key-Value-Einstellungen (Zugangspasswort-Hash, Wartungsmodus, Statistik-Zeitraum, GA-ID) |
| `activity_log`         | Aktivitäts-Log (Admin-Aktionen)                                   |
| `login_log`            | Anmeldeversuche (Admin + Zugangssperre)                           |
| `blocked_ips`          | Gesperrte IP-Adressen (manuell oder automatisch)                  |
| `rate_limit_hits` / `rate_limit_events` | Rate-Limit-Zählung bzw. protokollierte Überschreitungen |
| `migrations`           | Merkt sich, welche Migrationen bereits gelaufen sind              |

---

## Bilder & Assets

- **Mitglieder-Fotos**: `uploads/members/`
- **Berichts-Bilder**: `uploads/reports/`
- **Fahrzeug-/Wache-Fotos**: `assets/images/vehicles/` (Standardfotos) + `uploads/vehicles/` und `uploads/wache/` (über Admin ausgetauschte Fotos)
- **Dienstgrad-Abzeichen**: `assets/images/ranks/`
- **Sonstiges**: Geschichte, Einsatzgebiete (Feuer/Technik/Gefahrgut), Schutzgebietskarte, Ausschuss-Gruppenfoto, Sponsorenlogos

---

## Änderungsprotokoll

Kurzüberblick über die wesentlichen Erweiterungen seit der ursprünglichen Grundversion, thematisch gruppiert:

**Sicherheit**
- `.env` war öffentlich abrufbar → `.htaccess` gehärtet (Apache 2.2/2.4+), zusätzlich DB-/SMTP-Passwort AES-256-verschlüsselt in der `.env`
- Rate-Limiting + automatische IP-Sperren für Admin-Login, Zugangssperre und beide Kontaktformulare
- Absturzsicherer Mailversand (deaktivierte `mail()`-Funktion verursachte zuvor eine leere weiße Seite beim Absenden des Kontaktformulars)

**Protokolle & Monitoring**
- Aktivitäts-Log, Login-Log und Rate-Limit-Übersicht im Admin-Bereich, inkl. manueller IP-Sperr-Verwaltung
- Live-Meldungen aller drei Protokolle an konfigurierbare Discord-Webhooks

**Wartungsmodus**
- Ein-/ausschaltbare Zugangssperre in den Admin-Einstellungen
- Eigene Wartungsseite mit Feuerwehr-Erscheinungsbild (Warnstreifen, großes Wappen, drehendes Zahnrad) statt einer nackten Passwortabfrage

**Design & Mobile**
- Kompletter Mobile-Optimierungsdurchgang; dabei einen kritischen Bug gefunden und behoben, durch den das mobile Menü unsichtbar war (`backdrop-filter` machte die Navbar zum Containing Block für ihre fixed-positionierten Kinder)
- Bilder-Lightbox für alle inhaltlichen Fotos der Website
- Startseite überarbeitet: fotobasierte Berichts-Kacheln statt reiner Icon-Karten, neue "Unsere Einsatzgebiete"-Sektion (Feuer/Technik/Gefahrgut) mit echten Einsatzfotos
- Live-Übersicht "Aktuelle Alarmierungen Tirol" als eingebettetes, automatisch aktualisierendes Iframe unter Alarmierungen; am Handy durch eine Link-Karte ersetzt, da sich der Ausschnitt nicht zuverlässig auf ein fremdes Mobil-Layout übertragen lässt

**Organigramm & Ausschuss**
- Organigramm komplett neu strukturiert (Kommando/Gruppen/Beauftragte) und mit dem tatsächlich amtierenden Kommando abgeglichen (Kommandant und Kommandant-Stv. waren vertauscht)
- Aktuelle Portraitfotos aller 11 Ausschussmitglieder sowie das Ausschuss-Gruppenfoto übernommen

**Jugendfeuerwehr**
- Eigenes Kontaktformular direkt auf der Jugendseite
- Layout und Inhalte grundlegend überarbeitet und faktisch korrigiert (Treffen ist Dienstag 19–21 Uhr, nicht Montag)
- Kontakt-Karte zeigt automatisch die aktuell zuständige Jugendbetreuerin samt Foto

**Fahrzeuge & Wache**
- Fahrzeug- und Wache-Fotos sind jetzt über den Admin-Bereich austauschbar (Hinzufügen/Löschen je Fahrzeug-Galerie, Austauschen der vier Wache-Fotos) statt fest im Code zu stehen

**Rechtliches & Tracking**
- Impressum und Datenschutzerklärung überarbeitet, neuer Abschnitt "Allgemeine Hinweise" (Notruf-Hinweis, Haftungsausschluss, Status der Website)
- Google Analytics mit Cookie-Consent-Banner (inkl. Widerrufsmöglichkeit) integriert

**Sonstiges**
- Einsatzstatistik zählt jetzt ab dem 1. Freitag im März und wird manuell zurückgesetzt (statt eines fixen, automatisch weiterlaufenden Datums)
- Instagram-Verlinkung ergänzt, Berichte können optional mit einem Instagram-Beitrag oder einer externen Website verlinkt werden
- Einsatzart-Kategorisierung (Brand/Technisch/ABC/Unterstützung) für Einsätze und Übungen

---

## Lizenz

Dieses Projekt ist für die Freiwillige Feuerwehr Reichenau, Innsbruck Stadt, Tirol.
Bilder und Inhalte unterliegen dem Copyright der jeweiligen Rechteinhaber.

---

## Kontakt

**Freiwillige Feuerwehr Reichenau**
Rossaugasse 4, A-6020 Innsbruck
[reichenau@feuerwehr.tirol](mailto:reichenau@feuerwehr.tirol)
