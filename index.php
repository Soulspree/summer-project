<?php
/**
 * Homepage - Musician Booking System
 * Modern landing page for both musicians and clients
 */
// Include configuration and classes
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Gig.php';

$gigObj = new Gig();
$featuredGigs = $gigObj->getPublicUpcomingGigs(5);


// Include header
include_once 'includes/header.php';
?>

<!-- Hero Section with Gradient Background -->
<div class="hero-section position-relative overflow-hidden">
    <div class="hero-background"></div>
    <div class="hero-overlay"></div>
    <div class="container position-relative py-5">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="hero-content animate-fade-up text-center text-lg-start">
                    <h1 class="display-2 fw-bold text-white mb-4 hero-title">
                        Connect Musicians <br>
                        <span class="text-gradient">with Events</span>
                    </h1>
                    <p class="lead text-light mb-4 fs-5">
                        Nepal's premier platform for booking talented musicians.
                        Find the perfect artist for weddings, concerts, parties, and corporate events.
                    </p>
                    <div class="hero-stats mb-4">
                        <div class="row g-3">
                            <div class="col-4">
                                <div class="stat-item text-center">
                                    <div class="stat-number text-white fw-bold">150+</div>
                                    <div class="stat-label text-light small">Musicians</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item text-center">
                                    <div class="stat-number text-white fw-bold">500+</div>
                                    <div class="stat-label text-light small">Bookings</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item text-center">
                                    <div class="stat-number text-white fw-bold">4.8★</div>
                                    <div class="stat-label text-light small">Rating</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hero-buttons d-flex gap-3 flex-wrap justify-content-center justify-content-lg-start">
                        <a href="?page=register" class="btn btn-neon btn-neon-cyan btn-lg px-4 py-3 rounded-pill hover-lift">
                            <i class="fas fa-rocket me-2"></i>Get Started
                        </a>
                        <a href="?page=search" class="btn btn-neon btn-neon-pink btn-lg px-4 py-3 rounded-pill hover-lift">
                            <i class="fas fa-search me-2"></i>Find Musicians
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="hero-image animate-fade-in">
                    <div class="floating-card">
                        <div class="music-visualization">
                            <div class="sound-wave">
                                <div class="wave-bar"></div>
                                <div class="wave-bar"></div>
                                <div class="wave-bar"></div>
                                <div class="wave-bar"></div>
                                <div class="wave-bar"></div>
                                <div class="wave-bar"></div>
                            </div>
                        </div>
                        <div class="hero-card bg-white rounded-3 shadow-lg p-4 m-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar bg-primary rounded-circle me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-music text-white" style="line-height: 50px;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">Featured Artist</h6>
@@ -372,159 +372,202 @@ include_once 'includes/header.php';
                                <div class="stat-item">
                                    <div class="stat-number display-6 fw-bold">25+</div>
                                    <div class="stat-label">Genres</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="stat-item">
                                    <div class="stat-number display-6 fw-bold">4.8★</div>
                                    <div class="stat-label">Rating</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php if (!empty($featuredGigs)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Upcoming Events</h2>
        <div id="gigCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($featuredGigs as $index => $gig): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-1"><?= htmlspecialchars($gig['title']) ?></h5>
                            <p class="mb-1 small text-muted">
                                <?= htmlspecialchars($gig['gig_date']) ?> at <?= htmlspecialchars($gig['venue_name']) ?>
                            </p>
                            <p class="mb-3">By <?= htmlspecialchars($gig['musician_name']) ?></p>
                            <a href="/client/book-musician.php?musician_id=<?= $gig['musician_id'] ?>" class="btn btn-primary btn-sm">Book Now</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#gigCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#gigCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>
<?php else: ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-4">Upcoming Events</h2>
        <p class="text-center mb-0">No upcoming gigs at the moment.</p>
    </div>
</section>
<?php endif; ?>

<!-- Custom CSS for Landing Page -->
<style>
/* Hero Section Styles */
.hero-section {
    min-height: 100vh;
    position: relative;
    background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
}

.hero-background::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="music" patternUnits="userSpaceOnUse" width="20" height="20"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23music)"/></svg>');
    opacity: 0.2;
}

.hero-background::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at center, rgba(0,255,255,0.15), transparent 60%);
    animation: glow 8s linear infinite;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
}

.text-gradient {
    background: linear-gradient(45deg, #ffd700, #ffed4e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}


/* Floating Animation */
.floating-card {
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

/* Sound Wave Animation */
.sound-wave {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
    margin-bottom: 20px;
}

.wave-bar {
    width: 4px;
    height: 20px;
    background: linear-gradient(to top, #667eea, #764ba2);
    border-radius: 2px;
    animation: wave 1.2s ease-in-out infinite;
}

.wave-bar:nth-child(2) { animation-delay: 0.1s; }
.wave-bar:nth-child(3) { animation-delay: 0.2s; }
.wave-bar:nth-child(4) { animation-delay: 0.3s; }
.wave-bar:nth-child(5) { animation-delay: 0.4s; }
.wave-bar:nth-child(6) { animation-delay: 0.5s; }

@keyframes wave {
    0%, 100% { height: 20px; }
    50% { height: 40px; }
}

@keyframes glow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Floating Music Notes */
.floating-elements {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    overflow: hidden;
}

.floating-note {
    position: absolute;
    color: rgba(255, 255, 255, 0.3);
    font-size: 2rem;
    animation: floatNote 8s linear infinite;
}

.note-1 { left: 10%; animation-delay: 0s; }
.note-2 { left: 30%; animation-delay: 2s; }
.note-3 { left: 60%; animation-delay: 4s; }
.note-4 { left: 80%; animation-delay: 6s; }

@keyframes floatNote {
    0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
}

/* Card Hover Effects */
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
}

/* Neon Buttons */
.btn-neon {
    position: relative;
    background: transparent;
    color: #fff;
    border: 2px solid var(--neon-color);
    text-shadow: 0 0 5px var(--neon-color);
    box-shadow: 0 0 5px var(--neon-color), 0 0 15px var(--neon-color) inset;
    transition: all 0.3s ease;
}

.btn-neon:hover {
    color: #000;
    background: var(--neon-color);
    box-shadow: 0 0 20px var(--neon-color), 0 0 40px var(--neon-color);
}

.btn-neon-cyan { --neon-color: #00fff5; }
.btn-neon-pink { --neon-color: #ff00e6; }

/* User Type Cards */
.user-type-card {
    position: relative;
    overflow: hidden;
    border: none !important;
    transition: all 0.3s ease;
}

.card-gradient {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1;
}

.musician-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.client-gradient {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

@@ -581,57 +624,57 @@ include_once 'includes/header.php';
    opacity: 1;
    transform: translateY(0);
}

/* Feature Cards */
.feature-card {
    background: white;
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.feature-card:hover {
    border-color: rgba(102, 126, 234, 0.3);
}

.icon-wrapper {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    .display-2 { font-size: 2.5rem; }
    .display-4 { font-size: 2rem; }
    .hero-section { min-height: 100vh; }
    .floating-note { font-size: 1.5rem; }
}
</style>

<!-- Scroll Animation JavaScript -->
<script>
// Intersection Observer for scroll animations
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
            }
        });
    }, observerOptions);

    // Observe all elements with animate-on-scroll class
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>