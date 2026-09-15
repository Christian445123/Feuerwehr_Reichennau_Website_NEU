    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php?page=home" class="nav-logo">
                <img src="assets/images/logo.png" alt="FF Reichenau Wappen" class="nav-logo-img">
                <span class="nav-logo-divider"></span>
                <div class="nav-logo-text">
                    <span class="nav-logo-line nav-logo-line-light">Freiwillige</span>
                    <span class="nav-logo-line nav-logo-line-brand">Feuerwehr</span>
                    <span class="nav-logo-line nav-logo-line-dark">Reichenau</span>
                </div>
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Navigation öffnen">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php?page=home" class="nav-link <?php echo $current_page === 'home' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Home</a></li>
                <li class="nav-dropdown">
                    <a href="index.php?page=berichte" class="nav-link <?php echo $current_page === 'berichte' ? 'active' : ''; ?>"><i class="fas fa-newspaper"></i> Aktuelles <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=berichte#einsatz">Einsatz</a></li>
                        <li><a href="index.php?page=berichte#uebung">Übung</a></li>
                        <li><a href="index.php?page=berichte#jugend">Jugend</a></li>
                        <li><a href="index.php?page=berichte#sonstige">Sonstige</a></li>
                        <li><a href="index.php?page=berichte#archiv">Archiv</a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <a href="index.php?page=ueber-uns" class="nav-link <?php echo $current_page === 'ueber-uns' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Über Uns <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=ueber-uns#mannschaft">Mannschaft</a></li>
                        <li><a href="index.php?page=ueber-uns#kommando">Ausschuss</a></li>
                        <li><a href="index.php?page=ueber-uns#geschichte">Geschichte</a></li>
                        <li><a href="index.php?page=ueber-uns#schutzbereich">Schutzbereich</a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <a href="index.php?page=ausruestung" class="nav-link <?php echo $current_page === 'ausruestung' ? 'active' : ''; ?>"><i class="fas fa-fire-extinguisher"></i> Ausrüstung <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=ausruestung#wache">Gerätehaus</a></li>
                        <li><a href="index.php?page=ausruestung#fuhrpark">Fahrzeuge</a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <a href="index.php?page=jugend" class="nav-link <?php echo $current_page === 'jugend' ? 'active' : ''; ?>"><i class="fas fa-child"></i> Jugend <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=jugend#aktivitaeten">Aktivitäten</a></li>
                        <li><a href="index.php?page=jugend#machmit">Mach mit!</a></li>
                    </ul>
                </li>
                <li class="nav-dropdown">
                    <a href="index.php?page=kontakt" class="nav-link <?php echo in_array($current_page, ['kontakt', 'termine', 'alarmierungen', 'sicherheitstipps'], true) ? 'active' : ''; ?>"><i class="fas fa-concierge-bell"></i> Service <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=kontakt">Kontakt</a></li>
                        <li><a href="index.php?page=termine">Termine</a></li>
                        <li><a href="index.php?page=alarmierungen">Alarmierungen</a></li>
                        <li><a href="index.php?page=sicherheitstipps">Sicherheitstipps</a></li>
                    </ul>
                </li>
                <li><a href="http://facebook.ffr.at/" class="nav-link nav-social" target="_blank" rel="noopener"><i class="fab fa-facebook"></i></a></li>
                <li class="nav-admin-item"><a href="admin/login.php" class="nav-link nav-admin-link"><i class="fas fa-user-shield"></i> Admin</a></li>
            </ul>
        </div>
    </nav>
