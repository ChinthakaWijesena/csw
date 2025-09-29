/**
 * Main JavaScript File
 * Combined functionality from hero-ajax.js and index.js
 * Handles all AJAX functionality and page interactions
 */

/**
 * Hero Section AJAX Controller
 * Handles all AJAX functionality for the property search hero section
 */
class HeroAJAXController {
    constructor() {
        this.apiEndpoints = {
            locations: '../api/location/get_locations.php',
            search: '../api/property/search.php'
        };
        
        this.init();
    }

    /**
     * Initialize the AJAX controller
     */
    init() {
        this.bindEvents();
        this.updateWishlistCount();
        console.log('Hero AJAX Controller initialized');
    }

    /**
     * Bind all event listeners
     */
    bindEvents() {
        // Location dropdown events
        const provinceSelect = document.getElementById('province');
        const districtSelect = document.getElementById('district');
        const searchForm = document.getElementById('searchForm');

        if (provinceSelect) {
            provinceSelect.addEventListener('change', (e) => {
                this.loadDistricts(e.target.value);
            });
        }

        if (districtSelect) {
            districtSelect.addEventListener('change', (e) => {
                this.loadCities(e.target.value);
            });
        }

        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }
    }

    /**
     * Load districts based on province selection
     */
    async loadDistricts(provinceId) {
        const districtSelect = document.getElementById('district');
        const citySelect = document.getElementById('city');

        if (!districtSelect || !citySelect) return;

        // Reset dependent dropdowns
        this.resetDropdown(districtSelect, 'Select District');
        this.resetDropdown(citySelect, 'Select City', true);

        if (!provinceId) {
            districtSelect.disabled = true;
            return;
        }

        districtSelect.disabled = false;
        districtSelect.innerHTML = '<option value="">Loading districts...</option>';
        districtSelect.disabled = true;

        try {
            const response = await fetch(this.apiEndpoints.locations, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'districts',
                    province_id: provinceId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.resetDropdown(districtSelect, 'Select District');
                data.districts.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.id;
                    option.textContent = district.name;
                    districtSelect.appendChild(option);
                });
            } else {
                districtSelect.innerHTML = '<option value="">Error loading districts</option>';
            }
        } catch (error) {
            console.error('Error loading districts:', error);
            districtSelect.innerHTML = '<option value="">Error loading districts</option>';
        }
    }

    /**
     * Load cities based on district selection
     */
    async loadCities(districtId) {
        const citySelect = document.getElementById('city');

        if (!citySelect) return;

        this.resetDropdown(citySelect, 'Select City');

        if (!districtId) {
            citySelect.disabled = true;
            return;
        }

        citySelect.disabled = false;
        citySelect.innerHTML = '<option value="">Loading cities...</option>';
        citySelect.disabled = true;

        try {
            const response = await fetch(this.apiEndpoints.locations, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'cities',
                    district_id: districtId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.resetDropdown(citySelect, 'Select City');
                data.cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });
            } else {
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            }
        } catch (error) {
            console.error('Error loading cities:', error);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
        }
    }

    /**
     * Perform property search
     */
    async performSearch() {
        const formData = new FormData(document.getElementById('searchForm'));
        const searchParams = {};

        // Get form values
        for (let [key, value] of formData.entries()) {
            if (value) {
                searchParams[key] = value;
            }
        }

        console.log('Search parameters:', searchParams);

        // Show loading state
        this.showSearchLoading();

        try {
            const response = await fetch(this.apiEndpoints.search, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(searchParams)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.displaySearchResults(data.properties);
            } else {
                this.showSearchError(data.message || 'Search failed');
            }
        } catch (error) {
            console.error('Search error:', error);
            this.showSearchError('An error occurred while searching');
        }
    }

    /**
     * Display search results
     */
    displaySearchResults(properties) {
        const resultsSection = document.getElementById('searchResults');
        const resultsContent = document.getElementById('searchResultsContent');

        if (!resultsSection || !resultsContent) return;

        if (properties.length === 0) {
            resultsContent.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-search text-muted display-4 mb-3"></i>
                    <h4 class="text-muted">No properties found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                </div>
            `;
        } else {
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="text-muted mb-0">Found ${properties.length} properties</p>
                    </div>
                </div>
                <div class="row g-4">
            `;

            properties.forEach(property => {
                html += `
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="position-relative">
                                <img src="${property.primary_image || 'https://via.placeholder.com/400x250?text=No+Image'}" 
                                     class="card-img-top" 
                                     style="height: 250px; object-fit: cover;" 
                                     alt="${property.title}">
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-success">Verified</span>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">${property.title}</h5>
                                <p class="card-text text-muted small">${property.description.substring(0, 100)}...</p>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-bed me-1"></i>${property.bedrooms || 'N/A'} Beds
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-bath me-1"></i>${property.bathrooms || 'N/A'} Baths
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-ruler me-1"></i>${property.area_sqft || 'N/A'} sq ft
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-home me-1"></i>${property.property_type}
                                        </small>
                                    </div>
                                </div>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="h5 text-primary mb-0">$${property.monthly_rent.toLocaleString()}/month</span>
                                        <small class="text-muted">${property.city}, ${property.state}</small>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="property-details.php?id=${property.id}" class="btn btn-primary">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="toggleWishlist(${property.id})" data-property-id="${property.id}">
                                            <i class="far fa-heart me-1"></i>Add to Wishlist
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            resultsContent.innerHTML = html;
        }

        resultsSection.style.display = 'block';
        resultsSection.scrollIntoView({
            behavior: 'smooth'
        });
    }

    /**
     * Show search loading state
     */
    showSearchLoading() {
        const resultsSection = document.getElementById('searchResults');
        const resultsContent = document.getElementById('searchResultsContent');

        if (!resultsSection || !resultsContent) return;

        resultsContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted">Searching for properties...</p>
            </div>
        `;

        resultsSection.style.display = 'block';
        resultsSection.scrollIntoView({
            behavior: 'smooth'
        });
    }

    /**
     * Show search error
     */
    showSearchError(message) {
        const resultsSection = document.getElementById('searchResults');
        const resultsContent = document.getElementById('searchResultsContent');

        if (!resultsSection || !resultsContent) return;

        resultsContent.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `;

        resultsSection.style.display = 'block';
        resultsSection.scrollIntoView({
            behavior: 'smooth'
        });
    }

    /**
     * Clear search results
     */
    clearSearch() {
        const resultsSection = document.getElementById('searchResults');
        const searchForm = document.getElementById('searchForm');

        if (searchForm) {
            searchForm.reset();
        }

        // Reset dropdowns
        const districtSelect = document.getElementById('district');
        const citySelect = document.getElementById('city');
        
        if (districtSelect) {
            this.resetDropdown(districtSelect, 'Select District', true);
        }
        
        if (citySelect) {
            this.resetDropdown(citySelect, 'Select City', true);
        }

        // Hide results
        if (resultsSection) {
            resultsSection.style.display = 'none';
        }
    }

    /**
     * Toggle wishlist for a property
     */
    toggleWishlist(propertyId) {
        const button = document.querySelector(`[data-property-id="${propertyId}"]`);
        if (!button) return;

        const icon = button.querySelector('i');

        // Add loading state
        button.classList.add('loading');
        button.disabled = true;

        setTimeout(() => {
            if (button.classList.contains('active')) {
                // Remove from wishlist
                button.classList.remove('active');
                icon.className = 'far fa-heart me-1';
                button.innerHTML = '<i class="far fa-heart me-1"></i>Add to Wishlist';
                this.removeFromWishlist(propertyId);
            } else {
                // Add to wishlist
                button.classList.add('active');
                icon.className = 'fas fa-heart me-1';
                button.innerHTML = '<i class="fas fa-heart me-1"></i>Added to Wishlist';
                this.addToWishlist(propertyId);
            }

            // Remove loading state
            button.classList.remove('loading');
            button.disabled = false;

            // Update wishlist count
            this.updateWishlistCount();
        }, 500);
    }

    /**
     * Add property to wishlist
     */
    addToWishlist(propertyId) {
        let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        if (!wishlist.includes(propertyId)) {
            wishlist.push(propertyId);
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
        }
    }

    /**
     * Remove property from wishlist
     */
    removeFromWishlist(propertyId) {
        let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        wishlist = wishlist.filter(id => id !== propertyId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
    }

    /**
     * Check if property is in wishlist
     */
    isInWishlist(propertyId) {
        let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        return wishlist.includes(propertyId);
    }

    /**
     * Update wishlist count in navbar
     */
    updateWishlistCount() {
        let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
        const countElement = document.getElementById('wishlist-count');
        if (countElement) {
            countElement.textContent = wishlist.length;
        }
    }

    /**
     * Show message (disabled notifications)
     */
    showMessage(message, type) {
        // Function removed - notifications disabled
        console.log(`Message (${type}): ${message}`);
    }

    /**
     * Utility: Reset dropdown
     */
    resetDropdown(select, placeholder, disabled = false) {
        if (!select) return;
        
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = disabled;
    }
}

// Global functions for backward compatibility
function toggleWishlist(propertyId) {
    if (window.heroController) {
        window.heroController.toggleWishlist(propertyId);
    }
}

function addToWishlist(propertyId) {
    if (window.heroController) {
        window.heroController.addToWishlist(propertyId);
    }
}

function removeFromWishlist(propertyId) {
    if (window.heroController) {
        window.heroController.removeFromWishlist(propertyId);
    }
}

function isInWishlist(propertyId) {
    if (window.heroController) {
        return window.heroController.isInWishlist(propertyId);
    }
    return false;
}

function updateWishlistCount() {
    if (window.heroController) {
        window.heroController.updateWishlistCount();
    }
}

function showMessage(message, type) {
    if (window.heroController) {
        window.heroController.showMessage(message, type);
    }
}

function performSearch() {
    if (window.heroController) {
        window.heroController.performSearch();
    }
}

function loadDistricts(provinceId) {
    if (window.heroController) {
        window.heroController.loadDistricts(provinceId);
    }
}

function loadCities(districtId) {
    if (window.heroController) {
        window.heroController.loadCities(districtId);
    }
}

function clearSearch() {
    if (window.heroController) {
        window.heroController.clearSearch();
    }
}

// Advanced AJAX Features
function initializeAdvancedAJAX() {
    console.log('Initializing advanced AJAX features...');

    // Add loading indicators to form elements
    addLoadingIndicators();

    // Add form validation
    addFormValidation();

    // Add keyboard shortcuts
    addKeyboardShortcuts();
}

function addLoadingIndicators() {
    const selects = document.querySelectorAll('select');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            if (this.id === 'province' || this.id === 'district') {
                // Add loading class
                this.classList.add('loading');

                // Remove loading class after a delay
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 2000);
            }
        });
    });
}

function addFormValidation() {
    const searchForm = document.getElementById('searchForm');
    if (!searchForm) return;

    // Real-time validation
    const inputs = searchForm.querySelectorAll('select, input');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            validateFormField(this);
        });
    });
}

function validateFormField(field) {
    const value = field.value.trim();
    const fieldName = field.name;

    // Remove existing validation classes
    field.classList.remove('is-valid', 'is-invalid');

    if (value === '') {
        return; // Empty is valid for optional fields
    }

    // Basic validation
    let isValid = true;
    let message = '';

    switch (fieldName) {
        case 'province':
            isValid = value !== '';
            message = isValid ? 'Province selected' : 'Please select a province';
            break;
        case 'district':
            isValid = value !== '';
            message = isValid ? 'District selected' : 'Please select a district';
            break;
        case 'city':
            isValid = value !== '';
            message = isValid ? 'City selected' : 'Please select a city';
            break;
    }

    if (isValid) {
        field.classList.add('is-valid');
    } else {
        field.classList.add('is-invalid');
    }
}

function addKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + Enter to submit search
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            const searchForm = document.getElementById('searchForm');
            if (searchForm) {
                performSearch();
            }
        }

        // Escape to clear form
        if (e.key === 'Escape') {
            clearSearch();
        }
    });
}

function addSearchSuggestions() {
    // Add property type suggestions
    const propertyTypeSelect = document.getElementById('property_type');
    if (propertyTypeSelect) {
        propertyTypeSelect.addEventListener('focus', function() {
            // Could add AJAX call to get popular property types
            console.log('Property type suggestions could be loaded here');
        });
    }
}

// Scroll Animation Functions
function initScrollAnimations() {
    // Create intersection observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // Observe all animated elements
    const animatedElements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .scale-in, .bounce-in, .rotate-in');
    animatedElements.forEach(el => {
        observer.observe(el);
    });

    // Parallax effect for hero section
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.parallax');
        
        parallaxElements.forEach(element => {
            const speed = element.dataset.speed || 0.5;
            element.style.transform = `translateY(${scrolled * speed}px)`;
        });
    });
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing main JavaScript');

    // Initialize scroll animations
    initScrollAnimations();

    // Initialize Hero AJAX Controller
    window.heroController = new HeroAJAXController();

    // Initialize advanced AJAX features
    initializeAdvancedAJAX();

    // Initialize wishlist buttons on page load
    const wishlistButtons = document.querySelectorAll('.booking-btn-wishlist, [data-property-id]');
    wishlistButtons.forEach(button => {
        const propertyId = parseInt(button.getAttribute('data-property-id'));
        if (propertyId && isInWishlist(propertyId)) {
            button.classList.add('active');
            button.innerHTML = '<i class="fas fa-heart me-1"></i>Added to Wishlist';
        }
    });

    // Update wishlist count in navbar
    updateWishlistCount();

    // Property card hover effects
    const propertyCards = document.querySelectorAll('.booking-property-card, .card');
    propertyCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-6px)';
            this.style.transition = 'transform 0.3s ease';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add search suggestions
    addSearchSuggestions();
});
