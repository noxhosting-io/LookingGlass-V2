<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json');
echo json_encode($templateData, JSON_PRETTY_PRINT);


