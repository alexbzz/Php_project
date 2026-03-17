<?php
require_once __DIR__ . '/../config.php';
session_start();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (empty($nom)) $errors[] = "Le nom est requis.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (strlen($password) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    if ($password !== $confirm) $errors[] = "Les mots de passe ne correspondent pas.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$nom, $email, $hash]);

                // Récupérer l'ID de l'utilisateur nouvellement créé
                $stmt = $pdo->prepare("SELECT id, nom, role FROM utilisateurs WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                // Créer la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nom'];
                $_SESSION['user_role'] = $user['role'];

                // Rediriger vers le profil
                header('Location: /profil');
                exit;
            }
        } catch (\PDOException $e) {
            $errors[] = "Erreur serveur, veuillez réessayer.";
        }
    }
}

require_once __DIR__ . '/../front/register.html';