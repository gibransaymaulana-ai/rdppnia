<?php
$conn = new mysqli('localhost', 'root', '', 'rapot_pondok');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$queries = [
    "ALTER TABLE settings ADD COLUMN logo VARCHAR(255) DEFAULT 'default_logo.png'",
    "ALTER TABLE settings ADD COLUMN nsm VARCHAR(50) DEFAULT '121232010084'",
    "ALTER TABLE settings ADD COLUMN npsn VARCHAR(50) DEFAULT '20280146'",
    "ALTER TABLE settings ADD COLUMN address VARCHAR(255) DEFAULT 'Jl. Raya Leuwiliang Karacak Km.3 Kp Geledug'",
    "ALTER TABLE settings ADD COLUMN district VARCHAR(100) DEFAULT 'LEUWILIANG'",
    "ALTER TABLE settings ADD COLUMN city VARCHAR(100) DEFAULT 'KABUPATEN BOGOR'"
];

foreach ($queries as $q) {
    $conn->query($q);
}
echo "Database updated for profile.";
?>
