<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/schutzbereich.php';
$db = getDB();

// Eigenes kleines Kontaktformular wie im Vorbild, nutzt denselben
// abgesicherten Mailversand wie die anderen Kontaktformulare.
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
        $formErrors[] = t('jugend.error.rate_limit');
    }

    $csrfOk = hash_equals($_SESSION['mitmachen_csrf'], $_POST['csrf_token'] ?? '');
    $honeypotOk = ($_POST['website'] ?? '') === '';

    if (!$honeypotOk) {
        logActivity($db, 'public.honeypot_catch', 'Mitmachen-Formular', null, 'Öffentliche Website');
    }
    if (!$csrfOk || !$honeypotOk) {
        $formErrors[] = t('jugend.error.invalid_request');
    }
    if ($formData['vorname'] === '' || $formData['nachname'] === '') {
        $formErrors[] = t('jugend.error.name_required');
    }
    if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = t('jugend.error.email_invalid');
    }
    if ($formData['nachricht'] === '') {
        $formErrors[] = t('jugend.error.message_required');
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
            logActivity($db, 'public.mitmachen_sent', $formData['vorname'] . ' ' . $formData['nachname'] . ' <' . $formData['email'] . '>', null, 'Öffentliche Website');
            header('Location: index.php?page=mitmachen&sent=1#kontakt');
            exit;
        }
        $formErrors[] = t('jugend.error.send_failed');
    }
}

// Ansprechpartner: Kommandant (immer oben) und Kommandant-Stv. (immer unten)
// kommen aus dem Organigramm (Admin -> Organigramm) und werden über den
// Namen mit dem passenden Mitglied verknüpft - Änderungen dort wirken sich
// hier automatisch aus.
$contactPersons = [];
$orgStmt = $db->prepare("SELECT name FROM org_chart_positions WHERE position_key = ?");
$memberStmt = $db->prepare("SELECT * FROM members WHERE CONCAT(firstname, ' ', lastname) = ? LIMIT 1");
foreach ([['kommandant', t('orgchart.position.kommandant')], ['kommandant_stv', t('orgchart.position.kommandant_stv')]] as [$posKey, $role]) {
    $orgStmt->execute([$posKey]);
    $fullName = trim((string) $orgStmt->fetchColumn());
    if ($fullName === '') continue;
    $memberStmt->execute([$fullName]);
    $m = $memberStmt->fetch();
    if ($m) {
        $contactPersons[] = ['member' => $m, 'role' => $role];
    }
}
?>
    <section class="mitmachen-hero">
        <div class="container">
            <h1><?php echo t('mitmachen.hero_title'); ?></h1>
            <div class="mitmachen-questions">
                <p><?php echo t('mitmachen.question_1'); ?></p>
                <p><?php echo e(t('mitmachen.question_2')); ?></p>
                <p><?php echo e(t('mitmachen.question_3')); ?></p>
            </div>
            <h2><?php echo e(t('mitmachen.hero_cta')); ?></h2>
        </div>
    </section>

    <section class="section">
        <div class="container jugend-narrow">
            <h2 class="jugend-h2"><?php echo e(t('mitmachen.counts_heading')); ?></h2>
            <p><?php echo e(t('mitmachen.counts_p1')); ?></p>
            <p><?php echo e(t('mitmachen.counts_p2')); ?></p>
        </div>

        <div class="container">
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-hand-holding-heart"></i></div>
                    <h3><?php echo e(t('mitmachen.feature1_title')); ?></h3>
                    <p><?php echo e(t('mitmachen.feature1_text')); ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-people-group"></i></div>
                    <h3><?php echo e(t('mitmachen.feature2_title')); ?></h3>
                    <p><?php echo e(t('mitmachen.feature2_text')); ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-book-open"></i></div>
                    <h3><?php echo e(t('mitmachen.feature3_title')); ?></h3>
                    <p><?php echo e(t('mitmachen.feature3_text')); ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-dumbbell"></i></div>
                    <h3><?php echo e(t('mitmachen.feature4_title')); ?></h3>
                    <p><?php echo e(t('mitmachen.feature4_text')); ?></p>
                </div>
            </div>
        </div>
    </section>

    <?php if (isSchutzgebietCheckEnabled()): ?>
    <section class="section" style="padding-top: 0;">
        <div class="container jugend-narrow">
            <div class="schutzgebiet-check-card">
                <div class="schutzgebiet-check-badge"><i class="fas fa-location-crosshairs"></i></div>
                <h2><?php echo e(t('mitmachen.check_heading')); ?></h2>
                <p><?php echo e(t('mitmachen.check_text')); ?></p>

                <form id="schutzgebietCheckForm" class="schutzgebiet-check-form" onsubmit="return false;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sgStrasse"><?php echo e(t('mitmachen.check_street')); ?></label>
                            <input type="text" id="sgStrasse" name="strasse" placeholder="<?php echo e(t('mitmachen.check_street_placeholder')); ?>" required>
                        </div>
                        <div class="form-group schutzgebiet-check-hausnr">
                            <label for="sgHausnummer"><?php echo e(t('mitmachen.check_housenumber')); ?></label>
                            <input type="text" id="sgHausnummer" name="hausnummer" placeholder="4">
                        </div>
                        <button type="submit" class="btn btn-primary" id="sgCheckBtn"><i class="fas fa-magnifying-glass-location"></i> <?php echo e(t('mitmachen.check_button')); ?></button>
                    </div>
                </form>

                <div id="schutzgebietCheckResult" class="schutzgebiet-check-result" style="display:none;">
                    <div class="schutzgebiet-check-result-icon"><i class="fas"></i></div>
                    <div class="schutzgebiet-check-result-text">
                        <strong class="schutzgebiet-check-result-title"></strong>
                        <span class="schutzgebiet-check-result-desc"></span>
                    </div>
                </div>

                <p class="form-hint schutzgebiet-check-hint"><i class="fas fa-circle-info"></i> <?php echo t('mitmachen.check_hint', ['link' => '<a href="index.php?page=schutzbereich">' . e(t('mitmachen.check_hint_link')) . '</a>']); ?></p>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="einsatzgebiet-gallery">
                <div class="einsatzgebiet-gallery-item">
                    <img src="assets/images/mitmachen/mitmachen_01.jpg" alt="<?php echo e(t('mitmachen.gallery_alt')); ?>" data-lightbox-group="mitmachen" loading="lazy">
                </div>
                <div class="einsatzgebiet-gallery-item">
                    <img src="assets/images/mitmachen/mitmachen_02.jpg" alt="<?php echo e(t('mitmachen.gallery_alt')); ?>" data-lightbox-group="mitmachen" loading="lazy">
                </div>
                <div class="einsatzgebiet-gallery-item">
                    <img src="assets/images/mitmachen/mitmachen_03.jpg" alt="<?php echo e(t('mitmachen.gallery_alt_3')); ?>" data-lightbox-group="mitmachen" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <section class="section section-dark">
        <div class="container jugend-narrow">
            <div class="section-header">
                <h2 class="section-title"><?php echo e(t('mitmachen.why_heading')); ?></h2>
            </div>
            <h3 class="jugend-h2" style="color: var(--color-white);"><?php echo t('mitmachen.no_experience'); ?></h3>
            <p><?php echo e(t('mitmachen.no_experience_p1')); ?></p>
            <p><?php echo e(t('mitmachen.no_experience_p2')); ?></p>

            <div class="content-card" style="text-align: left; margin-top: 30px;">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-shoe-prints"></i></div>
                    <h2><?php echo e(t('mitmachen.how_heading')); ?></h2>
                </div>
                <div class="content-card-body">
                    <p><?php echo e(t('mitmachen.how_p1')); ?></p>
                    <p><strong><?php echo e(t('mitmachen.how_notice_bold')); ?></strong> <?php echo t('mitmachen.how_notice_rest', ['link' => '<a href="index.php?page=schutzbereich">' . e(t('mitmachen.how_notice_link')) . '</a>']); ?></p>
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
                        <h2><?php echo e(t('jugend.contact_intro_question')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <?php if ($formSent): ?>
                            <div class="form-alert form-alert-success">
                                <p><?php echo e(t('jugend.form_success')); ?></p>
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
                                        <label for="mm_vorname"><?php echo e(t('jugend.label_vorname')); ?> <span class="req">(*)</span></label>
                                        <input type="text" id="mm_vorname" name="vorname" required value="<?php echo htmlspecialchars($formData['vorname']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="mm_nachname"><?php echo e(t('jugend.label_nachname')); ?> <span class="req">(*)</span></label>
                                        <input type="text" id="mm_nachname" name="nachname" required value="<?php echo htmlspecialchars($formData['nachname']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="mm_email"><?php echo e(t('jugend.label_email')); ?> <span class="req">(*)</span></label>
                                    <input type="email" id="mm_email" name="email" required value="<?php echo htmlspecialchars($formData['email']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="mm_nachricht"><?php echo e(t('jugend.label_nachricht')); ?> <span class="req">(*)</span></label>
                                    <textarea id="mm_nachricht" name="nachricht" rows="4" required><?php echo htmlspecialchars($formData['nachricht']); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> <?php echo e(t('jugend.submit')); ?></button>
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

<?php if (isSchutzgebietCheckEnabled()): ?>
<script>
var sgCheckI18n = <?php echo json_encode([
    'checking' => t('mitmachen.check_checking'),
    'checkButton' => t('mitmachen.check_button'),
    'errorTitle' => t('mitmachen.check_error_title'),
    'notFoundTitle' => t('mitmachen.check_not_found_title'),
    'notFoundDesc' => t('mitmachen.check_not_found_desc'),
    'yesTitle' => t('mitmachen.check_yes_title'),
    'yesDesc' => t('mitmachen.check_yes_desc'),
    'noTitle' => t('mitmachen.check_no_title'),
    'noDesc' => t('mitmachen.check_no_desc'),
    'networkError' => t('mitmachen.check_network_error'),
], JSON_UNESCAPED_UNICODE); ?>;
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('schutzgebietCheckForm');
    if (!form) return;
    var btn = document.getElementById('sgCheckBtn');
    var resultBox = document.getElementById('schutzgebietCheckResult');
    var resultIcon = resultBox.querySelector('.schutzgebiet-check-result-icon i');
    var resultTitle = resultBox.querySelector('.schutzgebiet-check-result-title');
    var resultDesc = resultBox.querySelector('.schutzgebiet-check-result-desc');

    function showResult(state, icon, title, desc) {
        resultBox.className = 'schutzgebiet-check-result sg-' + state;
        resultIcon.className = 'fas ' + icon;
        resultTitle.textContent = title;
        resultDesc.innerHTML = desc;
        resultBox.style.display = 'flex';
        // Kurzes Einblenden, statt dass das Ergebnis einfach da ist
        resultBox.classList.remove('sg-reveal');
        void resultBox.offsetWidth;
        resultBox.classList.add('sg-reveal');
    }

    form.addEventListener('submit', function () {
        var strasse = document.getElementById('sgStrasse').value.trim();
        var hausnummer = document.getElementById('sgHausnummer').value.trim();
        if (!strasse) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + sgCheckI18n.checking;
        resultBox.style.display = 'none';

        var url = 'schutzgebiet-check.php?strasse=' + encodeURIComponent(strasse) + '&hausnummer=' + encodeURIComponent(hausnummer);
        fetch(url).then(function (r) { return r.json(); }).then(function (data) {
            if (data.error) {
                showResult('error', 'fa-triangle-exclamation', sgCheckI18n.errorTitle, data.error);
            } else if (!data.found) {
                showResult('unknown', 'fa-circle-question', sgCheckI18n.notFoundTitle, sgCheckI18n.notFoundDesc);
            } else if (data.in_schutzgebiet) {
                showResult('yes', 'fa-circle-check', sgCheckI18n.yesTitle, sgCheckI18n.yesDesc);
            } else {
                showResult('no', 'fa-circle-info', sgCheckI18n.noTitle, sgCheckI18n.noDesc);
            }
        }).catch(function () {
            showResult('error', 'fa-triangle-exclamation', sgCheckI18n.errorTitle, sgCheckI18n.networkError);
        }).finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magnifying-glass-location"></i> ' + sgCheckI18n.checkButton;
        });
    });
});
</script>
<?php endif; ?>
