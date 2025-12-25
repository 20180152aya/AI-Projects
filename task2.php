<?php
session_start();

require 'vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$error_debug = ""; 
$dataFile = 'data.txt'; 
$contextFile = file_exists($dataFile) ? file_get_contents($dataFile) : "Knowledge base is empty.";

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}
if (isset($_GET['clear'])) {
    $_SESSION['chat_history'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['question'])) {
    
 
    $current_msg_hash = md5($_POST['question']); 
    if (isset($_SESSION['last_msg_hash']) && $_SESSION['last_msg_hash'] === $current_msg_hash) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    $_SESSION['last_msg_hash'] = $current_msg_hash;


    $userQuestion = $_POST['question'];
    $apiKey = $_ENV['GEMINI_API_KEY'];
    
    $_SESSION['chat_history'][] = ["role" => "user", "parts" => [["text" => $userQuestion]]];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
    $systemInstruction = "Answer strictly based on the context provided: \n" . $contextFile;

    $data = [
        "contents" => $_SESSION['chat_history'],
        "system_instruction" => ["parts" => [["text" => $systemInstruction]]]
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
        $answer = $resArr['candidates'][0]['content']['parts'][0]['text'];
        $_SESSION['chat_history'][] = ["role" => "model", "parts" => [["text" => $answer]]];
    } else {
        $_SESSION['error_temp'] = "API Error. Check your Key or Context size.";
    }
    curl_close($ch);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if(isset($_SESSION['error_temp'])){
    $error_debug = $_SESSION['error_temp'];
    unset($_SESSION['error_temp']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .chat-container { width: 100%; max-width: 600px; height: 85vh; background: #ffffff; display: flex; flex-direction: column; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); overflow: hidden; }
        .chat-header { background:#1a88a3ff; color: white; padding: 15px 20px; text-align: center; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .chat-header a { color: #e0f2f1; text-decoration: none; font-size: 0.75rem; background: rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 5px; }
        .chat-messages { flex: 1; padding: 25px; overflow-y: auto; background: #fff; display: flex; flex-direction: column; gap: 15px; }
        .message { display: flex; align-items: flex-end; max-width: 85%; }
        .message.user { align-self: flex-end; flex-direction: row-reverse; }
        .message.bot { align-self: flex-start; }
        .bubble { padding: 12px 16px; border-radius: 18px; font-size: 0.95rem; line-height: 1.4; word-wrap: break-word; }
        .user .bubble { background: #1a88a3ff; color: white; border-bottom-right-radius: 4px; margin-right: 8px; }
        .bot .bubble { background: #f1f1f1; color: #1f2937; border-bottom-left-radius: 4px; margin-left: 8px; }
        .chat-input { padding: 20px; background: white; border-top: 1px solid #f3f4f6; display: flex; gap: 12px; }
        textarea { flex: 1; border: 1px solid #e5e7eb; border-radius: 24px; padding: 12px 20px; resize: none; outline: none; background: #f9fafb; height: 45px; }
        button { background: #1a88a3ff; color: white; border: none; width: 45px; height: 45px; border-radius: 50%; cursor: pointer; display: flex; justify-content: center; align-items: center; }
        .debug-error { color: red; font-size: 0.8rem; padding: 10px; text-align: center; background: #ffebeb; }
    </style>
</head>
<body>

    <div class="chat-container">
        <div class="chat-header">
            <span><i class="fas fa-brain"></i> AI Assistant</span>
            <a href="?clear=1"><i class="fas fa-trash"></i> Clear Chat</a>
        </div>

        <?php if ($error_debug): ?>
            <div class="debug-error"><?php echo $error_debug; ?></div>
        <?php endif; ?>

        <div class="chat-messages" id="chat-box">
            <div class="message bot">
                <div class="bubble">Hello! How can I help you today?</div>
            </div>

            <?php foreach ($_SESSION['chat_history'] as $msg): ?>
                <div class="message <?php echo ($msg['role'] === 'user') ? 'user' : 'bot'; ?>">
                    <div class="bubble">
                        <?php echo nl2br(htmlspecialchars($msg['parts'][0]['text'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form class="chat-input" method="POST" id="chat-form">
            <textarea name="question" id="question-input" placeholder="Type message..." required></textarea>
            <button type="submit" id="send-btn"><i class="fas fa-arrow-up"></i></button>
        </form>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;

        document.getElementById('chat-form').onsubmit = function() {
            document.getElementById('send-btn').disabled = true;
            document.getElementById('send-btn').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        };

        
        document.getElementById('question-input').addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('chat-form').submit();
            }
        });
    </script>
</body>
</html>