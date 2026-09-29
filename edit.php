<?php
// edit.php - uzdevuma labosana (CRUD: UPDATE)
require 'db.php';
vajag_login();

// Uzdevuma id nak no adreses: edit.php?id=5
$id = $_GET['id'] ?? 0;

// Atrodam uzdevumu, bet tikai ja tas pieder pieteikusajam lietotajam (AND user_id = ?).
// Tapec, ja kads adrese ierakstis cita lietotaja uzdevuma id, vins to neatradis.
$stmt = $db->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$uzd = $stmt->fetch();

if (!$uzd) {
    die('Uzdevums nav atrasts');
}

$kludas = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    parbaudit_token();

    // Parrakstam vecas vertibas ar jaunajam no formas
    $uzd['title'] = trim($_POST['title'] ?? '');
    $uzd['description'] = trim($_POST['description'] ?? '');
    $uzd['due_date'] = $_POST['due_date'] ?? '';
    $uzd['status'] = $_POST['status'] ?? '';
    $uzd['category_id'] = $_POST['category_id'] ?? '';

    $kludas = parbaudit_uzdevumu($db, $uzd);

    if (count($kludas) == 0) {
        // UPDATE ar parametriem. WHERE ari parbauda user_id - dubulta drosiba.
        $stmt = $db->prepare('UPDATE tasks SET category_id = ?, title = ?, description = ?, due_date = ?, status = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([
            $uzd['category_id'] ?: null,
            $uzd['title'],
            $uzd['description'],
            $uzd['due_date'] ?: null,
            $uzd['status'],
            $id,
            $_SESSION['user_id']
        ]);
        ieraksti_log("laboja uzdevumu $id");
        header('Location: index.php');
        exit;
    }
}

// Kategorijas izveles sarakstam
$stmt = $db->prepare('SELECT * FROM categories WHERE user_id = ? ORDER BY name');
$stmt->execute([$_SESSION['user_id']]);
$kategorijas = $stmt->fetchAll();

require 'header.php';
?>
<h2>Labot uzdevumu</h2>

<?php foreach ($kludas as $k) { ?>
    <p class="error"><?= h($k) ?></p>
<?php } ?>

<!-- Forma aizpildita ar esosajiem datiem (task_fields.php nem tos no $uzd) -->
<form method="post" action="edit.php?id=<?= h($id) ?>">
    <?php include 'task_fields.php'; ?>
    <button type="submit">Saglabāt</button>
    <a href="index.php">Atpakaļ</a>
</form>

<?php require 'footer.php'; ?>
