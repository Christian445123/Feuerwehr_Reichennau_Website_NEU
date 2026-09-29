<?php
require_once __DIR__ . '/../config/gate.php';
requireSiteAccess();
?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 class="page-title">Über Uns</h1>
            <p class="page-subtitle">Die Freiwillige Feuerwehr Reichenau stellt sich vor</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="quick-links-grid">
                <a href="index.php?page=mannschaft" class="quick-link-card">
                    <div class="quick-link-icon"><i class="fas fa-hard-hat"></i></div>
                    <h3>Mannschaft</h3>
                    <p>Kommando, Mannschaft, Ehrenmitglieder und Jugend</p>
                </a>
                <a href="index.php?page=ausschuss" class="quick-link-card">
                    <div class="quick-link-icon"><i class="fas fa-sitemap"></i></div>
                    <h3>Ausschuss &amp; Organigramm</h3>
                    <p>Verwaltung und Struktur unserer Wehr</p>
                </a>
                <a href="index.php?page=geschichte" class="quick-link-card">
                    <div class="quick-link-icon"><i class="fas fa-landmark"></i></div>
                    <h3>Geschichte</h3>
                    <p>Von der HISTA-Gruppe zur eigenen Feuerwehr</p>
                </a>
                <a href="index.php?page=schutzbereich" class="quick-link-card">
                    <div class="quick-link-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Schutzbereich</h3>
                    <p>Unser Einsatzgebiet auf der Karte</p>
                </a>
            </div>
        </div>
    </section>
