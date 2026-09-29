<?php
// db.php - so failu ieklauj (require) visas parejas lapas.
// Seit ir: sesijas sakums, datubazes savienojums, tabulu izveide un palidzfunkcijas.

// Sesijas sikdatnes iestatijumi:
// httponly - JavaScript nevar nolasit sesijas sikdatni (aizsardziba pret XSS zagsanu)
// samesite Strict - parluks nesuta sikdatni, ja pieprasijums nak no citas lapas (aizsardziba pret CSRF)
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
session_start(); // sak vai turpina sesiju, lai $_SESSION masivs butu pieejams
date_default_timezone_set('Europe/Riga'); // lai log faila laiks butu Latvijas laiks

// Izveido mapes datubazei un log failam, ja to vel nav
if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data');
if (!is_dir(__DIR__ . '/logs')) mkdir(__DIR__ . '/logs');

// Savienojums ar SQLite datubazi caur PDO.
// SQLite datubaze ir viens fails (data/database.db), serveris nav vajadzigs.
// Ja fails neeksiste, SQLite to izveido pats.
$db = new PDO('sqlite:' . __DIR__ . '/data/database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);      // kludas gadijuma mest iznemumu (exception)
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // rezultatus atgriezt ka asociativu masivu ['title' => ...]
$db->exec('PRAGMA foreign_keys = ON'); // SQLite pec noklusejuma arejas atslegas nepārbauda, tapec ieslēdzam

// --- TABULAS ---
// CREATE TABLE IF NOT EXISTS - tabulu izveido tikai pirmaja reize, velak so izlaiz

// Lietotaji. Parole tiek glabata ka hash, nevis ka teksts.
// UNIQUE - nevar but divi lietotaji ar vienadu vardu vai e-pastu.
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");

// Kategorijas. Katra kategorija pieder vienam lietotajam (user_id -> users.id).
// ON DELETE CASCADE - ja izdzes lietotaju, izdzesas ari vina kategorijas.
$db->exec("CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Uzdevumi - galvena tabula, kurai ir CRUD.
// Saistita ar users (kam pieder) un categories (kura kategorija).
// ON DELETE SET NULL - ja izdzes kategoriju, uzdevums paliek, tikai bez kategorijas.
$db->exec("CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category_id INTEGER,
    title TEXT NOT NULL,
    description TEXT,
    due_date TEXT,
    status TEXT NOT NULL DEFAULT 'Jauns',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)");

// Neveiksmigie pieteiksanas meginajumi - aizsardzibai pret parolu minesanu.
// Katru reizi, kad parole nepareiza, seit ieraksta rindu.
$db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    ip TEXT,
    time TEXT DEFAULT CURRENT_TIMESTAMP
)");

// Atlautie uzdevuma statusi (izmanto validacija un izveles laukos)
$statusi = ['Jauns', 'Procesā', 'Pabeigts'];

// --- CSRF AIZSARDZIBA ---
// Katrai sesijai izveido nejausu tokenu (64 simboli).
// Tas tiek ielikts katra forma ka slepts lauks. Svesa lapa so tokenu nezina,
// tapec nevar nosutit viltotu formu lietotaja vārdā.
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// Parbauda, vai formas tokens sakrit ar sesijas tokenu.
// hash_equals salidzina drosi (vienmer vienada laika), lai nevar uzminet pa simbolam.
function parbaudit_token() {
    if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        ieraksti_log('nederigs CSRF tokens');
        die('Nederīgs pieprasījums');
    }
}

// Ja lietotajs nav pieteicies, parsuta uz login lapu.
// So izsauc lapu sakuma, kuras drikst redzet tikai pieteicies lietotajs.
function vajag_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Ieraksta darbibu log failā: datums | lietotajs | IP | darbiba
// FILE_APPEND - pievieno faila beigas, nevis parraksta.
function ieraksti_log($teksts) {
    $lietotajs = $_SESSION['username'] ?? 'viesis';
    $rinda = date('Y-m-d H:i:s') . ' | ' . $lietotajs . ' | ' . $_SERVER['REMOTE_ADDR'] . ' | ' . $teksts . "\n";
    file_put_contents(__DIR__ . '/logs/log.txt', $rinda, FILE_APPEND);
}

// Drosa teksta izvade HTML.
// htmlspecialchars parveido < > " ' & par HTML entitijam, lai lietotaja ievaditais
// teksts netiktu izpildits ka HTML/JavaScript (aizsardziba pret XSS).
function h($teksts) {
    return htmlspecialchars($teksts ?? '');
}

// Uzdevuma formas datu parbaude (validacija). Izmanto add.php un edit.php.
// Atgriez masivu ar kludam - ja tas tukss, dati ir korekti.
function parbaudit_uzdevumu($db, $dati) {
    global $statusi;
    $kludas = [];

    // Nosaukums obligats un ne garaks par 100 simboliem
    // mb_strlen skaita simbolus pareizi ari ar garumzimem
    if ($dati['title'] == '' || mb_strlen($dati['title']) > 100) {
        $kludas[] = 'Nosaukumam jābūt 1-100 simboli';
    }
    if (mb_strlen($dati['description']) > 1000) {
        $kludas[] = 'Apraksts par garu (max 1000)';
    }
    // Datums nav obligats, bet ja ir, tam jabut formata GGGG-MM-DD un eksistejosam
    // (piem. 2026-02-31 neizies, jo DateTime to parvers par 2026-03-03)
    if ($dati['due_date'] != '') {
        $d = DateTime::createFromFormat('Y-m-d', $dati['due_date']);
        if (!$d || $d->format('Y-m-d') != $dati['due_date']) {
            $kludas[] = 'Nepareizs datums';
        }
    }
    // Statusam jabut viens no atlautajiem - lietotajs var izmainit formu parluka
    if (!in_array($dati['status'], $statusi)) {
        $kludas[] = 'Nepareizs statuss';
    }
    // Ja izveleta kategorija, parbaudam, ka ta tiešām pieder sim lietotajam
    if ($dati['category_id'] != '') {
        $stmt = $db->prepare('SELECT id FROM categories WHERE id = ? AND user_id = ?');
        $stmt->execute([$dati['category_id'], $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $kludas[] = 'Nepareiza kategorija';
        }
    }
    return $kludas;
}
