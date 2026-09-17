<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Jugendbetreuer:in dynamisch aus der Mitgliederliste holen, damit sich die
// Kontakt-Karte automatisch aktualisiert, wenn sich die Funktion mal ändert.
$jbStmt = $db->query("SELECT * FROM members WHERE active = 1 AND functions LIKE '%Jugendbetreuer%' LIMIT 1");
$jugendbetreuerin = $jbStmt->fetch();
?>
    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Jugendfeuerwehr</h1>
            <p class="page-subtitle">Gemeinsam stark für morgen</p>
        </div>
    </section>

    <section class="section" id="aktivitaeten">
        <div class="container">
            <div class="jugend-hero">
                <div class="jugend-hero-content">
                    <h2>Lust auf Action, viel Spaß und neue Freunde?</h2>
                    <p class="lead">In der Feuerwehrjugend bist du mittendrin statt nur dabei – von 11 bis 15 Jahren!</p>
                    <p>Bei der Feuerwehrjugend Reichenau lernst du spielerisch alles rund um das Feuerwehrwesen. Während spannenden Übungen, Bewerben, Lagern und Ausflügen erwartet dich Abenteuer und jede Menge Spaß.</p>
                </div>
                <div class="jugend-hero-icon">
                    <i class="fas fa-child"></i>
                </div>
            </div>

            <img src="assets/images/jugend_gruppe.jpg" alt="Unsere Jugendfeuerwehrgruppe" class="content-image jugend-photo-large" data-lightbox-group="jugend-gruppe">

            <div class="info-cards-grid">
                <div class="info-card">
                    <div class="info-card-icon"><i class="fas fa-calendar-check"></i></div>
                    <h3>Wann?</h3>
                    <p>Wir treffen uns <strong>jeden Dienstag von 19 bis 21 Uhr</strong> in der Feuerwache Reichenau zu Übungen, Schulungen &amp; Teamspielen.</p>
                </div>

                <div class="info-card">
                    <div class="info-card-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h3>Wo?</h3>
                    <p>In unserer Feuerwehrwache in der <strong>Rossaugasse 4, 6020 Innsbruck</strong>.</p>
                </div>

                <div class="info-card">
                    <div class="info-card-icon"><i class="fas fa-graduation-cap"></i></div>
                    <h3>Was?</h3>
                    <p>Spielerisches Kennenlernen des Feuerwehrwesens, Bewerbe &amp; Wissenstests in Bronze, Silber und Gold, Erste-Hilfe-Kurse und jede Menge Teamgeist.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Verantwortung übernehmen und helfen können -->
    <section class="section section-dark">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Verantwortung übernehmen<br>und helfen können.</h2>
                <p class="section-subtitle">Möchtest du Teil unseres Teams werden? Dann komm einfach vorbei und lerne uns kennen!</p>
            </div>

            <div class="jugend-dark-grid">
                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-list-check"></i></div>
                        <h2>Das erwartet dich</h2>
                    </div>
                    <div class="content-card-body">
                        <ul class="jugend-checklist">
                            <li><i class="fas fa-check"></i> Treffen am Dienstag von 19 bis 21 Uhr in der Feuerwache Reichenau (Rossaugasse 4) mit Übungen, Schulungen &amp; Teamspielen</li>
                            <li><i class="fas fa-check"></i> Bewerbe &amp; Wissenstests (Bronze, Silber, Gold)</li>
                            <li><i class="fas fa-check"></i> Erste-Hilfe-Kurse &amp; sportliche Challenges</li>
                            <li><i class="fas fa-check"></i> Unvergessliche Erlebnisse bei Ausflügen &amp; Jugendlagern</li>
                            <li><i class="fas fa-check"></i> Ein starkes Team mit Respekt, Zusammenhalt und viel Spaß!</li>
                        </ul>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <div class="content-card-icon"><i class="fas fa-user-shield"></i></div>
                        <h2>Infos für Eltern</h2>
                    </div>
                    <div class="content-card-body">
                        <p><strong>Die Mitgliedschaft in der Feuerwehrjugend ist kostenlos.</strong> Uniform, Ausrüstung und Material werden von uns gestellt.</p>
                        <p>Die Jugendlichen sind fester Bestandteil der Freiwilligen Feuerwehr Reichenau und werden von speziell geschulten Jugendbetreuer:innen begleitet, die mit viel Herzblut für Ausbildung, Organisation und Unterstützung sorgen.</p>
                        <p><strong>Schule hat Vorrang:</strong> Wir achten darauf, dass sich die Teilnahme problemlos mit schulischen Verpflichtungen vereinbaren lässt.</p>
                        <p><strong>Mehr als nur Feuerwehr:</strong> Neben Technik und Wissen fördern wir Teamgeist, Respekt, Problemlösungsfähigkeit und Zivilcourage – Werte, die ein Leben lang begleiten.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="cta-box" id="machmit">
                <?php if ($jugendbetreuerin && $jugendbetreuerin['photo']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($jugendbetreuerin['photo']); ?>" alt="<?php echo htmlspecialchars($jugendbetreuerin['firstname'] . ' ' . $jugendbetreuerin['lastname']); ?>" class="jugend-contact-photo">
                <?php else: ?>
                    <div class="cta-icon"><i class="fas fa-envelope-open-text"></i></div>
                <?php endif; ?>
                <h3>Interesse?</h3>
                <p>
                    Du hast Fragen oder willst gleich loslegen?
                    <?php if ($jugendbetreuerin): ?>
                        Dann melde dich bei unserer Jugendbetreuerin <strong><?php echo htmlspecialchars($jugendbetreuerin['firstname'] . ' ' . $jugendbetreuerin['lastname']); ?></strong> per E-Mail:
                    <?php else: ?>
                        Dann schick uns einfach ein Mail:
                    <?php endif; ?>
                </p>
                <a href="mailto:reichenau@feuerwehr.tirol" class="btn btn-primary"><i class="fas fa-envelope"></i> reichenau@feuerwehr.tirol</a>
            </div>
        </div>
    </section>
