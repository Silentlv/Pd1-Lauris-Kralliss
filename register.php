<?php
// register.php - jauna lietotaja registracija (sign-up)
require 'db.php';

$kludas = [];
$username = '';
$email = '';

// Formu apstradajam tikai, ja ta nosutita ar POST.
// Kad lapu vienkarsi atver (GET), tiek paradita tuksa forma.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    parbaudit_token(); // CSRF parbaude

    // trim nonem atstarpes sakuma un beigas
    // ?? '' - ja lauks nav nosutits, nemam tuksu tekstu (lai nav kludas)
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $parole = $_POST['password'] ?? '';
    $parole2 = $_POST['password2'] ?? '';

    // --- Validacija servera puse ---
    // HTML atributiem (required u.c.) nevar uzticeties, jo tos var apiet,
    // tapec viss tiek parbaudits ari seit PHP.

    // Regularā izteiksme: tikai burti, cipari un _, garums 3-30
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $kludas[] = 'Lietotājvārdam jābūt 3-30 simboli (burti, cipari, _)';
    }
    // filter_var ar FILTER_VALIDATE_EMAIL - iebuveta PHP e-pasta parbaude
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $kludas[] = 'Nepareizs e-pasts';
    }
    if (strlen($parole) < 8) {
        $kludas[] = 'Parolei jābūt vismaz 8 simboli';
    }
    // Parole jasatur vismaz viens burts un viens cipars
    if (!preg_match('/[a-zA-Z]/', $parole) || !preg_match('/[0-9]/', $parole)) {
        $kludas[] = 'Parolē jābūt gan burtam, gan ciparam';
    }
    if ($parole != $parole2) {
        $kludas[] = 'Paroles nesakrīt';
    }

    // Parbaudam, vai lietotajvards vai e-pasts jau nav aiznemts.
    // Parametrizets vaicajums: ? vieta tiek ielikta vertiba ar execute(),
    // tapec lietotaja ievade nevar mainit SQL vaicajumu (aizsardziba pret SQL injekciju).
    if (count($kludas) == 0) {
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $kludas[] = 'Lietotājvārds vai e-pasts jau aizņemts';
        }
    }

    // Ja kludu nav - saglabajam lietotaju
    if (count($kludas) == 0) {
        // password_hash izveido paroles hash ar bcrypt algoritmu un nejausu "salt".
        // Datubaze glaba tikai hash - pasu paroli no ta atgut nevar.
        $hash = password_hash($parole, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $hash]);

        ieraksti_log("jauns lietotajs $username");

        // Parsutam uz login lapu. Pec POST vienmer parsuta (redirect),
        // lai, atsvaidzinot lapu, forma netiktu nosutita velreiz.
        header('Location: login.php?ok=1');
        exit;
    }
}

require 'header.php';
?>
<h2>Reģistrācija</h2>

<!-- Izvadam visas kludas (ar h(), lai butu drosi) -->
<?php foreach ($kludas as $k) { ?>
    <p class="error"><?= h($k) ?></p>
<?php } ?>

<!-- method="post" - dati tiek sutiti pieprasijuma kermeni, nevis adrese (parole neredzama URL) -->
<form method="post" action="register.php">
    <!-- slepts CSRF tokens -->
    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">

    <label>Lietotājvārds</label>
    <!-- value saglaba ievadito, ja bija kluda, lai nav jāraksta velreiz -->
    <input type="text" name="username" value="<?= h($username) ?>" required>

    <label>E-pasts</label>
    <input type="email" name="email" value="<?= h($email) ?>" required>

    <label>Parole</label>
    <input type="password" name="password" required minlength="8">

    <label>Parole vēlreiz</label>
    <input type="password" name="password2" required minlength="8">

    <button type="submit">Reģistrēties</button>
</form>

<?php require 'footer.php'; ?>
