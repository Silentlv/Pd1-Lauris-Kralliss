<?php
// delete.php - uzdevuma dzesana (CRUD: DELETE)
// Sai lapai nav HTML - ta tikai izdzes un parsuta atpakal uz sarakstu.
require 'db.php';
vajag_login();

// Dzest var tikai ar POST. Ja butu GET (delete.php?id=5), tad
// kads varetu iedot saiti, kuru atverot uzdevums izdzestos.
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die('Nepareiza metode');
}
parbaudit_token();

$id = $_POST['id'] ?? 0;

// AND user_id = ? - var izdzest tikai savu uzdevumu
$stmt = $db->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);

ieraksti_log("izdzesa uzdevumu $id");
header('Location: index.php');
exit;
