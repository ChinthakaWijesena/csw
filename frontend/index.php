<?php
require_once __DIR__ . '/../config/config.php';

// Load featured properties for homepage
$propertyModel = new Property();
$featuredProperties = $propertyModel->search([], 1, 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PropertyHub 2030 | Find Your Dream Space</title>
  <link rel="stylesheet" href="style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body>
  
  <!-- Navigation -->
  <header class="header">
    <nav class="nav container">
      <div class="nav-brand">
        <span class="logo-icon">◆</span>
        <span class="logo-text">PropertyHub</span>
      </div>
      <ul class="nav-menu">
        <li><a href="#properties" class="nav-link">Properties</a></li>
        <li><a href="#features" class="nav-link">Features</a></li>
        <li><a href="#agents" class="nav-link">Agents</a></li>
        <li><a href="#contact" class="nav-link">Contact</a></li>
        <li><a href="#" class="btn btn-primary">List Property</a></li>
      </ul>
      <button class="nav-toggle" aria-label="Toggle navigation">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </nav>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-background"></div>
    <div class="container hero-content">
      <h1 class="hero-title">Discover Your Future Living Space</h1>
      <p class="hero-subtitle">AI-powered property matching for the modern world</p>
      
      <!-- Search Box -->
      <form class="search-box" method="GET" action="search.php">
        <div class="search-field">
          <label for="location">Location</label>
          <input type="text" id="location" name="q" placeholder="City, district or title">
        </div>
        <div class="search-field">
          <label for="type">Property Type</label>
          <select id="type" name="property_type">
            <option value="">Any</option>
            <option value="Apartment">Apartment</option>
            <option value="House">House</option>
            <option value="Studio">Studio</option>
            <option value="Commercial">Commercial</option>
          </select>
        </div>
        <div class="search-field">
          <label for="action">Action</label>
          <select id="action" name="status">
            <option value="rent">Rent</option>
            <option value="buy">Buy</option>
          </select>
        </div>
        <button class="btn btn-search" type="submit">Search Now</button>
      </form>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats">
    <div class="container">
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-number">2.4K+</div>
          <div class="stat-label">Properties Listed</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">1.8K+</div>
          <div class="stat-label">Happy Clients</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">500+</div>
          <div class="stat-label">Expert Agents</div>
        </div>
        <div class="stat-item">
          <div class="stat-number">85+</div>
          <div class="stat-label">Cities Covered</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Featured Properties -->
  <section id="properties" class="properties">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Featured Properties</h2>
        <p class="section-subtitle">Handpicked spaces for modern living</p>
      </div>
      
      <div class="property-grid">
        <?php if (!empty($featuredProperties)) : ?>
          <?php foreach ($featuredProperties as $property) : 
            $title = $property['title'] ?? 'Untitled Property';
            $city = $property['city'] ?? 'Unknown City';
            $state = $property['state'] ?? 'Unknown State';
            $bedrooms = (int)($property['bedrooms'] ?? 0);
            $bathrooms = (int)($property['bathrooms'] ?? 0);
            $area = (int)($property['area_sqft'] ?? 0);
            $price = $property['monthly_rent'] ?? null;
            $image = $property['primary_image'] ?? null;
            $type = $property['property_type'] ?? '';
          ?>
          <article class="property-card">
            <div class="property-image">
              <?php if (!empty($type)) : ?><div class="property-badge"><?php echo htmlspecialchars($type); ?></div><?php endif; ?>
              <?php if ($image) : ?>
                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($title); ?>" class="property-img" />
              <?php else : ?>
                <div class="placeholder-img"></div>
              <?php endif; ?>
            </div>
            <div class="property-content">
              <h3 class="property-title"><?php echo htmlspecialchars($title); ?></h3>
              <p class="property-location"><?php echo htmlspecialchars($city . ', ' . $state); ?></p>
              <div class="property-features">
                <span><?php echo $bedrooms; ?> Beds</span>
                <span><?php echo $bathrooms; ?> Baths</span>
                <span><?php echo number_format($area); ?> sqft</span>
              </div>
              <div class="property-footer">
                <div class="property-price"><?php echo $price !== null ? ('LKR ' . number_format((float)$price)) : 'Contact for price'; ?></div>
                <button class="btn btn-secondary">View Details</button>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        <?php else : ?>
          <p>No featured properties available right now. Please check back later.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="features">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Why Choose PropertyHub</h2>
        <p class="section-subtitle">Next-generation property platform</p>
      </div>
      
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">◈</div>
          <h3 class="feature-title">AI-Powered Matching</h3>
          <p class="feature-description">Advanced algorithms find your perfect property based on lifestyle preferences</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">◉</div>
          <h3 class="feature-title">Virtual Reality Tours</h3>
          <p class="feature-description">Explore properties in immersive 3D from anywhere in the world</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">◐</div>
          <h3 class="feature-title">Instant Verification</h3>
          <p class="feature-description">Blockchain-verified listings ensure authenticity and transparency</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">◎</div>
          <h3 class="feature-title">Smart Analytics</h3>
          <p class="feature-description">Real-time market insights and investment predictions at your fingertips</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">◇</div>
          <h3 class="feature-title">24/7 AI Assistant</h3>
          <p class="feature-description">Instant support and personalized recommendations any time you need</p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">◆</div>
          <h3 class="feature-title">Secure Transactions</h3>
          <p class="feature-description">End-to-end encrypted payments with smart contract protection</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta">
    <div class="container">
      <div class="cta-content">
        <h2 class="cta-title">Ready to Find Your Dream Property?</h2>
        <p class="cta-text">Join thousands of satisfied clients who found their perfect space with PropertyHub</p>
        <div class="cta-buttons">
          <button class="btn btn-primary btn-large">Get Started</button>
          <button class="btn btn-outline btn-large">Learn More</button>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-column">
          <div class="footer-brand">
            <span class="logo-icon">◆</span>
            <span class="logo-text">PropertyHub</span>
          </div>
          <p class="footer-description">The future of property discovery, powered by AI and built for you.</p>
        </div>
        
        <div class="footer-column">
          <h4 class="footer-title">Company</h4>
          <ul class="footer-links">
            <li><a href="#">About Us</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Press</a></li>
            <li><a href="#">Blog</a></li>
          </ul>
        </div>
        
        <div class="footer-column">
          <h4 class="footer-title">Support</h4>
          <ul class="footer-links">
            <li><a href="#">Help Center</a></li>
            <li><a href="#">Contact</a></li>
            <li><a href="#">Privacy</a></li>
            <li><a href="#">Terms</a></li>
          </ul>
        </div>
        
        <div class="footer-column">
          <h4 class="footer-title">Connect</h4>
          <ul class="footer-links">
            <li><a href="#">Twitter</a></li>
            <li><a href="#">LinkedIn</a></li>
            <li><a href="#">Instagram</a></li>
            <li><a href="#">Facebook</a></li>
          </ul>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; 2030 PropertyHub. All rights reserved.</p>
      </div>
    </div>
  </footer>

</body>
</html>