<?php
/* 
    Le module "Bibliothèque" permet à chaque utilisateur de gérer sa collection de jeux.
    Il peut :
    - Voir la liste de ses jeux avec le temps de jeu et les succès débloqués.
    - Ajouter un jeu à sa bibliothèque (en choisissant parmi les jeux référencés).
    - Retirer un jeu de sa bibliothèque.
    - Mettre à jour le temps de jeu pour chaque jeu.
    - Noter chaque jeu (1 à 5 étoiles).
    - Voir les détails d'un jeu (description, niveaux, succès, tutos, astuces).
    - Débloquer ou retirer des succès manuellement (pour simuler les progrès).
 */ 
require_once __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$jeu_id  = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── TOUTES LES ACTIONS POST ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['unlock_succes'])) {
        $suc_id = (int)$_POST['succes_id'];
        $check  = $pdo->prepare("SELECT id FROM user_succes WHERE user_id=? AND succes_id=?");
        $check->execute([$user_id, $suc_id]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO user_succes (user_id, succes_id, obtenu_le) VALUES (?,?,NOW())")
                ->execute([$user_id, $suc_id]);
        }
        header('Location: /bibliotheque' . ($jeu_id ? '?id=' . $jeu_id . '&flash=succes' : ''));
        exit;
    }

    if (isset($_POST['lock_succes'])) {
        $pdo->prepare("DELETE FROM user_succes WHERE user_id=? AND succes_id=?")
            ->execute([$user_id, (int)$_POST['succes_id']]);
        header('Location: /bibliotheque' . ($jeu_id ? '?id=' . $jeu_id . '&flash=retire' : ''));
        exit;
    }

    if (isset($_POST['add_jeu'])) {
        $jid   = (int)$_POST['jeu_id'];
        $temps = max(0, (int)($_POST['temps_jeu'] ?? 0));
        $check = $pdo->prepare("SELECT id FROM user_jeux WHERE user_id=? AND jeu_id=?");
        $check->execute([$user_id, $jid]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO user_jeux (user_id, jeu_id, date_ajout, temps_jeu) VALUES (?,?,NOW(),?)")
                ->execute([$user_id, $jid, $temps]);
        }
        header('Location: /bibliotheque');
        exit;
    }

    if (isset($_POST['remove_jeu'])) {
        $pdo->prepare("DELETE FROM user_jeux WHERE id=? AND user_id=?")
            ->execute([(int)$_POST['user_jeu_id'], $user_id]);
        header('Location: /bibliotheque');
        exit;
    }

    if (isset($_POST['update_temps'])) {
        $pdo->prepare("UPDATE user_jeux SET temps_jeu=? WHERE id=? AND user_id=?")
            ->execute([max(0, (int)$_POST['temps_jeu']), (int)$_POST['user_jeu_id'], $user_id]);
        header('Location: /bibliotheque');
        exit;
    }

    // ✅ update_note bien dans le bloc POST
    if (isset($_POST['update_note'])) {
        $note = (int)$_POST['note'];
        if ($note >= 1 && $note <= 5) {
            $pdo->prepare("UPDATE user_jeux SET note=? WHERE id=? AND user_id=?")
                ->execute([$note, (int)$_POST['user_jeu_id'], $user_id]);
        }
        header('Location: /bibliotheque');
        exit;
    }
}

$flash = $_GET['flash'] ?? '';

// ── VUE LISTE ────────────────────────────────────────────────────
if (!$jeu_id) {

    $stmt = $pdo->prepare("
        SELECT uj.id as uj_id, uj.date_ajout, uj.temps_jeu, uj.note,
               j.id as jeu_id, j.nom, j.type, j.description, j.image
        FROM user_jeux uj
        JOIN jeux j ON j.id = uj.jeu_id
        WHERE uj.user_id = ?
        ORDER BY uj.date_ajout DESC
    ");
    $stmt->execute([$user_id]);
    $ma_bibliotheque = $stmt->fetchAll();

    $mes_jeu_ids      = array_column($ma_bibliotheque, 'jeu_id');
    $tous_jeux        = $pdo->query("SELECT id, nom, type FROM jeux ORDER BY nom")->fetchAll();
    $jeux_disponibles = array_filter($tous_jeux, fn($j) => !in_array($j['id'], $mes_jeu_ids));

    $stmt = $pdo->prepare("SELECT succes_id FROM user_succes WHERE user_id=?");
    $stmt->execute([$user_id]);
    $succes_debloques = array_column($stmt->fetchAll(), 'succes_id');

    $succes_par_jeu = [];
    foreach ($ma_bibliotheque as $bib) {
        $stmt = $pdo->prepare("SELECT * FROM succes WHERE jeu_id=?");
        $stmt->execute([$bib['jeu_id']]);
        $succes_par_jeu[$bib['jeu_id']] = $stmt->fetchAll();
    }

    $total_jeux   = count($ma_bibliotheque);
    $total_temps  = array_sum(array_column($ma_bibliotheque, 'temps_jeu'));
    $total_succes = count($succes_debloques);

    require_once __DIR__ . '/../front/bibliotheque_liste.html';
    exit;
}

// ── VUE DÉTAIL ───────────────────────────────────────────────────
$check = $pdo->prepare("
    SELECT uj.*, j.nom, j.type, j.description, j.image
    FROM user_jeux uj
    JOIN jeux j ON j.id = uj.jeu_id
    WHERE uj.user_id = ? AND uj.jeu_id = ?
");
$check->execute([$user_id, $jeu_id]);
$user_jeu = $check->fetch();

if (!$user_jeu) {
    header('Location: /bibliotheque');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM jeux WHERE id = ?");
$stmt->execute([$jeu_id]);
$jeu = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM niveaux WHERE jeu_id = ? ORDER BY id");
$stmt->execute([$jeu_id]);
$niveaux = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM succes WHERE jeu_id = ? ORDER BY id");
$stmt->execute([$jeu_id]);
$succes = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT succes_id FROM user_succes WHERE user_id = ?");
$stmt->execute([$user_id]);
$succes_debloques = array_column($stmt->fetchAll(), 'succes_id');

$stmt = $pdo->prepare("SELECT * FROM tutos WHERE jeu_id = ? ORDER BY id");
$stmt->execute([$jeu_id]);
$tutos = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM astuces WHERE jeu_id = ? ORDER BY id");
$stmt->execute([$jeu_id]);
$astuces = $stmt->fetchAll();

$nb_total     = count($succes);
$nb_debloques = count(array_filter($succes, fn($s) => in_array($s['id'], $succes_debloques)));
$pct          = $nb_total > 0 ? round($nb_debloques / $nb_total * 100) : 0;
$total_jeux   = 1;
$total_temps  = $user_jeu['temps_jeu'] ?? 0;
$total_succes = $nb_debloques;
require_once __DIR__ . '/../front/bibliotheque.html';
