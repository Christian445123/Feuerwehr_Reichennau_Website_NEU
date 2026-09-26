    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php?page=home" class="nav-logo">
                <img src="assets/images/logo_feuerwehr_tirol.png" alt="Freiwillige Feuerwehr Reichenau" class="nav-logo-img">
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Navigation öffnen">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <ul class="nav-menu" id="navMenu">
                <li>
                    <a href="index.php?page=berichte" class="nav-link <?php echo $current_page === 'berichte' ? 'active' : ''; ?>"><i class="fas fa-newspaper"></i> Aktuelles</a>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo $current_page === 'ueber-uns' ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-users"></i> Über Uns <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=ueber-uns#mannschaft">Mannschaft</a></li>
                        <li><a href="index.php?page=ueber-uns#kommando">Ausschuss</a></li>
                        <li><a href="index.php?page=ueber-uns#geschichte">Geschichte</a></li>
                        <li><a href="index.php?page=ueber-uns#schutzbereich">Schutzbereich</a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo in_array($current_page, ['feuer', 'technik', 'gefahrgut'], true) ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-triangle-exclamation"></i> Einsatzbereiche <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=feuer">Feuer</a></li>
                        <li><a href="index.php?page=technik">Technik</a></li>
                        <li><a href="index.php?page=gefahrgut">Gefahrgut</a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo in_array($current_page, ['fuhrpark', 'geraetehaus'], true) ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-fire-extinguisher"></i> Ausrüstung <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=geraetehaus">Gerätehaus</a></li>
                        <li><a href="index.php?page=fuhrpark">Fuhrpark</a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo $current_page === 'jugend' ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-child"></i> Jugend <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=jugend#aktivitaeten">Aktivitäten</a></li>
                        <li><a href="index.php?page=jugend#machmit">Mach mit!</a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <span class="nav-link nav-label <?php echo in_array($current_page, ['kontakt', 'alarmierungen', 'sicherheitstipps'], true) ? 'active' : ''; ?>" tabindex="0"><i class="fas fa-concierge-bell"></i> Service <i class="fas fa-chevron-down dropdown-arrow"></i></span>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=kontakt">Kontakt</a></li>
                        <li><a href="index.php?page=alarmierungen">Alarmierungen</a></li>
                        <li><a href="index.php?page=sicherheitstipps">Sicherheitstipps</a></li>
                    </ul>
                </li>
                <li><a href="index.php?page=mitmachen" class="nav-link nav-cta">Mitmachen</a></li>
            </ul>
        </div>
    </nav>
