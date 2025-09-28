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


$conn->close();
?>
