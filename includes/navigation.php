<?php
// Global navigation bar
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white role="navigation" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand" href="/pages/home.php"aria-label="Home">Musician Booking</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/pages/home.php" aria-label="Home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="/pages/about.php" aria-label="About">About</a></li>
                <li class="nav-item"><a class="nav-link" href="/pages/contact.php" aria-label="Contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="/pages/register.php">Register</a></li>
                <li class="nav-item"><a class="nav-link" href="/pages/search.php">Search</a></li>
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="/pages/logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/pages/login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>