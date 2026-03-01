<?php

$TOKEN = "8689749336:AAFP465sHej4857JisWra4qb0DCwQJA1Kkk";
$MI_USER_ID = 1101538753;

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$message = $update["message"] ?? null;

if (!$message) {
    exit;
}

$user_id = $message["from"]["id"];
$text = $message["text"] ?? "";
$chat_id = $message["chat"]["id"];

// seguridad: solo tú puedes usar el bot
if ($user_id != $MI_USER_ID) {
    exit;
}

$respuesta = "✅ Bot funcionando\n\nRecibí:\n" . $text;

$url = "https://api.telegram.org/bot$TOKEN/sendMessage";

$data = [
    "chat_id" => $chat_id,
    "text" => $respuesta
];

$options = [
    "http" => [
        "header"  => "Content-Type: application/x-www-form-urlencoded",
        "method"  => "POST",
        "content" => http_build_query($data),
    ],
];

$context = stream_context_create($options);
file_get_contents($url, false, $context);

?>
