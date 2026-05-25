<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=medin_db', 'root', '');
$stmt = $db->query('DESCRIBE medicines');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
