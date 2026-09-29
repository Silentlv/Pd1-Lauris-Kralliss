<?php
// login.php - lietotaja pieteiksanas (log-in) ar aizsardzibu pret parolu minesanu
require 'db.php';

// Ja jau pieteicies, nav jēgas radit login formu
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$kluda = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    parbaudit_token(); // CSRF parbaude

    $username = trim($_POST['username'] ?? '');
    $parole = $_POST['password'] ?? '';

    // --- Aizsardziba pret parolu minesanu (brute force) ---
    // Saskaitam, cik nepareizu meginajumu sim lietotajvardam bijis pedejas 15 minutes.
    // datetime('now', '-15 minutes') - SQLite funkcija, kas dod laiku pirms 15 min.
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE username = ? AND time > datetime('now', '-15 minutes')");
    $stmt->execute([$username]);
    $meginajumi = $stmt->fetchColumn(); // fetchColumn atgriez vienu vertibu (skaitu)

    if ($meginajumi >= 5) {
        // Pēc 5 nepareizam parolem vairs pat neparbaudam paroli
        $kluda = 'Par daudz mēģinājumu. Pamēģini pēc 15 minūtēm.';
        ieraksti_log("bloketa pieteiksanas $username");
    } else {
        // Atrodam lietotaju pec varda (parametrizets vaicajums)
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(); // false, ja tada lietotaja nav

        // password_verify salidzina ievadito paroli ar saglabato hash
        if ($user && password_verify($parole, $user['password'])) {
            // Veiksmigi - notiram neveiksmigos meginajumus
            $stmt = $db->prepare('DELETE FROM login_attempts WHERE username = ?');
            $stmt->execute([$username]);

            // Izveido jaunu sesijas ID, lai uzbrucejs nevaretu izmantot
            // iepriekš zinamu sesijas ID (session fixation aizsardziba)
            session_regenerate_id(true);

            // Saglabajam sesija, kas ir pieteicies. Tas ari nozime "ir ielogojies".
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            ieraksti_log('pieteicas');
            header('Location: index.php');
            exit;
        } else {
            // Nepareizi - pierakstam meginajumu
            $stmt = $db->prepare('INSERT INTO login_attempts (username, ip) VALUES (?, ?)');
            $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);
            ieraksti_log("nepareiza parole $username");

            // Viens un tas pats pazinojums abos gadijumos, lai nevar noskaidrot,
            // vai tads lietotajs vispar eksiste
            $kluda = 'Nepareizs lietotājvārds vai parole';
        }
    }
}

require 'header.php';
?>
<h2>Pieteikšanās</h2>

<!-- ?ok=1 adrese nozime, ka atnacam no veiksmigas registracijas -->
<?php if (isset($_GET['ok'])) { ?>
    <p class="success">Reģistrācija veiksmīga, vari pieteikties.</p>
<?php } ?>

<?php if ($kluda != '') { ?>
    <p class="error"><?= h($kluda) ?></p>
<?php } ?>

<form method="post" action="login.php">
    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">

    <label>Lietotājvārds</label>
    <input type="text" name="username" value="<?= h($username) ?>" required>

    <label>Parole</label>
    <input type="password" name="password" required>

    <button type="submit">Pieteikties</button>
</form>

<?php require 'footer.php'; ?>
