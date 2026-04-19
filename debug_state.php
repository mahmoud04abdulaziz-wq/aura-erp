<?php
require_once __DIR__ . '/config/db_connect.php';
$r = $pdo->query('SHOW CREATE TABLE suppliers')->fetch(PDO::FETCH_NUM);
echo $r[1] . PHP_EOL . PHP_EOL;
$r2 = $pdo->query('SHOW CREATE TABLE item_master')->fetch(PDO::FETCH_NUM);
echo $r2[1]; 
