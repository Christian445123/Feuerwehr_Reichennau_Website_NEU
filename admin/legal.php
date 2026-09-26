<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/legal.php';
require_once __DIR__ . '/../config/logging.php';
requireLogin();
requirePermission('legal.manage');

$pageTitle = 'Rechtstexte';
$activePage = 'legal';

$db = getDB();
$documents = getLegalDocuments();
$doc = $_GET['doc'] ?? 'datenschutz';
if (!isset($documents[$doc])) $doc = 'datenschutz';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postDoc = $_POST['doc'] ?? '';
    if (!verifyCsrf() || !isset($documents[$postDoc])) {
        flash('error', 'Ungültiger Sicherheits-Token.');
        header('Location: legal.php');
        exit;
    }

    if (($_POST['action'] ?? '') === 'reset') {
        resetLegalSections($db, $postDoc);
        logActivity($db, 'legal.reset', $documents[$postDoc]);
        flash('success', 'Der Standardtext von "' . $documents[$postDoc] . '" wurde wiederhergestellt.');
    } else {
        $icons = $_POST['icon'] ?? [];
        $titles = $_POST['title'] ?? [];
        $bodies = $_POST['body'] ?? [];
        $sections = [];
        foreach ($titles as $i => $title) {
            $title = trim($title);
            $body = sanitizeLegalHtml($bodies[$i] ?? '');
            if ($title === '' && trim(strip_tags($body)) === '') continue;
            $icon = preg_replace('/[^a-z0-9 \-]/i', '', $icons[$i] ?? '');
            $sections[] = [
                'icon' => $icon !== '' ? $icon : 'fas fa-file-alt',
                'title' => $title,
                'body' => $body,
            ];
        }
        if (!$sections) {
            flash('error', 'Der Text darf nicht leer sein. Zum Zurücksetzen bitte "Standardtext wiederherstellen" nutzen.');
        } else {
            saveLegalSections($db, $postDoc, $sections);
            logActivity($db, 'legal.save', $documents[$postDoc]);
            flash('success', '"' . $documents[$postDoc] . '" wurde gespeichert.');
        }
    }
    header('Location: legal.php?doc=' . urlencode($postDoc));
    exit;
}

$sections = getLegalSections($db, $doc);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;">
    <?php foreach ($documents as $key => $label): ?>
        <a href="legal.php?doc=<?php echo $key; ?>" class="btn <?php echo $key === $doc ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo e($label); ?></a>
    <?php endforeach; ?>
</div>

<p style="margin-bottom: 16px; color: #6c757d;">
    Jeder Abschnitt erscheint auf der Website als eigene Karte.
    <?php echo $doc === 'datenschutz' ? 'Die Datenschutzerklärung findest du unter /Datenschutz.' : 'Dieser Text erscheint auf der Seite "Kontakt &amp; Impressum".'; ?>
    Aktuell: <strong><?php echo isLegalCustomized($db, $doc) ? 'angepasster Text' : 'Standardtext'; ?></strong>.
    Hinweis: Rechtstexte am besten von einer fachkundigen Stelle prüfen lassen.
</p>

<form method="POST" id="legalForm">
    <?php echo csrfField(); ?>
    <input type="hidden" name="doc" value="<?php echo e($doc); ?>">
    <div id="sections">
        <?php foreach ($sections as $s): ?>
        <div class="admin-card legal-section" style="margin-bottom:16px;">
            <div class="admin-card-body">
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
                    <div class="form-group" style="flex:1 1 260px; margin:0;">
                        <label>Überschrift</label>
                        <input type="text" name="title[]" value="<?php echo e(html_entity_decode($s['title'] ?? '')); ?>">
                    </div>
                    <div class="form-group" style="flex:0 1 200px; margin:0;">
                        <label>Symbol (Font Awesome)</label>
                        <input type="text" name="icon[]" value="<?php echo e($s['icon'] ?? 'fas fa-file-alt'); ?>">
                    </div>
                </div>
                <div class="legal-toolbar">
                    <button type="button" data-cmd="bold" title="Fett"><i class="fas fa-bold"></i></button>
                    <button type="button" data-cmd="italic" title="Kursiv"><i class="fas fa-italic"></i></button>
                    <button type="button" data-cmd="h4" title="Zwischenüberschrift"><i class="fas fa-heading"></i></button>
                    <button type="button" data-cmd="p" title="Absatz"><i class="fas fa-paragraph"></i></button>
                    <button type="button" data-cmd="insertUnorderedList" title="Liste"><i class="fas fa-list-ul"></i></button>
                    <button type="button" data-cmd="link" title="Link"><i class="fas fa-link"></i></button>
                    <button type="button" data-cmd="html" title="HTML-Ansicht"><i class="fas fa-code"></i></button>
                    <span style="flex:1"></span>
                    <button type="button" data-cmd="up" title="Nach oben"><i class="fas fa-arrow-up"></i></button>
                    <button type="button" data-cmd="down" title="Nach unten"><i class="fas fa-arrow-down"></i></button>
                    <button type="button" data-cmd="remove" title="Abschnitt löschen" style="color:#c0392b;"><i class="fas fa-trash"></i></button>
                </div>
                <div class="legal-editor" contenteditable="true"><?php echo sanitizeLegalHtml($s['body'] ?? ''); ?></div>
                <textarea name="body[]" class="legal-source" style="display:none; width:100%; min-height:260px; font-family:monospace;"></textarea>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" id="addSection" class="btn btn-secondary"><i class="fas fa-plus"></i> Abschnitt hinzufügen</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Speichern</button>
    </div>
</form>

<form method="POST" style="margin-top:24px;" onsubmit="return confirm('Wirklich alle Änderungen verwerfen und den Standardtext wiederherstellen?');">
    <?php echo csrfField(); ?>
    <input type="hidden" name="doc" value="<?php echo e($doc); ?>">
    <input type="hidden" name="action" value="reset">
    <button type="submit" class="btn btn-danger"><i class="fas fa-rotate-left"></i> Standardtext wiederherstellen</button>
</form>

<style>
.legal-toolbar { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:6px; }
.legal-toolbar button { border:1px solid #ccd; background:#f5f6f8; border-radius:4px; padding:6px 10px; cursor:pointer; }
.legal-toolbar button:hover { background:#e8eaee; }
.legal-editor { border:1px solid #ccd; border-radius:4px; padding:12px; min-height:140px; background:#fff; line-height:1.6; }
.legal-editor h4 { margin:12px 0 6px; }
.legal-editor p { margin:0 0 10px; }
</style>

<script>
(function () {
    var box = document.getElementById('sections');

    function syncSource(section) {
        var ed = section.querySelector('.legal-editor');
        var src = section.querySelector('.legal-source');
        if (src.style.display === 'none') src.value = ed.innerHTML;
    }

    function newSection() {
        var tpl = box.querySelector('.legal-section');
        var node;
        if (tpl) {
            node = tpl.cloneNode(true);
            node.querySelectorAll('input').forEach(function (i) { i.value = i.name === 'icon[]' ? 'fas fa-file-alt' : ''; });
            node.querySelector('.legal-editor').innerHTML = '<p></p>';
            node.querySelector('.legal-editor').style.display = '';
            node.querySelector('.legal-source').style.display = 'none';
        }
        return node;
    }

    document.getElementById('addSection').addEventListener('click', function () {
        var n = newSection();
        if (n) { box.appendChild(n); n.querySelector('input').focus(); }
    });

    box.addEventListener('mousedown', function (e) {
        // Fokus im Editor behalten, damit Formatierungen greifen
        if (e.target.closest('.legal-toolbar button')) e.preventDefault();
    });

    box.addEventListener('click', function (e) {
        var btn = e.target.closest('.legal-toolbar button');
        if (!btn) return;
        var section = btn.closest('.legal-section');
        var ed = section.querySelector('.legal-editor');
        var src = section.querySelector('.legal-source');
        var cmd = btn.getAttribute('data-cmd');
        var htmlMode = src.style.display !== 'none';

        if (cmd === 'html') {
            if (htmlMode) { ed.innerHTML = src.value; src.style.display = 'none'; ed.style.display = ''; }
            else { src.value = ed.innerHTML; src.style.display = 'block'; ed.style.display = 'none'; }
            return;
        }
        if (cmd === 'remove') {
            if (confirm('Diesen Abschnitt wirklich löschen?')) section.remove();
            return;
        }
        if (cmd === 'up' && section.previousElementSibling) { box.insertBefore(section, section.previousElementSibling); return; }
        if (cmd === 'down' && section.nextElementSibling) { box.insertBefore(section.nextElementSibling, section); return; }
        if (htmlMode) return;

        ed.focus();
        if (cmd === 'link') {
            var url = prompt('Link-Adresse (z.B. https://... oder mailto:...)');
            if (url) document.execCommand('createLink', false, url);
        } else if (cmd === 'h4' || cmd === 'p') {
            document.execCommand('formatBlock', false, cmd);
        } else {
            document.execCommand(cmd, false, null);
        }
    });

    document.getElementById('legalForm').addEventListener('submit', function () {
        box.querySelectorAll('.legal-section').forEach(function (section) {
            var src = section.querySelector('.legal-source');
            if (src.style.display === 'none') src.value = section.querySelector('.legal-editor').innerHTML;
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
