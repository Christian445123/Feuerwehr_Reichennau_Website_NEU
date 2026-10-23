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
<div class="site-alert-banner" id="siteAlertBanner" style="border-color: <?php echo htmlspecialchars($siteAlert['color']); ?>;">
    <div class="site-alert-banner-inner">
        <i class="fas <?php echo htmlspecialchars($siteAlertIcon); ?> site-alert-banner-icon" style="color: <?php echo htmlspecialchars($siteAlert['color']); ?>;"></i>
        <div class="site-alert-banner-text">
            <strong><?php echo htmlspecialchars($siteAlertLabel); ?><?php echo $siteAlert['area'] !== '' ? ': ' . htmlspecialchars($siteAlert['area']) : ''; ?></strong>
            <?php if ($siteAlert['text'] !== ''): ?>
                <span><?php echo htmlspecialchars($siteAlert['text']); ?></span>
            <?php endif; ?>
        </div>
        <button type="button" class="site-alert-banner-close" id="siteAlertBannerClose" aria-label="Hinweis schließen">&times;</button>
    </div>
</div>
<script>
(function () {
    var banner = document.getElementById('siteAlertBanner');
    var closeBtn = document.getElementById('siteAlertBannerClose');
    if (!banner || !closeBtn) return;
    var DISMISS_KEY = 'site_alert_dismissed_' + <?php echo json_encode(md5($siteAlert['type'] . '|' . $siteAlert['color'] . '|' . $siteAlert['area'] . '|' . $siteAlert['text'])); ?>;
    var dismissed = false;
    try { dismissed = sessionStorage.getItem(DISMISS_KEY) === '1'; } catch (e) {}
    if (dismissed) {
        banner.style.display = 'none';
    } else {
        document.body.classList.add('has-site-alert-banner');
    }
    closeBtn.addEventListener('click', function () {
        banner.style.display = 'none';
        document.body.classList.remove('has-site-alert-banner');
        try { sessionStorage.setItem(DISMISS_KEY, '1'); } catch (e) {}
    });
})();
</script>
<?php endif; ?>
