<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/logging.php';

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

    if (!checkRateLimit(getDB(), 'contact_form', 5, 3600)) {
        $formErrors[] = 'Zu viele Anfragen. Bitte versuche es später erneut.';
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
        // Fängt auch fatale Fehler ab (z.B. wenn der Hoster mail() deaktiviert
        // hat), damit hier nie eine leere weiße Seite statt einer
        // verständlichen Fehlermeldung erscheint.
        try {
            $sent = sendContactMail($subject, implode("\n", $bodyLines), $formData['email'], $formData['vorname'] . ' ' . $formData['nachname'], $mailError);
        } catch (\Throwable $e) {
            error_log('Kontaktformular-Mailversand fehlgeschlagen: ' . $e->getMessage());
            $sent = false;
        }

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
                            <p>Unsere Jugendfeuerwehrgruppe trifft sich jeden Dienstag von 19 bis 21 Uhr in der Wache. Wenn auch du Lust hast unserer Jugendfeuerwehrgruppe beizutreten, komm einfach an einem Dienstag vorbei und schau es dir an.</p>
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
                            <img src="assets/images/schutzgebiet_karte.jpg" alt="Schutzbereich der FF Reichenau (amtliche Karte, Stadt Innsbruck)" class="content-image" data-lightbox-group="schutzgebiet-karte-kontakt">
                            <p class="schutzgebiet-map-hint">
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
                                <h4>Medieninhaberin &amp; Herausgeberin</h4>
                                <p>
                                    Freiwillige Feuerwehr Reichenau / Innsbruck Stadt<br>
                                    Roßaugasse 4, A-6020 Innsbruck
                                </p>
                                <p><strong>Vertreten durch:</strong><br>
                                    Kommandant: Helmut Plank<br>
                                    Schriftführerin: Nina Rippl<br>
                                    Kassier: Martin Rainalter
                                </p>
                                <p>
                                    Tel.: <a href="tel:+43512345160">+43 (0)512 / 345160</a><br>
                                    E-Mail: <a href="mailto:reichenau@feuerwehr.tirol">reichenau@feuerwehr.tirol</a>
                                </p>
                            </div>
                            <div>
                                <h4>Vereinszweck &amp; Blattlinie</h4>
                                <p>Die Freiwillige Feuerwehr Reichenau ist eine öffentliche Einrichtung der Gefahrenabwehr (Brand- und Katastrophenschutz) im Schutzgebiet Reichenau/Innsbruck. Diese Website dient der Information der Öffentlichkeit über Einsätze, Aktivitäten und Angebote der Feuerwehr sowie der Mitgliederwerbung.</p>
                            </div>
                        </div>
                        <hr>
                        <h4>Copyright</h4>
                        <p>Alle Rechte vorbehalten. Verantwortlich für Inhalt und Gestaltung der Internetpräsentation ist die Freiwillige Feuerwehr Reichenau. Layout und Gestaltung dieser Präsentation sowie die enthaltenen Informationen sind gemäß dem Urheberrechtsgesetz geschützt.</p>
                        <p>Die Feuerwehr Reichenau erteilt die Erlaubnis, alle auf diesen Internetseiten erscheinenden Inhalte zur Informationsgewinnung des Anwenders zu nutzen und einen Ausdruck zu erstellen. Für eine gewerbliche Nutzung gilt dies nur nach einer vorher erteilten Zustimmung der Verantwortlichen.</p>
                    </div>
                </div>
            </div>

            <!-- Allgemeine Hinweise -->
            <div class="impressum-section">
                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-circle-info"></i></div>
                        <h2>Allgemeine Hinweise</h2>
                    </div>
                    <div class="content-card-body">
                        <h4><i class="fas fa-phone-volume"></i> Im Notfall</h4>
                        <p>Diese Website, das Kontaktformular und unsere E-Mail-Adresse werden <strong>nicht laufend rund um die Uhr überwacht</strong> und sind daher für echte Notfälle ungeeignet. Wählen Sie im Ernstfall immer den <strong>Euronotruf 112</strong> oder die <strong>Feuerwehr 122</strong>.</p>

                        <h4><i class="fas fa-triangle-exclamation"></i> Keine Gewähr für Inhalte</h4>
                        <p>Wir erstellen die Inhalte dieser Website mit größtmöglicher Sorgfalt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte – insbesondere von Terminen, Einsatzberichten und Statistiken – können wir dennoch keine Gewähr übernehmen. Änderungen ohne vorherige Ankündigung sind möglich.</p>

                        <h4><i class="fas fa-link"></i> Eingebettete Inhalte Dritter</h4>
                        <p>Auf einzelnen Seiten binden wir Inhalte externer Anbieter ein, z.&nbsp;B. die Alarmierungsübersicht des Landes-Feuerwehrverbands Tirol unter Service&nbsp;&rarr;&nbsp;Alarmierungen. Für diese Inhalte ist der jeweilige externe Anbieter verantwortlich. Details dazu finden Sie in unserer <a href="index.php?page=datenschutz">Datenschutzerklärung</a>.</p>

                        <h4><i class="fas fa-clock-rotate-left"></i> Status dieser Website</h4>
                        <p>Diese Website befindet sich derzeit im Aufbau und ist noch nicht die offizielle, öffentlich zugängliche Internetpräsenz der Freiwilligen Feuerwehr Reichenau. Der Zugriff ist übergangsweise passwortgeschützt.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>
