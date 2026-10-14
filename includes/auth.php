<?php
require_once __DIR__ . '/db.php';
session_start();

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        header('Location: ../public/index.php');
        exit;
    }
}

function has_role($roles) {
    $user = current_user();
    if (!$user) return false;
    if (is_array($roles)) {
        return in_array($user['role_name'], $roles, true);
    }
    return $user['role_name'] === $roles;
}

function fetch_company($conn) {
    $stmt = $conn->query("SELECT id, name, logo_path FROM company_settings LIMIT 1");
    if ($stmt) {
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }
    return ['name' => 'Perusahaan', 'logo_path' => null];
}
?>