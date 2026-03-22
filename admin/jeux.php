<?php
/* 
    Le module "Jeux" permet à l'administrateur de gérer les jeux référencés sur le site.
    Il peut :
    - Ajouter, modifier ou supprimer un jeu (nom, type, description, image).
    - Gérer les niveaux associés à chaque jeu (nom, difficulté, description).
    - Gérer les succès liés à chaque jeu (nom, description).
    - Ajouter des tutos YouTube pour chaque jeu (titre, URL).
    - Ajouter des astuces pour chaque jeu (contenu textuel).
 */
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

    // ── Jeu ──
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
            } else {
                $stmt = $pdo->prepare("INSERT INTO jeux (nom, type, description, image, created_at) VALUES (?,?,?,?,NOW())");
                $stmt->execute([$nom, $type, $description, $image]);
            }
            header('Location: /admin/jeux?success=' . urlencode("Jeu sauvegardé."));
            exit;
        }
    }

    if (isset($_POST['delete_jeu'])) {
        $id = (int)$_POST['delete_jeu'];
        $pdo->prepare("DELETE FROM niveaux WHERE jeu_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM succes WHERE jeu_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM tutos WHERE jeu_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM astuces WHERE jeu_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM user_jeux WHERE jeu_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM jeux WHERE id=?")->execute([$id]);
        header('Location: /admin/jeux?success=' . urlencode("Jeu supprimé."));
        exit;
    }

    // ── Niveaux ──
    if (isset($_POST['save_niveau'])) {
        $jid         = (int)$_POST['jeu_id'];
        $nom         = trim($_POST['nom'] ?? '');
        $difficulte  = $_POST['difficulte'] ?? 'facile';
        $description = trim($_POST['description'] ?? '');
        $niv_id      = (int)($_POST['niv_id'] ?? 0);

        if ($niv_id > 0) {
            $pdo->prepare("UPDATE niveaux SET nom=?, difficulte=?, description=? WHERE id=?")
                ->execute([$nom, $difficulte, $description, $niv_id]);
        } else {
            $pdo->prepare("INSERT INTO niveaux (jeu_id, nom, difficulte, description) VALUES (?,?,?,?)")
                ->execute([$jid, $nom, $difficulte, $description]);
        }
        header('Location: /admin/jeux?action=niveaux&id=' . $jid);
        exit;
    }

    if (isset($_POST['delete_niveau'])) {
        $back = (int)$_POST['back_jeu_id'];
        $pdo->prepare("DELETE FROM niveaux WHERE id=?")->execute([(int)$_POST['delete_niveau']]);
        header('Location: /admin/jeux?action=niveaux&id=' . $back);
        exit;
    }

    // ── Succès ──
    if (isset($_POST['save_succes'])) {
        $jid         = (int)$_POST['jeu_id'];
        $nom         = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $suc_id      = (int)($_POST['suc_id'] ?? 0);

        if ($suc_id > 0) {
            $pdo->prepare("UPDATE succes SET nom=?, description=? WHERE id=?")
                ->execute([$nom, $description, $suc_id]);
        } else {
            $pdo->prepare("INSERT INTO succes (jeu_id, nom, description) VALUES (?,?,?)")
                ->execute([$jid, $nom, $description]);
        }
        header('Location: /admin/jeux?action=succes&id=' . $jid);
        exit;
    }

    if (isset($_POST['delete_succes'])) {
        $back = (int)$_POST['back_jeu_id'];
        $pdo->prepare("DELETE FROM succes WHERE id=?")->execute([(int)$_POST['delete_succes']]);
        header('Location: /admin/jeux?action=succes&id=' . $back);
        exit;
    }

    // ── Tutos YouTube ──
    if (isset($_POST['save_tuto'])) {
        $jid   = (int)$_POST['jeu_id'];
        $titre = trim($_POST['titre'] ?? '');
        $url   = trim($_POST['url'] ?? '');

        if (!empty($titre) && !empty($url)) {
            $pdo->prepare("INSERT INTO tutos (jeu_id, titre, url) VALUES (?,?,?)")
                ->execute([$jid, $titre, $url]);
        }
        header('Location: /admin/jeux?action=tutos&id=' . $jid);
        exit;
    }

    if (isset($_POST['delete_tuto'])) {
        $back = (int)$_POST['back_jeu_id'];
        $pdo->prepare("DELETE FROM tutos WHERE id=?")->execute([(int)$_POST['delete_tuto']]);
        header('Location: /admin/jeux?action=tutos&id=' . $back);
        exit;
    }

    // ── Astuces ──
    if (isset($_POST['save_astuce'])) {
        $jid     = (int)$_POST['jeu_id'];
        $contenu = trim($_POST['contenu'] ?? '');

        if (!empty($contenu)) {
            $pdo->prepare("INSERT INTO astuces (jeu_id, contenu) VALUES (?,?)")
                ->execute([$jid, $contenu]);
        }
        header('Location: /admin/jeux?action=astuces&id=' . $jid);
        exit;
    }

    if (isset($_POST['delete_astuce'])) {
        $back = (int)$_POST['back_jeu_id'];
        $pdo->prepare("DELETE FROM astuces WHERE id=?")->execute([(int)$_POST['delete_astuce']]);
        header('Location: /admin/jeux?action=astuces&id=' . $back);
        exit;
    }
}

// ── Données ──
$jeux    = $pdo->query("SELECT * FROM jeux ORDER BY created_at DESC")->fetchAll();
$jeu     = null;
$niveaux = [];
$succes  = [];
$tutos   = [];
$astuces = [];

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

    if ($action === 'tutos') {
        $stmt = $pdo->prepare("SELECT * FROM tutos WHERE jeu_id=? ORDER BY id");
        $stmt->execute([$jeu_id]);
        $tutos = $stmt->fetchAll();
    }

    if ($action === 'astuces') {
        $stmt = $pdo->prepare("SELECT * FROM astuces WHERE jeu_id=? ORDER BY id");
        $stmt->execute([$jeu_id]);
        $astuces = $stmt->fetchAll();
    }
}

$flash = $_GET['success'] ?? '';

require_once __DIR__ . '/../front/admin_jeux.html';