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
        $jugendFormErrors[] = t('jugend.error.rate_limit');
    }

    $csrfOk = hash_equals($_SESSION['jugend_csrf'], $_POST['csrf_token'] ?? '');
    $honeypotOk = ($_POST['website'] ?? '') === '';

    if (!$csrfOk || !$honeypotOk) {
        $jugendFormErrors[] = t('jugend.error.invalid_request');
    }
    if ($jugendFormData['vorname'] === '' || $jugendFormData['nachname'] === '') {
        $jugendFormErrors[] = t('jugend.error.name_required');
    }
    if ($jugendFormData['email'] === '' || !filter_var($jugendFormData['email'], FILTER_VALIDATE_EMAIL)) {
        $jugendFormErrors[] = t('jugend.error.email_invalid');
    }
    if ($jugendFormData['nachricht'] === '') {
        $jugendFormErrors[] = t('jugend.error.message_required');
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
        $jugendFormErrors[] = t('jugend.error.send_failed');
    }
}
?>
    <!-- Hero: Original-Grafik der Referenzseite -->
    <section class="jugend-hero-banner" style="background-image: url('assets/images/jugend_banner.jpg'); background-color: #e4002b;">
        <div class="container">
            <h1><?php echo t('jugend.hero_title'); ?></h1>
        </div>
    </section>

    <section class="jugend-yellow-band" id="aktivitaeten">
        <div class="container jugend-narrow">
            <h2 class="jugend-h2"><?php echo e(t('jugend.yellow_h2')); ?></h2>
            <h3 class="jugend-h3"><?php echo e(t('jugend.yellow_h3')); ?></h3>
        </div>
    </section>

    <section class="section">
        <div class="container jugend-narrow">
            <p><?php echo e(t('jugend.intro_p1')); ?></p>
            <p><strong><?php echo e(t('jugend.intro_p2')); ?></strong></p>
            <p class="jugend-statement"><?php echo t('jugend.statement'); ?></p>
        </div>
    </section>

    <!-- Möchtest du Teil unseres Teams werden? -->
    <section class="jugend-red-band">
        <div class="container">
            <h2><?php echo e(t('jugend.red_h2')); ?></h2>
            <h3><?php echo e(t('jugend.red_h3')); ?></h3>
        </div>
    </section>

    <section class="section">
        <div class="container jugend-narrow">
            <div class="content-card">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-list-check"></i></div>
                    <h2><?php echo e(t('jugend.expect_heading')); ?></h2>
                </div>
                <div class="content-card-body">
                    <ul class="jugend-checklist">
                        <li><i class="fas fa-check"></i> <?php echo t('jugend.expect_1'); ?></li>
                        <li><i class="fas fa-check"></i> <?php echo t('jugend.expect_2'); ?></li>
                        <li><i class="fas fa-check"></i> <?php echo t('jugend.expect_3'); ?></li>
                        <li><i class="fas fa-check"></i> <?php echo t('jugend.expect_4'); ?></li>
                        <li><i class="fas fa-check"></i> <?php echo t('jugend.expect_5'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Infos für Eltern -->
    <section class="jugend-yellow-band">
        <div class="container jugend-narrow">
            <h2 class="jugend-h2"><?php echo e(t('jugend.parents_heading')); ?></h2>
            <p><strong><?php echo e(t('jugend.parents_p1_bold')); ?></strong> <?php echo e(t('jugend.parents_p1_rest')); ?></p>
            <p><?php echo e(t('jugend.parents_p2')); ?></p>
            <p><strong><?php echo e(t('jugend.parents_p3_bold')); ?></strong> <?php echo e(t('jugend.parents_p3_rest')); ?></p>
            <p><strong><?php echo e(t('jugend.parents_p4_bold')); ?></strong> <?php echo e(t('jugend.parents_p4_rest')); ?></p>
        </div>
    </section>

    <!-- Kontakt -->
    <section class="section" id="machmit">
        <div class="container">
            <div class="jugend-contact-grid">
                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-envelope-open-text"></i></div>
                        <h2><?php echo e(t('nav.kontakt')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <p>
                            <?php echo e(t('jugend.contact_intro_question')); ?>
                            <?php if ($jugendbetreuerin): ?>
                                <?php echo e(t('jugend.contact_intro_with_betreuer')); ?>
                            <?php else: ?>
                                <?php echo e(t('jugend.contact_intro_without_betreuer')); ?>
                            <?php endif; ?>
                        </p>

                        <?php if ($jugendFormSent): ?>
                            <div class="form-alert form-alert-success">
                                <p><?php echo e(t('jugend.form_success')); ?></p>
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
                                        <label for="jf_vorname"><?php echo e(t('jugend.label_vorname')); ?> <span class="req">(*)</span></label>
                                        <input type="text" id="jf_vorname" name="vorname" required value="<?php echo htmlspecialchars($jugendFormData['vorname']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="jf_nachname"><?php echo e(t('jugend.label_nachname')); ?> <span class="req">(*)</span></label>
                                        <input type="text" id="jf_nachname" name="nachname" required value="<?php echo htmlspecialchars($jugendFormData['nachname']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="jf_email"><?php echo e(t('jugend.label_email')); ?> <span class="req">(*)</span></label>
                                    <input type="email" id="jf_email" name="email" required value="<?php echo htmlspecialchars($jugendFormData['email']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="jf_nachricht"><?php echo e(t('jugend.label_nachricht')); ?> <span class="req">(*)</span></label>
                                    <textarea id="jf_nachricht" name="nachricht" rows="4" required><?php echo htmlspecialchars($jugendFormData['nachricht']); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> <?php echo e(t('jugend.submit')); ?></button>
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
                    <p class="jugend-betreuer-label"><?php echo e(t('jugend.betreuerin_label')); ?></p>
                    <h4><?php echo $jugendbetreuerin ? htmlspecialchars($jugendbetreuerin['firstname'] . ' ' . $jugendbetreuerin['lastname']) : 'FF Reichenau'; ?></h4>
                    <a href="mailto:reichenau@feuerwehr.tirol"><i class="fas fa-envelope"></i> reichenau@feuerwehr.tirol</a>
                </div>
            </div>
        </div>
    </section>
