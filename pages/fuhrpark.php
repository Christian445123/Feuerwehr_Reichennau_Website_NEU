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
            <h1 class="page-title"><?php echo e(t('nav.fuhrpark')); ?></h1>
            <p class="page-subtitle"><?php echo e(t('fuhrpark.subtitle')); ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="content-grid">


                <!-- Fuhrpark -->
                <div class="content-card" id="fuhrpark">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-truck"></i></div>
                        <h2><?php echo e(t('nav.fuhrpark')); ?></h2>
                    </div>
                    <div class="content-card-body">
                        <p><?php echo e(t('fuhrpark.intro')); ?></p>

                        <div class="vehicle-grid">

                            <!-- Transportfahrzeug TF -->
                            <div class="vehicle-card" id="tf">
                                <?php renderVehicleImages($vehiclePhotos['tf'] ?? [], t('fuhrpark.tf.name')); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-shuttle-van"></i> <?php echo e(t('fuhrpark.tf.heading')); ?></h3>
                                    <table class="vehicle-specs">
                                        <tr><th><?php echo e(t('fuhrpark.spec.marke')); ?></th><td>Mercedes, Vito</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.kennzeichen')); ?></th><td>I-422IBK (A)</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.leistung')); ?></th><td>80 kW / 110 PS</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.baujahr')); ?></th><td>2010</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.aufbau')); ?></th><td><?php echo e(t('fuhrpark.aufbau.eigen_bf')); ?></td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.funkrufname')); ?></th><td>TF Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> <?php echo e(t('fuhrpark.equipment_heading')); ?></h4>
                                        <p><?php echo e(t('fuhrpark.tf.equipment')); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tanklöschfahrzeug TLFH -->
                            <div class="vehicle-card" id="tlfh">
                                <?php renderVehicleImages($vehiclePhotos['tlfh'] ?? [], t('fuhrpark.tlfh.name')); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-fire-extinguisher"></i> <?php echo e(t('fuhrpark.tlfh.heading')); ?></h3>
                                    <table class="vehicle-specs">
                                        <tr><th><?php echo e(t('fuhrpark.spec.marke')); ?></th><td>Scania</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.kennzeichen')); ?></th><td>I-501IBK (A)</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.leistung')); ?></th><td>250 kW / 340 PS</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.baujahr')); ?></th><td>2003</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.aufbau')); ?></th><td>EMPL</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.funkrufname')); ?></th><td>Tank Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> <?php echo e(t('fuhrpark.equipment_heading')); ?></h4>
                                        <p><?php echo e(t('fuhrpark.tlfh.equipment')); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Kleinlöschfahrzeug Allrad KLF-A -->
                            <div class="vehicle-card" id="klf-a">
                                <?php renderVehicleImages($vehiclePhotos['klf_a'] ?? [], t('fuhrpark.klf_a.name')); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-truck-pickup"></i> <?php echo e(t('fuhrpark.klf_a.heading')); ?></h3>
                                    <table class="vehicle-specs">
                                        <tr><th><?php echo e(t('fuhrpark.spec.marke')); ?></th><td>Mercedes, Sprinter</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.kennzeichen')); ?></th><td>I-543IBK (A)</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.leistung')); ?></th><td>115 kW / 156 PS</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.baujahr')); ?></th><td>2005</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.aufbau')); ?></th><td>EMPL</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.funkrufname')); ?></th><td>KLF Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> <?php echo e(t('fuhrpark.equipment_heading')); ?></h4>
                                        <p><?php echo e(t('fuhrpark.klf_a.equipment')); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Transportfahrzeug LAST1 -->
                            <div class="vehicle-card" id="last1">
                                <?php renderVehicleImages($vehiclePhotos['last1'] ?? [], t('fuhrpark.last1.name')); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-truck-moving"></i> <?php echo e(t('fuhrpark.last1.heading')); ?></h3>
                                    <table class="vehicle-specs">
                                        <tr><th><?php echo e(t('fuhrpark.spec.marke')); ?></th><td>Ford, Ranger</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.kennzeichen')); ?></th><td>I-537IBK (A)</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.leistung')); ?></th><td>115 kW / 156 PS</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.baujahr')); ?></th><td>2009</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.aufbau')); ?></th><td><?php echo e(t('fuhrpark.aufbau.eigen')); ?></td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.funkrufname')); ?></th><td>LAST1 Reichenau</td></tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Gefahrgutfahrzeug GGF -->
                            <div class="vehicle-card" id="ggf">
                                <?php renderVehicleImages($vehiclePhotos['ggf'] ?? [], t('fuhrpark.ggf.name')); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-biohazard"></i> <?php echo e(t('fuhrpark.ggf.heading')); ?></h3>
                                    <table class="vehicle-specs">
                                        <tr><th><?php echo e(t('fuhrpark.spec.marke')); ?></th><td>Mercedes, Atego Blue Tac</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.kennzeichen')); ?></th><td>I-463IBK (A)</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.leistung')); ?></th><td>160 kW / 220 PS</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.baujahr')); ?></th><td>2011</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.aufbau')); ?></th><td>EMPL</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.funkrufname')); ?></th><td>GGF Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> <?php echo e(t('fuhrpark.equipment_heading')); ?></h4>
                                        <p><?php echo e(t('fuhrpark.ggf.equipment')); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Großpumpenhänger -->
                            <div class="vehicle-card" id="grosspumpe">
                                <?php renderVehicleImages($vehiclePhotos['grosspumpe'] ?? [], t('fuhrpark.grosspumpe.name')); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-water"></i> <?php echo e(t('fuhrpark.grosspumpe.heading')); ?></h3>
                                    <table class="vehicle-specs">
                                        <tr><th><?php echo e(t('fuhrpark.spec.marke')); ?></th><td><?php echo e(t('fuhrpark.spec.anhaenger_value')); ?></td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.kennzeichen')); ?></th><td>I-574IBK (A)</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.foerdermenge')); ?></th><td><?php echo e(t('fuhrpark.grosspumpe.foerdermenge_value')); ?></td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.baujahr')); ?></th><td>2007</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.aufbau')); ?></th><td><?php echo e(t('fuhrpark.aufbau.eigen_bf')); ?></td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.funkrufname')); ?></th><td>Großpumpe Reichenau</td></tr>
                                    </table>
                                    <div class="vehicle-equipment">
                                        <h4><i class="fas fa-toolbox"></i> <?php echo e(t('fuhrpark.equipment_heading')); ?></h4>
                                        <p><?php echo e(t('fuhrpark.grosspumpe.equipment')); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Anhänger leicht -->
                            <div class="vehicle-card" id="anhaenger">
                                <?php renderVehicleImages($vehiclePhotos['anhaenger'] ?? [], t('fuhrpark.anhaenger.name')); ?>
                                <div class="vehicle-info">
                                    <h3><i class="fas fa-trailer"></i> <?php echo e(t('fuhrpark.anhaenger.heading')); ?></h3>
                                    <p><?php echo e(t('fuhrpark.anhaenger.description')); ?></p>
                                    <table class="vehicle-specs">
                                        <tr><th><?php echo e(t('fuhrpark.spec.baujahr')); ?></th><td>2005</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.eigengewicht')); ?></th><td>380 kg</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.nutzlast')); ?></th><td>370 kg</td></tr>
                                        <tr><th><?php echo e(t('fuhrpark.spec.gesamtgewicht')); ?></th><td>750 kg</td></tr>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
