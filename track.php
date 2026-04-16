<?php
// track.php
require 'db.php';
session_start();

if (isset($_GET['stage'])) {
    $stage = (int)$_GET['stage'];
    $player_name = isset($_SESSION['player_name']) ? $_SESSION['player_name'] : 'Nežinomas';
    
    // Naudojame "ON DUPLICATE KEY UPDATE", kad tiesiog atnaujintų laiką
    $stmt = $pdo->prepare("INSERT INTO hunt_progress (player_name, stage) VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE visited_at = CURRENT_TIMESTAMP");
    $stmt->execute([$player_name, $stage]);
    
    echo "Stotelė $stage užfiksuota vartotojui $player_name.";
} else {
    echo "Nenurodyta stotelė.";
}
?>