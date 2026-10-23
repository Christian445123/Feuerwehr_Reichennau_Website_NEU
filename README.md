# FF Reichenau – Website

Offizielle Webpräsenz der **Freiwilligen Feuerwehr Reichenau** (Innsbruck Stadt, Tirol).

---

## Inhaltsverzeichnis

- [Überblick](#überblick)
- [Technologie-Stack](#technologie-stack)
- [Installation & Start](#installation--start)
- [Sicherheit](#sicherheit)
- [Wartungsmodus / Zugangssperre](#wartungsmodus--zugangssperre)
- [Admin-Bereich & Berechtigungen](#admin-bereich--berechtigungen)
- [Protokolle & Discord-Benachrichtigungen](#protokolle--discord-benachrichtigungen)
- [Mitglieder-System](#mitglieder-system)
- [Organigramm & Dienstgrade](#organigramm--dienstgrade)
- [Berichte-System](#berichte-system)
- [Startseite: Hintergrund, Statistik, Info-Leiste](#startseite-hintergrund-statistik-info-leiste)
- [Einsatzbereitschaft (Bereit / Nicht bereit / Übung)](#einsatzbereitschaft-bereit--nicht-bereit--übung)
- [Mehrsprachigkeit (Deutsch/Englisch) & automatische Übersetzung](#mehrsprachigkeit-deutschenglisch--automatische-übersetzung)
- [Wetterwarnungen & Seiten-Warnhinweis](#wetterwarnungen--seiten-warnhinweis)
- [Schutzbereich-Karte](#schutzbereich-karte)
- [Haussammlung](#haussammlung)
- [Fahrzeug- & Wache-Fotos](#fahrzeug--wache-fotos)
- [Rechtstexte (Impressum/Datenschutz) im Admin-Bereich](#rechtstexte-impressumdatenschutz-im-admin-bereich)
- [Kontaktformulare](#kontaktformulare)
- [Google Analytics](#google-analytics)
- [Deployment](#deployment)
- [Seiten](#seiten)
- [Datenbank](#datenbank)
- [Bilder & Assets](#bilder--assets)
- [Lizenz & Kontakt](#lizenz--kontakt)

---

## Überblick

Die Website bildet die Freiwillige Feuerwehr Reichenau vollständig ab:

- **Öffentlicher Bereich**: Startseite mit Hero-Diashow/Statistik/Info-Leiste, Über Uns (Mannschaft, Kommando, Ausschuss, Ehrenmitglieder, Jugend, Geschichte, Organigramm, Schutzbereich), Ausrüstung (Fuhrpark & Gerätehaus getrennt), Jugendfeuerwehr mit eigenem Kontaktformular, Einsatzberichte (Mehrfachkategorien, Bilder-Lightbox, interne Einsatznummern), Termine, Alarmierungen (eigene Statistik + Live-Übersicht Landes-Feuerwehrverband Tirol), Sicherheitstipps, Mitmachen, Haussammlung, Kontakt & Impressum, Datenschutzerklärung – alles zweisprachig (Deutsch/Englisch) abrufbar.
- **Admin-Bereich**: CRUD für Mitglieder (inkl. Papierkorb), Berichte, Dienstgrade, Organigramm, Rollen & Benutzer, granulares Berechtigungssystem, umfassendes Gesamtprotokoll + Einzel-Protokolle mit Discord-Anbindung, Rate-Limiting mit IP-Sperren, austauschbare Fahrzeug-/Wache-/Hero-Fotos, Rechtstexte-Editor, Haussammlungs-Verwaltung, Schutzbereich-Karteneditor, Einsatzbereitschafts-Umschalter (inkl. SMS/API-Webhook), Seiten-Warnhinweis-System, Git-Deploy-Button.
- **Sicherheit**: Verschlüsselte Zugangsdaten, gehärtete `.htaccess` + PHP-seitige Security-Header/CSP, Rate-Limiting + automatische IP-Sperren, CSRF-Schutz, optionale Zwei-Faktor-Authentifizierung (TOTP) und "Passwort vergessen" für Admin-Konten.
- **Wartungsmodus**: Ein-/ausschaltbare Zugangssperre mit eigener Wartungsseite statt einer nackten Passwortabfrage.

---

## Technologie-Stack

| Komponente     | Technologie                                            |
|----------------|---------------------------------------------------------|
| **Backend**    | PHP 8.x (ohne Framework, Plain PHP)                     |
| **Datenbank**  | MariaDB/MySQL (PDO)                                     |
| **Frontend**   | HTML5, CSS3 (Custom Properties), Vanilla JS             |
| **Schriften**  | Poppins & Barlow Condensed (öffentlich), Inter (Admin)  |
| **Icons**      | Font Awesome 6.5.1 (CDN)                                |
| **Externe Dienste** | Google Analytics 4 (optional, Cookie-Consent), Discord-Webhooks (optional, für Logs), DeepL-API (optional, automatische Übersetzung), GeoSphere Austria Warn-API + UWZ.AT (Wetterwarnungen), Alarmierungsübersicht Landes-Feuerwehrverband Tirol (Iframe-Embed), Nominatim/OpenStreetMap (Schutzbereich-Karte) |
| **Webserver**  | Apache (`.htaccess`) oder PHP Built-in Development Server |

---

## Installation & Start

### Voraussetzungen

- **PHP 8.0+** mit aktivierter `pdo_mysql`- und `curl`-Erweiterung, idealerweise `gd` (für automatische Bildverkleinerung bei Uploads)
- Laufender MySQL/MariaDB-Server mit den Zugangsdaten aus `.env`
- Keine weiteren Abhängigkeiten (kein Composer, kein Node.js)

### Schnellstart

```bash
git clone <repo-url> FFR
cd FFR
php -S localhost:8000
# oder unter Windows: start.bat
```

Die Website ist dann erreichbar unter **http://localhost:8000**, der Admin-Bereich unter **http://localhost:8000/admin/**.

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

# SMTP (für Kontaktformulare & Passwort-Reset-Mails; leer = Fallback auf PHP mail())
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

# DeepL-API für automatische Übersetzungen (optional, leer = deaktiviert)
DEEPL_API_KEY=enc:...
```

Zusätzlich sensible, serverspezifische Einstellungen (DB-/SMTP-Passwort, Discord-Webhooks, DeepL-Key) lassen sich bequem über **Admin → Einstellungen** pflegen, ohne die `.env` von Hand zu bearbeiten – sie landen verschlüsselt in der gitignoreten `config/env.local.php`.

Die `.env`-/`env.local.php`-Dateien selbst sind in `.gitignore` und werden **nie** eingecheckt (das Repository ist öffentlich auf GitHub).

### Datenbank & Migrationen

Tabellen und der Standard-Admin werden bei jedem Seitenaufruf automatisch durch `config/database.php` → `initDatabase()` angelegt, falls sie fehlen. Direkt danach prüft `runMigrations()` (siehe `config/migrations.php`), ob es noch nicht angewendete Schema- oder Datenänderungen gibt, und wendet nur die fehlenden an. Eine `migrations`-Tabelle merkt sich, was bereits gelaufen ist – nach dem Hochladen neuer Dateien reicht also ein einziger Seitenaufruf, kein manueller SQL-Import nötig.

---

## Sicherheit

- **Verschlüsselte Zugangsdaten**: `DB_PASSWORD`, `SMTP_PASSWORD` und `DEEPL_API_KEY` werden mit AES-256-CBC verschlüsselt gespeichert (Präfix `enc:`), der Schlüssel liegt in der gitignoreten `config/secret_key.php`. Verschlüsselten Wert erzeugen: `php config/encrypt-tool.php "mein-passwort"`.
- **Security-Header + CSP**: `config/security-headers.php` setzt zusätzlich zur `.htaccess` (falls der Webserver diese ignoriert, z.B. hinter Cloudflare/nginx) `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Strict-Transport-Security` und eine Content-Security-Policy direkt per PHP.
- **Gehärtete `.htaccess`**: blockiert den direkten Web-Zugriff auf Dotfiles (`.env`, `.git`, ...) sowie `.db`/`.sql`/`.log`-Dateien, kompatibel mit Apache 2.2 und 2.4+.
- **Rate-Limiting + automatische IP-Sperren**: Admin-Login, Zugangssperre und alle öffentlichen Formulare (Kontakt, Jugend, Mitmachen) sind rate-limitiert. Bei deutlicher Überschreitung wird die IP automatisch gesperrt (siehe [Protokolle](#protokolle--discord-benachrichtigungen)).
- **CSRF-Schutz** auf allen Formularen im Admin-Bereich sowie den öffentlichen Formularen, zusätzlich Honeypot-Felder gegen Bots.
- **Zwei-Faktor-Authentifizierung (TOTP)**: optional je Admin-Konto aktivierbar (`config/totp.php`, selbst implementiert, kompatibel mit Google/Microsoft Authenticator, Authy usw.), zusätzlich optionale Login-E-Mail-Benachrichtigung und einstellbares Session-Timeout (**Admin → Mein Konto**).
- **"Passwort vergessen"** für Admin-Konten (`admin/forgot-password.php`, `admin/reset-password.php`): Reset-Link per E-Mail, in der Datenbank wird nur der Hash des Tokens gespeichert.
- **Absturzsicherer Mailversand**: `config/mail.php` prüft vorab, ob `mail()` serverseitig deaktiviert ist, und alle aufrufenden Formulare fangen Fehler ab, damit nie eine leere weiße Seite statt einer Fehlermeldung erscheint.
- **Sichere Datei-Uploads**: MIME-Type-Prüfung per `finfo`, zufällige Dateinamen, automatische Verkleinerung großer Bilder (sofern `gd` verfügbar), Upload-Verzeichnisse außerhalb der direkten Code-Struktur.

---

## Wartungsmodus / Zugangssperre

Solange die Website nicht offiziell ist, kann der Zugriff über **Admin → Einstellungen** komplett gesperrt werden:

- **Aktiv**: Besucher sehen eine Wartungsseite (`zugang.php`) mit drehendem Zahnrad-Icon im Feuerwehr-Look und müssen das Zugangspasswort eingeben.
- **Deaktiviert**: Die Website ist für alle frei zugänglich, keine Passwortabfrage.

Jeder Anmeldeversuch (Erfolg wie Fehlschlag) wird protokolliert und ist rate-limitiert.

---

## Admin-Bereich & Berechtigungen

Erreichbar unter **/admin/**. Das Hauptkonto `admin` hat immer und unveränderlich Vollzugriff (kann sich nie selbst aussperren). Weitere Benutzer erhalten Rollen mit granularen Berechtigungen (**Admin → Rollen**):

| Berechtigung            | Beschreibung                                                           |
|--------------------------|-------------------------------------------------------------------------|
| `reports.manage`        | Berichte verwalten (erstellen, bearbeiten, löschen, archivieren, veröffentlichen) |
| `members.manage`        | Mitglieder verwalten (erstellen, bearbeiten, löschen/Papierkorb, Fotos) |
| `members.functions`     | Funktionen & Abzeichen zuweisen (Kommando, Ausschuss, Dienstgrad, Verwendungsabzeichen) |
| `ranks.manage`          | Dienstgrade verwalten                                                   |
| `orgchart.manage`       | Organigramm verwalten (Namen den Positionen zuordnen)                   |
| `media.manage`          | Fahrzeug-, Wache- und Startseiten-Hero-Fotos austauschen                |
| `schutzbereich.manage`  | Schutzgebiet-Karte verwalten (Grenze auf der Karte bearbeiten)          |
| `haussammlung.manage`   | Haussammlungs-Popup verwalten (Zeitraum, QR-Code, Texte)                |
| `legal.manage`          | Rechtstexte bearbeiten (Datenschutzerklärung, Impressum, Allgemeine Hinweise) |
| `settings.manage`       | Website-Einstellungen (Zugangspasswort, Wartungsmodus, Berichtsjahre, Analytics, SMTP/DB/Discord/DeepL-Zugangsdaten, Seiten-Warnhinweis) |
| `users.manage`          | Benutzer & Rollen verwalten                                             |
| `logs.manage`           | Gesamtprotokoll & Einzel-Protokolle einsehen, IP-Adressen sperren       |
| `deploy.manage`         | Deployment: neuesten Stand von GitHub holen (`git pull`)                |

---

## Protokolle & Discord-Benachrichtigungen

Mehrere getrennte Protokolle, zusammengeführt im **Gesamtprotokoll** (`admin/gesamtprotokoll.php`, Berechtigung `logs.manage`) mit Filter- und Suchmöglichkeit, zusätzlich einzeln einsehbar:

1. **Aktivitäts-Log** (`admin/logs.php`): sicherheitsrelevante Admin-Aktionen – Login/Logout, Berichte/Mitglieder/Dienstgrade/Benutzer/Rollen anlegen/ändern/löschen, Organigramm-, Haussammlungs-, Rechtstext-, Schutzbereich- und Einstellungsänderungen, Einsatzbereitschafts-Wechsel, Deployments.
2. **Login-Log** (`admin/login-log.php`): jeder Anmeldeversuch am Admin-Bereich *und* an der Website-Zugangssperre, mit IP-Adresse, User-Agent und Erfolg/Fehlschlag. Direkter "IP sperren"-Button pro Eintrag.
3. **Rate-Limit & IP-Sperren** (`admin/rate-limit.php`): Übersicht aller Rate-Limit-Überschreitungen sowie manuell/automatisch gesperrter IP-Adressen, mit Formular zum manuellen Sperren (befristet oder dauerhaft) und Entsperren.
4. **Kontaktformular-/Spam-Log**: öffentliche Formular-Einsendungen sowie durch das Honeypot-Feld abgefangene Bot-Versuche werden mitprotokolliert.

Alle Protokolle können zusätzlich **live an Discord gemeldet** werden: in der `.env` bzw. unter Admin → Einstellungen je einen Webhook für Aktivität, Login und Rate-Limit eintragen. Ohne eingetragenen Webhook passiert einfach nichts – ein Discord-Ausfall blockiert nie die Website selbst (kurzes Timeout, Fehler werden verschluckt).

---

## Mitglieder-System

### Grundgruppen & Multi-Funktions-System

Jedes Mitglied gehört zu genau **einer Grundgruppe** (Mannschaft, Ehrenmitglieder oder Jugend) und kann zusätzlich beliebig viele Funktionen zugewiesen bekommen (JSON-Spalte `functions`, z.B. gleichzeitig Kommando **und** Ausschuss mit je eigener Rolle). Die Foto-Kacheln unter "Über Uns" gruppieren Mitglieder automatisch nach Sektion; Kommando/Ausschuss zeigen beim Anklicken eine Detailkarte (Flip-Effekt), Mannschaft/Ehrenmitglieder bleiben statisch.

### Papierkorb (Soft-Delete)

"Löschen" verschiebt ein Mitglied nur in den **Papierkorb** (`deleted_at` wird gesetzt), statt es sofort endgültig zu entfernen. Im Papierkorb lässt sich das Mitglied jederzeit wiederherstellen; nach 24 Stunden wird es automatisch endgültig gelöscht (`purgeExpiredDeletedMembers()`, läuft passiv bei jedem Aufruf von Admin → Mitglieder). Löschen fragt über eine vollflächige Bestätigung nach, nicht über den nativen Browser-`confirm()`.

---

## Organigramm & Dienstgrade

Das Organigramm (`pages/ueber-uns.php`) ist in drei Sektionen gegliedert – **Kommando**, **Gruppen**, **Beauftragte**. Die Struktur ist fest im Code hinterlegt, nur die **Namen** je Position werden über **Admin → Organigramm** gepflegt. Ist eine Position frei, erscheint automatisch "derzeit nicht besetzt"; wird im Organigramm ein Name gefunden, der auch ein Mitglied mit hinterlegtem Dienstgrad ist, erscheint automatisch das passende Rang-Abzeichen.

30+ offizielle Dienstgrade des Tiroler Landesfeuerwehrverbands (`config/ranks.php`), jeweils mit PNG-Abzeichen (`assets/images/ranks/`), verwaltet über **Admin → Dienstgrade**.

---

## Berichte-System

Einsatzberichte (`pages/berichte.php`) unterstützen:

- **Mehrfachkategorien**: ein Bericht kann gleichzeitig mehreren Hauptkategorien angehören (z.B. "Einsatz" **und** "Übung"), jede mit eigenen Unterkategorien (Brand/Technisch/ABC, bei Einsätzen zusätzlich Unterstützung – max. 2 Unterkategorien bei Einsatz, max. 3 bei Übung), bei der Anzeige nach Kategorie gruppiert dargestellt ("Einsatz: Brand" neben "Übung: Technisch").
- **Interne Einsatznummer**: jeder Einsatzbericht bekommt automatisch eine fortlaufende, jahresbezogene Nummer (z.B. "E07/26" = 7. Einsatz des Jahres 2026), vergeben beim Speichern. Standardmäßig nur intern sichtbar, über **Admin → Einstellungen** öffentlich zuschaltbar.
- **Titelbild-Auswahl**, mehrere Bilder pro Bericht, optionaler Link zu Instagram-Beitrag oder externer Website.
- **Manuelle Archivierung** (statt rein zeitbasiert) mit vollflächiger Bestätigung; archivierte Berichte bleiben unter "Alle" und den Kategorie-Filtern weiterhin sichtbar.
- **Nachträglich-nicht-veröffentlicht-Flags**: separat einstellbar, ob ein unveröffentlichter Bericht in der Statistik bzw. unter "Aktuelle Alarmierungen" mitgezählt/angezeigt wird.

**Bilder-Lightbox**: Alle inhaltlichen Fotos der Website lassen sich anklicken und vergrößert ansehen, bei mehreren Bildern mit Pfeilen zum Durchblättern.

---

## Startseite: Hintergrund, Statistik, Info-Leiste

- **Hero-Hintergrund** (`config/hero.php`, **Admin → Startseiten-Hintergrund**): entweder ein stehendes Bild oder eine Diashow aus mehreren Bildern mit einstellbarem Wechselintervall.
- **Statistik-Kacheln**: zählen die veröffentlichten Einsätze des "Aktuellen Kalenderjahres" (einstellbar unter Admin → Einstellungen → Berichtsjahre, nicht einfach die Serveruhr), aufgeschlüsselt nach Brand-/Technischen/ABC-Einsätzen, mit Zähl-hoch-Animation beim Sichtbarwerden.
- **Rote Info-Leiste**: 24/7/365-Hinweis sowie Notruf-Nummer, ebenfalls mit Count-up-Animation.

---

## Einsatzbereitschaft (Bereit / Nicht bereit / Übung)

Status der Wehr mit drei Zuständen – **Einsatzbereit** (grün), **Nicht einsatzbereit** (rot) und **Übung** (orange) – sichtbar auf der Startseite. Umschaltbar:

- manuell über **Admin → Einsatzbereitschaft**,
- automatisch über einen Webhook (`api-einsatzbereitschaft.php`) mit geheimem Token, z.B. per SMS-Gateway oder iOS-Kurzbefehl ausgelöst.

Jeder Wechsel wird protokolliert (Zeitpunkt + Auslöser: manuell/API).

---

## Mehrsprachigkeit (Deutsch/Englisch) & automatische Übersetzung

- **Sprachumschaltung** (`config/i18n.php`): Deutsch ist immer Standard; über den Umschalter in der Navigation wird ein Cookie gesetzt, danach bleibt Englisch bestehen, bis zurückgeschaltet wird. Betrifft nur die öffentliche Website, nicht Admin-Bereich/Zugangssperre. Alle UI-Texte liegen in `lang/de.php`/`lang/en.php`, abgerufen über die `t()`-Hilfsfunktion.
- **Automatische Übersetzung** (`config/translate.php`, **Admin → Einstellungen**): bei konfiguriertem DeepL-API-Key werden Berichte und Mitgliederprofile beim Speichern automatisch ins Englische mitübersetzt. Ein Nachübersetzen-Button übersetzt bestehende, vor der Einrichtung angelegte Inhalte in Batches nach, inkl. Anzeige des monatlichen DeepL-Kontingents.

---

## Wetterwarnungen & Seiten-Warnhinweis

Zwei unabhängige Systeme im Footer bzw. seitenweit:

- **Footer-Wetterübersicht**: kleine Liste der aktuell aktiven GeoSphere-Austria-Warnungen für Innsbruck-Stadt und Innsbruck-Land (`warnungen-tirol.php`, server-seitiger Proxy mit Caching auf die öffentliche GeoSphere-Warn-API), daneben ein kompaktes, ebenfalls server-seitig gecachtes Kartenbild von UWZ.AT für ganz Österreich (`uwz-map.php`).
- **Seiten-Warnhinweis** (`config/site-alert.php`, **Admin → Einstellungen**): ein Vollbild-Overlay über die gesamte Website, das bestätigt werden muss und danach als kleine, bleibende Markierung links am Bildschirmrand sichtbar bleibt. Auslöser, in dieser Reihenfolge: Admin-Testmodus (Beispielinhalt) → manuell erzeugter Hinweis (frei wählbarer Typ Wetter/Verkehr/Hochwasser/Sicherheit/Sonstiges, eigener Ort, zu meidender Bereich, Text, Farbe) → automatisch bei einer echten aktiven GeoSphere-Warnung für Innsbruck-Stadt/-Land.

---

## Schutzbereich-Karte

Interaktive Karte des Einsatzgebiets (`pages/schutzbereich.php`, Leaflet/OpenStreetMap) mit einem Adress-Check ("liegt diese Adresse im Schutzbereich?", `schutzgebiet-check.php` als Nominatim-Proxy mit Caching) und einem eigenen Kachel-Proxy (`tile-proxy.php`). Die Gebietsgrenze selbst wird über **Admin → Schutzbereich** als Polygon direkt auf der Karte bearbeitet.

---

## Haussammlung

Eigene Seite (`pages/haussammlung.php`) samt Hinweis-Popup, das Besuchern einmal pro Besuch angezeigt wird (QR-Code, Kurztext), verwaltet über **Admin → Haussammlung** (Titel, Texte, QR-Code-Bild, Aktivierung).

---

## Fahrzeug- & Wache-Fotos

Fotos der Fahrzeuge (Fuhrpark, eigene Seite getrennt vom Gerätehaus) und der Wache-Bereiche lassen sich über **Admin → Fahrzeug- & Wache-Fotos** austauschen, ganz ohne Code-Änderung – jedes Fahrzeug hat eine Foto-Galerie (erstes Foto = Hauptbild), die Wache-Bereiche feste, einzeln austauschbare Slots. Alle Bilder sind zusätzlich über die Bilder-Lightbox anklickbar.

---

## Rechtstexte (Impressum/Datenschutz) im Admin-Bereich

Datenschutzerklärung, Impressum und "Allgemeine Hinweise" lassen sich über **Admin → Rechtstexte** (`admin/legal.php`) direkt bearbeiten, ohne Code-Änderung; Standardtexte liegen in `config/legal-defaults.php` und werden bei Bedarf dort ergänzt (z.B. bei neuen Datenabruf-/Cookie-Funktionen wie den Wetterwarnungen).

---

## Kontaktformulare

Mehrere getrennte Formulare, die alle denselben absturzsicheren Mailversand (`config/mail.php`) nutzen:

- **Hauptformular** (`pages/kontakt.php`): allgemeine Anfragen, sendet an `SMTP_TO_EMAIL`.
- **Jugend-Formular** (`pages/jugend.php`): eigenes, kompaktes Formular speziell für Interessent:innen der Jugendfeuerwehr.
- **Mitmachen-Formular** (`pages/mitmachen.php`): Interessentenanfragen für den Beitritt zur Feuerwehr.

Alle sind CSRF-geschützt, haben ein Honeypot-Feld gegen Bots und sind rate-limitiert. Ist noch kein SMTP hinterlegt, wird automatisch auf PHP's `mail()` zurückgegriffen; ist auch das serverseitig deaktiviert, erscheint eine verständliche Fehlermeldung statt eines Absturzes.

---

## Google Analytics

Optional über **Admin → Einstellungen** einrichtbar (GA4-Measurement-ID, Format `G-XXXXXXXXXX`). Wird öffentlich erst nach Zustimmung im Cookie-Consent-Banner geladen (`anonymize_ip: true`); ohne Einwilligung wird nichts nachgeladen. Die Einwilligung lässt sich auf der Datenschutzseite jederzeit widerrufen.

---

## Deployment

**Admin → Deploy** (`admin/deploy.php`, Berechtigung `deploy.manage`) führt auf dem Server einen `git pull` aus, um den neuesten Stand von GitHub zu holen – direkt im Admin-Bereich, ohne SSH/FTP. Datenbank-Migrationen laufen danach beim nächsten Seitenaufruf automatisch mit.

---

## Seiten

| Route              | Datei                       | Beschreibung                                                       |
|--------------------|------------------------------|---------------------------------------------------------------------|
| `home`             | `pages/home.php`             | Startseite: Hero-Diashow, Statistik, Info-Leiste, aktuelle Berichte, Alarmierungen, Social Media |
| `mannschaft`       | `pages/mannschaft.php`       | Mannschaft (Mitglieder-Kacheln)                                      |
| `ausschuss`        | `pages/ausschuss.php`        | Ausschuss (mit Gruppenfoto, Flip-Detailkarten)                       |
| `geschichte`       | `pages/geschichte.php`       | Vereinsgeschichte                                                     |
| `schutzbereich`    | `pages/schutzbereich.php`    | Interaktive Schutzbereich-Karte mit Adress-Check                     |
| `fuhrpark`         | `pages/fuhrpark.php`         | Fahrzeuge (austauschbare Fotos)                                      |
| `geraetehaus`      | `pages/geraetehaus.php`      | Gerätehaus/Wache                                                      |
| `feuer` / `technik` / `gefahrgut` | `pages/*.php`  | Einsatzgebiete mit Beispielfotos                                      |
| `jugend`           | `pages/jugend.php`           | Jugendfeuerwehr mit eigenem Kontaktformular                          |
| `berichte`         | `pages/berichte.php`         | Einsatzberichte mit Mehrfachkategorie-Filter und Bildergalerien      |
| `termine`          | `pages/termine.php`          | Kommende Termine und Veranstaltungen                                 |
| `alarmierungen`    | `pages/alarmierungen.php`    | Eigene Einsatzstatistik + Live-Übersicht Landes-Feuerwehrverband Tirol |
| `sicherheitstipps` | `pages/sicherheitstipps.php` | Sicherheitstipps für die Bevölkerung                                  |
| `mitmachen`        | `pages/mitmachen.php`        | Interessentenformular für den Beitritt                               |
| `haussammlung`     | `pages/haussammlung.php`     | Infoseite zur jährlichen Haussammlung                                |
| `kontakt`          | `pages/kontakt.php`          | Kontaktformular, Kontaktdaten, Impressum, Allgemeine Hinweise         |
| `datenschutz`      | `pages/datenschutz.php`      | Datenschutzerklärung (DSGVO-konform, Österreich)                     |

---

## Datenbank

### Wichtige Tabellen

| Tabelle                | Beschreibung                                                    |
|-------------------------|------------------------------------------------------------------|
| `users`                | Admin-Benutzer (username, password-hash, name, Rolle/permissions, 2FA-Secret) |
| `roles`                | Admin-Rollen mit je einer Liste von Berechtigungen                |
| `members`              | Mitglieder mit Rang, Funktionen (JSON), Foto, Details, `deleted_at` (Papierkorb) |
| `reports` / `report_images` | Einsatzberichte mit Mehrfachkategorien, Einsatznummer und Bildern (1:n) |
| `org_chart_positions`  | Organigramm-Positionen (Schlüssel, Label, aktueller Name)        |
| `ranks`                | Dienstgrade mit Abzeichen-Pfad                                    |
| `vehicle_photos`       | Fahrzeug-Foto-Galerien (vehicle_key, Pfad, Sortierung)            |
| `media_slots`          | Feste Einzelbild-Slots (Wache-/Hero-Fotos)                        |
| `site_settings`        | Key-Value-Einstellungen (Zugangspasswort-Hash, Wartungsmodus, Statistik-Zeitraum, GA-ID, Einsatzbereitschaft, Seiten-Warnhinweis, Haussammlung, ...) |
| `activity_log`         | Aktivitäts-Log (Admin-Aktionen)                                   |
| `login_log`            | Anmeldeversuche (Admin + Zugangssperre)                           |
| `blocked_ips`          | Gesperrte IP-Adressen (manuell oder automatisch)                  |
| `rate_limit_hits` / `rate_limit_events` | Rate-Limit-Zählung bzw. protokollierte Überschreitungen |
| `password_resets`      | Gehashte Reset-Tokens für "Passwort vergessen"                    |
| `migrations`           | Merkt sich, welche Migrationen bereits gelaufen sind              |

---

## Bilder & Assets

- **Mitglieder-Fotos**: `uploads/members/`
- **Berichts-Bilder**: `uploads/reports/`
- **Fahrzeug-/Wache-/Hero-Fotos**: `assets/images/vehicles/` (Standardfotos) + `uploads/vehicles/`, `uploads/wache/`, `uploads/hero/` (über Admin ausgetauschte Fotos)
- **Dienstgrad-Abzeichen**: `assets/images/ranks/`
- **Sonstiges**: Geschichte, Einsatzgebiete (Feuer/Technik/Gefahrgut), Schutzgebietskarte, Ausschuss-Gruppenfoto, Haussammlungs-QR-Code

---

## Lizenz & Kontakt

Dieses Projekt ist für die Freiwillige Feuerwehr Reichenau, Innsbruck Stadt, Tirol.
Bilder und Inhalte unterliegen dem Copyright der jeweiligen Rechteinhaber.

**Freiwillige Feuerwehr Reichenau**
Rossaugasse 4, A-6020 Innsbruck
[reichenau@feuerwehr.tirol](mailto:reichenau@feuerwehr.tirol)
