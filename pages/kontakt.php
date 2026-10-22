<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/logging.php';
require_once __DIR__ . '/../config/schutzbereich.php';

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

    $db = getDB();
    if (!checkRateLimit($db, 'contact_form', 5, 3600)) {
        $formErrors[] = t('kontakt.error.rate_limit');
    }

    $csrfOk = hash_equals($_SESSION['kontakt_csrf'], $_POST['csrf_token'] ?? '');
    $honeypotOk = ($_POST['website'] ?? '') === ''; // Anti-Spam-Feld, für Menschen unsichtbar

    if (!$honeypotOk) {
        // Das Honeypot-Feld füllt nur automatisierter Spam aus - ein echter
        // Besucher sieht es nie. Fürs Protokoll festhalten, damit man im
        // Gesamtprotokoll sieht, wie oft Bots abgefangen werden.
        logActivity($db, 'public.honeypot_catch', 'Kontaktformular', null, 'Öffentliche Website');
    }
    if (!$csrfOk || !$honeypotOk) {
        $formErrors[] = t('kontakt.error.invalid_request');
    }
    if ($formData['vorname'] === '' || $formData['nachname'] === '') {
        $formErrors[] = t('kontakt.error.name_required');
    }
    if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors[] = t('kontakt.error.email_invalid');
    }
    if ($formData['nachricht'] === '') {
        $formErrors[] = t('kontakt.error.message_required');
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
            logActivity($db, 'public.kontakt_sent', $formData['vorname'] . ' ' . $formData['nachname'] . ' <' . $formData['email'] . '>', null, 'Öffentliche Website');
            header('Location: index.php?page=kontakt&sent=1#kontaktformular');
            exit;
        }
        $formErrors[] = t('kontakt.error.send_failed');
    }
}

$formSent = isset($_GET['sent']);
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title"><?php echo e(t('kontakt.title')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('kontakt.subtitle')); ?></p>
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
                            <h2><?php echo e(t('kontakt.data_heading')); ?></h2>
                        </div>
                        <div class="content-card-body">
                            <div class="kontakt-list">
                                <div class="kontakt-item">
                                    <i class="fas fa-building"></i>
                                    <div>
                                        <strong><?php echo e(t('footer.org_name')); ?></strong><br>
                                        <?php echo e(t('footer.org_location')); ?>
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
                                    <div><a href="http://www.ff-reichennau.at">www.ff-reichennau.at</a></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mitmachen -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon"><i class="fas fa-hands-helping"></i></div>
                            <h2><?php echo e(t('nav.mitmachen')); ?></h2>
                        </div>
                        <div class="content-card-body">
                            <h4><i class="fas fa-child"></i> <?php echo e(t('mannschaft.jugendfeuerwehr')); ?></h4>
                            <p><?php echo e(t('kontakt.jugend_p1')); ?></p>
                            <p><?php echo e(t('kontakt.jugend_p2')); ?></p>
                            <p><strong><?php echo e(t('kontakt.age_label')); ?></strong> <?php echo e(t('kontakt.jugend_age')); ?></p>

                            <hr>

                            <h4><i class="fas fa-hard-hat"></i> <?php echo e(t('termine.mannschaft_label')); ?></h4>
                            <p><?php echo e(t('kontakt.mannschaft_p1')); ?></p>
                            <p><?php echo e(t('kontakt.mannschaft_p2')); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Karte / Schutzgebiet -->
                <div class="kontakt-map">
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon"><i class="fas fa-location-dot"></i></div>
                            <h2><?php echo e(t('kontakt.location_heading')); ?></h2>
                        </div>
                        <div class="content-card-body">
                            <div id="kontakt-standort-map" class="kontakt-standort-map" role="img" aria-label="<?php echo e(t('kontakt.location_map_aria')); ?>"></div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon"><i class="fas fa-map"></i></div>
                            <h2><?php echo e(t('kontakt.protection_area_heading')); ?></h2>
                        </div>
                        <div class="content-card-body">
                            <div id="kontakt-schutzgebiet-map" class="kontakt-schutzgebiet-map" role="img" aria-label="<?php echo e(t('schutzbereich.map_aria_label')); ?>"></div>
                            <p class="schutzgebiet-map-hint">
                                <a href="https://www.google.com/maps/search/?api=1&query=Freiwillige+Feuerwehr+Reichenau+Ro%C3%9Faugasse+4+Innsbruck" target="_blank" rel="noopener"><?php echo e(t('kontakt.open_google_maps')); ?> <i class="fas fa-external-link-alt"></i></a>
                            </p>
                        </div>
                    </div>

                    <!-- Bankverbindung -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon"><i class="fas fa-university"></i></div>
                            <h2><?php echo e(t('kontakt.bank_heading')); ?></h2>
                        </div>
                        <div class="content-card-body">
                            <p><strong>HYPO TIROL Bank</strong><br>
                            IBAN: AT33 5700 0002 3004 4140</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Impressum -->
            <?php if (isEnglish()): ?>
                <p class="form-alert" style="background: rgba(243,156,18,0.1); border: 1px solid rgba(243,156,18,0.3); border-radius: var(--radius); padding: 14px 18px; margin-top: 24px;"><i class="fas fa-circle-info"></i> <?php echo e(t('kontakt.impressum_legal_language_notice')); ?></p>
            <?php endif; ?>
            <?php
            require_once __DIR__ . '/../config/legal.php';
            $legalDb = getDB();
            renderLegalSections(getLegalSections($legalDb, 'impressum'), 'impressum-section');
            renderLegalSections(getLegalSections($legalDb, 'hinweise'), 'impressum-section');
            ?>

        </div>
    </section>

    <section class="section" id="kontaktformular">
        <div class="container">
            <div class="content-card kontakt-form-card">
                <div class="content-card-header">
                    <div class="content-card-icon"><i class="fas fa-paper-plane"></i></div>
                    <h2><?php echo e(t('kontakt.form_heading')); ?></h2>
                </div>
                <div class="content-card-body">
                    <?php if ($formSent): ?>
                        <div class="form-alert form-alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo e(t('kontakt.form_success')); ?>
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
                            <label for="anrede"><?php echo e(t('kontakt.label_anrede')); ?></label>
                            <select id="anrede" name="anrede">
                                <option value="" <?php echo $formData['anrede'] === '' ? 'selected' : ''; ?>><?php echo e(t('kontakt.please_select')); ?></option>
                                <option value="Frau" <?php echo $formData['anrede'] === 'Frau' ? 'selected' : ''; ?>><?php echo e(t('kontakt.anrede_frau')); ?></option>
                                <option value="Herr" <?php echo $formData['anrede'] === 'Herr' ? 'selected' : ''; ?>><?php echo e(t('kontakt.anrede_herr')); ?></option>
                                <option value="Divers" <?php echo $formData['anrede'] === 'Divers' ? 'selected' : ''; ?>><?php echo e(t('kontakt.anrede_divers')); ?></option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="vorname"><?php echo e(t('jugend.label_vorname')); ?> <span class="req">(*)</span></label>
                                <input type="text" id="vorname" name="vorname" value="<?php echo htmlspecialchars($formData['vorname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="nachname"><?php echo e(t('jugend.label_nachname')); ?> <span class="req">(*)</span></label>
                                <input type="text" id="nachname" name="nachname" value="<?php echo htmlspecialchars($formData['nachname']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="strasse"><?php echo e(t('kontakt.label_strasse')); ?></label>
                                <input type="text" id="strasse" name="strasse" value="<?php echo htmlspecialchars($formData['strasse']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="ort"><?php echo e(t('kontakt.label_ort')); ?></label>
                                <input type="text" id="ort" name="ort" value="<?php echo htmlspecialchars($formData['ort']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email"><?php echo e(t('kontakt.label_email')); ?> <span class="req">(*)</span></label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="telefon"><?php echo e(t('kontakt.label_telefon')); ?></label>
                                <input type="text" id="telefon" name="telefon" value="<?php echo htmlspecialchars($formData['telefon']); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nachricht"><?php echo e(t('jugend.label_nachricht')); ?> <span class="req">(*)</span></label>
                            <textarea id="nachricht" name="nachricht" rows="6" required><?php echo htmlspecialchars($formData['nachricht']); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> <?php echo e(t('kontakt.submit')); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

<!-- Standort- und Schutzgebiets-Karte (Leaflet + OpenStreetMap). Die
     Schutzgebiets-Fläche ist dieselbe wie auf der Seite "Schutzbereich"
     (Über uns) - Koordinaten kommen aus site_settings/schutzbereich_polygon,
     siehe config/schutzbereich.php und admin/schutzbereich.php. Kartenkacheln
     laufen über tile-proxy.php, siehe Kommentar dort. -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('kontakt-standort-map');
    if (!mapEl || typeof L === 'undefined') return;

    var wacheCoords = [47.27245, 11.43098];
    var map = L.map('kontakt-standort-map', { scrollWheelZoom: false }).setView(wacheCoords, 16);

    L.tileLayer('tile-proxy.php?z={z}&x={x}&y={y}', {
        maxZoom: 19,
        attribution: <?php echo json_encode(t('schutzbereich.osm_attribution', ['link' => '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'])); ?>
    }).addTo(map);

    L.marker(wacheCoords).addTo(map)
        .bindPopup('<strong><?php echo e(t('schutzbereich.station_name')); ?></strong><br>Rossaugasse 4, 6020 Innsbruck')
        .openPopup();

    mapEl.addEventListener('click', function () {
        map.scrollWheelZoom.enable();
    });
    mapEl.addEventListener('mouseleave', function () {
        map.scrollWheelZoom.disable();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('kontakt-schutzgebiet-map');
    if (!mapEl || typeof L === 'undefined') return;

    var schutzbereichCoords = <?php echo json_encode(getSchutzbereichPolygon()); ?>;

    var map = L.map('kontakt-schutzgebiet-map', { scrollWheelZoom: false });

    L.tileLayer('tile-proxy.php?z={z}&x={x}&y={y}', {
        maxZoom: 19,
        attribution: <?php echo json_encode(t('schutzbereich.osm_attribution', ['link' => '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'])); ?>
    }).addTo(map);

    var schutzbereichPolygon = L.polygon(schutzbereichCoords, {
        color: '#e4002b',
        weight: 3,
        fillColor: '#e4002b',
        fillOpacity: 0.15
    }).addTo(map);

    map.fitBounds(schutzbereichPolygon.getBounds(), { padding: [10, 10] });

    mapEl.addEventListener('click', function () {
        map.scrollWheelZoom.enable();
    });
    mapEl.addEventListener('mouseleave', function () {
        map.scrollWheelZoom.disable();
    });
});
</script>
