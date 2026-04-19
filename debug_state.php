<?php
require_once __DIR__ . '/config/db_connect.php';
echo "=== material_requests ===" . PHP_EOL;
$cols = $pdo->query('SHOW COLUMNS FROM material_requests')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']}) " . ($c['Null']==='NO'?'NOT NULL':'') . " {$c['Default']}" . PHP_EOL;

echo PHP_EOL . "=== purchase_orders ===" . PHP_EOL;
$cols = $pdo->query('SHOW COLUMNS FROM purchase_orders')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']}) " . ($c['Null']==='NO'?'NOT NULL':'') . " {$c['Default']}" . PHP_EOL;

echo PHP_EOL . "=== production_orders ===" . PHP_EOL;
$cols = $pdo->query('SHOW COLUMNS FROM production_orders')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']}) " . ($c['Null']==='NO'?'NOT NULL':'') . " {$c['Default']}" . PHP_EOL;

echo PHP_EOL . "=== recipes ===" . PHP_EOL;
$cols = $pdo->query('SHOW COLUMNS FROM recipes')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']}) " . ($c['Null']==='NO'?'NOT NULL':'') . " {$c['Default']}" . PHP_EOL;
