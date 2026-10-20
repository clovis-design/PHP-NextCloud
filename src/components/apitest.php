<?php
header('Content-Type: application/json');

$value = random_int(0, 100);; // replace with your real source (database, etc.)

echo json_encode(['value' => $value]);
?>