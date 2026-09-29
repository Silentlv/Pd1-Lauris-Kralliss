<?php
// header.php - lapas augsa (HTML sakums un navigacija).
// To ieklauj katra lapa ar require 'header.php', lai nav jāatkārto viens un tas pats kods.
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uzdevumi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="nav">
    <?php if (isset($_SESSION['user_id'])) { ?>
        <!-- Pieteicies lietotajs redz sadalas un iziesanas pogu -->
        <a href="index.php">Uzdevumi</a>
        <a href="categories.php">Kategorijas</a>
        <span class="user"><?= h($_SESSION['username']) ?></span>
        <!-- Iziesana ir forma ar POST un CSRF tokenu, nevis parasta saite (GET),
             lai svesa lapa nevaretu lietotaju izlogot ar vienu saiti -->
        <form method="post" action="logout.php" class="inline">
            <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
            <button type="submit">Iziet</button>
        </form>
    <?php } else { ?>
        <!-- Nepieteicies lietotajs redz tikai login un registraciju -->
        <a href="login.php">Pieteikties</a>
        <a href="register.php">Reģistrēties</a>
    <?php } ?>
</div>
<div class="content">
