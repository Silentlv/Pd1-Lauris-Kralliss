<?php
// router.php - vajadzigs tikai PHP iebuvetajam serverim:
//   php -S localhost:8000 router.php
// Serveris katru pieprasijumu vispirms palaiz caur so failu.
// Seit aizliedzam atvert mapes data (datubaze ar parolu hash) un logs (log fails),
// jo citadi tos varetu lejupieladet caur parluku.

$cels = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // piem. "/data/database.db"

if (strpos($cels, '/data') === 0 || strpos($cels, '/logs') === 0) {
    http_response_code(403); // 403 = aizliegts
    die('Nav pieejas');
}

// return false - lai serveris pieprasijumu apstrada ka parasti (atver prasito failu)
return false;
