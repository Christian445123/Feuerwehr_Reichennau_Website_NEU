<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
requireLogin();
requirePermission('deploy.manage');

$pageTitle = 'Deployment';
$activePage = 'deploy';

$projectRoot = realpath(__DIR__ . '/..');
$gitDir = $projectRoot . '/.git';

/**
 * Führt einen Befehl im Projektverzeichnis aus und liefert Ausgabe + Erfolg.
 * Kein Nutzer-Input fließt in den Befehl ein - feste, fest verdrahtete Kommandos.
 */
function runGitCommand(string $projectRoot, string $command): array {
    if (!function_exists('shell_exec')) {
        return ['ok' => false, 'output' => 'shell_exec() ist auf diesem Server deaktiviert. Dein Hoster erlaubt keine Befehlsausführung über PHP - ein Git-Pull-Button kann hier nicht funktionieren.'];
    }
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (in_array('shell_exec', $disabled, true)) {
        return ['ok' => false, 'output' => 'shell_exec() ist in der PHP-Konfiguration deines Hosters explizit gesperrt (disable_functions). Bitte den Hoster kontaktieren oder weiterhin per FTP hochladen.'];
    }

    $fullCommand = 'cd ' . escapeshellarg($projectRoot) . ' && ' . $command . ' 2>&1';
    $output = shell_exec($fullCommand);

    if ($output === null) {
        return ['ok' => false, 'output' => 'Befehl konnte nicht ausgeführt werden (kein Ergebnis von shell_exec).'];
    }

    return ['ok' => true, 'output' => trim($output)];
}

$gitAvailable = is_dir($gitDir);
$pullResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: deploy.php');
        exit;
    }

    if (!$gitAvailable) {
        flash('error', 'Auf diesem Server ist kein Git-Repository eingerichtet (kein .git-Ordner gefunden).');
        header('Location: deploy.php');
        exit;
    }

    if (($_POST['action'] ?? '') === 'pull') {
        // fetch + reset --hard: der Server wird exakt auf den GitHub-Stand
        // gebracht, unabhängig davon, ob dort zwischenzeitlich Dateien direkt
        // verändert wurden. Unversionierte Dateien (.env, hochgeladene Fotos,
        // die Datenbank) bleiben davon unberührt - nur von Git verfolgte
        // Dateien werden zurückgesetzt.
        $branchResult = runGitCommand($projectRoot, 'git rev-parse --abbrev-ref HEAD');
        $branch = $branchResult['ok'] ? trim($branchResult['output']) : 'main';
        if ($branch === '' || str_contains($branch, "\n")) {
            $branch = 'main';
        }

        $fetch = runGitCommand($projectRoot, 'git fetch origin ' . escapeshellarg($branch));
        $reset = runGitCommand($projectRoot, 'git reset --hard ' . escapeshellarg('origin/' . $branch));

        $pullResult = [
            'ok' => $fetch['ok'] && $reset['ok'],
            'output' => "\$ git fetch origin $branch\n" . $fetch['output'] . "\n\n\$ git reset --hard origin/$branch\n" . $reset['output'],
        ];

        if ($pullResult['ok']) {
            flash('success', 'Server wurde auf den neuesten GitHub-Stand gebracht.');
        } else {
            flash('error', 'Deployment fehlgeschlagen - siehe Log unten.');
        }
    }
}

$currentCommit = null;
$status = null;
if ($gitAvailable) {
    $log = runGitCommand($projectRoot, 'git log -1 --format="%H|%ci|%s"');
    if ($log['ok'] && $log['output'] !== '') {
        [$hash, $date, $subject] = array_pad(explode('|', $log['output'], 3), 3, '');
        $currentCommit = ['hash' => $hash, 'date' => $date, 'subject' => $subject];
    }
    $statusResult = runGitCommand($projectRoot, 'git status --porcelain');
    $status = $statusResult['ok'] ? trim($statusResult['output']) : null;
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-card" style="max-width: 720px;">
    <div class="admin-card-header"><h2><i class="fab fa-github"></i> Deployment</h2></div>
    <div class="admin-card-body">
        <?php if (!$gitAvailable): ?>
            <div class="permission-note">
                <i class="fas fa-triangle-exclamation"></i>
                Auf diesem Server wurde kein Git-Repository gefunden (<code><?php echo e($projectRoot); ?>/.git</code> existiert nicht).
                Diese Funktion setzt voraus, dass die Website hier ursprünglich per <code>git clone</code> eingerichtet wurde
                (z.B. über die "Git Version Control"-Funktion im Hosting-Panel), nicht per FTP-Upload.
                Bis das eingerichtet ist, bitte weiterhin per FTP hochladen.
            </div>
        <?php else: ?>
            <p style="margin-bottom:16px;color:var(--gray-600);">
                Holt den neuesten Stand vom GitHub-Repository und setzt den Server exakt darauf zurück.
                Hochgeladene Fotos, die Datenbank und die <code>.env</code>-Datei sind davon nicht betroffen.
            </p>

            <?php if ($currentCommit): ?>
                <table class="admin-table" style="margin-bottom:20px;">
                    <tr><td><strong>Aktueller Commit</strong></td><td><code><?php echo e(substr($currentCommit['hash'], 0, 10)); ?></code></td></tr>
                    <tr><td><strong>Nachricht</strong></td><td><?php echo e($currentCommit['subject']); ?></td></tr>
                    <tr><td><strong>Datum</strong></td><td><?php echo e($currentCommit['date']); ?></td></tr>
                </table>
            <?php endif; ?>

            <?php if (!empty($status)): ?>
                <div class="permission-note">
                    <i class="fas fa-info-circle"></i> Auf dem Server gibt es direkt geänderte Dateien, die nicht in Git eingecheckt sind. Ein Pull überschreibt diese Änderungen unwiderruflich.
                </div>
            <?php endif; ?>

            <form method="POST" onsubmit="return confirm('Server jetzt auf den neuesten GitHub-Stand zurücksetzen? Direkt auf dem Server geänderte Dateien gehen dabei verloren.');">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="pull">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-cloud-download-alt"></i> Neuesten Stand von GitHub holen
                </button>
            </form>

            <?php if ($pullResult): ?>
                <h4 style="margin-top:24px;margin-bottom:8px;">Ergebnis</h4>
                <pre style="background:var(--gray-100,#f8f9fa);padding:14px;border-radius:8px;font-size:0.8rem;overflow-x:auto;white-space:pre-wrap;"><?php echo e($pullResult['output']); ?></pre>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
