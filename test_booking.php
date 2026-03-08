<?php
require_once('wp-load.php');

$data = json_encode(array(
    "name" => "Test",
    "email" => "test@example.com",
    "phone" => "1234567890",
    "type" => "doctor",
    "category" => "",
    "breed" => "",
    "doctor" => "Dr. Malli babu",
    "date" => "2030-01-01",
    "time" => "10:00 AM",
    "payment" => "clinic",
    "amount" => 500
));

$_SERVER['REQUEST_METHOD'] = 'POST';
$stream = fopen('php://memory', 'r+');
fwrite($stream, $data);
rewind($stream);

// Replace php://input with our stream is tricky in standard code,
// but we can just call the handler directly if we override file_get_contents inside the function, 
// OR we can just hit the site with a real request if we know the URL.

// Since wp-load.php is included, let's see site url:
echo get_option('siteurl');
