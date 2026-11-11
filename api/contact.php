
<?php
// Contact form API - OWNER: Lai
// Handles messages from the About/Contact page.

require_once __DIR__ . '/helpers.php';

// Only allow POST requests
if (request_method() !== 'POST') {
    fail('Method not allowed. Use POST.', 405);
}

// Read the JSON data sent by Vue
$input = json_input();

$name = isset($input['name']) && is_string($input['name'])
    ? trim($input['name']) : '';

$email = isset($input['email']) && is_string($input['email'])
    ? trim($input['email']) : '';

$message = isset($input['message']) && is_string($input['message'])
    ? trim($input['message']) : '';

// Server-side validation
if ($name === '' || $email === '' || $message === '') {
    fail('All fields are required.', 400);
}

if (strlen($name) > 100) {
    fail('Name must not exceed 100 characters.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($email) > 150) {
    fail('Please enter a valid email address.', 400);
}

if (strlen($message) < 10 || strlen($message) > 2000) {
    fail('Message must contain 10 to 2000 characters.', 400);
}

// Store the contact message in MySQL.
// Requires the contact_messages table to be created.
$conn = db();

$stmt = $conn->prepare(
    'INSERT INTO contact_messages (name, email, message)
     VALUES (?, ?, ?)'
);

$stmt->bind_param('sss', $name, $email, $message);
$stmt->execute();

$stmt->close();

// Return success only after saving
reply(array('ok' => true));
