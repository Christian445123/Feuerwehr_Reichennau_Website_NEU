<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/mail.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['kontakt_csrf'])) {
    $_SESSION['kontakt_csrf'] = bin2hex(random_bytes(32));
}

$formData = ['anrede' => '', 'vorname' => '', 'nachname' => '', 'strasse' => '', 'ort' => '', 'email' => '', 'telefon' => '', 'nachricht' => ''];
$formErrors = [];
$formSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kontakt_submit'])) {
    foreach ($formData as $key => $_) {
        $formData[$key] = trim($_POST[$key] ?? '');
    }

    $csrfOk = hash_equals($_SESSION['kontakt_csrf'], $_POST['csrf_token'] ?? '');
    $honeypotOk = ($_POST['website'] ?? '') === ''; // Anti-Spam-Feld, für Menschen unsichtbar

    if (!$csrfOk || !$honeypotOk) {
        $formErrors[] = 'Ungültige Anfrage. Bitte lade die Seite neu und versuche es erneut.';
    }
    if ($formData['vorname'] === '' || $formData['nachname'] === '') {
        $formErrors[] = 'Bitte gib Vor- und Nachname an.';
    }
    if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = 'Bitte gib eine gültige E-Mail-Adresse an.';
    }
    if ($formData['nachricht'] === '') {
        $formErrors[] = 'Bitte gib eine Nachricht ein.';
    }

    if (empty($formErrors)) {
        $subject = 'Kontaktanfrage von ' . $formData['vorname'] . ' ' . $formData['nachname'];
        $bodyLines = [
            'Neue Kontaktanfrage über die Website der FF Reichenau',
            '',
            'Anrede: ' . ($formData['anrede'] ?: '-'),
            'Name: ' . $formData['vorname'] . ' ' . $formData['nachname'],
            'Strasse / Nr.: ' . ($formData['strasse'] ?: '-'),
            'Ort: ' . ($formData['ort'] ?: '-'),
            'E-Mail: ' . $formData['email'],
            'Telefon: ' . ($formData['telefon'] ?: '-'),
            '',
            'Nachricht:',
            $formData['nachricht'],
        ];
        $mailError = null;
        $sent = sendContactMail($subject, implode("\n", $bodyLines), $formData['email'], $formData['vorname'] . ' ' . $formData['nachname'], $mailError);

        if ($sent) {
            unset($_SESSION['kontakt_csrf']);
            header('Location: index.php?page=kontakt&sent=1#kontaktformular');
            exit;
        }
        $formErrors[] = 'Die Nachricht konnte leider nicht versendet werden. Bitte versuche es später erneut oder schreibe direkt an reichenau@feuerwehr.tirol.';
    }
}

$formSent = isset($_GET['sent']);
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Kontakt &amp; Impressum</h1>
            <p class="page-subtitle">Wir freuen uns auf Ihre Nachricht</p>
        </div>
    </section>

    <section class="section" id="kontaktformular">
        <div class="container">
            <div class="content-card kontakt-form-card">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-paper-plane"></i></div>
                    <h2>Kontaktanfrage</h2>
                </div>
                <div class="content-card-body">
                    <?php if ($formSent): ?>
                        <div class="form-alert form-alert-success">
                            <i class="fas fa-check-circle"></i> Vielen Dank für deine Nachricht! Wir melden uns so schnell wie möglich bei dir.
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($formErrors)): ?>
                        <div class="form-alert form-alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <ul>
                                <?php foreach ($formErrors as $err): ?>
                                    <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=kontakt#kontaktformular" class="public-form">
                        <input type="hidden" name="kontakt_submit" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['kontakt_csrf']); ?>">
                        <input type="text" name="website" value="" class="form-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div class="form-group">
                            <label for="anrede">Anrede</label>
                            <select id="anrede" name="anrede">
                                <option value="" <?php echo $formData['anrede'] === '' ? 'selected' : ''; ?>>Bitte auswählen</option>
                                <option value="Frau" <?php echo $formData['anrede'] === 'Frau' ? 'selected' : ''; ?>>Frau</option>
                                <option value="Herr" <?php echo $formData['anrede'] === 'Herr' ? 'selected' : ''; ?>>Herr</option>
                                <option value="Divers" <?php echo $formData['anrede'] === 'Divers' ? 'selected' : ''; ?>>Divers</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="vorname">Vorname <span class="req">(*)</span></label>
                                <input type="text" id="vorname" name="vorname" value="<?php echo htmlspecialchars($formData['vorname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="nachname">Nachname <span class="req">(*)</span></label>
                                <input type="text" id="nachname" name="nachname" value="<?php echo htmlspecialchars($formData['nachname']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="strasse">Strasse / Nr.</label>
                                <input type="text" id="strasse" name="strasse" value="<?php echo htmlspecialchars($formData['strasse']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="ort">Ort</label>
                                <input type="text" id="ort" name="ort" value="<?php echo htmlspecialchars($formData['ort']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">E-Mail <span class="req">(*)</span></label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="telefon">Telefon allgemein</label>
                                <input type="text" id="telefon" name="telefon" value="<?php echo htmlspecialchars($formData['telefon']); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nachricht">Nachricht <span class="req">(*)</span></label>
                            <textarea id="nachricht" name="nachricht" rows="6" required><?php echo htmlspecialchars($formData['nachricht']); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Anfrage senden
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">

            <div class="kontakt-grid">

                <!-- Kontaktinformationen -->
                <div class="kontakt-info">
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon"><i class="fas fa-address-card"></i></div>
                            <h2>Kontaktdaten</h2>
                        </div>
                        <div class="content-card-body">
                            <div class="kontakt-list">
                                <div class="kontakt-item">
                                    <i class="fas fa-building"></i>
                                    <div>
                                        <strong>Freiwillige Feuerwehr Reichenau</strong><br>
                                        Innsbruck Stadt
                                    </div>
                                </div>
                                <div class="kontakt-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        Rossaugasse 4<br>
                                        A-6020 Innsbruck
                                    </div>
                                </div>
                                <div class="kontakt-item">
                                    <i class="fas fa-phone"></i>
                                    <div><a href="tel:+43512345160">+43 (0)512 / 345160</a></div>
                                </div>
                                <div class="kontakt-item">
                                    <i class="fas fa-envelope"></i>
                                    <div><a href="mailto:reichenau@feuerwehr.tirol">reichenau@feuerwehr.tirol</a></div>
                                </div>
                                <div class="kontakt-item">
                                    <i class="fas fa-globe"></i>
                                    <div><a href="http://www.ffr.at">www.ffr.at</a></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mitmachen -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon"><i class="fas fa-hands-helping"></i></div>
                            <h2>Mitmachen</h2>
                        </div>
                        <div class="content-card-body">
                            <h4><i class="fas fa-child"></i> Jugendfeuerwehr</h4>
                            <p>Unsere Jugendfeuerwehrgruppe trifft sich jeden Montag (ausgenommen Ferien und Feiertage) um 19:00 Uhr in der Wache. Wenn auch du Lust hast unserer Jugendfeuerwehrgruppe beizutreten, komm einfach an einem Montag vorbei und schau es dir an.</p>
                            <p>Die Jugendfeuerwehr lernt nicht nur spielerisch das Feuerwehrwesen kennen, sondern macht auch Übungen und theoretische Ausbildungen. Einmal im Jahr kann man dann das Erlernte beim Wissenstest unter Beweis stellen und erhält dafür eine Auszeichnung.</p>
                            <p><strong>Alter:</strong> 11 bis 15 Jahre</p>

                            <hr>

                            <h4><i class="fas fa-hard-hat"></i> Aktive Einsatzmannschaft</h4>
                            <p>Ab dem 15. Lebensjahr kann man der aktiven Einsatzmannschaft beitreten. Die aktive Mannschaft trifft sich jeden Freitag (ausgenommen Feiertage) um 19:15 Uhr zu Übungen und Schulungen im Feuerwehrhaus.</p>
                            <p>Wenn du etwas Sinnvolles für deine Mitmenschen machen möchtest, dann schau einfach vorbei und lerne uns kennen.</p>
                        </div>
                    </div>
                </div>

                <!-- Karte / Schutzgebiet -->
                <div class="kontakt-map">
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon"><i class="fas fa-map"></i></div>
                            <h2>Unser Schutzgebiet</h2>
                        </div>
                        <div class="content-card-body">
                            <div id="schutzgebietMap" class="schutzgebiet-map"></div>
                            <p class="schutzgebiet-map-hint">
                                Grenze näherungsweise nachgezeichnet anhand unserer amtlichen Schutzbereichs-Karte.
                                <a href="https://www.google.com/maps/search/?api=1&query=Freiwillige+Feuerwehr+Reichenau+Ro%C3%9Faugasse+4+Innsbruck" target="_blank" rel="noopener">Standort auf Google Maps öffnen <i class="fas fa-external-link-alt"></i></a>
                            </p>
                        </div>
                    </div>

                    <!-- Bankverbindung -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon"><i class="fas fa-university"></i></div>
                            <h2>Bankverbindung</h2>
                        </div>
                        <div class="content-card-body">
                            <p><strong>HYPO TIROL Bank</strong><br>
                            IBAN: AT33 5700 0002 3004 4140</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Impressum -->
            <div class="impressum-section">
                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-gavel"></i></div>
                        <h2>Impressum</h2>
                    </div>
                    <div class="content-card-body">
                        <div class="impressum-grid">
                            <div>
                                <h4>Herausgeberin</h4>
                                <p>Freiwillige Feuerwehr Reichenau / Innsbruck Stadt<br>Roßaugasse 4, A-6020 Innsbruck</p>
                                <p>Bei Fragen oder Anliegen wenden Sie sich bitte an <a href="mailto:reichenau@feuerwehr.tirol">reichenau@feuerwehr.tirol</a> – wir kümmern uns darum.</p>
                            </div>
                            <div>
                                <h4>Copyright</h4>
                                <p>Alle Rechte vorbehalten. Verantwortlich für Inhalt und Gestaltung der Internetpräsentation ist die Freiwillige Feuerwehr Reichenau.</p>
                                <p>Die Feuerwehr Reichenau erteilt die Erlaubnis, alle auf diesen Internetseiten erscheinenden Inhalte zur Informationsgewinnung des Anwenders zu nutzen und einen Ausdruck zu erstellen. Für eine gewerbliche Nutzung gilt dies nur nach einer vorher erteilten Zustimmung der Verantwortlichen.</p>
                            </div>
                        </div>
                        <p class="impressum-note">Layout und Gestaltung dieser Präsentationen sowie die enthaltenen Informationen sind gemäß dem Urheberrechtsgesetz geschützt. Alle Angaben erfolgen ohne Gewähr. Eine Haftung für Schäden, die sich aus der Verwendung der veröffentlichten Inhalte ergeben, ist ausgeschlossen.</p>
                        <p class="impressum-note">Diese Webseite wird veröffentlicht und gepflegt durch die Freiwillige Feuerwehr Reichenau / Innsbruck Stadt.</p>
                        <p class="impressum-note"><strong>Hinweis:</strong> Diese Website befindet sich derzeit im Aufbau und ist noch nicht die offizielle, öffentlich zugängliche Internetpräsenz der Freiwilligen Feuerwehr Reichenau. Der Zugriff ist übergangsweise passwortgeschützt.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('schutzgebietMap');
    if (!mapEl || typeof L === 'undefined') return;

    // Grenzverlauf: der Kern (Stadtteil Reichenau) stammt aus den echten,
    // amtlichen OpenStreetMap-Verwaltungsgrenzen (Nominatim, Relation 19639238)
    // - keine Schätzung. Der Bereich reicht laut FF Reichenau aber über den
    // Stadtteil Reichenau hinaus auch über Teile von Pradl/Pradler Saggen und
    // das Gewerbegebiet Rossau (siehe "Über uns" -> Geschichte); dieser
    // zusätzliche westliche/südliche Bereich (Punkte ab "Erweiterung") ist
    // anhand der amtlichen Schutzbereichs-Karte nachgezeichnet und mit realen
    // Orientierungspunkten (Pradler Platz, Hauptbahnhof) abgeglichen, aber
    // nicht vermessungsgenau - bei Bedarf hier direkt anpassen.
    var schutzgebiet = [
        // -- Stadtteil Reichenau (offizielle Verwaltungsgrenze, OSM) --
        [47.2713, 11.4144],
        [47.2701, 11.4151],
        [47.2690, 11.4154],
        [47.2682, 11.4174],
        [47.2664, 11.4213],
        [47.2657, 11.4229],
        [47.2650, 11.4238],
        [47.2688, 11.4263],
        [47.2717, 11.4273],
        [47.2727, 11.4280],
        // -- Erweiterung Richtung Osten, damit das Feuerwehrhaus (Rossau) mit
        //    eingeschlossen ist --
        [47.2732, 11.4300],
        [47.2722, 11.4322],
        [47.2712, 11.4318],
        [47.2705, 11.4296],
        // -- zurück zur Reichenau-Grenze (Nordbereich entlang des Inn) --
        [47.2741, 11.4287],
        [47.2772, 11.4222],
        [47.2783, 11.4181],
        [47.2775, 11.4164],
        [47.2760, 11.4145],
        [47.2750, 11.4153],
        [47.2739, 11.4159],
        [47.2730, 11.4159],
        [47.2720, 11.4153],
        [47.2715, 11.4149],
        // -- Erweiterung Richtung Westen (Teile Pradl/Pradler Saggen/Rossau
        //    Richtung Hauptbahnhof) - als geschlossener Keil ohne
        //    Selbstüberschneidung --
        [47.2695, 11.4095],
        [47.2650, 11.4040],
        [47.2612, 11.4025],
        [47.2655, 11.4015],
        [47.2700, 11.4070]
    ];

    var map = L.map('schutzgebietMap', { scrollWheelZoom: false });

    // Hinweis: der kostenlose tile.openstreetmap.org-Server ist laut Nutzungs-
    // richtlinie nur für kurze Tests gedacht und blockt echte Websites (403
    // "Access blocked"). CARTO stellt kostenlose Kacheln ausdrücklich auch für
    // den produktiven Einsatz bereit, daher hier stattdessen genutzt.
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
        subdomains: 'abcd',
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>-Mitwirkende &copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>'
    }).addTo(map);

    var polygon = L.polygon(schutzgebiet, {
        color: '#d5001c',
        weight: 3,
        fillColor: '#d5001c',
        fillOpacity: 0.12
    }).addTo(map);

    L.marker([47.2724477, 11.4309793]).addTo(map)
        .bindPopup('<strong>Feuerwehrhaus Reichenau</strong><br>Rossaugasse 4, 6020 Innsbruck');

    map.fitBounds(polygon.getBounds(), { padding: [20, 20] });
});
</script>
