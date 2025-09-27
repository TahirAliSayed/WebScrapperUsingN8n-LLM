
<?php
$n8nResponse = null;
$result = $conn->query("SELECT data FROM n8n_responses ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $n8nResponse = json_decode($row['data'], true);
}

// Include database connection
include __DIR__ . '/../DB/db.php';

$message = "";

// n8n webhook URL
$n8nWebhookUrl = "http://localhost:5678/webhook-test/db53f19c-471c-43a5-a426-a1f40b8fbfdd";

// Check if form is submitted (normal form submission)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['site_url'])) {
    $siteUrl = trim($_POST['site_url']);

    if (!empty($siteUrl) && filter_var($siteUrl, FILTER_VALIDATE_URL)) {
        $stmt = $conn->prepare("INSERT INTO url (links) VALUES (?)");
        $stmt->bind_param("s", $siteUrl);

        if ($stmt->execute()) {
            $message = "✅ URL stored successfully!";

            // --- Trigger n8n workflow ---
            $ch = curl_init($n8nWebhookUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            $payload = json_encode(['site_url' => $siteUrl]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $message .= " ⚠️ n8n workflow could not be triggered: $curlError";
            } else {
                $message .= " 🚀 n8n workflow triggered successfully!";
            }
        } else {
            $message = "❌ Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $message = "⚠️ Invalid URL provided.";
    }
}

// Handle JSON input (if sent from API/cURL/fetch)
$incomingData = json_decode(file_get_contents("php://input"), true);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Site URL Submit</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen p-6">

  <!-- Card -->
  <div class="w-full max-w-md bg-white shadow-lg rounded-2xl p-8 mb-6">
    <h1 class="text-2xl font-semibold text-gray-800 text-center mb-6">
      Enter Website URL
    </h1>

    <!-- Show success/error message -->
    <?php if (!empty($message)): ?>
      <div class="mb-4 text-center p-3 rounded-lg 
                  <?php echo (strpos($message, '✅') !== false) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" class="flex flex-col gap-4">
      <input 
        type="url" 
        name="site_url" 
        placeholder="https://example.com" 
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        required
      >
      <button 
        type="submit" 
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition duration-200"
      >
        Submit
      </button>
    </form>
  </div>

  <!-- Incoming JSON Data Display -->
  <?php if (!empty($n8nResponse)): ?>
<div class="w-full max-w-2xl bg-white shadow-lg rounded-2xl p-6 mt-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">📊 Processed Data from n8n</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($n8nResponse as $key => $value): ?>
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                <span class="font-semibold text-gray-700"><?php echo htmlspecialchars(ucwords(str_replace('_',' ',$key))); ?>:</span>
                <p class="text-gray-800 mt-1"><?php echo htmlspecialchars(is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>


</body>
</html>
