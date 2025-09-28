<?php
// Include database connection if needed
include __DIR__ . '/../DB/db.php';

// Get incoming JSON data from n8n
$incomingData = json_decode(file_get_contents("php://input"), true);

// Optional: store received data in database
if (!empty($incomingData)) {
    // Try to find the URL ID based on the data from n8n
    $urlId = null;
    
    // Method 1: If n8n sends back the original URL in the response
    if (isset($incomingData['url']) || isset($incomingData['site_url']) || isset($incomingData['original_url'])) {
        $originalUrl = $incomingData['url'] ?? $incomingData['site_url'] ?? $incomingData['original_url'];
        $stmt = $conn->prepare("SELECT id FROM url WHERE links = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("s", $originalUrl);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $urlId = $row['id'];
        }
        $stmt->close();
    }
    
    // Method 2: If no URL found in response, use the most recent URL entry
    if ($urlId === null) {
        $stmt = $conn->prepare("SELECT id FROM url ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $urlId = $row['id'];
        }
        $stmt->close();
    }
    
    // Only insert if we found a valid URL ID
    if ($urlId !== null) {
        $stmt = $conn->prepare("INSERT INTO n8n_responses (url_id, data) VALUES (?, ?)");
        $stmt->bind_param("is", $urlId, json_encode($incomingData));
        $stmt->execute();
        $stmt->close();
    }
}

// Respond to n8n with success
header('Content-Type: application/json');
echo json_encode(['status' => 'received']);
$conn->close();
?>