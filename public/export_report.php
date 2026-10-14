<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();
if (!$user || $user['role_name'] !== 'admin_utama') { 
    header('Location: ./index.php'); 
    exit; 
}

$conn = get_db_connection();
$month = trim($_GET['month'] ?? '');
$scope = trim($_GET['scope'] ?? 'all');

if (!preg_match('/^\d{4}-\d{2}$/', $month)) { 
    $month = date('Y-m'); 
}
$dt = DateTime::createFromFormat('Y-m-d', $month . '-01');
$start = $dt->format('Y-m-01 00:00:00');
$end = $dt->format('Y-m-t 23:59:59');

// Nama file
$fnameScope = ($scope === 'all') ? 'semua' : $scope;
$filename = 'laporan_' . $fnameScope . '_' . str_replace('-', '', $month) . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=' . $filename);

$out = fopen('php://output', 'w');
// BOM UTF-8 agar Excel mengenali encoding dengan benar
fwrite($out, "\xEF\xBB\xBF");

$fmt_rp = function($n){ 
    return 'Rp ' . number_format((float)$n, 0, ',', '.'); 
};

try {
    if ($scope === 'stock') {
        // Agregasi pemasukan dan pengeluaran per barang - DIPERBAIKI
        $inQuery = "
            SELECT i.id, i.name AS item, SUM(sm.qty) AS total 
            FROM stock_movements sm 
            JOIN items i ON i.id = sm.item_id 
            WHERE sm.type = 'in' 
            AND sm.created_at BETWEEN :start AND :end 
            GROUP BY i.id, i.name 
            ORDER BY total DESC
        ";
        $stmt = $conn->prepare($inQuery);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $inItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $outQuery = "
            SELECT i.id, i.name AS item, SUM(sm.qty) AS total 
            FROM stock_movements sm 
            JOIN items i ON i.id = sm.item_id 
            WHERE sm.type = 'out' 
            AND sm.created_at BETWEEN :start AND :end 
            GROUP BY i.id, i.name 
            ORDER BY total DESC
        ";
        $stmt = $conn->prepare($outQuery);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $outItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        fputcsv($out, ['Jenis', 'Barang', 'Total Qty']);
        
        foreach ($inItems as $row) {
            fputcsv($out, ['IN', $row['item'], (int)$row['total']]);
        }
        
        foreach ($outItems as $row) {
            fputcsv($out, ['OUT', $row['item'], (int)$row['total']]);
        }
    }
    elseif ($scope === 'purchase') {
        $query = "
            SELECT id, COALESCE(purchase_date, created_at) AS tgl, price_total 
            FROM purchases 
            WHERE COALESCE(purchase_date, created_at) BETWEEN :start AND :end 
            ORDER BY id DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        fputcsv($out, ['ID', 'Tanggal', 'Total Harga']);
        
        foreach ($rows as $r) {
            fputcsv($out, [
                (int)$r['id'], 
                ($r['tgl'] ?? ''), 
                $fmt_rp($r['price_total'])
            ]);
        }
    }
    elseif ($scope === 'reimburse') {
        $query = "
            SELECT id, created_at AS tgl, description, amount 
            FROM reimbursements 
            WHERE created_at BETWEEN :start AND :end 
            ORDER BY id DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        fputcsv($out, ['ID', 'Tanggal', 'Deskripsi', 'Total']);
        
        foreach ($rows as $r) {
            fputcsv($out, [
                (int)$r['id'], 
                ($r['tgl'] ?? ''), 
                ($r['description'] ?? ''), 
                $fmt_rp($r['amount'])
            ]);
        }
    }
    else { 
        // all: gabungkan dalam satu CSV dengan kolom seragam
        fputcsv($out, ['Kategori', 'ID', 'Tanggal', 'Nama/Deskripsi', 'Jumlah/Total']);

        // Stok Masuk
        $inQuery = "
            SELECT i.id, i.name AS item, SUM(sm.qty) AS total 
            FROM stock_movements sm 
            JOIN items i ON i.id = sm.item_id 
            WHERE sm.type = 'in' 
            AND sm.created_at BETWEEN :start AND :end 
            GROUP BY i.id, i.name 
            ORDER BY total DESC
        ";
        $stmt = $conn->prepare($inQuery);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $inItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($inItems as $row) {
            fputcsv($out, [
                'STOK MASUK', 
                $row['id'], 
                '', 
                $row['item'], 
                (int)$row['total']
            ]);
        }

        // Stok Keluar
        $outQuery = "
            SELECT i.id, i.name AS item, SUM(sm.qty) AS total 
            FROM stock_movements sm 
            JOIN items i ON i.id = sm.item_id 
            WHERE sm.type = 'out' 
            AND sm.created_at BETWEEN :start AND :end 
            GROUP BY i.id, i.name 
            ORDER BY total DESC
        ";
        $stmt = $conn->prepare($outQuery);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $outItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($outItems as $row) {
            fputcsv($out, [
                'STOK KELUAR', 
                $row['id'], 
                '', 
                $row['item'], 
                (int)$row['total']
            ]);
        }

        // Purchases
        $purchaseQuery = "
            SELECT id, COALESCE(purchase_date, created_at) AS tgl, price_total 
            FROM purchases 
            WHERE COALESCE(purchase_date, created_at) BETWEEN :start AND :end 
            ORDER BY id DESC
        ";
        $stmt = $conn->prepare($purchaseQuery);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($purchases as $p) {
            fputcsv($out, [
                'PEMBELIAN', 
                (int)$p['id'], 
                ($p['tgl'] ?? ''), 
                '', 
                $fmt_rp($p['price_total'])
            ]);
        }

        // Reimburse
        $reimburseQuery = "
            SELECT id, created_at AS tgl, description, amount 
            FROM reimbursements 
            WHERE created_at BETWEEN :start AND :end 
            ORDER BY id DESC
        ";
        $stmt = $conn->prepare($reimburseQuery);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $reims = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reims as $r) {
            fputcsv($out, [
                'REIMBURSE', 
                (int)$r['id'], 
                ($r['tgl'] ?? ''), 
                ($r['description'] ?? ''), 
                $fmt_rp($r['amount'])
            ]);
        }

        // Tambahkan data pengambilan barang (item_takings) jika ada
        $takingQuery = "
            SELECT 
                it.id,
                it.taking_date AS tgl,
                u.name AS user_name,
                i.name AS item_name,
                it.quantity,
                it.purpose,
                it.status
            FROM item_takings it
            JOIN users u ON u.id = it.user_id
            JOIN items i ON i.id = it.item_id
            WHERE it.created_at BETWEEN :start AND :end
            ORDER BY it.created_at DESC
        ";
        $stmt = $conn->prepare($takingQuery);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $takings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($takings as $t) {
            fputcsv($out, [
                'PENGAMBILAN BARANG', 
                (int)$t['id'], 
                ($t['tgl'] ?? ''), 
                $t['item_name'] . ' - ' . $t['user_name'] . ' (' . $t['purpose'] . ')', 
                (int)$t['quantity']
            ]);
        }
    }

} catch (PDOException $e) {
    // Jika ada error, tampilkan pesan error
    fputcsv($out, ['Error', 'Terjadi kesalahan dalam mengekspor data']);
    fputcsv($out, ['Message', $e->getMessage()]);
}

fclose($out);
exit;
?>
