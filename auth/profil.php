<?php
require_once __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$errors = [];
$success = false;

$avatars = [
    'avatar1' => ['img' => '/assets/avatar1.jpg', 'label' => 'Avatar 1'],
    'avatar2' => ['img' => '/assets/avatar2',     'label' => 'Avatar 2'],
    'avatar3' => ['img' => '/assets/avatar3.jpg', 'label' => 'Avatar 3'],
    'avatar4' => ['img' => '/assets/avatar4.jpg', 'label' => 'Avatar 4'],
];

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: /login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $avatar   = $_POST['avatar'] ?? $user['avatar'];
    $theme    = $_POST['theme'] ?? $user['theme'];
    $bio      = trim($_POST['bio'] ?? '');

    if (empty($nom)) $errors[] = "Le nom est requis.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (!array_key_exists($avatar, $avatars)) $errors[] = "Avatar invalide.";
    if (!in_array($theme, ['dark', 'light'])) $errors[] = "Thème invalide.";

    if (!empty($password)) {
        if (strlen($password) < 6) $errors[] = "Le mot de passe doit faire au moins 6 caractères.";
        if ($password !== $confirm) $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) $errors[] = "Cet email est déjà utilisé.";
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom=?, email=?, password=?, avatar=?, theme=?, bio=? WHERE id=?");
            $stmt->execute([$nom, $email, $hash, $avatar, $theme, $bio, $user['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom=?, email=?, avatar=?, theme=?, bio=? WHERE id=?");
            $stmt->execute([$nom, $email, $avatar, $theme, $bio, $user['id']]);
        }

        $_SESSION['user_nom'] = $nom;
        $success = true;

        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $stmt->execute([$user['id']]);
        $user = $stmt->fetch();
    }
}

require_once __DIR__ . '/../front/profil.php';