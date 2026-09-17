<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/media.php';
$db = getDB();

$vehiclePhotos = getAllVehiclePhotos($db);
$wacheSlots = [];
foreach (getAllMediaSlots($db) as $slot) {
    $wacheSlots[$slot['slot_key']] = $slot;
}

/**
 * Rendert das Bild-Slider-Markup (Hauptbild + Vorschaubilder) für ein
 * Fahrzeug. Fotos kommen aus der Datenbank (Admin -> Fahrzeug- & Wache-
 * Fotos) statt fest im Code zu stehen, damit sie austauschbar sind.
 */
function renderVehicleImages(array $photos, string $vehicleName): void {
    if (empty($photos)) {
        return;
    }
    $paths = array_map(fn($p) => $p['photo_path'], $photos);
    ?>
    <div class="vehicle-image-slider">
        <img src="<?php echo htmlspecialchars($photos[0]['photo_path']); ?>" alt="<?php echo htmlspecialchars($vehicleName); ?>" class="vehicle-main-img" data-lightbox-images='<?php echo json_encode($paths); ?>'>
        <?php if (count($photos) > 1): ?>
            <div class="vehicle-thumbs">
                <?php foreach ($photos as $i => $p): ?>
                    <img src="<?php echo htmlspecialchars($p['photo_path']); ?>" alt="<?php echo htmlspecialchars($vehicleName); ?>" class="vehicle-thumb<?php echo $i === 0 ? ' active' : ''; ?>" onclick="switchVehicleImg(this)">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Ausrüstung</h1>
            <p class="page-subtitle">Fuhrpark und Wache der FF Reichenau</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="content-grid">

                <!-- Fuhrpark -->
                <div class="content-card" id="fuhrpark">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-truck"></i></div>
                        <h2>Fuhrpark</h2>
                    </div>
                    <div class="content-card-body">
                        <p>Unser Fuhrpark umfasst 5 Einsatzfahrzeuge sowie 2 Anhänger, die regelmäßig gewartet und modernisiert werden.</p>

                        <div class="vehicle-grid">

                            <!-- Transportfahrzeug TF -->
                            <div class="vehicle-card" id="tf">
                                <?php renderVehicleImages($vehiclePhotos['tf'] ?? [], 'Transportfahrzeug TF'); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-shuttle-van"></i> Transportfahrzeug – TF</h3>
                                    <table class="vehicle-specs">
                                        <tr><th>Marke</th><td>Mercedes, Vito</td></tr>
                                        <tr><th>Kennzeichen</th><td>I-422IBK (A)</td></tr>
                                        <tr><th>Leistung</th><td>80 kW / 110 PS</td></tr>
                                        <tr><th>Baujahr</th><td>2010</td></tr>
                                        <tr><th>Aufbau</th><td>Eigenaufbau – BF Innsbruck</td></tr>
                                        <tr><th>Funkrufname</th><td>TF Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> Gerätschaften</h4>
                                        <p>Der TF ist so ausgelegt, dass es für die Heckbeladung ein Containersystem gibt. Wir haben derzeit 3 Container zum Wechseln: 1 Container mit Material für Unwettereinsätze, 1 Container mit Beleuchtungsmitteln und 1 Container ist derzeit mit A-Druckschläuchen für die Großpumpe bestückt.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tanklöschfahrzeug TLFH -->
                            <div class="vehicle-card" id="tlfh">
                                <?php renderVehicleImages($vehiclePhotos['tlfh'] ?? [], 'Tanklöschfahrzeug TLFH'); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-fire-extinguisher"></i> Tanklöschfahrzeug mit Hochdruck – TLFH</h3>
                                    <table class="vehicle-specs">
                                        <tr><th>Marke</th><td>Scania</td></tr>
                                        <tr><th>Kennzeichen</th><td>I-501IBK (A)</td></tr>
                                        <tr><th>Leistung</th><td>250 kW / 340 PS</td></tr>
                                        <tr><th>Baujahr</th><td>2003</td></tr>
                                        <tr><th>Aufbau</th><td>EMPL</td></tr>
                                        <tr><th>Funkrufname</th><td>Tank Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> Gerätschaften</h4>
                                        <p>2000 Liter Wassertank, 3 TWIN-PACK Atemschutzgeräte, Wärmebildkamera, Hochleistungslüfter, Schanzwerkzeug, Stromerzeuger 11 kV, sämtliche wasserführenden Armaturen für den Brandeinsatz und vieles mehr.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Kleinlöschfahrzeug Allrad KLF-A -->
                            <div class="vehicle-card" id="klf-a">
                                <?php renderVehicleImages($vehiclePhotos['klf_a'] ?? [], 'Kleinlöschfahrzeug Allrad KLF-A'); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-truck-pickup"></i> Kleinlöschfahrzeug Allrad – KLF-A</h3>
                                    <table class="vehicle-specs">
                                        <tr><th>Marke</th><td>Mercedes, Sprinter</td></tr>
                                        <tr><th>Kennzeichen</th><td>I-543IBK (A)</td></tr>
                                        <tr><th>Leistung</th><td>115 kW / 156 PS</td></tr>
                                        <tr><th>Baujahr</th><td>2005</td></tr>
                                        <tr><th>Aufbau</th><td>EMPL</td></tr>
                                        <tr><th>Funkrufname</th><td>KLF Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> Gerätschaften</h4>
                                        <p>3 Atemschutzgeräte, Schanzwerkzeug, Stromerzeuger 11 kV, sämtliche wasserführenden Armaturen für den Brandeinsatz und vieles mehr.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Transportfahrzeug LAST1 -->
                            <div class="vehicle-card" id="last1">
                                <?php renderVehicleImages($vehiclePhotos['last1'] ?? [], 'Transportfahrzeug LAST1'); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-truck-moving"></i> Transportfahrzeug – LAST1</h3>
                                    <table class="vehicle-specs">
                                        <tr><th>Marke</th><td>Ford, Ranger</td></tr>
                                        <tr><th>Kennzeichen</th><td>I-537IBK (A)</td></tr>
                                        <tr><th>Leistung</th><td>115 kW / 156 PS</td></tr>
                                        <tr><th>Baujahr</th><td>2009</td></tr>
                                        <tr><th>Aufbau</th><td>Eigenaufbau</td></tr>
                                        <tr><th>Funkrufname</th><td>LAST1 Reichenau</td></tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Gefahrgutfahrzeug GGF -->
                            <div class="vehicle-card" id="ggf">
                                <?php renderVehicleImages($vehiclePhotos['ggf'] ?? [], 'Gefahrgutfahrzeug GGF'); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-biohazard"></i> Gefahrgutfahrzeug – GGF</h3>
                                    <table class="vehicle-specs">
                                        <tr><th>Marke</th><td>Mercedes, Atego Blue Tac</td></tr>
                                        <tr><th>Kennzeichen</th><td>I-463IBK (A)</td></tr>
                                        <tr><th>Leistung</th><td>160 kW / 220 PS</td></tr>
                                        <tr><th>Baujahr</th><td>2011</td></tr>
                                        <tr><th>Aufbau</th><td>EMPL</td></tr>
                                        <tr><th>Funkrufname</th><td>GGF Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> Gerätschaften</h4>
                                        <p>Gehenddekostraße, Liegenddekostraße, 3 Schutzstufe III Anzüge, 6 Schutzstufe II Anzüge, 6 Einweganzüge, 1 ELRO-Schlauchquetschpumpe, 1 FLUX-Fasspumpe, Stromerzeuger 14 kV, Belüftungsgerät 230 V, Hochdruckanlage Kärcher, Stromschnellangriff, Luftschnellangriff, Öl- und Chemikalienbindemittel, 2 Faltbehälter und vieles mehr.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Großpumpenhänger -->
                            <div class="vehicle-card" id="grosspumpe">
                                <?php renderVehicleImages($vehiclePhotos['grosspumpe'] ?? [], 'Großpumpenhänger'); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-water"></i> Großpumpenhänger</h3>
                                    <table class="vehicle-specs">
                                        <tr><th>Marke</th><td>Anhänger</td></tr>
                                        <tr><th>Kennzeichen</th><td>I-574IBK (A)</td></tr>
                                        <tr><th>Fördermenge</th><td>6000 Liter pro Minute</td></tr>
                                        <tr><th>Baujahr</th><td>2007</td></tr>
                                        <tr><th>Aufbau</th><td>Eigenaufbau – BF Innsbruck</td></tr>
                                        <tr><th>Funkrufname</th><td>Großpumpe Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> Gerätschaften</h4>
                                        <p>A-Saugschläuche, A-Druckschläuche</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Anhänger leicht -->
                            <div class="vehicle-card" id="anhaenger">
                                <?php renderVehicleImages($vehiclePhotos['anhaenger'] ?? [], 'Anhänger leicht'); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-trailer"></i> Anhänger leicht – bis 750 kg</h3>
                                    <p>Der leichte Anhänger ist hauptsächlich für Materialtransport zum Einsatz oder nach einem Einsatz gedacht.</p>
                                    <table class="vehicle-specs">
                                        <tr><th>Baujahr</th><td>2005</td></tr>
                                        <tr><th>Eigengewicht</th><td>380 kg</td></tr>
                                        <tr><th>Nutzlast</th><td>370 kg</td></tr>
                                        <tr><th>Gesamtgewicht</th><td>750 kg</td></tr>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Wache -->
                <div class="content-card" id="wache">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-building"></i></div>
                        <h2>Wache</h2>
                    </div>
                    <div class="content-card-body">
                        <p>Die Wache Reichenau besteht aus einer Fahrzeughalle für die 5 Feuerwehrfahrzeuge, einen Umkleidebereich, eine Funkkabine, einem Bekleidungsraum und einem Atemschutzarbeitsplatz. Im Anschluss an den Umkleidebereich gibt es noch einen kleinen Garagenanbau wo die 2 Anhänger (Großpumpe und leichter Anhänger bis 750 kg) abgestellt sind und ein Gerätelager, wo diverse Gerätschaften gelagert werden. Seit 2013 ist diese kleine Garage mit einem Tor versehen, so dass die Geräte und Anhänger besser geschützt werden können.</p>

                        <div class="wache-gallery">
                            <?php foreach (['wache_umkleide1', 'wache_umkleide2', 'wache_ats', 'wache_funk'] as $slotKey): ?>
                                <?php if (isset($wacheSlots[$slotKey])): ?>
                                    <div class="gallery-item">
                                        <img src="<?php echo htmlspecialchars($wacheSlots[$slotKey]['photo_path']); ?>" alt="<?php echo htmlspecialchars($wacheSlots[$slotKey]['label']); ?>" data-lightbox-group="wache">
                                        <p class="gallery-caption"><?php echo htmlspecialchars($wacheSlots[$slotKey]['label']); ?></p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="address-card">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>FF Reichenau – Wache</strong><br>
                                Rossaugasse 4<br>
                                A-6020 Innsbruck
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
