<?php

// Include database connection
include __DIR__ . '/../DB/db.php';

$message = "";

// n8n webhook URL
$n8nWebhookUrl = "http://localhost:5678/webhook-test/db53f19c-471c-43a5-a426-a1f40b8fbfdd";

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
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $payload = json_encode(['site_url' => $siteUrl]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            curl_exec($ch);
            curl_close($ch);

            $message .= " 🚀 n8n workflow triggered successfully!";
        } else {
            $message = "❌ Error storing URL: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $message = "⚠️ Invalid URL provided.";
    }
}

// Function to format field names for display
function formatFieldName($key) {
    // Convert snake_case and camelCase to readable format
    $formatted = preg_replace('/([A-Z])/', ' $1', $key);
    $formatted = str_replace(['_', '-'], ' ', $formatted);
    return ucwords(trim($formatted));
}

// Function to format values based on their content
function formatValue($value, $key = '') {
    if (is_null($value)) {
        return '<span class="text-gray-400 italic">Not provided</span>';
    }
    
    if (is_bool($value)) {
        return $value ? 
            '<span class="inline-block px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Yes</span>' : 
            '<span class="inline-block px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">No</span>';
    }
    
    if (is_array($value)) {
        if (empty($value)) {
            return '<span class="text-gray-400 italic">Empty array</span>';
        }
        
        // Check if it's a simple array or complex nested structure
        $isSimpleArray = array_reduce($value, function($carry, $item) {
            return $carry && (is_string($item) || is_numeric($item));
        }, true);
        
        if ($isSimpleArray && count($value) <= 5) {
            // Display as badges for simple arrays
            $badges = array_map(function($item) {
                return '<span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium mr-1 mb-1">' . htmlspecialchars($item) . '</span>';
            }, $value);
            return implode('', $badges);
        } else {
            // Display as collapsible JSON for complex arrays
            $jsonString = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return '<details class="mt-2">
                        <summary class="cursor-pointer text-blue-600 hover:text-blue-800 font-medium">View Details (' . count($value) . ' items)</summary>
                        <pre class="mt-2 p-3 bg-gray-100 rounded text-xs overflow-x-auto">' . htmlspecialchars($jsonString) . '</pre>
                    </details>';
        }
    }
    
    if (is_string($value)) {
        // Check if it's a URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return '<a href="' . htmlspecialchars($value) . '" target="_blank" class="text-blue-600 hover:text-blue-800 underline break-all">' . htmlspecialchars($value) . '</a>';
        }
        
        // Check if it's an email
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return '<a href="mailto:' . htmlspecialchars($value) . '" class="text-blue-600 hover:text-blue-800 underline">' . htmlspecialchars($value) . '</a>';
        }
        
        // Check if it's a long text (description, content, etc.)
        if (strlen($value) > 200) {
            $preview = substr($value, 0, 200) . '...';
            return '<details class="mt-1">
                        <summary class="cursor-pointer text-gray-600 hover:text-gray-800">' . htmlspecialchars($preview) . ' <span class="text-blue-600 font-medium">Read more</span></summary>
                        <div class="mt-2 p-3 bg-gray-50 rounded text-sm">' . nl2br(htmlspecialchars($value)) . '</div>
                    </details>';
        }
        
        // Regular string
        return '<span class="text-gray-800">' . nl2br(htmlspecialchars($value)) . '</span>';
    }
    
    if (is_numeric($value)) {
        return '<span class="font-medium text-gray-900">' . number_format($value) . '</span>';
    }
    
    // Fallback for other types
    return '<span class="text-gray-600">' . htmlspecialchars(print_r($value, true)) . '</span>';
}

// Function to get appropriate icon for field type
function getFieldIcon($key, $value) {
    $key = strtolower($key);
    
    if (strpos($key, 'url') !== false || strpos($key, 'link') !== false) return '🔗';
    if (strpos($key, 'email') !== false) return '📧';
    if (strpos($key, 'title') !== false || strpos($key, 'name') !== false) return '📝';
    if (strpos($key, 'description') !== false || strpos($key, 'content') !== false) return '📄';
    if (strpos($key, 'status') !== false) return '📊';
    if (strpos($key, 'date') !== false || strpos($key, 'time') !== false) return '📅';
    if (strpos($key, 'count') !== false || strpos($key, 'number') !== false) return '🔢';
    if (strpos($key, 'image') !== false || strpos($key, 'photo') !== false) return '🖼️';
    if (strpos($key, 'tag') !== false || strpos($key, 'keyword') !== false) return '🏷️';
    if (strpos($key, 'error') !== false) return '⚠️';
    if (strpos($key, 'success') !== false) return '✅';
    if (is_bool($value)) return '🔘';
    if (is_array($value)) return '📋';
    
    return '📋'; // Default icon
}

// ---- Fetch the latest n8n responses (multiple for better context) ----
// ---- Fetch the latest n8n response ----
$responses = [];
$sql = "SELECT data, created_at FROM n8n_responses ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $responseData = json_decode($row['data'], true);
        if ($responseData) {
            $responses[] = [
                'data' => $responseData,
                'created_at' => $row['created_at']
            ];
        }
    }
}
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Site URL Submit</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .response-card {
        transition: all 0.3s ease;
    }
    .response-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    details[open] summary {
        color: #3B82F6;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen p-6">

  <div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Website Analysis Dashboard</h1>
      <p class="text-gray-600">Submit a URL to analyze and view n8n workflow responses</p>
    </div>

    <!-- URL Submission Card -->
    <div class="bg-white shadow-lg rounded-2xl p-8 mb-8">
      <h2 class="text-xl font-semibold text-gray-800 text-center mb-6">
        🌐 Enter Website URL
      </h2>

      <!-- Show success/error message -->
      <?php if (!empty($message)): ?>
        <div class="mb-6 text-center p-4 rounded-lg animate-pulse
                    <?php echo (strpos($message, '✅') !== false) ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <!-- Form -->
      <form method="POST" class="flex flex-col sm:flex-row gap-4">
        <input 
          type="url" 
          name="site_url" 
          placeholder="https://example.com" 
          class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          required
        >
        <button 
          type="submit" 
          class="px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-lg transition duration-200 transform hover:scale-105"
        >
          Analyze 🚀
        </button>
      </form>
    </div>

    <!-- Responses Section -->
    <?php if (!empty($responses)): ?>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-800">📊 Analysis Results</h2>
        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
          <?php echo count($responses); ?> Response<?php echo count($responses) > 1 ? 's' : ''; ?>
        </span>
      </div>

      <?php foreach ($responses as $index => $response): ?>
      <div class="response-card bg-white shadow-lg rounded-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">
              <?php echo $index === 0 ? '🆕 Latest Response' : '📄 Response #' . ($index + 1); ?>
            </h3>
            <span class="text-sm text-gray-500 bg-white px-3 py-1 rounded-full">
              📅 <?php echo date('M j, Y • g:i A', strtotime($response['created_at'])); ?>
            </span>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <?php if (empty($response['data'])): ?>
            <div class="text-center py-8 text-gray-500">
              <div class="text-4xl mb-2">📭</div>
              <p>No data available in this response</p>
            </div>
          <?php else: ?>
            <div class="grid gap-4">
              <?php 
              // Sort fields to show important ones first
              $sortedData = $response['data'];
              $priorityFields = ['title', 'url', 'description', 'status', 'success', 'error'];
              $sortedKeys = array_merge(
                array_intersect($priorityFields, array_keys($sortedData)),
                array_diff(array_keys($sortedData), $priorityFields)
              );
              ?>
              
              <?php foreach ($sortedKeys as $key): ?>
                <?php $value = $sortedData[$key]; ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                  <div class="flex items-start gap-3">
                    <span class="text-xl flex-shrink-0 mt-1">
                      <?php echo getFieldIcon($key, $value); ?>
                    </span>
                    <div class="flex-1 min-w-0">
                      <h4 class="font-medium text-gray-700 mb-2">
                        <?php echo formatFieldName($key); ?>
                      </h4>
                      <div class="text-sm">
                        <?php echo formatValue($value, $key); ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Footer with raw JSON toggle -->
        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
          <details>
            <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 font-medium">
              🔍 View Raw JSON Data
            </summary>
            <pre class="mt-3 p-4 bg-gray-800 text-green-400 rounded-lg text-xs overflow-x-auto font-mono">
<?php echo json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
            </pre>
          </details>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div class="bg-white shadow-lg rounded-2xl p-12 text-center">
      <div class="text-6xl mb-4">📊</div>
      <h3 class="text-xl font-semibold text-gray-800 mb-2">No Analysis Data Yet</h3>
      <p class="text-gray-600 mb-6">Submit a URL above to see n8n workflow analysis results here.</p>
      <div class="text-sm text-gray-500">
        Results will appear automatically after processing
      </div>
    </div>
    <?php endif; ?>
  </div>

</body>
</html>