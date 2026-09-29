<?php
// add.php - jauna uzdevuma pievienosana (CRUD: CREATE)
require 'db.php';
vajag_login();

$kludas = [];
// Sakuma vertibas tuksai formai
$uzd = ['title' => '', 'description' => '', 'due_date' => '', 'status' => 'Jauns', 'category_id' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    parbaudit_token();

    // Nolasam formas laukus
    $uzd['title'] = trim($_POST['title'] ?? '');
    $uzd['description'] = trim($_POST['description'] ?? '');
    $uzd['due_date'] = $_POST['due_date'] ?? '';
    $uzd['status'] = $_POST['status'] ?? '';
    $uzd['category_id'] = $_POST['category_id'] ?? '';

    // Validacija - funkcija ir db.php
    $kludas = parbaudit_uzdevumu($db, $uzd);

    if (count($kludas) == 0) {
        // INSERT ar parametriem. user_id nemam no sesijas, nevis no formas,
        // lai nevaretu izveidot uzdevumu cita lietotaja vārdā.
        $stmt = $db->prepare('INSERT INTO tasks (user_id, category_id, title, description, due_date, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $_SESSION['user_id'],
            $uzd['category_id'] ?: null, // ja tukss - saglabajam NULL (bez kategorijas)
            $uzd['title'],
            $uzd['description'],
            $uzd['due_date'] ?: null,    // ja tukss - NULL (bez termina)
            $uzd['status']
        ]);
        ieraksti_log('pievienoja uzdevumu ' . $db->lastInsertId()); // lastInsertId - jauna ieraksta id
        header('Location: index.php');
        exit;
    }
}

// Lietotaja kategorijas izveles sarakstam
$stmt = $db->prepare('SELECT * FROM categories WHERE user_id = ? ORDER BY name');
$stmt->execute([$_SESSION['user_id']]);
$kategorijas = $stmt->fetchAll();

require 'header.php';
?>
<h2>Jauns uzdevums</h2>

<?php foreach ($kludas as $k) { ?>
    <p class="error"><?= h($k) ?></p>
<?php } ?>

<form method="post" action="add.php">
    <?php include 'task_fields.php'; // formas lauki ir atseviska faila, jo tie pasi ari edit.php ?>
    <button type="submit">Pievienot</button>
    <a href="index.php">Atpakaļ</a>
</form>

<?php require 'footer.php'; ?>
