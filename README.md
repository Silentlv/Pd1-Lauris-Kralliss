# PD1 - Uzdevumu saraksts

PHP + SQLite. Datubāze izveidojas pati (data/database.db), darbības tiek rakstītas logs/log.txt.

Palaišana:

    php -S localhost:8000 router.php

Tad atvērt http://localhost:8000/register.php

Tabulas: users, categories, tasks, login_attempts

Testēšanas komandda: 
sqlite3 -header -column data/database.db "SELECT id, username, email, password FROM users;"
sqlite3 -header -column data/database.db "SELECT * FROM categories;"
sqlite3 -header -column data/database.db "SELECT * FROM tasks;"
sqlite3 -header -column data/database.db "SELECT * FROM login_attempts;"
