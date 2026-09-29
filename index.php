<?php
// index.php - uzdevumu saraksts (CRUD: READ jeb lasisana)
require 'db.php';
vajag_login(); // lapu redz tikai pieteicies lietotajs

// Filtrs pec statusa nak no adreses (?status=Jauns).
// Seit izmanto GET, jo dati netiek mainiti - tikai nolasiti.
$filtrs = $_GET['status'] ?? '';

// LEFT JOIN - pievienojam kategorijas nosaukumu no otras tabulas.
// LEFT, lai uzdevumi bez kategorijas ari paraditos.
// WHERE tasks.user_id = ? - katrs lietotajs redz tikai savus uzdevumus.
if (in_array($filtrs, $statusi)) {
    // Ja izvelets derigs statuss - filtrejam pec ta
    $stmt = $db->prepare('SELECT tasks.*, categories.name AS category FROM tasks
        LEFT JOIN categories ON categories.id = tasks.category_id
        WHERE tasks.user_id = ? AND tasks.status = ?
        ORDER BY tasks.due_date');
    $stmt->execute([$_SESSION['user_id'], $filtrs]);
} else {
    // Citadi radam visus
    $stmt = $db->prepare('SELECT tasks.*, categories.name AS category FROM tasks
        LEFT JOIN categories ON categories.id = tasks.category_id
        WHERE tasks.user_id = ?
        ORDER BY tasks.due_date');
    $stmt->execute([$_SESSION['user_id']]);
}
$uzdevumi = $stmt->fetchAll(); // visas rindas ka masivs

require 'header.php';
?>
<h2>Mani uzdevumi</h2>

<a href="add.php" class="btn">+ Pievienot uzdevumu</a>

<!-- Filtra forma ar GET - izveletais statuss paradas adrese -->
<form method="get" action="index.php" class="inline">
    <select name="status">
        <option value="">Visi</option>
        <?php foreach ($statusi as $s) { ?>
            <option <?= $filtrs == $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php } ?>
    </select>
    <button type="submit">Filtrēt</button>
</form>

<?php if (count($uzdevumi) == 0) { ?>
    <p>Nav neviena uzdevuma.</p>
<?php } else { ?>
<table>
    <tr>
        <th>Nosaukums</th>
        <th>Apraksts</th>
        <th>Kategorija</th>
        <th>Termiņš</th>
        <th>Statuss</th>
        <th></th>
    </tr>
    <!-- Katram uzdevumam viena tabulas rinda. Viss teksts caur h() pret XSS. -->
    <?php foreach ($uzdevumi as $u) { ?>
    <tr>
        <td><?= h($u['title']) ?></td>
        <td><?= h($u['description']) ?></td>
        <td><?= h($u['category']) ?></td>
        <td><?= h($u['due_date']) ?></td>
        <td><?= h($u['status']) ?></td>
        <td>
            <!-- Labot - parasta saite (GET), jo ta tikai atver formu -->
            <a href="edit.php?id=<?= $u['id'] ?>">Labot</a>
            <!-- Dzest - forma ar POST un tokenu, jo dzesana maina datus.
                 confirm() prasa apstiprinajumu pirms nosutisanas. -->
            <form method="post" action="delete.php" class="inline" onsubmit="return confirm('Dzēst?')">
                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="delete">Dzēst</button>
            </form>
        </td>
    </tr>
    <?php } ?>
</table>
<?php } ?>

<?php require 'footer.php'; ?>
