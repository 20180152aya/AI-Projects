<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 1. التأكد من وجود ملف البيانات
if (!file_exists('tasks.json')) {
    die("Error: tasks.json not found. Go to index.php first.");
}

$taskData = json_decode(file_get_contents('tasks.json'), true);
$currentTime = time();
$intervalSeconds = $taskData['minutes'] * 60;
$nextRunTime = $taskData['last_sent'] + $intervalSeconds;

echo "Current Time: " . date('H:i:s', $currentTime) . "<br>";
echo "Next Reminder At: " . date('H:i:s', $nextRunTime) . "<br>";

// 2. فحص هل حان وقت الإرسال؟
if ($currentTime >= $nextRunTime) {
    echo "Time is up! Attempting to send email...<br>";
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['EMAIL_USER'];
        $mail->Password = $_ENV['EMAIL_PASS'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom($_ENV['EMAIL_USER'], 'AI Agent');
        $mail->addAddress($_ENV['EMAIL_USER']);
        $mail->Subject = 'AI Reminder: ' . $taskData['task'];
        $mail->Body = 'Reminder to: ' . $taskData['task'];
        
        if($mail->send()) {
            echo "✅ SUCCESS: Email sent to " . $_ENV['EMAIL_USER'] . "<br>";
            $taskData['last_sent'] = time();
            file_put_contents('tasks.json', json_encode($taskData));
            echo "Data updated. Next run in " . $taskData['minutes'] . " minutes.";
        }
    } catch (Exception $e) {
        echo "❌ MAILER ERROR: " . $mail->ErrorInfo;
    }
} else {
    $wait = $nextRunTime - $currentTime;
    echo "Wait $wait more seconds...";
}