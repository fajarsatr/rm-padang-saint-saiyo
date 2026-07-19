<?php
// ============================================================
// Koneksi Database
// Otomatis pakai environment variable dari Railway kalau ada (MYSQLHOST dkk,
// disuntik otomatis saat kamu menambahkan service MySQL di Railway).
// Kalau tidak ada (artinya jalan di localhost/XAMPP/Laragon), pakai default di bawah.
// ============================================================
$DB_HOST = getenv('MYSQLHOST') ?: 'localhost';
$DB_NAME = getenv('MYSQLDATABASE') ?: 'rm_padang_saiyo';
$DB_USER = getenv('MYSQLUSER') ?: 'root';
$DB_PASS = getenv('MYSQLPASSWORD') ?: '';   // default XAMPP/Laragon: password root kosong
$DB_PORT = getenv('MYSQLPORT') ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal. Kalau di localhost: pastikan MySQL XAMPP/Laragon menyala dan database 'rm_padang_saiyo' sudah di-import. Kalau di Railway: pastikan service MySQL sudah ditambahkan ke project. Detail: " . $e->getMessage());
}
