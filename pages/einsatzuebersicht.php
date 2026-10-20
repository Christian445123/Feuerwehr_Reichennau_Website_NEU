<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/einsatzlog.php';
$db = getDB();

$types = getEinsatzlogTypes();
$stats = getEinsatzlogStats($db);
$years = getEinsatzlogYears($db);

$filterYear = null;
if (isset($_GET['jahr']) && $_GET['jahr'] !== '' && in_array((int) $_GET['jahr'], $years, true)) {
    $filterYear = (int) $_GET['jahr'];
}

$entries = getEinsatzlogEntries($db, $filterYear);
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Einsatzübersicht</h1>
            <p class="page-subtitle">Jede Alarmierung und Übung der FF Reichenau im Überblick</p>
        </div>
    </section>

    <section class="section">
        <div class="container">

            <div class="stats-grid" style="margin-bottom:50px;">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bell"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['alarmierungen']; ?>"><?php echo $stats['alarmierungen']; ?></div>
                    <div class="stat-label">Alarmierungen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dumbbell"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['uebungen']; ?>"><?php echo $stats['uebungen']; ?></div>
                    <div class="stat-label">Übungen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number" data-count="<?php echo $stats['stunden']; ?>"><?php echo $stats['stunden']; ?></div>
                    <div class="stat-label">Geleistete Stunden</div>
                </div>
            </div>

            <?php if (!empty($years)): ?>
            <div class="filter-tabs">
                <a href="index.php?page=einsatzuebersicht" class="filter-tab <?php echo $filterYear === null ? 'active' : ''; ?>">Alle Jahre</a>
                <?php foreach ($years as $y): ?>
                    <a href="index.php?page=einsatzuebersicht&jahr=<?php echo $y; ?>" class="filter-tab <?php echo $filterYear === (int) $y ? 'active' : ''; ?>"><?php echo $y; ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($entries)): ?>
                <p class="text-muted-public"><em>Noch keine Einträge vorhanden.</em></p>
            <?php else: ?>
                <div class="einsatzlog-table-wrap">
                    <table class="einsatzlog-table">
                        <thead>
                            <tr>
                                <th>Nr.</th>
                                <th>Alarmzeit</th>
                                <th>Einsatztext</th>
                                <th>Straße</th>
                                <th>Ort</th>
                                <th>Kurzbericht</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $en): ?>
                                <tr>
                                    <td><?php echo (int) $en['nr']; ?></td>
                                    <td><?php echo date('d.m.Y, H:i', strtotime($en['alarmzeit'])); ?></td>
                                    <td>
                                        <span class="einsatzlog-typ-dot einsatzlog-typ-<?php echo htmlspecialchars($en['typ']); ?>" title="<?php echo htmlspecialchars($types[$en['typ']] ?? ''); ?>"></span>
                                        <?php echo htmlspecialchars($en['einsatztext']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($en['strasse']); ?></td>
                                    <td><?php echo htmlspecialchars($en['ort']); ?></td>
                                    <td>
                                        <?php if (!empty($en['report_id'])): ?>
                                            <a href="index.php?page=berichte&id=<?php echo (int) $en['report_id']; ?>">Zum ausführlichen Bericht</a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($en['kurzbericht']); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </section>
