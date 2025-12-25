<?php
require 'vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userRequest = $_POST['remind_me'];
    $apiKey = $_ENV['GEMINI_API_KEY'];
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [["parts" => [["text" => "Extract task and interval in minutes from: '$userRequest'. Respond ONLY JSON: {'task': 'string', 'minutes': int}"]]]]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $resArr = json_decode($res, true);
    
    if (isset($resArr['candidates'][0]['content']['parts'][0]['text'])) {
        $aiResponse = $resArr['candidates'][0]['content']['parts'][0]['text'];
        $taskData = json_decode(trim(str_replace(['```json', '```'], '', $aiResponse)), true);

        if ($taskData) {
            $taskData['last_sent'] = time(); 
            file_put_contents('tasks.json', json_encode($taskData));
            $message = "Success! I will remind you to " . $taskData['task'] . " every " . $taskData['minutes'] . " minutes. You can close this page now.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Agent - Background Mode</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 400px; text-align: center; }
        input { width: 100%; padding: 12px; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #2ecc71; color: white; border: none; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>AI Background Agent</h2>
        <form method="POST">
            <input type="text" name="remind_me" placeholder="e.g. Remind me to walk every 2 minutes" required>
            <button type="submit">Start Agent</button>
        </form>
        <?php if ($message): ?>
            <p style="color: #27ae60; font-weight: bold;"><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>