<?php
/* 
    L'administrateur peut voir la liste des utilisateurs, 
    supprimer des comptes (sauf les autres admins) 
    et changer les rôles (admin/user).
 */
require_once __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $id = (int)$_POST['delete_user'];
        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ? AND role != 'admin'");
        $stmt->execute([$id]);
    }
    if (isset($_POST['change_role'])) {
        $id = (int)$_POST['change_role'];
        $role = $_POST['new_role'] === 'admin' ? 'admin' : 'user';
        $stmt = $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
        $stmt->execute([$role, $id]);
    }
    header('Location: /admin');
    exit;
}

$stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$total   = count($users);
$admins  = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$members = $total - $admins;

require_once __DIR__ . '/../front/admin_page.html';