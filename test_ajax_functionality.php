<?php
/**
 * Comprehensive AJAX Functionality Test Suite
 * Tests all AJAX endpoints and functionality
 */

require_once __DIR__ . '/config/config.php';

// Start output buffering to prevent headers already sent errors
ob_start();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>AJAX Functionality Test Suite</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <style>
        .test-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .test-section { margin-bottom: 30px; }
        .test-result { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .test-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .test-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .test-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .test-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .endpoint-test { margin: 10px 0; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; }
        .response-data { background: #f8f9fa; padding: 10px; border-radius: 3px; margin-top: 10px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class='test-container'>
        <h1 class='text-center mb-4'><i class='fas fa-vial'></i> AJAX Functionality Test Suite</h1>
        <p class='text-center text-muted'>Testing all AJAX endpoints and functionality</p>
";

// Test results storage
$test_results = [];
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

/**
 * Test helper function
 */
function run_test($test_name, $test_function) {
    global $total_tests, $passed_tests, $failed_tests, $test_results;
    
    $total_tests++;
    echo "<div class='endpoint-test'>";
    echo "<h5><i class='fas fa-cog'></i> $test_name</h5>";
    
    try {
        $result = $test_function();
        if ($result['success']) {
            echo "<div class='test-result test-success'><i class='fas fa-check'></i> PASSED: {$result['message']}</div>";
            $passed_tests++;
        } else {
            echo "<div class='test-result test-error'><i class='fas fa-times'></i> FAILED: {$result['message']}</div>";
            $failed_tests++;
        }
        
        if (isset($result['data'])) {
            echo "<div class='response-data'>Response: " . htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT)) . "</div>";
        }
        
        $test_results[] = [
            'name' => $test_name,
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null
        ];
        
    } catch (Exception $e) {
        echo "<div class='test-result test-error'><i class='fas fa-exclamation-triangle'></i> ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
        $failed_tests++;
        
        $test_results[] = [
            'name' => $test_name,
            'success' => false,
            'message' => $e->getMessage(),
            'data' => null
        ];
    }
    
    echo "</div>";
}

/**
 * Test AJAX endpoint
 */
function test_ajax_endpoint($url, $method = 'GET', $data = null, $expected_status = 200) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'message' => "cURL Error: $error",
            'data' => null
        ];
    }
    
    if ($http_code !== $expected_status) {
        return [
            'success' => false,
            'message' => "Expected HTTP $expected_status, got $http_code",
            'data' => ['http_code' => $http_code, 'response' => $response]
        ];
    }
    
    $decoded_response = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => "Invalid JSON response: " . json_last_error_msg(),
            'data' => ['response' => $response]
        ];
    }
    
    return [
        'success' => true,
        'message' => "HTTP $http_code - Valid JSON response",
        'data' => $decoded_response
    ];
}

// Test 1: OTP Send Endpoint
run_test("OTP Send API", function() {
    $url = "http://localhost/gov_project/api/otp/send.php";
    $data = [
        'phone' => '0712345678',
        'user_type' => 'customer'
    ];
    
    return test_ajax_endpoint($url, 'POST', $data);
});

// Test 2: OTP Verify Endpoint
run_test("OTP Verify API", function() {
    $url = "http://localhost/gov_project/api/otp/verify.php";
    $data = [
        'phone' => '0712345678',
        'otp_code' => '123456'
    ];
    
    return test_ajax_endpoint($url, 'POST', $data);
});

// Test 3: OTP Resend Endpoint
run_test("OTP Resend API", function() {
    $url = "http://localhost/gov_project/api/otp/resend.php";
    
    return test_ajax_endpoint($url, 'POST');
});

// Test 4: Property Search API
run_test("Property Search API", function() {
    $url = "http://localhost/gov_project/api/property/search.php";
    $data = [
        'province' => '1',
        'property_type' => 'apartment'
    ];
    
    return test_ajax_endpoint($url, 'POST', $data);
});

// Test 5: Property Add API (without authentication - should fail)
run_test("Property Add API (Unauthenticated)", function() {
    $url = "http://localhost/gov_project/api/property/add.php";
    $data = [
        'title' => 'Test Property',
        'description' => 'Test Description',
        'property_type' => 'apartment',
        'monthly_rent' => 50000,
        'city' => 'Colombo',
        'state' => 'Western'
    ];
    
    $result = test_ajax_endpoint($url, 'POST', $data, 401); // Expect 401 Unauthorized
    return $result;
});

// Test 6: Booking Create API (without authentication - should fail)
run_test("Booking Create API (Unauthenticated)", function() {
    $url = "http://localhost/gov_project/api/booking/create.php";
    $data = [
        'property_id' => 1,
        'check_in_date' => '2024-02-01',
        'check_out_date' => '2024-02-28',
        'monthly_rent' => 50000
    ];
    
    $result = test_ajax_endpoint($url, 'POST', $data, 401); // Expect 401 Unauthorized
    return $result;
});

// Test 7: Subscription Create API (without authentication - should fail)
run_test("Subscription Create API (Unauthenticated)", function() {
    $url = "http://localhost/gov_project/api/subscription/create.php";
    $data = [
        'property_id' => 1,
        'start_date' => '2024-02-01',
        'monthly_rent' => 50000
    ];
    
    $result = test_ajax_endpoint($url, 'POST', $data, 401); // Expect 401 Unauthorized
    return $result;
});

// Test 8: Location API - Districts
run_test("Location API - Get Districts", function() {
    $url = "http://localhost/gov_project/api/location/get_locations.php";
    $data = [
        'action' => 'districts',
        'province_id' => '1'
    ];
    
    return test_ajax_endpoint($url, 'POST', $data);
});

// Test 9: Location API - Cities
run_test("Location API - Get Cities", function() {
    $url = "http://localhost/gov_project/api/location/get_locations.php";
    $data = [
        'action' => 'cities',
        'district_id' => '1'
    ];
    
    return test_ajax_endpoint($url, 'POST', $data);
});

// Test 10: Wishlist API - Get Properties
run_test("Wishlist API - Get Properties", function() {
    $url = "http://localhost/gov_project/api/wishlist/get_properties.php";
    $data = [
        'property_ids' => [1, 2, 3]
    ];
    
    return test_ajax_endpoint($url, 'POST', $data);
});

// Test 11: Payment Process API (without authentication - should fail)
run_test("Payment Process API (Unauthenticated)", function() {
    $url = "http://localhost/gov_project/api/payment/process.php";
    $data = [
        'booking_id' => 1,
        'amount' => 50000,
        'customer_name' => 'Test Customer'
    ];
    
    $result = test_ajax_endpoint($url, 'POST', $data, 401); // Expect 401 Unauthorized
    return $result;
});

// Test 12: Visit Request API
run_test("Visit Request API", function() {
    $url = "http://localhost/gov_project/api/visits/request.php";
    $data = [
        'property_id' => 1,
        'requested_date' => '2024-02-15',
        'requested_time' => '14:00',
        'notes' => 'Test visit request'
    ];
    
    return test_ajax_endpoint($url, 'POST', $data);
});

// Test 13: Booking Track API
run_test("Booking Track API", function() {
    $url = "http://localhost/gov_project/api/booking/track.php";
    $data = [
        'booking_id' => 1,
        'action' => 'view'
    ];
    
    return test_ajax_endpoint($url, 'POST', $data);
});

// Test 14: Check AJAX Utility Files
run_test("AJAX Utility Files Check", function() {
    $files = [
        'frontend/js/ajax-utils.js',
        'frontend/js/auth-ajax.js',
        'frontend/js/property-ajax.js',
        'frontend/js/booking-ajax.js'
    ];
    
    $missing_files = [];
    foreach ($files as $file) {
        if (!file_exists($file)) {
            $missing_files[] = $file;
        }
    }
    
    if (empty($missing_files)) {
        return [
            'success' => true,
            'message' => "All AJAX utility files exist",
            'data' => ['files' => $files]
        ];
    } else {
        return [
            'success' => false,
            'message' => "Missing files: " . implode(', ', $missing_files),
            'data' => ['missing_files' => $missing_files]
        ];
    }
});

// Test 15: Check AJAX Styles
run_test("AJAX Styles Check", function() {
    $files = [
        'frontend/css/ajax-styles.css',
        'frontend/css/design-improvements.css'
    ];
    
    $missing_files = [];
    foreach ($files as $file) {
        if (!file_exists($file)) {
            $missing_files[] = $file;
        }
    }
    
    if (empty($missing_files)) {
        return [
            'success' => true,
            'message' => "All AJAX style files exist",
            'data' => ['files' => $files]
        ];
    } else {
        return [
            'success' => false,
            'message' => "Missing files: " . implode(', ', $missing_files),
            'data' => ['missing_files' => $missing_files]
        ];
    }
});

// Test 16: Database Connection Test
run_test("Database Connection Test", function() {
    try {
        global $database;
        $result = $database->fetch("SELECT 1 as test");
        
        if ($result && $result['test'] == 1) {
            return [
                'success' => true,
                'message' => "Database connection successful",
                'data' => ['connection' => 'active']
            ];
        } else {
            return [
                'success' => false,
                'message' => "Database query failed",
                'data' => null
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Database error: " . $e->getMessage(),
            'data' => null
        ];
    }
});

// Test 17: OTP Service Test
run_test("OTP Service Test", function() {
    try {
        require_once __DIR__ . '/api/otp/OTPService.php';
        $otp_service = new OTPService();
        
        // Test OTP generation
        $otp = $otp_service->generateOTP();
        
        if (strlen($otp) === 6 && is_numeric($otp)) {
            return [
                'success' => true,
                'message' => "OTP Service working correctly",
                'data' => ['generated_otp' => $otp]
            ];
        } else {
            return [
                'success' => false,
                'message' => "OTP generation failed",
                'data' => ['generated_otp' => $otp]
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "OTP Service error: " . $e->getMessage(),
            'data' => null
        ];
    }
});

// Test 18: Model Classes Test
run_test("Model Classes Test", function() {
    $models = [
        'User' => 'backend/models/User.php',
        'Property' => 'backend/models/Property.php',
        'Booking' => 'backend/models/Booking.php',
        'Payment' => 'backend/models/Payment.php',
        'PropertyType' => 'backend/models/PropertyType.php',
        'Province' => 'backend/models/Province.php',
        'District' => 'backend/models/District.php',
        'City' => 'backend/models/City.php'
    ];
    
    $missing_models = [];
    $loaded_models = [];
    
    foreach ($models as $class => $file) {
        if (file_exists($file)) {
            require_once $file;
            if (class_exists($class)) {
                $loaded_models[] = $class;
            } else {
                $missing_models[] = "$class (file exists but class not found)";
            }
        } else {
            $missing_models[] = "$class (file missing: $file)";
        }
    }
    
    if (empty($missing_models)) {
        return [
            'success' => true,
            'message' => "All model classes loaded successfully",
            'data' => ['loaded_models' => $loaded_models]
        ];
    } else {
        return [
            'success' => false,
            'message' => "Model issues: " . implode(', ', $missing_models),
            'data' => ['loaded_models' => $loaded_models, 'missing_models' => $missing_models]
        ];
    }
});

// Test 19: Configuration Test
run_test("Configuration Test", function() {
    $config_vars = [
        'APP_NAME',
        'APP_URL',
        'DEBUG_MODE',
        'DEV_FIXED_OTP',
        'DEV_FIXED_OTP_ENABLED',
        'SESSION_TIMEOUT'
    ];
    
    $missing_vars = [];
    $config_values = [];
    
    foreach ($config_vars as $var) {
        if (defined($var)) {
            $config_values[$var] = constant($var);
        } else {
            $missing_vars[] = $var;
        }
    }
    
    if (empty($missing_vars)) {
        return [
            'success' => true,
            'message' => "All configuration variables defined",
            'data' => ['config' => $config_values]
        ];
    } else {
        return [
            'success' => false,
            'message' => "Missing configuration: " . implode(', ', $missing_vars),
            'data' => ['config' => $config_values, 'missing' => $missing_vars]
        ];
    }
});

// Test 20: Frontend AJAX Integration Test
run_test("Frontend AJAX Integration Test", function() {
    $frontend_files = [
        'frontend/index.php',
        'frontend/search.php',
        'frontend/login.php',
        'frontend/property-details.php'
    ];
    
    $ajax_integration = [];
    
    foreach ($frontend_files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Check for AJAX script inclusions
            $has_ajax_utils = strpos($content, 'ajax-utils.js') !== false;
            $has_design_improvements = strpos($content, 'design-improvements.css') !== false;
            $has_ajax_scripts = strpos($content, 'ajax') !== false;
            
            $ajax_integration[$file] = [
                'ajax_utils' => $has_ajax_utils,
                'design_improvements' => $has_design_improvements,
                'ajax_scripts' => $has_ajax_scripts
            ];
        }
    }
    
    $all_integrated = true;
    foreach ($ajax_integration as $file => $integration) {
        if (!$integration['design_improvements']) {
            $all_integrated = false;
            break;
        }
    }
    
    if ($all_integrated) {
        return [
            'success' => true,
            'message' => "Frontend AJAX integration complete",
            'data' => ['integration' => $ajax_integration]
        ];
    } else {
        return [
            'success' => false,
            'message' => "Some frontend files missing AJAX integration",
            'data' => ['integration' => $ajax_integration]
        ];
    }
});

// Display test summary
echo "<div class='test-section'>
    <h2><i class='fas fa-chart-bar'></i> Test Summary</h2>
    <div class='row'>
        <div class='col-md-3'>
            <div class='card text-center'>
                <div class='card-body'>
                    <h3 class='text-primary'>$total_tests</h3>
                    <p class='mb-0'>Total Tests</p>
                </div>
            </div>
        </div>
        <div class='col-md-3'>
            <div class='card text-center'>
                <div class='card-body'>
                    <h3 class='text-success'>$passed_tests</h3>
                    <p class='mb-0'>Passed</p>
                </div>
            </div>
        </div>
        <div class='col-md-3'>
            <div class='card text-center'>
                <div class='card-body'>
                    <h3 class='text-danger'>$failed_tests</h3>
                    <p class='mb-0'>Failed</p>
                </div>
            </div>
        </div>
        <div class='col-md-3'>
            <div class='card text-center'>
                <div class='card-body'>
                    <h3 class='text-info'>" . round(($passed_tests / $total_tests) * 100, 1) . "%</h3>
                    <p class='mb-0'>Success Rate</p>
                </div>
            </div>
        </div>
    </div>
</div>";

// Display detailed results
echo "<div class='test-section'>
    <h2><i class='fas fa-list'></i> Detailed Results</h2>
    <div class='table-responsive'>
        <table class='table table-striped'>
            <thead>
                <tr>
                    <th>Test Name</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>";

foreach ($test_results as $result) {
    $status_class = $result['success'] ? 'text-success' : 'text-danger';
    $status_icon = $result['success'] ? 'fa-check' : 'fa-times';
    
    echo "<tr>
        <td>{$result['name']}</td>
        <td><i class='fas $status_icon $status_class'></i> " . ($result['success'] ? 'PASSED' : 'FAILED') . "</td>
        <td>{$result['message']}</td>
    </tr>";
}

echo "            </tbody>
        </table>
    </div>
</div>";

// Recommendations
echo "<div class='test-section'>
    <h2><i class='fas fa-lightbulb'></i> Recommendations</h2>
    <div class='alert alert-info'>
        <h5><i class='fas fa-info-circle'></i> Next Steps</h5>
        <ul class='mb-0'>
            <li>Review failed tests and fix any issues</li>
            <li>Test AJAX functionality in a browser environment</li>
            <li>Verify all endpoints work with proper authentication</li>
            <li>Test mobile responsiveness of AJAX features</li>
            <li>Monitor AJAX performance in production</li>
        </ul>
    </div>
</div>";

echo "</div>
</body>
</html>";

// Clean output buffer
ob_end_flush();
?>
