<?php
// db.php
$host = 'localhost';
$db   = 'apdarasl_7istorijos';
$user = 'apdarasl_7istorijos';
$pass = 'Kosmosas420!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Sekimo lentelė (kas kur yra)
    $pdo->exec("CREATE TABLE IF NOT EXISTS hunt_progress (
        stage INT UNIQUE NOT NULL,
        visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Turinio lentelė (tekstai ir užuominos)
    $pdo->exec("CREATE TABLE IF NOT EXISTS hunt_content (
        stage INT UNIQUE NOT NULL,
        title VARCHAR(255),
        description TEXT,
        clue TEXT,
        maps_url TEXT
    )");

    // Jei lentelė tuščia, sukuriame 7 tuščius įrašus, kad turėtume ką redaguoti
    $check = $pdo->query("SELECT COUNT(*) FROM hunt_content")->fetchColumn();
    if ($check == 0) {
        for ($i = 1; $i <= 7; $i++) {
            $pdo->prepare("INSERT INTO hunt_content (stage, title) VALUES (?, ?)")
                ->execute([$i, "Stotelė #$i"]);
        }
    }

} catch (PDOException $e) {
    die("Sistemos klaida.");
}
