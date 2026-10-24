<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getLanguage()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_title); ?> - FF Reichenau</title>
    <meta name="description" content="<?php echo htmlspecialchars(t('meta.description')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Barlow+Condensed:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if (in_array($current_page ?? '', ['schutzbereich', 'kontakt'], true)): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/style.css') ?: time(); ?>">
</head>
<body class="page-<?php echo htmlspecialchars($current_page ?? ""); ?>">
<?php
require_once __DIR__ . '/../config/site-alert.php';
$siteAlert = getActiveSiteAlert();
?>
<?php if ($siteAlert !== null):
    $siteAlertTypes = getSiteAlertTypes();
    $siteAlertIcon = $siteAlertTypes[$siteAlert['type']]['icon'] ?? 'fa-triangle-exclamation';
    $siteAlertLabel = $siteAlertTypes[$siteAlert['type']]['label'] ?? 'Hinweis';
?>
<div class="site-alert-overlay" id="siteAlertBanner" hidden>
    <div class="site-alert-overlay-box" style="border-color: <?php echo htmlspecialchars($siteAlert['color']); ?>;">
        <i class="fas <?php echo htmlspecialchars($siteAlertIcon); ?> site-alert-overlay-icon" style="color: <?php echo htmlspecialchars($siteAlert['color']); ?>;"></i>
        <strong class="site-alert-overlay-title"><?php echo htmlspecialchars($siteAlertLabel); ?><?php echo $siteAlert['location'] !== '' ? ' - ' . htmlspecialchars($siteAlert['location']) : ''; ?></strong>
        <?php if ($siteAlert['text'] !== ''): ?>
            <p class="site-alert-overlay-text"><?php echo htmlspecialchars($siteAlert['text']); ?></p>
        <?php endif; ?>
        <?php if ($siteAlert['area'] !== ''): ?>
            <p class="site-alert-overlay-area"><i class="fas fa-ban"></i> Bitte meiden: <strong><?php echo htmlspecialchars($siteAlert['area']); ?></strong></p>
        <?php endif; ?>
        <?php
        $siteAlertTipLines = array_values(array_filter(array_map('trim', explode("\n", (string) ($siteAlert['tips'] ?? '')))));
        ?>
        <?php if (!empty($siteAlertTipLines)): ?>
        <div class="site-alert-overlay-tips">
            <strong><i class="fas fa-circle-info"></i> Verhaltenstipps:</strong>
            <ul>
                <?php foreach ($siteAlertTipLines as $tip): ?>
                    <li><?php echo htmlspecialchars(ltrim($tip, '-• ')); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <button type="button" class="btn btn-primary site-alert-overlay-close" id="siteAlertBannerClose">
            <i class="fas fa-check"></i> Verstanden
        </button>
    </div>
</div>

<!-- Kleine, bleibende Markierung ganz links, erscheint nach dem Bestätigen
     des Vollbild-Hinweises - Klick öffnet den Hinweis erneut -->
<button type="button" class="site-alert-chip" id="siteAlertChip" style="border-color: <?php echo htmlspecialchars($siteAlert['color']); ?>; color: <?php echo htmlspecialchars($siteAlert['color']); ?>;" hidden aria-label="Warnhinweis erneut anzeigen">
    <i class="fas <?php echo htmlspecialchars($siteAlertIcon); ?>"></i>
</button>

<script>
(function () {
    var overlay = document.getElementById('siteAlertBanner');
    var closeBtn = document.getElementById('siteAlertBannerClose');
    var chip = document.getElementById('siteAlertChip');
    if (!overlay || !closeBtn || !chip) return;
    var DISMISS_KEY = 'site_alert_dismissed_' + <?php echo json_encode(md5($siteAlert['type'] . '|' . $siteAlert['color'] . '|' . $siteAlert['location'] . '|' . $siteAlert['area'] . '|' . $siteAlert['text'])); ?>;

    function showOverlay() {
        overlay.hidden = false;
        chip.hidden = true;
        document.body.classList.add('site-alert-open');
    }
    function showChip() {
        overlay.hidden = true;
        chip.hidden = false;
        document.body.classList.remove('site-alert-open');
    }

    var dismissed = false;
    try { dismissed = sessionStorage.getItem(DISMISS_KEY) === '1'; } catch (e) {}
    if (dismissed) {
        showChip();
    } else {
        showOverlay();
    }

    closeBtn.addEventListener('click', function () {
        showChip();
        try { sessionStorage.setItem(DISMISS_KEY, '1'); } catch (e) {}
    });
    chip.addEventListener('click', showOverlay);
})();
</script>
<?php endif; ?>
