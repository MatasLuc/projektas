<?php
// track.php
require 'db.php';

if (isset($_GET['stage'])) {
    $stage = (int)$_GET['stage'];
    
    // Naudojame "ON DUPLICATE KEY UPDATE", kad tiesiog atnaujintų laiką, jei vėl nuskenuos
    $stmt = $pdo->prepare("INSERT INTO hunt_progress (stage) VALUES (?) 
                           ON DUPLICATE KEY UPDATE visited_at = CURRENT_TIMESTAMP");
    $stmt->execute([$stage]);
    
    echo "Stotelė $stage užfiksuota.";
}
?>
