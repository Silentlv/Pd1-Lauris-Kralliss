<?php
// logout.php - iziesana no sistemas (log-out)
require 'db.php';

// Iziet var tikai ar POST (poga header.php), nevis atverot saiti.
// Citadi svesa lapa varetu ielikt <img src="logout.php"> un izlogot lietotaju.
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die('Nepareiza metode');
}
parbaudit_token();

ieraksti_log('izgaja'); // pierakstam pirms sesijas dzesanas, kamer vel zinam lietotaju

$_SESSION = [];     // iztuksojam visus sesijas datus
session_destroy();  // iznicinam sesiju serveri

header('Location: login.php');
exit;
