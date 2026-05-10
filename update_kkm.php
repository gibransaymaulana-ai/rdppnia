<?php
$conn = new mysqli('localhost', 'root', '', 'rapot_pondok');
$conn->query("ALTER TABLE subjects ADD COLUMN kkm INT DEFAULT 75");
echo "KKM updated.";
?>
