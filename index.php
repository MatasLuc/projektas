<?php
// BŪTINA: Pradedame sesiją vardo išsaugojimui
session_start();

require 'db.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// ---------------------------------------------------------
// 1. VARDO ĮVEDIMO LOGIKA
// ---------------------------------------------------------
if (isset($_POST['set_name'])) {
    $_SESSION['player_name'] = htmlspecialchars(trim($_POST['player_name']));
    // Perkrauname, kad dingtų POST duomenys
    header("Location: ?step=" . $step);
    exit;
}

if (!isset($_SESSION['player_name']) || empty($_SESSION['player_name'])) {
    ?>
    <!DOCTYPE html>
    <html lang="lt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>Pradėti iššūkį</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body { margin: 0; padding: 20px; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: #f8fafc; font-family: 'Inter', sans-serif; box-sizing: border-box; -webkit-font-smoothing: antialiased;}
            .card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); padding: 40px 30px; border-radius: 24px; text-align: center; width: 100%; max-width: 350px; animation: fadeInUp 0.8s ease forwards; box-sizing: border-box;}
            @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
            h1 { color: #fbbf24; font-size: 1.5rem; margin-bottom: 10px; }
            p { color: #cbd5e1; font-size: 1rem; margin-bottom: 25px; line-height: 1.5;}
            input { width: 100%; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; font-size: 1.1rem; box-sizing: border-box; margin-bottom: 20px; text-align: center; outline: none; transition: 0.2s; }
            input:focus { border-color: #fbbf24; }
            button { width: 100%; padding: 15px; background: #fbbf24; color: #0f2027; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
            button:active { transform: scale(0.98); }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Miesto Iššūkis</h1>
            <p>Sistemos inicializacija. Prašome identifikuoti save.</p>
            <form method="post">
                <input type="text" name="player_name" placeholder="Tavo vardas" required autocomplete="off">
                <button type="submit" name="set_name">Pradėti 🚀</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$player_name = $_SESSION['player_name'];

// ---------------------------------------------------------
// 2. ŽAIDIMO LOGIKA
// ---------------------------------------------------------

// Gauname turinį iš DB
$stmt = $pdo->prepare("SELECT * FROM hunt_content WHERE stage = ?");
$stmt->execute([$step]);
$data = $stmt->fetch();

if (!$data) die("Klaida: Žingsnis nerastas.");

// Fiksuojame progresą SU VARDU
$stmt = $pdo->prepare("INSERT INTO hunt_progress (player_name, stage) VALUES (?, ?) ON DUPLICATE KEY UPDATE visited_at = CURRENT_TIMESTAMP");
$stmt->execute([$player_name, $step]);

$is_finale = ($step === 7);

// PAGRINDINĖ MAGIJA: Pakeičiame {vardas} tekste į realų žmogaus vardą
$title_text = str_replace('{vardas}', $player_name, $data['title']);
$desc_text = str_replace('{vardas}', $player_name, $data['description']);
$clue_text = str_replace('{vardas}', $player_name, $data['clue']);
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($title_text); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
    
    <style>
        /* BAZINĖS TAISYKLĖS */
        body { 
            margin: 0; padding: 20px; 
            min-height: 100vh; 
            display: flex; align-items: center; justify-content: center; 
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }
        
        .card { 
            width: 100%; max-width: 420px; 
            padding: 40px 30px; 
            border-radius: 24px; 
            text-align: center; 
            box-sizing: border-box;
            animation: fadeInUp 0.8s ease-out forwards;
            opacity: 0; transform: translateY(20px);
        }

        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }

        /* -------------------------------------
           ŽAIDIMO REŽIMAS (1-6 STOTELĖS)
        ----------------------------------------*/
        body.game-mode { 
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); 
            color: #f8fafc; 
            font-family: 'Inter', sans-serif;
        }
        .game-mode .card { 
            background: rgba(255, 255, 255, 0.05); 
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
        }
        .game-mode h1 { 
            margin: 0 0 15px 0; font-size: 1.75rem; font-weight: 700; color: #fbbf24; 
            letter-spacing: -0.5px;
        }
        .game-mode p { 
            line-height: 1.7; font-size: 1.1rem; margin-bottom: 30px; color: #cbd5e1; 
        }
        .game-mode .clue-box { 
            background: rgba(0, 0, 0, 0.2); 
            padding: 25px 20px; 
            border-radius: 16px; 
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .game-mode .clue-text { font-weight: 600; color: #fff; font-size: 1.15rem; line-height: 1.5; }
        .game-mode .btn-help { 
            display: inline-block; margin-top: 20px; padding: 12px 24px; 
            background: transparent; color: #cbd5e1; 
            text-decoration: none; border-radius: 30px; font-size: 0.95rem; font-weight: 600;
            border: 1px solid #475569; transition: all 0.2s ease;
        }
        .game-mode .btn-help:active { background: #475569; color: #fff; }

        /* -------------------------------------
           FINALO REŽIMAS (7 STOTELĖ)
        ----------------------------------------*/
        body.finale-mode { 
            background: linear-gradient(to bottom right, #fdfbfb, #ebedee); 
            color: #1c1917; 
        }
        .finale-mode .card { 
            background: #ffffff; 
            box-shadow: 0 20px 40px rgba(140, 90, 64, 0.08); 
            border: 1px solid rgba(140, 90, 64, 0.1);
        }
        .finale-mode h1 { 
            font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 600; 
            color: #8C5A40; margin: 0 0 20px 0;
        }
        .finale-mode p { 
            font-family: 'Playfair Display', serif; font-style: italic;
            line-height: 1.8; font-size: 1.25rem; color: #57534e; margin-bottom: 40px; 
        }
        .finale-mode .clue-box { 
            padding: 0; background: transparent; border: none;
        }
        .finale-mode .clue-text { 
            font-family: 'Inter', sans-serif; font-weight: 400; color: #292524; font-size: 1.1rem; 
            text-transform: uppercase; letter-spacing: 2px;
        }
    </style>
</head>
<body class="<?php echo $is_finale ? 'finale-mode' : 'game-mode'; ?>">
    <div class="card">
        <h1><?php echo htmlspecialchars($title_text); ?></h1>
        <p><?php echo nl2br(htmlspecialchars($desc_text)); ?></p>
        
        <div class="clue-box">
            <div class="clue-text"><?php echo nl2br(htmlspecialchars($clue_text)); ?></div>
            <?php if (!$is_finale && !empty($data['maps_url'])): ?>
                <a href="<?php echo htmlspecialchars($data['maps_url']); ?>" class="btn-help" onclick="return confirm('Rodyti tikslią vietą žemėlapyje?');">Nerandu vietos... 📍</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
