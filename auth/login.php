<?php
/* 
    Page de connexion pour les utilisateurs.
    Permet aux utilisateurs de se connecter avec leur email et mot de passe.
    Affiche des messages d'erreur en cas de données invalides ou de problèmes de connexion.
    Redirige les utilisateurs vers leur profil ou la page d'administration après une connexion réussie.
 */
require_once __DIR__ . '/../config.php';

session_start();


$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (empty($password)) $errors[] = "Le mot de passe est requis.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: /admin');
                } else {
                    header('Location: /profil');
                }
                exit;
            } else {
                $errors[] = "Email ou mot de passe incorrect.";
            }
        } catch (\PDOException $e) {
            $errors[] = "Erreur serveur, veuillez réessayer.";
        }
    }
}

require_once __DIR__ . '/../front/login.html';