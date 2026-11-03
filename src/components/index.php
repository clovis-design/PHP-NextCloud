<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../output.css">
</head>
<body>

<?php
    $zipUrl = 'https://exemple.fr/archive.zip';

    // Fonction de traduction temporaire
    $translate = fn($key) => $key;
    include __DIR__ . '/ProgressBar.php';
    include __DIR__ . '/WebContentViewer.php';
?>
    
</body>
<script src="./js/ProgressBar.js" defer></script>
<script src="./js/WebContentViewer.js" defer></script>
</html>