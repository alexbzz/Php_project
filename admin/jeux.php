<?php
require_once __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login');
    exit;
}

$errors  = [];
$success = '';
$action  = $_GET['action'] ?? 'list';
$jeu_id  = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['save_jeu'])) {
        $nom         = trim($_POST['nom'] ?? '');
        $type        = trim($_POST['type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image       = trim($_POST['image'] ?? '');
        $edit_id     = (int)($_POST['edit_id'] ?? 0);

        if (empty($nom))  $errors[] = "Le nom est requis.";
        if (empty($type)) $errors[] = "Le type est requis.";

        if (empty($errors)) {
            if ($edit_id > 0) {
                $stmt = $pdo->prepare("UPDATE jeux SET nom=?, type=?, description=?, image=? WHERE id=?");
                $stmt->execute([$nom, $type, $description, $image, $edit_id]);
                $success = "Jeu mis à jour avec succès.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO jeux (nom, type, description, image, created_at) VALUES (?,?,?,?,NOW())");
                $stmt->execute([$nom, $type, $description, $image]);
                $success = "Jeu ajouté avec succès.";
            }
            header('Location: /admin/jeux?success=' . urlencode($success));
            exit;
        }
    }

    if (isset($_POST['delete_jeu'])) {
        $id = (int)$_POST['delete_jeu'];
        $pdo->prepare("DELETE FROM niveaux WHERE jeu_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM succes WHERE jeu_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM user_jeux WHERE jeu_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM jeux WHERE id=?")->execute([$id]);
        header('Location: /admin/jeux?success=' . urlencode("Jeu supprimé."));
        exit;
    }

    if (isset($_POST['save_niveau'])) {
        $jid         = (int)$_POST['jeu_id'];
        $nom         = trim($_POST['nom'] ?? '');
        $difficulte  = $_POST['difficulte'] ?? 'facile';
        $description = trim($_POST['description'] ?? '');
        $niv_id      = (int)($_POST['niv_id'] ?? 0);

        if ($niv_id > 0) {
            $stmt = $pdo->prepare("UPDATE niveaux SET nom=?, difficulte=?, description=? WHERE id=?");
            $stmt->execute([$nom, $difficulte, $description, $niv_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO niveaux (jeu_id, nom, difficulte, description) VALUES (?,?,?,?)");
            $stmt->execute([$jid, $nom, $difficulte, $description]);
        }
        header('Location: /admin/jeux?action=niveaux&id=' . $jid);
        exit;
    }

    if (isset($_POST['delete_niveau'])) {
        $niv_id = (int)$_POST['delete_niveau'];
        $back   = (int)$_POST['back_jeu_id'];
        $pdo->prepare("DELETE FROM niveaux WHERE id=?")->execute([$niv_id]);
        header('Location: /admin/jeux?action=niveaux&id=' . $back);
        exit;
    }

    if (isset($_POST['save_succes'])) {
        $jid         = (int)$_POST['jeu_id'];
        $nom         = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $suc_id      = (int)($_POST['suc_id'] ?? 0);

        if ($suc_id > 0) {
            $stmt = $pdo->prepare("UPDATE succes SET nom=?, description=? WHERE id=?");
            $stmt->execute([$nom, $description, $suc_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO succes (jeu_id, nom, description) VALUES (?,?,?)");
            $stmt->execute([$jid, $nom, $description]);
        }
        header('Location: /admin/jeux?action=succes&id=' . $jid);
        exit;
    }

    if (isset($_POST['delete_succes'])) {
        $suc_id = (int)$_POST['delete_succes'];
        $back   = (int)$_POST['back_jeu_id'];
        $pdo->prepare("DELETE FROM succes WHERE id=?")->execute([$suc_id]);
        header('Location: /admin/jeux?action=succes&id=' . $back);
        exit;
    }
}

$jeux    = $pdo->query("SELECT * FROM jeux ORDER BY created_at DESC")->fetchAll();
$jeu     = null;
$niveaux = [];
$succes  = [];

if ($jeu_id) {
    $stmt = $pdo->prepare("SELECT * FROM jeux WHERE id=?");
    $stmt->execute([$jeu_id]);
    $jeu = $stmt->fetch();

    if ($action === 'niveaux') {
        $stmt = $pdo->prepare("SELECT * FROM niveaux WHERE jeu_id=? ORDER BY id");
        $stmt->execute([$jeu_id]);
        $niveaux = $stmt->fetchAll();
    }

    if ($action === 'succes') {
        $stmt = $pdo->prepare("SELECT * FROM succes WHERE jeu_id=? ORDER BY id");
        $stmt->execute([$jeu_id]);
        $succes = $stmt->fetchAll();
    }
}

$flash = $_GET['success'] ?? '';

require_once __DIR__ . '/../front/admin_jeux.html';