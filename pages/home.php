<?php
/**
 * Homepage - Musician Booking System
 * Landing page for both musicians and clients
 */

// Include header
include_once 'includes/header.php';
?>

<div class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero__title">Connect Musicians with Events</h1>
                <p class="hero__subtitle">Nepal's premier platform for booking talented musicians for your events. Find the perfect artist for weddings, concerts, parties, and corporate events.</p>
                <div class="hero__actions d-flex flex-wrap">
                    <a href="?page=register" class="btn btn--light btn--lg">Get Started</a>
                    <a href="?page=search" class="btn btn--secondary btn--lg">Find Musicians</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="assets/images/hero-music.jpg" alt="Musicians performing" class="img-fluid rounded shadow"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNTAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iIzY2NzNkYyIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjE4IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk11c2ljaWFucyBQZXJmb3JtaW5nPC90ZXh0Pjwvc3ZnPg=='">
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <!-- Features Section -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-5">
            <h2 class="display-5 fw-bold">Why Choose Our Platform?</h2>
            <p class="text-muted">Connecting Nepal's music community with seamless booking experiences</p>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-search fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">Easy Discovery</h5>
                    <p class="card-text">Browse talented musicians by genre, location, and price range. Find the perfect match for your event.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
            <div class="card h-100 text-center">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-calendar-alt fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title">Smart Booking</h5>
                    <p class="card-text">Check real-time availability and book musicians instantly. Manage your bookings with our intuitive calendar system.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 text-center">
                <div class="card-body p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shield-alt fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title">Secure Platform</h5>
                    <p class="card-text">Safe and secure payment tracking. Verified musician profiles and transparent pricing for peace of mind.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- User Types Section -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="display-5 fw-bold">Join Our Community</h2>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card border-primary h-100">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0"><i class="fas fa-music me-2"></i>For Musicians</h4>
                </div>
                <div class="card-body p-4">
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Create professional profiles</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Showcase your music and portfolio</li>
@@ -127,34 +127,34 @@ include_once 'includes/header.php';
        <div class="col-md-3 text-center mb-3">
            <h2 class="text-primary fw-bold">150+</h2>
            <p class="mb-0">Registered Musicians</p>
        </div>
        <div class="col-md-3 text-center mb-3">
            <h2 class="text-success fw-bold">500+</h2>
            <p class="mb-0">Successful Bookings</p>
        </div>
        <div class="col-md-3 text-center mb-3">
            <h2 class="text-info fw-bold">25+</h2>
            <p class="mb-0">Music Genres</p>
        </div>
        <div class="col-md-3 text-center mb-3">
            <h2 class="text-warning fw-bold">4.8★</h2>
            <p class="mb-0">Average Rating</p>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="row">
        <div class="col-12 text-center">
            <div class="bg-primary text-white rounded p-5">
                <h2 class="fw-bold mb-3">Ready to Get Started?</h2>
                <p class="lead mb-4">Join thousands of musicians and event organizers making memorable experiences together.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="?page=register" class="btn btn--light btn--lg">Create Account</a>
                    <a href="?page=search" class="btn btn--secondary btn--lg">Browse Musicians</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once 'includes/footer.php'; ?>