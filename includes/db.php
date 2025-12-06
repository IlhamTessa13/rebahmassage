<?php
function db() {
    static $conn;

    if ($conn === null) {
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $dbname = 'rebah';

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $conn;
}
?>
