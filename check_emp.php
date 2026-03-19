<?php
require 'c:\Users\adamj\OneDrive\Desktop\grad prototype\config\db_connect.php';
$stmt = $pdo->query("DESCRIBE employees");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $row) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
