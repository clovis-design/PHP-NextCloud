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
</head>
<body>

<?php
    include __DIR__ . '/ProgressBar.php';
    include __DIR__ . '/EditFileName.php';
?>
    
</body>
<script src="./js/ProgressBar.js" defer></script>
<script src="./js/EditFileName.js" defer></script>
<script src="https://cdn.tailwindcss.com"></script>
</html>