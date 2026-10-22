    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php?page=home" class="nav-logo">
                <img src="assets/images/logo_feuerwehr_tirol.png" alt="Freiwillige Feuerwehr Reichenau" class="nav-logo-img">
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="<?php echo e(t('nav.open')); ?>">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <ul class="nav-menu" id="navMenu">
                <li>
                    <a href="index.php?page=berichte" class="nav-link <?php echo $current_page === 'berichte' ? 'active' : ''; ?>"><i class="fas fa-newspaper"></i> <?php echo e(t('nav.aktuelles')); ?></a>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo in_array($current_page, ['mannschaft', 'ausschuss', 'geschichte', 'schutzbereich'], true) ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-users"></i> <?php echo e(t('nav.ueber_uns')); ?> <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=mannschaft"><?php echo e(t('nav.mannschaft')); ?></a></li>
                        <li><a href="index.php?page=ausschuss"><?php echo e(t('nav.ausschuss')); ?></a></li>
                        <li><a href="index.php?page=geschichte"><?php echo e(t('nav.geschichte')); ?></a></li>
                        <li><a href="index.php?page=schutzbereich"><?php echo e(t('nav.schutzbereich')); ?></a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo in_array($current_page, ['feuer', 'technik', 'gefahrgut'], true) ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-triangle-exclamation"></i> <?php echo e(t('nav.einsatzbereiche')); ?> <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=feuer"><?php echo e(t('nav.feuer')); ?></a></li>
                        <li><a href="index.php?page=technik"><?php echo e(t('nav.technik')); ?></a></li>
                        <li><a href="index.php?page=gefahrgut"><?php echo e(t('nav.gefahrgut')); ?></a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo in_array($current_page, ['fuhrpark', 'geraetehaus'], true) ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-fire-extinguisher"></i> <?php echo e(t('nav.ausruestung')); ?> <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=geraetehaus"><?php echo e(t('nav.geraetehaus')); ?></a></li>
                        <li><a href="index.php?page=fuhrpark"><?php echo e(t('nav.fuhrpark')); ?></a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo $current_page === 'jugend' ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-child"></i> <?php echo e(t('nav.jugend')); ?> <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=jugend#aktivitaeten"><?php echo e(t('nav.jugend_aktivitaeten')); ?></a></li>
                        <li><a href="index.php?page=jugend#machmit"><?php echo e(t('nav.jugend_machmit')); ?></a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo in_array($current_page, ['kontakt', 'alarmierungen', 'sicherheitstipps'], true) ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-concierge-bell"></i> <?php echo e(t('nav.service')); ?> <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=kontakt"><?php echo e(t('nav.kontakt')); ?></a></li>
                        <li><a href="index.php?page=alarmierungen"><?php echo e(t('nav.alarmierungen')); ?></a></li>
                        <li><a href="index.php?page=sicherheitstipps"><?php echo e(t('nav.sicherheitstipps')); ?></a></li>
                    </ul>
                </li>
                <li><a href="index.php?page=mitmachen" class="nav-link nav-cta"><?php echo e(t('nav.mitmachen')); ?></a></li>
                <li class="nav-lang-switch">
                    <a href="<?php echo e(languageSwitchUrl('de')); ?>" class="nav-lang-link <?php echo !isEnglish() ? 'active' : ''; ?>">DE</a>
                    <span class="nav-lang-sep">/</span>
                    <a href="<?php echo e(languageSwitchUrl('en')); ?>" class="nav-lang-link <?php echo isEnglish() ? 'active' : ''; ?>">EN</a>
                </li>
            </ul>
        </div>
    </nav>
