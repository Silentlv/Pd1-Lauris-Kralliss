<?php
// categories.php - kategoriju parvaldiba (pilns CRUD otrai tabulai)
// Viena lapa apstrada trīs darbibas: add (pievienot), rename (parsaukt), delete (dzest).
// Kura darbiba - nosaka slepts lauks "action" forma.
require 'db.php';
vajag_login();

$kluda = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    parbaudit_token();

    $darbiba = $_POST['action'] ?? '';
    $nosaukums = trim($_POST['name'] ?? '');
    $id = $_POST['id'] ?? 0;

    // Pievienojot vai parsaucot parbaudam nosaukumu
    if ($darbiba == 'add' || $darbiba == 'rename') {
        if ($nosaukums == '' || mb_strlen($nosaukums) > 50) {
            $kluda = 'Nosaukumam jābūt 1-50 simboli';
        } else {
            // Vai lietotajam jau nav kategorijas ar tadu pasu nosaukumu.
            // id != ? - parsaucot neskaitam pasu kategoriju.
            $stmt = $db->prepare('SELECT id FROM categories WHERE user_id = ? AND name = ? AND id != ?');
            $stmt->execute([$_SESSION['user_id'], $nosaukums, $id]);
            if ($stmt->fetch()) {
                $kluda = 'Tāda kategorija jau ir';
            }
        }
    }

    if ($kluda == '') {
        if ($darbiba == 'add') {
            // CREATE
            $stmt = $db->prepare('INSERT INTO categories (user_id, name) VALUES (?, ?)');
            $stmt->execute([$_SESSION['user_id'], $nosaukums]);
            ieraksti_log("pievienoja kategoriju $nosaukums");
        } elseif ($darbiba == 'rename') {
            // UPDATE
            $stmt = $db->prepare('UPDATE categories SET name = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$nosaukums, $id, $_SESSION['user_id']]);
            ieraksti_log("parsauca kategoriju $id uz $nosaukums");
        } elseif ($darbiba == 'delete') {
            // DELETE. Uzdevumi paliek, jo tabula ir ON DELETE SET NULL.
            $stmt = $db->prepare('DELETE FROM categories WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $_SESSION['user_id']]);
            ieraksti_log("izdzesa kategoriju $id");
        }
        header('Location: categories.php');
        exit;
    }
}

// READ - kategorijas kopa ar uzdevumu skaitu katra.
// COUNT(tasks.id) + GROUP BY saskaita, cik uzdevumu ir katrai kategorijai.
$stmt = $db->prepare('SELECT categories.id, categories.name, COUNT(tasks.id) AS skaits
    FROM categories
    LEFT JOIN tasks ON tasks.category_id = categories.id
    WHERE categories.user_id = ?
    GROUP BY categories.id
    ORDER BY categories.name');
$stmt->execute([$_SESSION['user_id']]);
$kategorijas = $stmt->fetchAll();

require 'header.php';
?>
<h2>Kategorijas</h2>

<?php if ($kluda != '') { ?>
    <p class="error"><?= h($kluda) ?></p>
<?php } ?>

<!-- Jaunas kategorijas forma (action=add) -->
<form method="post" action="categories.php">
    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
    <input type="hidden" name="action" value="add">
    <input type="text" name="name" placeholder="Jauna kategorija" required maxlength="50">
    <button type="submit">Pievienot</button>
</form>

<table>
    <tr>
        <th>Nosaukums</th>
        <th>Uzdevumi</th>
        <th></th>
    </tr>
    <?php foreach ($kategorijas as $k) { ?>
    <tr>
        <td>
            <!-- Parsauksana (action=rename) - nosaukumu var labot tiesi tabula -->
            <form method="post" action="categories.php" class="inline">
                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                <input type="hidden" name="action" value="rename">
                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                <input type="text" name="name" value="<?= h($k['name']) ?>" required maxlength="50">
                <button type="submit">Saglabāt</button>
            </form>
        </td>
        <td><?= $k['skaits'] ?></td>
        <td>
            <!-- Dzesana (action=delete) -->
            <form method="post" action="categories.php" class="inline" onsubmit="return confirm('Dzēst kategoriju?')">
                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                <button type="submit" class="delete">Dzēst</button>
            </form>
        </td>
    </tr>
    <?php } ?>
</table>

<?php require 'footer.php'; ?>
