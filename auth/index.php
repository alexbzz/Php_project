<?php
require_once __DIR__ . '/../config.php';
session_start();

$jeux = $pdo->query("SELECT * FROM jeux ORDER BY created_at DESC")->fetchAll();

$nb_users  = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$nb_jeux   = $pdo->query("SELECT COUNT(*) FROM jeux")->fetchColumn();
$nb_succes = $pdo->query("SELECT COUNT(*) FROM succes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Lands Between — Sanctum</title>
    <link rel="stylesheet" href="/assets/index.css">
</head>
<body>

<div class="noise"></div>

<nav class="nav">
    <div class="nav-brand">
        <span class="nav-rune">ᚱ</span>
        <span class="nav-name">THE LANDS BETWEEN</span>
    </div>
    <div class="nav-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/bibliotheque" class="nav-link">MA BIBLIOTHÈQUE</a>
            <a href="/profil" class="nav-link">MON PROFIL</a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="/admin" class="nav-link gold">ELDEN LORD</a>
            <?php endif; ?>
            <a href="/logout" class="nav-link muted">QUITTER</a>
        <?php else: ?>
            <a href="/login" class="nav-link">SE CONNECTER</a>
            <a href="/register" class="nav-link nav-link--cta">COMMENCER</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <div class="hero-bg">
        <div class="hero-orb hero-orb--1"></div>
        <div class="hero-orb hero-orb--2"></div>
    </div>
    <div class="hero-content">
        <div class="hero-eyebrow">
            <span class="eyebrow-line"></span>
            SANCTUM — PLATEFORME DE JEU
            <span class="eyebrow-line"></span>
        </div>
        <h1 class="hero-title">
            <span class="hero-title-top">THE LANDS</span>
            <span class="hero-title-bot">BETWEEN</span>
        </h1>
        <p class="hero-sub">Forgez votre légende. Tracez votre chemin à travers les royaumes.<br>Que la grâce guide vos pas, Tarnished.</p>
        <div class="hero-cta">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/bibliotheque" class="cta-primary">MA BIBLIOTHÈQUE</a>
                <a href="/profil" class="cta-ghost">MON PROFIL</a>
            <?php else: ?>
                <a href="/register" class="cta-primary">COMMENCER LE VOYAGE</a>
                <a href="/login" class="cta-ghost">SE CONNECTER</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-rune-deco">ᚨ</div>
</section>

<div class="stats-band">
    <div class="stat-item">
        <span class="stat-num"><?= $nb_users ?></span>
        <span class="stat-lbl">TARNISHED</span>
    </div>
    <div class="stat-sep">✦</div>
    <div class="stat-item">
        <span class="stat-num"><?= $nb_jeux ?></span>
        <span class="stat-lbl">ROYAUMES</span>
    </div>
    <div class="stat-sep">✦</div>
    <div class="stat-item">
        <span class="stat-num"><?= $nb_succes ?></span>
        <span class="stat-lbl">SUCCÈS</span>
    </div>
</div>

<section class="games-section">
    <div class="section-head">
        <div class="section-divider">
            <span class="sdiv-rune">ᚦ</span>
            <span class="sdiv-label">ROYAUMES DISPONIBLES</span>
            <span class="sdiv-rune">ᚦ</span>
        </div>
    </div>

    <div class="games-grid">
        <?php foreach ($jeux as $i => $j):
            $nb_niv = $pdo->prepare("SELECT COUNT(*) FROM niveaux WHERE jeu_id=?");
            $nb_niv->execute([$j['id']]);
            $nb_suc = $pdo->prepare("SELECT COUNT(*) FROM succes WHERE jeu_id=?");
            $nb_suc->execute([$j['id']]);

            $deja = false;
            if (isset($_SESSION['user_id'])) {
                $check = $pdo->prepare("SELECT id FROM user_jeux WHERE user_id=? AND jeu_id=?");
                $check->execute([$_SESSION['user_id'], $j['id']]);
                $deja = (bool)$check->fetch();
            }
            ?>
            <div class="game-card" style="animation-delay: <?= $i * 0.08 ?>s">

                <?php if (!empty($j['image'])): ?>
                    <div class="game-card-img">
                        <img src="/assets/<?= htmlspecialchars($j['image']) ?>" alt="<?= htmlspecialchars($j['nom']) ?>">
                    </div>
                <?php endif; ?>

                <div class="game-card-body">
                    <div class="game-card-top">
                        <span class="game-type"><?= htmlspecialchars($j['type']) ?></span> #empeche le xss
                        <span class="game-num"><?= str_pad($j['id'], 2, '0', STR_PAD_LEFT) ?></span> #formate le numéro du jeu en 2 chiffres (01,02...)
                    </div>
                    <h3 class="game-name"><?= htmlspecialchars($j['nom']) ?></h3>
                    <p class="game-desc"><?= htmlspecialchars(mb_substr($j['description'], 0, 100)) ?>…</p>
                    <div class="game-pills">
                        <span class="game-pill">⬡ <?= $nb_niv->fetchColumn() ?> niveaux</span>
                        <span class="game-pill">♛ <?= $nb_suc->fetchColumn() ?> succès</span>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($deja): ?>
                            <a href="/bibliotheque?id=<?= $j['id'] ?>" class="game-pill-added">✔ VOIR DANS MA BIBLIOTHÈQUE</a>
                        <?php else: ?>
                            <form method="POST" action="/bibliotheque">
                                <input type="hidden" name="add_jeu" value="1">
                                <input type="hidden" name="jeu_id" value="<?= $j['id'] ?>">
                                <input type="hidden" name="temps_jeu" value="0">
                                <button type="submit" class="btn-add-bib">+ AJOUTER À MA BIBLIOTHÈQUE</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/login" class="btn-add-bib-ghost">→ SE CONNECTER POUR AJOUTER</a>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="features-section">
    <div class="section-divider" style="margin-bottom:48px">
        <span class="sdiv-rune">ᚾ</span>
        <span class="sdiv-label">POURQUOI NOUS REJOINDRE</span>
        <span class="sdiv-rune">ᚾ</span>
    </div>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-rune">ᚦ</div>
            <div class="feature-title">BIBLIOTHÈQUE</div>
            <div class="feature-desc">Gérez votre collection de jeux, suivez votre temps de jeu et organisez vos aventures.</div>
        </div>
        <div class="feature-card">
            <div class="feature-rune">♛</div>
            <div class="feature-title">SUCCÈS</div>
            <div class="feature-desc">Débloquez des récompenses, trackez vos exploits et progressez dans chaque royaume.</div>
        </div>
        <div class="feature-card">
            <div class="feature-rune">ᚨ</div>
            <div class="feature-title">PROFIL</div>
            <div class="feature-desc">Personnalisez votre fiche de voyageur, choisissez votre avatar et rédigez votre biographie.</div>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="footer-rune">ᚱ</div>
    <div class="footer-name">THE LANDS BETWEEN — SANCTUM</div>
    <div class="footer-sub">Que la grâce guide vos pas</div>
</footer>

</body>
</html>