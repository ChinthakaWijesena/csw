<?php
/**
 * Property Search API Endpoint
 * Handles property search and filtering
 */

// Turn off error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/Property.php';
require_once __DIR__ . '/../../backend/models/PropertyType.php';
require_once __DIR__ . '/../../backend/models/Province.php';
require_once __DIR__ . '/../../backend/models/District.php';
require_once __DIR__ . '/../../backend/models/City.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get search parameters
    $filters = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $filters = $input;
        } else {
            $filters = $_POST;
        }
    } else {
        $filters = $_GET;
    }

    // Sanitize filters
    $allowed_filters = [
        'province', 'district', 'city', 'property_type', 'min_price', 'max_price',
        'bedrooms', 'bathrooms', 'min_area', 'max_area', 'is_furnished', 'is_available',
        'search', 'q', 'sort_by', 'sort_order', 'page', 'limit'
    ];

    $search_params = [];
    foreach ($allowed_filters as $filter) {
        if (isset($filters[$filter]) && !empty($filters[$filter])) {
            $search_params[$filter] = sanitize_input($filters[$filter]);
        }
    }

    // Set default values
    $page = (int)($search_params['page'] ?? 1);
    $limit = (int)($search_params['limit'] ?? 12);
    $sort_by = $search_params['sort_by'] ?? 'created_at';
    $sort_order = $search_params['sort_order'] ?? 'DESC';

    // Validate pagination
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 50) $limit = 12;

    // Search properties
    $property_model = new Property();
    $properties = $property_model->search($search_params, $page, $limit);
    $total_count = $property_model->getSearchCount($search_params);

    // Get property types for filters
    $property_type_model = new PropertyType();
    $property_types = $property_type_model->getAllActive();

    // Get provinces for filters
    $province_model = new Province();
    $provinces = $province_model->getAllActive();

    // Calculate pagination info
    $total_pages = ceil($total_count / $limit);
    $has_next = $page < $total_pages;
    $has_prev = $page > 1;

    // Generate property cards HTML
    $property_cards_html = '';
    if (!empty($properties)) {
        $property_cards_html = generatePropertyCards($properties);
    }

    // Return data in the format expected by our JavaScript
    echo json_encode([
        'success' => true,
        'properties' => $properties,
        'total_count' => $total_count,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $total_pages,
        'has_next' => $has_next,
        'has_prev' => $has_prev,
        'filters' => [
            'property_types' => $property_types,
            'provinces' => $provinces
        ],
        'html' => $property_cards_html
    ]);

} catch (Exception $e) {
    error_log("Property Search Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching properties',
        'error_details' => $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("Property Search Fatal Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A fatal error occurred while searching properties',
        'error_details' => $e->getMessage()
    ]);
}

/**
 * Generate property cards HTML
 */
function generatePropertyCards($properties) {
    $html = '<div class="row">';
    
    foreach ($properties as $property) {
        $primary_image = $property['primary_image'] ?? '/assets/images/placeholder.jpg';
        $property_type = $property['property_type'] ?? 'Property';
        $location = ($property['city'] ?? 'Unknown') . ', ' . ($property['state'] ?? 'Unknown');
        $monthly_rent = number_format($property['monthly_rent'] ?? 0);
        $bedrooms = $property['bedrooms'] ?? 0;
        $bathrooms = $property['bathrooms'] ?? 0;
        $area = $property['area'] ?? 0;
        
        $html .= '
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card property-card h-100">
                    <div class="property-image">
                        <img src="' . $primary_image . '" alt="' . htmlspecialchars($property['title']) . '" class="card-img-top">
                        <div class="property-badge">
                            <span class="badge bg-primary">' . htmlspecialchars($property_type) . '</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">' . htmlspecialchars($property['title']) . '</h5>
                        <p class="card-text text-muted">' . htmlspecialchars($location) . '</p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> ' . $bedrooms . '</span>
                            <span><i class="fas fa-bath"></i> ' . $bathrooms . '</span>
                            <span><i class="fas fa-ruler"></i> ' . $area . ' sq ft</span>
                        </div>
                        <div class="property-price">
                            <span class="price">LKR ' . $monthly_rent . '</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group w-100">
                            <button class="btn btn-outline-primary btn-sm" onclick="PropertyAjax.viewProperty(' . $property['id'] . ')">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="PropertyAjax.addToWishlist(' . $property['id'] . ')">
                                <i class="fas fa-heart"></i> Wishlist
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="PropertyAjax.subscribeProperty(' . $property['id'] . ')">
                                <i class="fas fa-calendar"></i> Subscribe
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }
    
    $html .= '</div>';
    return $html;
}
?>
