<?php

function getProjectId($project_name, $workspace_id, $api_token) {

    static $cache = [];

    if (isset($cache[$project_name])) {
        return $cache[$project_name];
    }

    switch ($project_name) {
		case 'LIFE':
		case 'MYM_LIFE':
			$project_name = 'MYM_LIFE';
			break;

		case 'Galicia':
			$project_name = 'MYM_Galicia';
			break;

		case 'Interna':
		case 'Interno':
			$project_name = 'ARG_Actividades Internas';
			break;

		case 'InMotion':
		case 'In-Motion':
			$project_name = 'ARG-In Motion';
			break;

		case 'FUERA':
			$project_name = 'ARG_FUERA de la OFICINA';
			break;

		case 'LIFE':
			$project_name = 'MYM_Life';
			break;

		case 'SMG':
		case 'Swiss Medical':
		case 'Swiss':
			$project_name = 'MYM_SMG';
			break;

	}    

    $ch = curl_init("https://api.track.toggl.com/api/v9/workspaces/$workspace_id/projects");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$api_token:api_token");

    $response = curl_exec($ch);
    curl_close($ch);

    $projects = json_decode($response, true);

    foreach ($projects as $project) {
        if (strcasecmp($project["name"], $project_name) == 0) {
            $cache[$project_name] = $project["id"];
            return $project["id"];
        }
    }

    return null;
}

date_default_timezone_set(getenv("TZ") ?: "America/Argentina/Buenos_Aires");

$TOKEN = "8689749336:AAFP465sHej4857JisWra4qb0DCwQJA1Kkk";
$MI_USER_ID = 1101538753;

$TOGGL_API_TOKEN = getenv("TOGGL_API_TOKEN");
$WORKSPACE_ID = getenv("TOGGL_WORKSPACE_ID");

/**/
$PROJECT_ID = getenv("TOGGL_LIFE_PROJECT_ID");
$TAG_NAME = getenv("TOGGL_TAG_PROGRAMA");

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

  //if (!preg_match('/(\d{1,2}):?(\d{0,2})?\s*a\s*(\d{1,2}):?(\d{0,2})?\s+(.*)/i', $line, $m)) {
    if (!preg_match('/(\d{1,2}):?(\d{0,2})?\s*a\s*(\d{1,2}):?(\d{0,2})?\s+(.*)/i', $line, $m)) {
        continue;
    }

    $h1 = $m[1];
    $m1 = $m[2] ?: "00";
    $h2 = $m[3];
    $m2 = $m[4] ?: "00";
    
    //$desc = $m[5];
    $full = trim($m[5]);
    $parts = explode(" - ", $full);
    $desc = trim($parts[0] ?? "");
    $project_name = trim($parts[1] ?? "");
    $tag_name = trim($parts[2] ?? "");    

    $start = strtotime("$h1:$m1");
    $end = strtotime("$h2:$m2");

    $duration = $end - $start;

    $start_iso = date("Y-m-d\TH:i:sP", $start);

    $project_id = getProjectId($project_name, $WORKSPACE_ID, $TOGGL_API_TOKEN);

    if (!$project_id) {
        $resultados[] = "❌ Proyecto no encontrado: $project_name";
        continue;
    }

    
    $data = [
        "created_with" => "Telegram IA Bot",
        "description" => $desc,
        "start" => $start_iso,
        "duration" => $duration,
        "workspace_id" => (int)$WORKSPACE_ID,
        "project_id" => (int)$project_id,
        "tags" => [$tag_name]
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
        $resultados[] = "❌ Error en CURL en: $desc";
    } else {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if ($http_code == 200 || $http_code == 201) {
            $resultados[] = "✅ $desc";
        } else {
            $resultados[] = "❌ Error-*-> $http_code en: $desc\n$response\n$PROJECT_ID \n$TAG_NAME";
        }
    }
    curl_close($ch);

    //$resultados[] = "✅ $desc";
}

$respuesta = "Registros cargados:\n\n" . implode("\n", $resultados);

$url = "https://api.telegram.org/bot$TOKEN/sendMessage";

file_get_contents($url . "?" . http_build_query([
    "chat_id" => $chat_id,
    "text" => $respuesta
]));

?>
