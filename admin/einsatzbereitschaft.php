<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/einsatzbereitschaft.php';
requireLogin();
requirePermission('settings.manage');

$pageTitle = 'Einsatzbereitschaft';
$activePage = 'einsatzbereitschaft';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: einsatzbereitschaft.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $newStatus = ($_POST['status'] ?? '1') === '1';
        setEinsatzbereitschaft($newStatus, 'manual');
        logActivity($db, 'einsatzbereitschaft.change', ($newStatus ? 'Einsatzbereit' : 'Nicht einsatzbereit') . ' (manuell)');
        flash('success', $newStatus ? 'Status auf "Einsatzbereit" gesetzt.' : 'Status auf "Nicht einsatzbereit" gesetzt.');
    }

    if ($action === 'regenerate_token') {
        regenerateEinsatzbereitschaftApiToken();
        logActivity($db, 'einsatzbereitschaft.token_regenerate');
        flash('success', 'Neuer Token wurde erzeugt. Bisherige Einträge im SMS-Gateway/API müssen aktualisiert werden.');
    }

    if ($action === 'save_senders') {
        setEinsatzbereitschaftAllowedSenders($_POST['allowed_senders'] ?? '');
        logActivity($db, 'einsatzbereitschaft.senders_update');
        flash('success', 'Erlaubte Rufnummern wurden gespeichert.');
    }

    header('Location: einsatzbereitschaft.php');
    exit;
}

$meta = getEinsatzbereitschaftMeta();
$token = getEinsatzbereitschaftApiToken();
$scriptDir = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
$webhookUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . $scriptDir . '/api-einsatzbereitschaft.php';
$viaLabels = ['manual' => 'Manuell im Admin-Bereich', 'sms' => 'Per SMS', 'api' => 'Per API'];

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-card" style="max-width: 700px;">
    <div class="admin-card-header"><h2><i class="fas fa-truck-medical"></i> Aktueller Status</h2></div>
    <div class="admin-card-body">
        <div style="margin-bottom: 20px; padding: 20px; border-radius: var(--radius); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; background: <?php echo $meta['status'] ? 'rgba(39,174,96,0.08)' : 'rgba(213,0,28,0.06)'; ?>;">
            <div>
                <strong style="font-size: 1.2rem; color: <?php echo $meta['status'] ? 'var(--success)' : 'var(--danger)'; ?>;">
                    <i class="fas <?php echo $meta['status'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    <?php echo $meta['status'] ? 'Einsatzbereit' : 'Nicht einsatzbereit'; ?>
                </strong>
                <p style="margin: 6px 0 0; font-size: 0.85rem; color: var(--gray-600);">
                    <?php if ($meta['updated_at']): ?>
                        Zuletzt geändert am <?php echo e(date('d.m.Y \u\m H:i', strtotime($meta['updated_at']))); ?> Uhr
                        <?php if ($meta['via']): ?> &middot; <?php echo e($viaLabels[$meta['via']] ?? $meta['via']); ?><?php endif; ?>
                        <?php if ($meta['note']): ?> &middot; <?php echo e($meta['note']); ?><?php endif; ?>
                    <?php else: ?>
                        Noch nie geändert (Standardwert).
                    <?php endif; ?>
                </p>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="status" value="<?php echo $meta['status'] ? '0' : '1'; ?>">
                <button type="submit" class="btn <?php echo $meta['status'] ? 'btn-danger' : 'btn-primary'; ?>">
                    <i class="fas fa-toggle-on"></i> Auf "<?php echo $meta['status'] ? 'Nicht einsatzbereit' : 'Einsatzbereit'; ?>" setzen
                </button>
            </form>
        </div>
        <p style="color: var(--gray-600); font-size: 0.85rem;">Dieser Status wird auf der Startseite öffentlich angezeigt.</p>
    </div>
</div>

<div class="admin-card" style="max-width: 700px; margin-top: 24px;">
    <div class="admin-card-header"><h2><i class="fas fa-satellite-dish"></i> Automatisches Umschalten (SMS / API)</h2></div>
    <div class="admin-card-body">
        <p style="margin-bottom: 16px; color: var(--gray-600);">Diese Adresse kann als Ziel-URL in einem SMS-Gateway (eingehende SMS werden dorthin weitergeleitet) oder einer eigenen API eingetragen werden. Der Aufruf funktioniert per GET oder POST mit dem Token sowie entweder einem <code>status</code>-Feld ("bereit" / "nicht_bereit") oder einem Freitext-Feld <code>message</code>, <code>text</code>, <code>body</code> oder <code>sms</code>, das nach den Wörtern "BEREIT" bzw. "NICHT BEREIT" durchsucht wird.</p>

        <div class="form-group">
            <label>Webhook-Adresse</label>
            <input type="text" readonly value="<?php echo e($webhookUrl); ?>" onclick="this.select()">
        </div>

        <div class="form-group">
            <label>Token</label>
            <input type="text" readonly value="<?php echo e($token); ?>" onclick="this.select()">
        </div>

        <p style="font-size: 0.85rem; color: var(--gray-600); margin-bottom: 16px;">Beispiel: <code><?php echo e($webhookUrl); ?>?token=<?php echo e($token); ?>&amp;message=BEREIT</code></p>

        <form method="POST" onsubmit="return confirm('Neuen Token erzeugen? Bisherige Einträge im SMS-Gateway/API müssen danach angepasst werden.');">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="regenerate_token">
            <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-rotate"></i> Neuen Token erzeugen</button>
        </form>

        <hr style="margin: 20px 0;">

        <form method="POST" class="admin-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_senders">
            <div class="form-group">
                <label for="allowed_senders">Erlaubte Absender-Rufnummern (optional)</label>
                <input type="text" id="allowed_senders" name="allowed_senders" value="<?php echo e(getEinsatzbereitschaftAllowedSenders()); ?>" placeholder="z.B. +436601234567, +436609876543">
                <p class="form-hint">Kommagetrennt. Leer lassen, um jede Rufnummer zuzulassen (nur der Token zählt dann). Nur wirksam, wenn das SMS-Gateway die Absendernummer im Feld <code>from</code>, <code>sender</code> oder <code>msisdn</code> mitschickt.</p>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Speichern</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
