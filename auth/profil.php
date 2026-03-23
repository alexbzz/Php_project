<?php
/* 
    Page de profil utilisateur, accessible uniquement aux utilisateurs connectés.
    Affiche les informations du profil (nom, email, avatar, thème, bio) et permet de les modifier.
    Affiche la bibliothèque de jeux de l'utilisateur avec les succès débloqués et le temps de jeu pour chaque jeu.
    Permet d'ajouter ou de retirer des jeux de la bibliothèque, ainsi que de débloquer ou retirer des succès.
 */

require_once __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$errors  = [];
$success = false;
$flash   = '';

$avatars = [
    'avatar1' => ['img' => '/assets/avatar1.jpg', 'label' => 'Avatar 1'],
    'avatar2' => ['img' => '/assets/avatar2.jpg', 'label' => 'Avatar 2'],
    'avatar3' => ['img' => '/assets/avatar3.jpg', 'label' => 'Avatar 3'],
    'avatar4' => ['img' => '/assets/avatar4.jpg', 'label' => 'Avatar 4'],
];

$user_id = (int)$_SESSION['user_id'];

// Fonction de validation du mot de passe
function validatePassword($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule.";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
    }

    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un symbole spécial (!@#$%^&*...).";
    }

    return $errors;
}

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: /login');
    exit;
}

// Gérer les différentes actions POST

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profil'])) {
    $nom      = trim($_POST['nom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $avatar   = $_POST['avatar'] ?? $user['avatar'];
    $theme    = $_POST['theme'] ?? $user['theme'];
    $bio      = trim($_POST['bio'] ?? '');

    if (empty($nom))   $errors[] = "Le nom est requis.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (!array_key_exists($avatar, $avatars)) $errors[] = "Avatar invalide.";
    if (!in_array($theme, ['dark', 'light'])) $errors[] = "Thème invalide.";

    if (!empty($password)) {
        $passwordErrors = validatePassword($password);
        $errors = array_merge($errors, $passwordErrors);
        if ($password !== $confirm) $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) $errors[] = "Cet email est déjà utilisé.";
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom=?, email=?, password=?, avatar=?, theme=?, bio=? WHERE id=?");
            $stmt->execute([$nom, $email, $hash, $avatar, $theme, $bio, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom=?, email=?, avatar=?, theme=?, bio=? WHERE id=?");
            $stmt->execute([$nom, $email, $avatar, $theme, $bio, $user_id]);
        }
        $_SESSION['user_nom'] = $nom;
        $success = true;
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
}

// Gérer les actions liées à la bibliothèque de jeux et aux succès
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_jeu'])) {
    $jeu_id = (int)$_POST['jeu_id'];
    $temps  = max(0, (int)($_POST['temps_jeu'] ?? 0));

    $check = $pdo->prepare("SELECT id FROM user_jeux WHERE user_id=? AND jeu_id=?");
    $check->execute([$user_id, $jeu_id]);
    if ($check->fetch()) {
        $flash = "error:Ce jeu est déjà dans ta bibliothèque.";
    } else {
        $pdo->prepare("INSERT INTO user_jeux (user_id, jeu_id, date_ajout, temps_jeu) VALUES (?,?,NOW(),?)")
            ->execute([$user_id, $jeu_id, $temps]);
        $flash = "Jeu ajouté à ta bibliothèque !";
    }
    header('Location: /profil?flash=' . urlencode($flash) . '#bibliotheque');
    exit;
}

// Gérer les autres actions POST (remove_jeu, update_temps, unlock_succes, lock_succes) de manière similaire

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_jeu'])) {
    $pdo->prepare("DELETE FROM user_jeux WHERE id=? AND user_id=?")
        ->execute([(int)$_POST['user_jeu_id'], $user_id]);
    header('Location: /profil?flash=' . urlencode("Jeu retiré.") . '#bibliotheque');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_temps'])) {
    $pdo->prepare("UPDATE user_jeux SET temps_jeu=? WHERE id=? AND user_id=?")
        ->execute([max(0, (int)$_POST['temps_jeu']), (int)$_POST['user_jeu_id'], $user_id]);
    header('Location: /profil?flash=' . urlencode("Temps de jeu mis à jour.") . '#bibliotheque');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_succes'])) {
    $suc_id = (int)$_POST['succes_id'];
    $check  = $pdo->prepare("SELECT id FROM user_succes WHERE user_id=? AND succes_id=?");
    $check->execute([$user_id, $suc_id]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO user_succes (user_id, succes_id, obtenu_le) VALUES (?,?,NOW())")
            ->execute([$user_id, $suc_id]);
    }
    header('Location: /profil?flash=' . urlencode("♛ Succès débloqué !") . '#bibliotheque');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lock_succes'])) {
    $pdo->prepare("DELETE FROM user_succes WHERE user_id=? AND succes_id=?")
        ->execute([$user_id, (int)$_POST['succes_id']]);
    header('Location: /profil?flash=' . urlencode("Succès retiré.") . '#bibliotheque');
    exit;
}


if (empty($flash)) $flash = $_GET['flash'] ?? '';


$stmt = $pdo->prepare("
    SELECT uj.id as uj_id, uj.date_ajout, uj.temps_jeu,
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

// Récupérer les succès pour chaque jeu de la bibliothèque
$succes_par_jeu = [];
foreach ($ma_bibliotheque as $bib) {
    $stmt = $pdo->prepare("SELECT * FROM succes WHERE jeu_id=?");
    $stmt->execute([$bib['jeu_id']]);
    $succes_par_jeu[$bib['jeu_id']] = $stmt->fetchAll();
}

$total_jeux    = count($ma_bibliotheque);
$total_temps   = array_sum(array_column($ma_bibliotheque, 'temps_jeu'));
$total_succes  = count($succes_debloques);

require_once __DIR__ . '/../front/profil.html';
