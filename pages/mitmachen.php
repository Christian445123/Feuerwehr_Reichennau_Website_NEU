<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/logging.php';
$db = getDB();

// Eigenes kleines Kontaktformular wie im Vorbild, nutzt denselben
// abgesicherten Mailversand wie die anderen Kontaktformulare.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['mitmachen_csrf'])) {
    $_SESSION['mitmachen_csrf'] = bin2hex(random_bytes(32));
}

$formData = ['vorname' => '', 'nachname' => '', 'email' => '', 'nachricht' => ''];
$formErrors = [];
$formSent = isset($_GET['sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mitmachen_submit'])) {
    foreach ($formData as $key => $_) {
        $formData[$key] = trim($_POST[$key] ?? '');
    }

    if (!checkRateLimit($db, 'mitmachen_contact_form', 5, 3600)) {
        $formErrors[] = 'Zu viele Anfragen. Bitte versuche es später erneut.';
    }

    $csrfOk = hash_equals($_SESSION['mitmachen_csrf'], $_POST['csrf_token'] ?? '');
    $honeypotOk = ($_POST['website'] ?? '') === '';

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
        $subject = 'Mitmachen-Anfrage von ' . $formData['vorname'] . ' ' . $formData['nachname'];
        $bodyLines = [
            'Neue Anfrage über das Mitmachen-Formular der Website',
            '',
            'Name: ' . $formData['vorname'] . ' ' . $formData['nachname'],
            'E-Mail: ' . $formData['email'],
            '',
            'Nachricht:',
            $formData['nachricht'],
        ];
        $mailError = null;
        try {
            $sent = sendContactMail($subject, implode("\n", $bodyLines), $formData['email'], $formData['vorname'] . ' ' . $formData['nachname'], $mailError);
        } catch (\Throwable $e) {
            error_log('Mitmachen-Kontaktformular fehlgeschlagen: ' . $e->getMessage());
            $sent = false;
        }

        if ($sent) {
            unset($_SESSION['mitmachen_csrf']);
            header('Location: index.php?page=mitmachen&sent=1#kontakt');
            exit;
        }
        $formErrors[] = 'Die Nachricht konnte leider nicht versendet werden. Bitte versuche es später erneut oder schreibe direkt an reichenau@feuerwehr.tirol.';
    }
}

$contactPersons = [];
foreach ([['Helmut', 'Plank', 'Kommandant'], ['David', 'Danner', 'Kommandant-Stv.']] as [$fn, $ln, $role]) {
    $stmt = $db->prepare("SELECT * FROM members WHERE firstname = ? AND lastname = ? LIMIT 1");
    $stmt->execute([$fn, $ln]);
    $m = $stmt->fetch();
    if ($m) {
        $contactPersons[] = ['member' => $m, 'role' => $role];
    }
}
?>
    <section class="mitmachen-hero">
        <div class="container">
            <h1>Werde Teil<br>unserer Mannschaft!</h1>
            <div class="mitmachen-questions">
                <p>Du bist zwischen<br>16 und 65 Jahre alt?</p>
                <p>Du möchtest dich ehrenamtlich engagieren und Menschen in Not helfen?</p>
                <p>Du suchst Zusammenhalt und Kameradschaft?</p>
            </div>
            <h2>Dann bist du bei der Freiwilligen Feuerwehr Reichenau genau richtig!</h2>
        </div>
    </section>

    <section class="section">
        <div class="container jugend-narrow">
            <h2 class="jugend-h2">Dein Einsatz zählt</h2>
            <p>Unsere Mannschaft besteht aus motivierten Frauen und Männern verschiedenster Berufe, Lebenswege und Altersgruppen.</p>
            <p>Was uns verbindet? Der Wille, zu helfen, wenn andere Hilfe brauchen – sei es bei Bränden, Naturkatastrophen, technischen Einsätzen oder Gefahrguteinsätzen.</p>
        </div>

        <div class="container">
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-hand-holding-heart"></i></div>
                    <h3>Sinnvolles Engagement</h3>
                    <p>Du hilfst Menschen in deiner Umgebung und leistest einen wichtigen Beitrag zur Sicherheit unserer Stadt.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-people-group"></i></div>
                    <h3>Teamspirit &amp; Kameradschaft</h3>
                    <p>Du wirst Teil einer starken Gemeinschaft.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-book-open"></i></div>
                    <h3>Wissen fürs Leben</h3>
                    <p>Technisches Know-how, Erste Hilfe, Funk &amp; Einsatzführung.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-dumbbell"></i></div>
                    <h3>Körperliche &amp; geistige Herausforderung</h3>
                    <p>Für alle, die sich weiterentwickeln wollen.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="einsatzgebiet-gallery">
                <div class="einsatzgebiet-gallery-item">
                    <img src="assets/images/mitmachen/mitmachen_01.jpg" alt="Mannschaft im Einsatz" data-lightbox-group="mitmachen" loading="lazy">
                </div>
                <div class="einsatzgebiet-gallery-item">
                    <img src="assets/images/mitmachen/mitmachen_02.jpg" alt="Mannschaft im Einsatz" data-lightbox-group="mitmachen" loading="lazy">
                </div>
                <div class="einsatzgebiet-gallery-item">
                    <img src="assets/images/mitmachen/mitmachen_03.jpg" alt="Stationsbetrieb Regelangriff" data-lightbox-group="mitmachen" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <section class="section section-dark">
        <div class="container jugend-narrow">
            <div class="section-header">
                <h2 class="section-title">Warum du dabei sein solltest:</h2>
            </div>
            <h3 class="jugend-h2" style="color: var(--color-white);">Kein Vorwissen?<br>Kein Problem!</h3>
            <p>Du brauchst keine Vorkenntnisse oder spezielle Ausbildung. Alles, was du mitbringen musst, ist Bereitschaft, Teamgeist und Verlässlichkeit. Deine Ausbildung erhältst du bei uns – Schritt für Schritt, in Theorie und Praxis.</p>
            <p>Wir freuen uns über jede neue Verstärkung – egal ob du 16, 30 oder 50 bist und eine neue Herausforderung suchst.</p>

            <div class="content-card" style="text-align: left; margin-top: 30px;">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-shoe-prints"></i></div>
                    <h2>So kannst du mitmachen</h2>
                </div>
                <div class="content-card-body">
                    <p>Nimm Kontakt mit uns auf und komm zu einem unserer Übungs- oder Schulungsabende vorbei. Wir zeigen dir gerne, wie die Feuerwehr Reichenau arbeitet und beantworten alle deine Fragen.</p>
                    <p><strong>Wichtiger Hinweis:</strong> Um aktives Mitglied der FF Reichenau zu werden, musst du deinen ständigen Wohnsitz im Schutzgebiet unserer Feuerwehr haben. Alle Infos dazu findest du <a href="index.php?page=ueber-uns#schutzbereich">hier</a>.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="kontakt">
        <div class="container">
            <div class="jugend-contact-grid">
                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-envelope-open-text"></i></div>
                        <h2>Du hast Fragen oder willst gleich loslegen?</h2>
                    </div>
                    <div class="content-card-body">
                        <?php if ($formSent): ?>
                            <div class="form-alert form-alert-success">
                                <p>Vielen Dank für deine Nachricht! Wir melden uns bei dir.</p>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($formErrors)): ?>
                                <div class="form-alert form-alert-error">
                                    <?php foreach ($formErrors as $err): ?>
                                        <p><?php echo htmlspecialchars($err); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" class="public-form">
                                <input type="hidden" name="mitmachen_submit" value="1">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['mitmachen_csrf']); ?>">
                                <input type="text" name="website" value="" class="form-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="mm_vorname">Vorname <span class="req">(*)</span></label>
                                        <input type="text" id="mm_vorname" name="vorname" required value="<?php echo htmlspecialchars($formData['vorname']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="mm_nachname">Nachname <span class="req">(*)</span></label>
                                        <input type="text" id="mm_nachname" name="nachname" required value="<?php echo htmlspecialchars($formData['nachname']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="mm_email">E-Mail-Adresse <span class="req">(*)</span></label>
                                    <input type="email" id="mm_email" name="email" required value="<?php echo htmlspecialchars($formData['email']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="mm_nachricht">Deine Nachricht <span class="req">(*)</span></label>
                                    <textarea id="mm_nachricht" name="nachricht" rows="4" required><?php echo htmlspecialchars($formData['nachricht']); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Abschicken</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mitmachen-contacts">
                    <?php foreach ($contactPersons as $cp): ?>
                        <div class="jugend-betreuer-card">
                            <?php if ($cp['member']['photo']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($cp['member']['photo']); ?>" alt="<?php echo htmlspecialchars($cp['member']['firstname'] . ' ' . $cp['member']['lastname']); ?>" class="jugend-contact-photo">
                            <?php endif; ?>
                            <p class="jugend-betreuer-label"><?php echo htmlspecialchars($cp['role']); ?></p>
                            <h4><?php echo htmlspecialchars($cp['member']['firstname'] . ' ' . $cp['member']['lastname']); ?></h4>
                            <a href="mailto:reichenau@feuerwehr.tirol"><i class="fas fa-envelope"></i> reichenau@feuerwehr.tirol</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
