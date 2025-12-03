<?php
// Setează ca raportarea erorilor să fie afișată, util pentru depanare
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Verificare Dependențe (păstrezi codul existent) ---
$required_php_version = '7.3.0';
if (version_compare(PHP_VERSION, $required_php_version, '<')) {
    die("Eroare: Această aplicație necesită PHP versiunea " . $required_php_version . " sau mai nouă. Versiunea ta actuală este " . PHP_VERSION . ".");
}
if (!extension_loaded('curl')) {
    die("Eroare: Extensia PHP cURL nu este instalată sau activată. Te rog să o activezi în fișierul php.ini.");
}
// --- Sfârșit Verificare Dependențe ---

// Define calea către fișierul unde salvăm site-urile
$sites_file = 'sites.txt';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sites'])) {
    $sites_raw = $_POST['sites'];
    $sites = array_filter(array_map('trim', explode("\n", $sites_raw)));

    // --- NOU: Salvează lista de site-uri în fișier pentru utilizare ulterioară ---
    // Verifică dacă fișierul este scriibil
    if (is_writable(dirname($sites_file))) { // dirname() pentru director, is_writable() pentru fișier/director
        file_put_contents($sites_file, $sites_raw);
    } else {
        // Poți adăuga un mesaj de eroare sau logare dacă fișierul nu poate fi scris
        // echo "<p style='color: red;'>Avertisment: Nu se poate salva lista de site-uri. Verificați permisiunile directorului.</p>";
    }
    // --- Sfârșit salvare ---

    echo '<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezultate Verificare Status Site-uri</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
        h1 { text-align: center; color: #333; }
        .result-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .result-table th, .result-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .result-table th { background-color: #f2f2f2; }
        .status-up { color: green; font-weight: bold; }
        .status-down { color: red; font-weight: bold; }
        .status-error { color: orange; font-weight: bold; }
        .back-button { display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
        .back-button:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Rezultate Verificare Status Site-uri</h1>
        <table class="result-table">
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Status</th>
                    <th>Cod Status</th>
                    <th>Timp Răspuns (s)</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($sites as $url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $start_time = microtime(true);
            curl_exec($ch);
            $end_time = microtime(true);

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $response_time = round($end_time - $start_time, 3);

            curl_close($ch);

            $status_class = '';
            $status_text = '';

            if ($http_code >= 200 && $http_code < 300) {
                $status_text = 'UP';
                $status_class = 'status-up';
            } elseif ($http_code >= 300 && $http_code < 400) {
                $status_text = 'Redirecționare';
                $status_class = 'status-error';
            } elseif ($http_code >= 400 && $http_code < 500) {
                $status_text = 'Client Error';
                $status_class = 'status-down';
            } elseif ($http_code >= 500) {
                $status_text = 'Server Error';
                $status_class = 'status-down';
            } else {
                $status_text = 'Necunoscut';
                $status_class = 'status-error';
            }

            echo '<tr>';
            echo '<td>' . htmlspecialchars($url) . '</td>';
            echo '<td class="' . $status_class . '">' . $status_text . '</td>';
            echo '<td>' . $http_code . '</td>';
            echo '<td>' . $response_time . '</td>';
            echo '</tr>';

        } else {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($url) . '</td>';
            echo '<td class="status-error">URL Invalid</td>';
            echo '<td>N/A</td>';
            echo '<td>N/A</td>';
            echo '</tr>';
        }
    }

    echo '      </tbody>
        </table>
        <a href="index.php" class="back-button">Verifică alte site-uri</a>
    </div>
</body>
</html>';

} else {
    // Redirecționează la index.php acum
    header("Location: index.php");
    exit();
}
?>