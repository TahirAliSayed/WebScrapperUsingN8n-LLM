<?php
// submit.php

// Include database connection
include 'db.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the URL from the form
    $siteUrl = trim($_POST['site_url']);

    // Validate URL
    if (!empty($siteUrl) && filter_var($siteUrl, FILTER_VALIDATE_URL)) {
        
        // Prepare statement to avoid SQL injection
        $stmt = $conn->prepare("INSERT INTO url (links) VALUES (?)");
        $stmt->bind_param("s", $siteUrl);

        if ($stmt->execute()) {
            echo "✅ URL stored successfully!";
        } else {
            echo "❌ Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "⚠️ Invalid URL provided.";
    }
}

// Fetch latest n8n response
$n8nResponse = null;
$result = $conn->query("SELECT data FROM n8n_responses ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $n8nResponse = json_decode($row['data'], true);
}


$conn->close();
?>
