<?php
// Test the access check and purchase functionality locally
require_once 'config/config.php';

echo "<h2>Testing Access Check & Purchase Functionality</h2>";

$testPhone = "01000733148";
$testGrade = "senior2";
$testSubject = "mathematics";

echo "<h3>Student Data Test:</h3>";
echo "Phone: $testPhone<br>";
echo "Grade: $testGrade<br>";
echo "Subject: $testSubject<br>";

// Test Session 1 access (should fail - not purchased)
echo "<h3>1. Testing Session 1 Access (should be denied):</h3>";
$url1 = "http://localhost:8000/api/sessions.php?action=check-access&session_number=1&phone=" . urlencode($testPhone) . "&grade=" . urlencode($testGrade) . "&subject=" . urlencode($testSubject);
echo "URL: $url1<br>";
$result1 = file_get_contents($url1);
echo "Result: <pre>" . json_encode(json_decode($result1, true), JSON_PRETTY_PRINT) . "</pre>";

// Test Session 2 access (should succeed - already purchased)
echo "<h3>2. Testing Session 2 Access (should be granted):</h3>";
$url2 = "http://localhost:8000/api/sessions.php?action=check-access&session_number=2&phone=" . urlencode($testPhone) . "&grade=" . urlencode($testGrade) . "&subject=" . urlencode($testSubject);
echo "URL: $url2<br>";
$result2 = file_get_contents($url2);
echo "Result: <pre>" . json_encode(json_decode($result2, true), JSON_PRETTY_PRINT) . "</pre>";

// Test purchase Session 1 
echo "<h3>3. Testing Purchase Session 1 (should work - has 600 EGP balance):</h3>";
$url3 = "http://localhost:8000/api/sessions.php?action=purchase-session&sessionNumber=1&phone=" . urlencode($testPhone) . "&grade=" . urlencode($testGrade) . "&subject=" . urlencode($testSubject);
echo "URL: $url3<br>";
$result3 = file_get_contents($url3);
echo "Result: <pre>" . json_encode(json_decode($result3, true), JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>4. Test API Config Detection:</h3>";
echo "<script>
document.write('<p>API Base URL: ' + window.API_BASE_URL + '</p>');
document.write('<p>Base URL: ' + window.BASE_URL + '</p>');
</script>";
?>

<script src="js/api-config.js"></script>