<?php
// Include database connection if needed
include __DIR__ . '/../DB/db.php';

// Get incoming JSON data from n8n
$incomingData = json_decode(file_get_contents("php://input"), true);

// Optional: store received data in database
if (!empty($incomingData)) {
    $stmt = $conn->prepare("INSERT INTO n8n_responses (data) VALUES (?)");
    $stmt->bind_param("s", json_encode($incomingData));
    $stmt->execute();
    $stmt->close();
}

// Respond to n8n with success
header('Content-Type: application/json');
echo json_encode(['status' => 'received']);
$conn->close();
?>
