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
    include __DIR__ . '/ProgressBar.php';
    include __DIR__ . '/FileExistsDialog.php';
?>
    
</body>
<script src="./js/ProgressBar.js" defer></script>
<script src="./js/FileExistsDialog.js" defer></script>
</html>