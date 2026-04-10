    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php?page=home" class="nav-logo">
                <img src="assets/images/logo.png" alt="FF Reichenau Logo" class="nav-logo-img">
                <div class="nav-logo-text">
                    <span class="nav-logo-title">FF Reichenau</span>
                    <span class="nav-logo-subtitle">Innsbruck Stadt</span>
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
                    <a href="index.php?page=ueber-uns" class="nav-link <?php echo $current_page === 'ueber-uns' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Über Uns <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=ueber-uns#kommando">Kommando</a></li>
                        <li><a href="index.php?page=ueber-uns#ausschuss">Ausschuss</a></li>
                        <li><a href="index.php?page=ueber-uns#mannschaft">Mannschaft</a></li>
                        <li><a href="index.php?page=ueber-uns#ehrenmitglieder">Ehrenmitglieder</a></li>
                        <li><a href="index.php?page=ueber-uns#geschichte">Geschichte</a></li>
                        <li><a href="index.php?page=ueber-uns#schutzbereich">Schutzbereich</a></li>
                    </ul>
                </li>
                <li><a href="index.php?page=ausruestung" class="nav-link <?php echo $current_page === 'ausruestung' ? 'active' : ''; ?>"><i class="fas fa-fire-extinguisher"></i> Ausrüstung</a></li>
                <li><a href="index.php?page=jugend" class="nav-link <?php echo $current_page === 'jugend' ? 'active' : ''; ?>"><i class="fas fa-child"></i> Jugend</a></li>
                <li class="nav-dropdown">
                    <a href="index.php?page=berichte" class="nav-link <?php echo $current_page === 'berichte' ? 'active' : ''; ?>"><i class="fas fa-newspaper"></i> Berichte <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?page=berichte#einsatz">Einsatz</a></li>
                        <li><a href="index.php?page=berichte#uebung">Übung</a></li>
                        <li><a href="index.php?page=berichte#jugend">Jugend</a></li>
                        <li><a href="index.php?page=berichte#sonstige">Sonstige</a></li>
                        <li><a href="index.php?page=berichte#archiv">Archiv</a></li>
                    </ul>
                </li>
                <li><a href="index.php?page=termine" class="nav-link <?php echo $current_page === 'termine' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Termine</a></li>
                <li><a href="index.php?page=kontakt" class="nav-link <?php echo $current_page === 'kontakt' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Kontakt</a></li>
                <li><a href="http://facebook.ffr.at/" class="nav-link nav-social" target="_blank" rel="noopener"><i class="fab fa-facebook"></i></a></li>
            </ul>
        </div>
    </nav>
