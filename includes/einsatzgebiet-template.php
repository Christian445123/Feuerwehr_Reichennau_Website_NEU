<?php
/**
 * Gemeinsame Vorlage für die drei Einsatzgebiet-Seiten (Feuer, Technik,
 * Gefahrgut) - Vorbild: neo.ffr.at/feuer/, /technik/, /gefahrgut/.
 * Jede Seite ist nur ein dünner Wrapper, der renderEinsatzgebietPage() mit
 * ihren eigenen Inhalten aufruft.
 */

function renderEinsatzgebietPage(PDO $db, array $cfg): void {
    $heroImage = $cfg['heroImage'];
    $title = $cfg['title'];
    $introParagraphs = $cfg['intro'];
    $galleryFolder = $cfg['galleryFolder'];
    $galleryImages = $cfg['galleryImages'];
    $subcategory = $cfg['subcategory'];
    $subcategoryLabel = $cfg['subcategoryLabel'];

    $stmt = $db->prepare("SELECT r.*, (SELECT ri.filename FROM report_images ri WHERE ri.report_id = r.id ORDER BY ri.sort_order LIMIT 1) as thumb FROM reports r WHERE r.published = 1 AND r.subcategory = ? ORDER BY r.date DESC, r.created_at DESC LIMIT 6");
    $stmt->execute([$subcategory]);
    $reports = $stmt->fetchAll();

    $categoryLabels = ['einsatz' => 'Einsatz', 'uebung' => 'Übung', 'jugend' => 'Jugend', 'veranstaltungen' => 'Veranstaltung', 'sonstige' => 'Sonstiges'];
    ?>
    <section class="einsatzgebiet-hero" style="background-image: linear-gradient(rgba(15,15,15,0.62), rgba(15,15,15,0.62)), url('<?php echo htmlspecialchars($heroImage); ?>');">
        <div class="container">
            <p class="einsatzgebiet-eyebrow">Einsatzgebiet</p>
            <h1><?php echo htmlspecialchars($title); ?></h1>
        </div>
    </section>

    <section class="section section-dark">
        <div class="container jugend-narrow">
            <?php foreach ($introParagraphs as $p): ?>
                <p><?php echo $p; ?></p>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section section-dark" style="padding-top: 0;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Bildergalerie zum Einsatzgebiet <?php echo htmlspecialchars($title); ?></h2>
            </div>
            <div class="einsatzgebiet-gallery">
                <?php foreach ($galleryImages as $img): ?>
                    <div class="einsatzgebiet-gallery-item">
                        <img src="assets/images/einsatzgebiete/<?php echo htmlspecialchars($galleryFolder); ?>/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($title); ?>" data-lightbox-group="einsatzgebiet-<?php echo htmlspecialchars($galleryFolder); ?>" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($reports)): ?>
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Berichte zum Einsatzgebiet <?php echo htmlspecialchars($title); ?></h2>
                <p class="section-subtitle">Aktuelle Einsätze und Übungen im Bereich <?php echo htmlspecialchars($subcategoryLabel); ?></p>
            </div>
            <div class="news-grid">
                <?php foreach ($reports as $r): ?>
                    <a href="index.php?page=berichte&id=<?php echo (int) $r['id']; ?>" class="news-card">
                        <div class="news-card-media<?php echo !$r['thumb'] ? ' news-card-media-fallback' : ''; ?>">
                            <?php if ($r['thumb']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($r['thumb']); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <i class="fas fa-newspaper"></i>
                            <?php endif; ?>
                        </div>
                        <div class="news-card-tags">
                            <span class="news-card-badge news-badge-<?php echo htmlspecialchars($r['category']); ?>"><?php echo htmlspecialchars($categoryLabels[$r['category']] ?? ucfirst($r['category'])); ?></span>
                        </div>
                        <div class="news-card-overlay">
                            <h3 class="news-card-title"><?php echo htmlspecialchars($r['title']); ?></h3>
                            <div class="news-card-date"><i class="fas fa-calendar-alt"></i> <?php echo date('d.m.Y', strtotime($r['date'])); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="section-cta">
                <a href="index.php?page=berichte" class="btn btn-primary"><i class="fas fa-newspaper"></i> Alle Berichte ansehen</a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <?php
}
