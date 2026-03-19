<?php
require 'c:\Users\adamj\OneDrive\Desktop\grad prototype\config\db_connect.php';
$stmt = $pdo->query("SELECT * FROM roles");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
