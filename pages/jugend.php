<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/logging.php';
$db = getDB();

// Jugendbetreuer:in dynamisch aus der Mitgliederliste holen, damit sich die
// Kontakt-Karte automatisch aktualisiert, wenn sich die Funktion mal ändert.
$jbStmt = $db->query("SELECT * FROM members WHERE active = 1 AND functions LIKE '%Jugendbetreuer%' LIMIT 1");
$jugendbetreuerin = $jbStmt->fetch();

// Eigenes kleines Kontaktformular für die Jugendfeuerwehr (wie im Vorbild),
// nutzt denselben Mailversand wie das große Kontaktformular.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['jugend_csrf'])) {
    $_SESSION['jugend_csrf'] = bin2hex(random_bytes(32));
}

$jugendFormData = ['vorname' => '', 'nachname' => '', 'email' => '', 'nachricht' => ''];
$jugendFormErrors = [];
$jugendFormSent = isset($_GET['jugend_sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jugend_submit'])) {
    foreach ($jugendFormData as $key => $_) {
        $jugendFormData[$key] = trim($_POST[$key] ?? '');
    }

    if (!checkRateLimit($db, 'jugend_contact_form', 5, 3600)) {
        $jugendFormErrors[] = 'Zu viele Anfragen. Bitte versuche es später erneut.';
    }

    $csrfOk = hash_equals($_SESSION['jugend_csrf'], $_POST['csrf_token'] ?? '');
    $honeypotOk = ($_POST['website'] ?? '') === '';

    if (!$csrfOk || !$honeypotOk) {
        $jugendFormErrors[] = 'Ungültige Anfrage. Bitte lade die Seite neu und versuche es erneut.';
    }
    if ($jugendFormData['vorname'] === '' || $jugendFormData['nachname'] === '') {
        $jugendFormErrors[] = 'Bitte gib Vor- und Nachname an.';
    }
    if ($jugendFormData['email'] === '' || !filter_var($jugendFormData['email'], FILTER_VALIDATE_EMAIL)) {
        $jugendFormErrors[] = 'Bitte gib eine gültige E-Mail-Adresse an.';
    }
    if ($jugendFormData['nachricht'] === '') {
        $jugendFormErrors[] = 'Bitte gib eine Nachricht ein.';
    }

    if (empty($jugendFormErrors)) {
        $subject = 'Jugendfeuerwehr-Anfrage von ' . $jugendFormData['vorname'] . ' ' . $jugendFormData['nachname'];
        $bodyLines = [
            'Neue Anfrage über das Jugendfeuerwehr-Formular der Website',
            '',
            'Name: ' . $jugendFormData['vorname'] . ' ' . $jugendFormData['nachname'],
            'E-Mail: ' . $jugendFormData['email'],
            '',
            'Nachricht:',
            $jugendFormData['nachricht'],
        ];
        $mailError = null;
        try {
            $sent = sendContactMail($subject, implode("\n", $bodyLines), $jugendFormData['email'], $jugendFormData['vorname'] . ' ' . $jugendFormData['nachname'], $mailError);
        } catch (\Throwable $e) {
            error_log('Jugend-Kontaktformular fehlgeschlagen: ' . $e->getMessage());
            $sent = false;
        }

        if ($sent) {
            unset($_SESSION['jugend_csrf']);
            header('Location: index.php?page=jugend&jugend_sent=1#machmit');
            exit;
        }
        $jugendFormErrors[] = 'Die Nachricht konnte leider nicht versendet werden. Bitte versuche es später erneut oder schreibe direkt an reichenau@feuerwehr.tirol.';
    }
}
?>
    <!-- Hero: großes Foto mit Titel, wie im Vorbild neo.ffr.at/jugend/ -->
    <section class="jugend-hero-banner" style="background-image: linear-gradient(rgba(15,15,15,0.6), rgba(15,15,15,0.6)), url('assets/images/jugend_gruppe.jpg');">
        <div class="container">
            <h1>Gemeinsam Stark<br>Für Morgen</h1>
        </div>
    </section>

    <section class="jugend-yellow-band" id="aktivitaeten">
        <div class="container jugend-narrow">
            <h2 class="jugend-h2">Lust auf Action, viel Spaß und neue Freunde?</h2>
            <h3 class="jugend-h3">In der Feuerwehrjugend bist du mittendrin statt nur dabei – von 11 bis 15 Jahren!</h3>
            <p>Bei der Feuerwehrjugend Reichenau lernst du spielerisch alles rund um das Feuerwehrwesen. Während spannenden Übungen, Bewerben, Lagern und Ausflügen erwartet dich Abenteuer und jede Menge Spaß.</p>
            <p><strong>Dabei wirst du auf das vorbereitet, was zählt:</strong></p>
            <p class="jugend-statement">Verantwortung übernehmen<br>und helfen können.</p>
        </div>
    </section>

    <!-- Möchtest du Teil unseres Teams werden? -->
    <section class="jugend-red-band">
        <div class="container">
            <h2>Möchtest du Teil unseres Teams werden?</h2>
            <h3>Dann komm einfach vorbei und lerne uns kennen!</h3>
        </div>
    </section>

    <section class="section">
        <div class="container jugend-narrow">
            <div class="content-card">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-list-check"></i></div>
                    <h2>Das erwartet dich</h2>
                </div>
                <div class="content-card-body">
                    <ul class="jugend-checklist">
                        <li><i class="fas fa-check"></i> Treffen am Dienstag von 19 bis 21 Uhr in der Feuerwache Reichenau (Rossaugasse 4) mit Übungen, Schulungen &amp; Teamspielen</li>
                        <li><i class="fas fa-check"></i> Bewerbe &amp; Wissenstests (Bronze, Silber, Gold)</li>
                        <li><i class="fas fa-check"></i> Erste-Hilfe-Kurse &amp; sportliche Challenges</li>
                        <li><i class="fas fa-check"></i> Unvergessliche Erlebnisse bei Ausflügen &amp; Jugendlagern</li>
                        <li><i class="fas fa-check"></i> Ein starkes Team mit Respekt, Zusammenhalt und viel Spaß!</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Infos für Eltern -->
    <section class="jugend-yellow-band">
        <div class="container jugend-narrow">
            <h2 class="jugend-h2">Infos für Eltern</h2>
            <p><strong>Die Mitgliedschaft in der Feuerwehrjugend ist kostenlos.</strong> Uniform, Ausrüstung und Material werden von uns gestellt.</p>
            <p>Die Jugendlichen sind fester Bestandteil der Freiwilligen Feuerwehr Reichenau und werden von speziell geschulten Jugendbetreuer:innen begleitet, die mit viel Herzblut für Ausbildung, Organisation und Unterstützung sorgen.</p>
            <p><strong>Schule hat Vorrang:</strong> Wir achten darauf, dass sich die Teilnahme problemlos mit schulischen Verpflichtungen vereinbaren lässt.</p>
            <p><strong>Mehr als nur Feuerwehr:</strong> Neben Technik und Wissen fördern wir Teamgeist, Respekt, Problemlösungsfähigkeit und Zivilcourage – Werte, die ein Leben lang begleiten.</p>
        </div>
    </section>

    <!-- Kontakt -->
    <section class="section" id="machmit">
        <div class="container">
            <div class="jugend-contact-grid">
                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-envelope-open-text"></i></div>
                        <h2>Kontakt</h2>
                    </div>
                    <div class="content-card-body">
                        <p>
                            Du hast Fragen oder willst gleich loslegen?
                            <?php if ($jugendbetreuerin): ?>
                                Dann melde dich bei unserer Jugendbetreuerin über das Kontaktformular oder per E-Mail:
                            <?php else: ?>
                                Dann schick uns einfach eine Nachricht über das Formular oder per E-Mail:
                            <?php endif; ?>
                        </p>

                        <?php if ($jugendFormSent): ?>
                            <div class="form-alert form-alert-success">
                                <p>Vielen Dank für deine Nachricht! Wir melden uns bei dir.</p>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($jugendFormErrors)): ?>
                                <div class="form-alert form-alert-error">
                                    <?php foreach ($jugendFormErrors as $err): ?>
                                        <p><?php echo htmlspecialchars($err); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" class="public-form">
                                <input type="hidden" name="jugend_submit" value="1">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['jugend_csrf']); ?>">
                                <input type="text" name="website" value="" class="form-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="jf_vorname">Vorname <span class="req">(*)</span></label>
                                        <input type="text" id="jf_vorname" name="vorname" required value="<?php echo htmlspecialchars($jugendFormData['vorname']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="jf_nachname">Nachname <span class="req">(*)</span></label>
                                        <input type="text" id="jf_nachname" name="nachname" required value="<?php echo htmlspecialchars($jugendFormData['nachname']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="jf_email">E-Mail-Adresse <span class="req">(*)</span></label>
                                    <input type="email" id="jf_email" name="email" required value="<?php echo htmlspecialchars($jugendFormData['email']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="jf_nachricht">Deine Nachricht <span class="req">(*)</span></label>
                                    <textarea id="jf_nachricht" name="nachricht" rows="4" required><?php echo htmlspecialchars($jugendFormData['nachricht']); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Abschicken</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="jugend-betreuer-card">
                    <?php if ($jugendbetreuerin && $jugendbetreuerin['photo']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($jugendbetreuerin['photo']); ?>" alt="<?php echo htmlspecialchars($jugendbetreuerin['firstname'] . ' ' . $jugendbetreuerin['lastname']); ?>" class="jugend-contact-photo">
                    <?php else: ?>
                        <div class="cta-icon"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                    <p class="jugend-betreuer-label">Jugendbetreuerin</p>
                    <h4><?php echo $jugendbetreuerin ? htmlspecialchars($jugendbetreuerin['firstname'] . ' ' . $jugendbetreuerin['lastname']) : 'FF Reichenau'; ?></h4>
                    <a href="mailto:reichenau@feuerwehr.tirol"><i class="fas fa-envelope"></i> reichenau@feuerwehr.tirol</a>
                </div>
            </div>
        </div>
    </section>
