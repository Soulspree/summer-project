<?php
/**
 * Homepage - Musician Booking System
 * Modern landing page for both musicians and clients
 */

// Include header
include_once 'includes/header.php';
?>

<!-- Hero Section with Gradient Background -->
<div class="hero-section position-relative overflow-hidden">
    <div class="hero-background"></div>
    <div class="hero-overlay"></div>
    <div class="container position-relative py-5">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="hero-content animate-fade-up">
                    <h1 class="display-3 fw-bold text-white mb-4 hero-title">
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
                    <div class="hero-buttons d-flex gap-3 flex-wrap">
                        <a href="?page=register" class="btn btn-primary btn-lg px-4 py-3 rounded-pill hover-lift">
                            <i class="fas fa-rocket me-2"></i>Get Started
                        </a>
                        <a href="?page=search" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill hover-lift">
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
                                    <small class="text-muted">Available for booking</small>
                                </div>
                            </div>
                            <div class="rating mb-2">
                                <span class="text-warning">★★★★★</span>
                                <small class="text-muted ms-2">4.9 (127 reviews)</small>
                            </div>
                            <div class="price-tag">
                                <span class="h5 text-success mb-0">Rs. 15,000</span>
                                <small class="text-muted">/event</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Floating Elements -->
    <div class="floating-elements">
        <div class="floating-note note-1">♪</div>
        <div class="floating-note note-2">♫</div>
        <div class="floating-note note-3">♪</div>
        <div class="floating-note note-4">♬</div>
    </div>
</div>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <div class="section-header animate-on-scroll">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill mb-3">Why Choose Us</span>
                    <h2 class="display-5 fw-bold mb-4">Everything you need in one platform</h2>
                    <p class="lead text-muted col-lg-8 mx-auto">
                        We've built the most comprehensive platform for musicians and event organizers in Nepal
                    </p>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card h-100 p-4 rounded-4 border-0 shadow-sm hover-lift animate-on-scroll">
                    <div class="feature-icon mb-4">
                        <div class="icon-wrapper bg-primary-subtle text-primary rounded-3 p-3 d-inline-flex">
                            <i class="fas fa-search fa-2x"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3">Smart Discovery</h4>
                    <p class="text-muted mb-4">Advanced search with filters for genre, location, price, and availability. Find your perfect musical match instantly.</p>
                    <ul class="feature-list list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Genre-based filtering</li>
                        <li><i class="fas fa-check text-success me-2"></i>Location proximity</li>
                        <li><i class="fas fa-check text-success me-2"></i>Price range selection</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card h-100 p-4 rounded-4 border-0 shadow-sm hover-lift animate-on-scroll">
                    <div class="feature-icon mb-4">
                        <div class="icon-wrapper bg-success-subtle text-success rounded-3 p-3 d-inline-flex">
                            <i class="fas fa-calendar-alt fa-2x"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3">Instant Booking</h4>
                    <p class="text-muted mb-4">Real-time availability checking and instant booking confirmation. Manage all your events in one dashboard.</p>
                    <ul class="feature-list list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Real-time availability</li>
                        <li><i class="fas fa-check text-success me-2"></i>Instant confirmation</li>
                        <li><i class="fas fa-check text-success me-2"></i>Event management</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mx-auto">
                <div class="feature-card h-100 p-4 rounded-4 border-0 shadow-sm hover-lift animate-on-scroll">
                    <div class="feature-icon mb-4">
                        <div class="icon-wrapper bg-info-subtle text-info rounded-3 p-3 d-inline-flex">
                            <i class="fas fa-shield-alt fa-2x"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3">Secure & Trusted</h4>
                    <p class="text-muted mb-4">Verified profiles, secure payments, and transparent pricing. Your bookings are protected with our guarantee.</p>
                    <ul class="feature-list list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Verified musicians</li>
                        <li><i class="fas fa-check text-success me-2"></i>Secure payments</li>
                        <li><i class="fas fa-check text-success me-2"></i>Booking guarantee</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- User Types Section with Modern Cards -->
<section class="user-types-section py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <div class="section-header animate-on-scroll">
                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill mb-3">Join Today</span>
                    <h2 class="display-5 fw-bold mb-4">Choose Your Path</h2>
                    <p class="lead text-muted">Whether you're a musician or event organizer, we have the perfect solution for you</p>
                </div>
            </div>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-lg-5 col-md-6">
                <div class="user-type-card musician-card h-100 rounded-4 overflow-hidden shadow-lg animate-on-scroll">
                    <div class="card-gradient musician-gradient"></div>
                    <div class="card-content p-5 text-white position-relative">
                        <div class="card-icon mb-4">
                            <i class="fas fa-music fa-3x"></i>
                        </div>
                        <h3 class="fw-bold mb-3">For Musicians</h3>
                        <p class="mb-4 opacity-90">Showcase your talent, manage bookings, and grow your music career with our comprehensive platform.</p>
                        
                        <div class="benefits-list mb-4">
                            <div class="benefit-item d-flex align-items-center mb-3">
                                <div class="benefit-icon bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                    <i class="fas fa-user-circle text-white"></i>
                                </div>
                                <span>Professional profile creation</span>
                            </div>
                            <div class="benefit-item d-flex align-items-center mb-3">
                                <div class="benefit-icon bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                    <i class="fas fa-calendar text-white"></i>
                                </div>
                                <span>Smart calendar management</span>
                            </div>
                            <div class="benefit-item d-flex align-items-center mb-3">
                                <div class="benefit-icon bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                    <i class="fas fa-chart-line text-white"></i>
                                </div>
                                <span>Earnings analytics</span>
                            </div>
                        </div>
                        
                        <a href="?page=register&type=musician" class="btn btn-light btn-lg rounded-pill px-4 hover-lift">
                            <i class="fas fa-music me-2"></i>Join as Musician
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-5 col-md-6">
                <div class="user-type-card client-card h-100 rounded-4 overflow-hidden shadow-lg animate-on-scroll">
                    <div class="card-gradient client-gradient"></div>
                    <div class="card-content p-5 text-white position-relative">
                        <div class="card-icon mb-4">
                            <i class="fas fa-calendar-check fa-3x"></i>
                        </div>
                        <h3 class="fw-bold mb-3">For Event Organizers</h3>
                        <p class="mb-4 opacity-90">Find and book perfect musicians for your events. From weddings to corporate events, we've got you covered.</p>
                        
                        <div class="benefits-list mb-4">
                            <div class="benefit-item d-flex align-items-center mb-3">
                                <div class="benefit-icon bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                    <i class="fas fa-search text-white"></i>
                                </div>
                                <span>Advanced musician discovery</span>
                            </div>
                            <div class="benefit-item d-flex align-items-center mb-3">
                                <div class="benefit-icon bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                    <i class="fas fa-bolt text-white"></i>
                                </div>
                                <span>Instant booking confirmation</span>
                            </div>
                            <div class="benefit-item d-flex align-items-center mb-3">
                                <div class="benefit-icon bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                    <i class="fas fa-star text-white"></i>
                                </div>
                                <span>Review and rating system</span>
                            </div>
                        </div>
                        
                        <a href="?page=register&type=client" class="btn btn-light btn-lg rounded-pill px-4 hover-lift">
                            <i class="fas fa-calendar-plus me-2"></i>Join as Client
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <div class="section-header animate-on-scroll">
                    <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill mb-3">Success Stories</span>
                    <h2 class="display-5 fw-bold mb-4">What Our Community Says</h2>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="testimonial-card bg-white rounded-4 p-4 shadow-sm h-100 animate-on-scroll">
                    <div class="testimonial-rating mb-3">
                        <span class="text-warning fs-5">★★★★★</span>
                    </div>
                    <p class="testimonial-text mb-4">"This platform revolutionized how I manage my music bookings. I've tripled my monthly gigs and the payment tracking is fantastic!"</p>
                    <div class="testimonial-author d-flex align-items-center">
                        <div class="author-avatar bg-primary rounded-circle me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-user text-white" style="line-height: 50px;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Ramesh Maharjan</h6>
                            <small class="text-muted">Folk Musician, Kathmandu</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="testimonial-card bg-white rounded-4 p-4 shadow-sm h-100 animate-on-scroll">
                    <div class="testimonial-rating mb-3">
                        <span class="text-warning fs-5">★★★★★</span>
                    </div>
                    <p class="testimonial-text mb-4">"Found the perfect band for our wedding in just minutes. The booking process was seamless and the musicians were incredible!"</p>
                    <div class="testimonial-author d-flex align-items-center">
                        <div class="author-avatar bg-success rounded-circle me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-user text-white" style="line-height: 50px;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Priya Shrestha</h6>
                            <small class="text-muted">Event Organizer, Lalitpur</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="testimonial-card bg-white rounded-4 p-4 shadow-sm h-100 animate-on-scroll">
                    <div class="testimonial-rating mb-3">
                        <span class="text-warning fs-5">★★★★★</span>
                    </div>
                    <p class="testimonial-text mb-4">"As a corporate event planner, this platform saves me hours of research. The verified profiles give me confidence in every booking."</p>
                    <div class="testimonial-author d-flex align-items-center">
                        <div class="author-avatar bg-info rounded-circle me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-user text-white" style="line-height: 50px;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Anil Gurung</h6>
                            <small class="text-muted">Corporate Planner, Pokhara</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="cta-card bg-gradient rounded-4 p-5 text-center text-white position-relative overflow-hidden animate-on-scroll">
                    <div class="cta-background"></div>
                    <div class="position-relative">
                        <h2 class="display-4 fw-bold mb-4">Ready to Make Music Happen?</h2>
                        <p class="lead mb-5 col-lg-8 mx-auto opacity-90">
                            Join Nepal's fastest-growing music community. Whether you're booking your dream event or building your music career, we're here to help you succeed.
                        </p>
                        <div class="cta-buttons d-flex gap-3 justify-content-center flex-wrap">
                            <a href="?page=register" class="btn btn-light btn-lg px-5 py-3 rounded-pill hover-lift">
                                <i class="fas fa-star me-2"></i>Start Your Journey
                            </a>
                            <a href="?page=search" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill hover-lift">
                                <i class="fas fa-play me-2"></i>Explore Musicians
                            </a>
                        </div>
                        <div class="cta-stats mt-5 row g-4">
                            <div class="col-md-3 col-6">
                                <div class="stat-item">
                                    <div class="stat-number display-6 fw-bold">150+</div>
                                    <div class="stat-label">Musicians</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="stat-item">
                                    <div class="stat-number display-6 fw-bold">500+</div>
                                    <div class="stat-label">Events</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
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

<!-- Custom CSS for Landing Page -->
<style>
/* Hero Section Styles */
.hero-section {
    min-height: 100vh;
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="music" patternUnits="userSpaceOnUse" width="20" height="20"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23music)"/></svg>');
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.3);
}

.text-gradient {
    background: linear-gradient(45deg, #ffd700, #ffed4e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.min-vh-75 {
    min-height: 75vh;
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

.card-content {
    position: relative;
    z-index: 2;
}

/* CTA Section */
.cta-section .bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.cta-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0.1;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white"/><circle cx="80" cy="40" r="1.5" fill="white"/><circle cx="40" cy="80" r="1" fill="white"/><circle cx="90" cy="10" r="1" fill="white"/></svg>');
}

/* Animation Classes */
.animate-fade-up {
    opacity: 0;
    transform: translateY(30px);
    animation: fadeUp 1s ease forwards;
}

@keyframes fadeUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    opacity: 0;
    animation: fadeIn 1.5s ease forwards 0.5s;
}

@keyframes fadeIn {
    to { opacity: 1; }
}

.animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.animate-on-scroll.show {
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
    .display-3 { font-size: 2.5rem; }
    .display-4 { font-size: 2rem; }
    .hero-section { min-height: 70vh; }
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