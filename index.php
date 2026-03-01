<?php

date_default_timezone_set(getenv("TZ") ?: "America/Argentina/Buenos_Aires");

$TOKEN = "8689749336:AAFP465sHej4857JisWra4qb0DCwQJA1Kkk";
$MI_USER_ID = 1101538753;

$TOGGL_API_TOKEN = getenv("TOGGL_API_TOKEN");
$WORKSPACE_ID = getenv("TOGGL_WORKSPACE_ID");

/**/
$PROJECT_ID = getenv("TOGGL_LIFE_PROJECT_ID");
$TAG_ID = getenv("TOGGL_TAG_MAILS_ID");

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) exit;

$message = $update["message"] ?? null;

if (!$message) exit;

$user_id = $message["from"]["id"];
$text = trim($message["text"] ?? "");
$chat_id = $message["chat"]["id"];

if ($user_id != $MI_USER_ID) exit;

// dividir líneas
$lines = explode("\n", $text);

$resultados = [];

foreach ($lines as $line) {

    if (!preg_match('/(\d{1,2}):?(\d{0,2})?\s*a\s*(\d{1,2}):?(\d{0,2})?\s+(.*)/i', $line, $m)) {
        continue;
    }

    $h1 = $m[1];
    $m1 = $m[2] ?: "00";
    $h2 = $m[3];
    $m2 = $m[4] ?: "00";
    $desc = $m[5];

    $start = strtotime("$h1:$m1");
    $end = strtotime("$h2:$m2");

    $duration = $end - $start;

    $start_iso = date("Y-m-d\TH:i:sP", $start);

    $data = [
        "created_with" => "Telegram IA Bot",
        "description" => $desc,
        "start" => $start_iso,
        "duration" => $duration,
        "workspace_id" => (int)$WORKSPACE_ID, 
        "project_id" => (int)$PROJECT_ID,
        "tags" => [(int)$TAG_ID]
    ];

    $ch = curl_init("https://api.track.toggl.com/api/v9/workspaces/$WORKSPACE_ID/time_entries");

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_USERPWD, "$TOGGL_API_TOKEN:api_token");

    $response = curl_exec($ch);

    if ($response === false) {
        $resultados[] = "❌ Error CURL en: $desc";
    } else {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if ($http_code == 200 || $http_code == 201) {
            $resultados[] = "✅ $desc";
        } else {
            $resultados[] = "❌ Error $http_code en: $desc\n$response\n$PROJECT_ID \n$TAG_ID";
        }
    }
    curl_close($ch);

    $resultados[] = "✅ $desc";
}

$respuesta = "Registros cargados:\n\n" . implode("\n", $resultados);

$url = "https://api.telegram.org/bot$TOKEN/sendMessage";

file_get_contents($url . "?" . http_build_query([
    "chat_id" => $chat_id,
    "text" => $respuesta
]));

?>
