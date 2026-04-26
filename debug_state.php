<?php
require_once __DIR__ . '/config/db_connect.php';
$cols = $pdo->query('SHOW COLUMNS FROM recipes')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . ' ' . $c['Type'] . PHP_EOL;
