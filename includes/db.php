<?php
// includes/db.php
require_once __DIR__ . '/config.php';
// Pastikan file ini hanya berisi PHP (tidak ada output HTML atau BOM sebelum <?php)

/**
 * Mengembalikan koneksi PDO ke PostgreSQL
 * Sesuaikan konfigurasi di bawah sesuai environment-mu
 */
if (!function_exists('get_db_connection')) {
    function get_db_connection() {
        $host = DB_HOST;
        $port = DB_PORT;
        $dbname = DB_NAME;
        $user = DB_USER;
        $password = DB_PASS;

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

        try {
            $conn = new PDO($dsn, $user, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Jika ingin hasil associative default:
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $conn;
        } catch (PDOException $e) {
            // Jangan tampilkan error sensitif di production
            die("Koneksi DB gagal: " . $e->getMessage());
        }
    }
}

/**
 * Cek apakah sebuah kolom ada pada tabel (PostgreSQL)
 * Menggunakan information_schema.columns
 */
if (!function_exists('column_exists')) {
    function column_exists(PDO $conn, string $table, string $column): bool {
        $sql = "
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_name = :table
                  AND column_name = :column
            ) AS exists_col;
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':table' => $table,
            ':column' => $column
        ]);

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        // fetch returns array like ['exists_col' => 't'/'f' or 1/0], but PDO with boolean cast ok
        if ($res === false) return false;
        $val = array_values($res)[0];
        return (bool) $val;
    }
}
