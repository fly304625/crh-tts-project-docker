<?php
// Налаштування відображення помилок
error_reporting(E_ALL);
ini_set('display_errors', 1);

function generateSpeech($text) {
    $curl = curl_init('http://172.22.0.2:5000/generate');
    //$curl = curl_init('http://python-service:5000/generate');
    
    error_log("Надсилаємо запит до Python сервера з текстом: " . $text);
    
    $payload = json_encode(['text' => $text]);
    error_log("Корисне навантаження: " . $payload);
    
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_DNS_CACHE_TIMEOUT => 2,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_RESOLVE => ['python-service:5000:172.28.0.2']
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err = curl_error($curl);
    
    error_log("Код відповіді: " . $httpCode);
    error_log("Відповідь: " . $response);
    if ($err) {
        error_log("Помилка CURL: " . $err);
    }
    
    curl_close($curl);
    
    if ($err) {
        return ['success' => false, 'error' => $err];
    }
    
    return json_decode($response, true);
}

// Обробка POST запиту
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Отримано POST запит");
    $text = $_POST['text'] ?? '';
    if (!empty($text)) {
        error_log("Отримано текст: " . $text);
        $result = generateSpeech($text);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else {
        error_log("Не отримано тексту в POST запиті");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Кримськотатарський Text-to-Speech</title>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        textarea {
            width: 100%;
            height: 150px;
            margin: 1rem 0;
            padding: 0.5rem;
        }
        button {
            padding: 0.5rem 1rem;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <h1>Кримськотатарський Text-to-Speech</h1>
    <form id="ttsForm">
        <textarea name="text" placeholder="Введіть текст кримськотатарською мовою" required></textarea>
        <button type="submit">Озвучити</button>
    </form>
    <div id="status"></div>
    <audio id="audioPlayer" controls style="display:none"></audio>
    
    <script>
    document.getElementById('ttsForm').onsubmit = async (e) => {
        e.preventDefault();
        const status = document.getElementById('status');
        const audio = document.getElementById('audioPlayer');
        status.textContent = 'Генерування аудіо...';
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: new FormData(e.target)
            });
            const data = await response.json();
            
            if (data.success) {
                status.textContent = 'Аудіо згенеровано!';
                audio.src = 'output/' + data.filename;
                audio.style.display = 'block';
                audio.play();
            } else {
                status.textContent = 'Помилка: ' + (data.error || 'Невідома помилка');
                status.className = 'error';
            }
        } catch (error) {
            status.textContent = 'Помилка при обробці запиту';
            status.className = 'error';
        }
    };
    </script>
</body>
</html>
