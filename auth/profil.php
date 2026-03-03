<?php
require_once __DIR__ . '/../config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$errors  = [];
$success = false;

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: /login');
    exit;
}

// Récupérer les jeux de l'utilisateur
$stmt = $pdo->prepare("
    SELECT j.nom, j.type, j.image, uj.date_ajout, uj.temps_jeu
    FROM user_jeux uj
    JOIN jeux j ON j.id = uj.jeu_id
    WHERE uj.user_id = ?
    ORDER BY uj.date_ajout DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_jeux = $stmt->fetchAll();

// Récupérer les succès de l'utilisateur
$stmt = $pdo->prepare("
    SELECT s.nom, s.description, j.nom AS jeu, us.obtenu_le
    FROM user_succes us
    JOIN succes s ON s.id = us.succes_id
    JOIN jeux j ON j.id = s.jeu_id
    WHERE us.user_id = ?
    ORDER BY us.obtenu_le DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_succes = $stmt->fetchAll();

// Traitement formulaire modification profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (empty($nom))                                             $errors[] = "Le nom est requis.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

    if (!empty($password)) {
        if (strlen($password) < 6)    $errors[] = "Le mot de passe doit faire au moins 6 caractères.";
        if ($password !== $confirm)   $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) $errors[] = "Cet email est déjà utilisé.";
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$nom, $email, $hash, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ? WHERE id = ?");
            $stmt->execute([$nom, $email, $_SESSION['user_id']]);
        }

        $_SESSION['user_nom'] = $nom;
        $success = true;

        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
}

require_once __DIR__ . '/../front/profil.html';