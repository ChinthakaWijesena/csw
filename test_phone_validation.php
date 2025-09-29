<?php
/**
 * Test Phone Validation Updates
 * Tests the new regex pattern for Sri Lankan phone numbers
 */

require_once __DIR__ . '/config/config.php';

echo "<h2>Phone Validation Test</h2>";
echo "<p>Testing the new regex pattern: <code>^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$</code></p>";

// Test cases - Only 07XXXXXXXX format
$test_cases = [
    // Valid 07XXXXXXXX formats (should pass)
    '0712345678' => true,   // Valid mobile
    '0723456789' => true,   // Valid mobile
    '0745678901' => true,   // Valid mobile
    '0756789012' => true,   // Valid mobile
    '0767890123' => true,   // Valid mobile
    '0778901234' => true,   // Valid mobile
    '0789012345' => true,   // Valid mobile
    
    // Invalid 07XXXXXXXX formats (should fail)
    '0734567890' => false,  // Invalid - 3rd digit is 3
    '0790123456' => false,  // Invalid - 3rd digit is 9
    '0701234567' => false,  // Invalid - too short
    '07123456789' => false, // Invalid - too long
    '0812345678' => false,  // Invalid - doesn't start with 07
    '071234567' => false,   // Invalid - too short
];

echo "<h3>Test Results</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Phone Number</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";

$passed = 0;
$total = count($test_cases);

foreach ($test_cases as $phone => $expected) {
    $actual = validate_phone($phone);
    $status = ($actual !== false) ? 'PASS' : 'FAIL';
    $result = ($actual !== false) === $expected ? '✅' : '❌';
    
    if (($actual !== false) === $expected) {
        $passed++;
    }
    
    echo "<tr>";
    echo "<td><code>$phone</code></td>";
    echo "<td>" . ($expected ? 'Valid' : 'Invalid') . "</td>";
    echo "<td>" . ($actual !== false ? 'Valid' : 'Invalid') . "</td>";
    echo "<td>$result $status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Summary</h3>";
echo "<p><strong>Passed:</strong> $passed / $total tests</p>";
echo "<p><strong>Success Rate:</strong> " . round(($passed / $total) * 100, 1) . "%</p>";

if ($passed === $total) {
    echo "<p style='color: green;'><strong>🎉 All tests passed! Phone validation is working correctly.</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Some tests failed. Please check the validation logic.</strong></p>";
}

// Test format_phone function - Now expects 07XXXXXXXX format
echo "<h3>Format Phone Function Test</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Input</th><th>Output</th><th>Status</th></tr>";

$format_tests = [
    '0712345678' => '0712345678',  // Should remain as 07XXXXXXXX
    '0723456789' => '0723456789',  // Should remain as 07XXXXXXXX
    '0745678901' => '0745678901',  // Should remain as 07XXXXXXXX
    '712345678' => '0712345678',   // Should add leading 0
    '723456789' => '0723456789',   // Should add leading 0
    '94712345678' => '0712345678', // Should convert 94 to 07
    '+94712345678' => '0712345678', // Should convert 94 to 07
];

foreach ($format_tests as $input => $expected) {
    $actual = format_phone($input);
    $status = ($actual === $expected) ? '✅ PASS' : '❌ FAIL';
    
    echo "<tr>";
    echo "<td><code>$input</code></td>";
    echo "<td><code>$actual</code></td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Test OTPService formatPhoneNumber function
echo "<h3>OTPService FormatPhoneNumber Test</h3>";
try {
    require_once __DIR__ . '/api/otp/OTPService.php';
    $otp_service = new OTPService();
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($otp_service);
    $method = $reflection->getMethod('formatPhoneNumber');
    $method->setAccessible(true);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Input</th><th>Output</th><th>Status</th></tr>";
    
    $otp_tests = [
        '0712345678' => '0712345678',
        '0723456789' => '0723456789',
        '0745678901' => '0745678901',
        '+94712345678' => '0712345678',  // Should convert to 07XXXXXXXX
        '+94723456789' => '0723456789',  // Should convert to 07XXXXXXXX
        '0734567890' => false, // Invalid
        '0790123456' => false, // Invalid
    ];
    
    foreach ($otp_tests as $input => $expected) {
        $actual = $method->invoke($otp_service, $input);
        $status = ($actual === $expected) ? '✅ PASS' : '❌ FAIL';
        
        echo "<tr>";
        echo "<td><code>$input</code></td>";
        echo "<td><code>" . ($actual ?: 'false') . "</code></td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing OTPService: " . $e->getMessage() . "</p>";
}

echo "<h3>JavaScript Regex Test</h3>";
echo "<p>Test the JavaScript regex pattern in your browser console:</p>";
echo "<pre>";
echo "// Test the new regex pattern
const phoneRegex = /^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/;

// Valid numbers (should return true)
console.log('0712345678:', phoneRegex.test('0712345678')); // true
console.log('0723456789:', phoneRegex.test('0723456789')); // true
console.log('0745678901:', phoneRegex.test('0745678901')); // true

// Invalid numbers (should return false)
console.log('0734567890:', phoneRegex.test('0734567890')); // false (3rd digit is 3)
console.log('0790123456:', phoneRegex.test('0790123456')); // false (3rd digit is 9)
console.log('0701234567:', phoneRegex.test('0701234567')); // false (too short)
";
echo "</pre>";

echo "<h3>Next Steps</h3>";
echo "<ul>";
echo "<li>Test the phone validation in the browser</li>";
echo "<li>Try registering with different phone number formats</li>";
echo "<li>Test admin login with the new format</li>";
echo "<li>Verify OTP sending works with both formats</li>";
echo "</ul>";
?>
