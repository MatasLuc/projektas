<?php
// BŪTINA: Pradedame sesiją vardo išsaugojimui
session_start();

require 'db.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$total_steps = 7; // Bendras stotelių skaičius

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

$stmt = $pdo->prepare("SELECT * FROM hunt_content WHERE stage = ?");
$stmt->execute([$step]);
$data = $stmt->fetch();

if (!$data) die("Klaida: Žingsnis nerastas.");

$stmt = $pdo->prepare("INSERT INTO hunt_progress (player_name, stage) VALUES (?, ?) ON DUPLICATE KEY UPDATE visited_at = CURRENT_TIMESTAMP");
$stmt->execute([$player_name, $step]);

$is_finale = ($step === $total_steps);

$title_text = str_replace('{vardas}', $player_name, $data['title']);
$desc_text = str_replace('{vardas}', $player_name, $data['description']);
$clue_text = str_replace('{vardas}', $player_name, $data['clue']);

$progress_percent = ($step / $total_steps) * 100;
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($title_text); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-game: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            --bg-finale: linear-gradient(to bottom right, #fdfbfb, #ebedee);
            --accent: #fbbf24;
            --nav-bg: rgba(15, 23, 42, 0.95);
        }

        body { 
            margin: 0; padding: 0 20px 100px 20px; /* Padding apačioje meniu juostai */
            min-height: 100vh; 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            box-sizing: border-box; -webkit-font-smoothing: antialiased;
            background: var(--bg-game); color: #f8fafc; font-family: 'Inter', sans-serif;
        }
        
        /* Progresijos juosta (Viršuje) */
        .top-bar {
            position: absolute; top: 0; left: 0; width: 100%; padding: 20px;
            box-sizing: border-box; text-align: center;
        }
        .progress-text { font-size: 0.85rem; font-weight: 600; color: #cbd5e1; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;}
        .progress-bg { background: rgba(255,255,255,0.1); height: 6px; border-radius: 10px; width: 100%; max-width: 300px; margin: 0 auto; overflow: hidden; }
        .progress-fill { background: var(--accent); height: 100%; border-radius: 10px; transition: width 0.5s ease; }

        .card { 
            width: 100%; max-width: 420px; padding: 40px 30px; border-radius: 24px; text-align: center; box-sizing: border-box;
            background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
            animation: fadeInUp 0.8s ease-out forwards; opacity: 0; transform: translateY(20px);
            margin-top: 50px;
        }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        h1 { margin: 0 0 15px 0; font-size: 1.75rem; font-weight: 700; color: var(--accent); letter-spacing: -0.5px; }
        p { line-height: 1.7; font-size: 1.1rem; margin-bottom: 30px; color: #cbd5e1; }
        
        .clue-box { background: rgba(0, 0, 0, 0.2); padding: 25px 20px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); }
        .clue-text { font-weight: 600; color: #fff; font-size: 1.15rem; line-height: 1.5; }
        .btn-help { 
            display: inline-block; margin-top: 20px; padding: 12px 24px; 
            background: transparent; color: #cbd5e1; text-decoration: none; border-radius: 30px; font-size: 0.95rem; font-weight: 600;
            border: 1px solid #475569; transition: all 0.2s ease;
        }
        .btn-help:active { background: #475569; color: #fff; }

        /* Apatinis Navigacijos Meniu */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%; background: var(--nav-bg);
            backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 12px 0;
            padding-bottom: env(safe-area-inset-bottom, 12px); border-top: 1px solid rgba(255,255,255,0.05); z-index: 900;
        }
        .nav-item { color: #64748b; text-decoration: none; display: flex; flex-direction: column; align-items: center; font-size: 0.75rem; font-weight: 500; cursor: pointer; transition: 0.2s; border: none; background: none;}
        .nav-item span.icon { font-size: 1.4rem; margin-bottom: 4px; }
        .nav-item.active { color: var(--accent); }

        /* Modalų Stiliai (DUK ir Kontaktai) */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: 0.3s ease; z-index: 1000; padding: 20px; box-sizing: border-box;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-card {
            background: #1e293b; border-radius: 24px; width: 100%; max-width: 400px; padding: 30px 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5); transform: translateY(50px); transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            border: 1px solid rgba(255,255,255,0.1); position: relative; color: #f8fafc; text-align: left; max-height: 80vh; overflow-y: auto;
        }
        .modal-overlay.active .modal-card { transform: translateY(0); }
        .modal-close { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.1); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;}
        .modal-title { font-size: 1.3rem; color: var(--accent); margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;}
        
        .faq-item { margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;}
        .faq-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
        .faq-q { font-weight: 600; margin-bottom: 8px; color: #e2e8f0; font-size: 1.05rem;}
        .faq-a { color: #94a3b8; font-size: 0.95rem; line-height: 1.5; margin: 0;}
        
        .contact-box { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 16px; text-align: center; margin-bottom: 15px;}
        .phone-number { font-size: 1.5rem; color: #10b981; font-weight: 700; margin: 10px 0; text-decoration: none; display: block;}
        .btn-call { display: block; background: #10b981; color: white; padding: 12px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 1.1rem; }

        /* Finalo režimo perrašymai */
        body.finale-mode { background: var(--bg-finale); color: #1c1917; }
        .finale-mode .top-bar .progress-text { color: #57534e; }
        .finale-mode .progress-bg { background: rgba(0,0,0,0.1); }
        .finale-mode .progress-fill { background: #8C5A40; }
        .finale-mode .card { background: #ffffff; box-shadow: 0 20px 40px rgba(140, 90, 64, 0.08); border: 1px solid rgba(140, 90, 64, 0.1); }
        .finale-mode h1 { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 600; color: #8C5A40; }
        .finale-mode p { font-family: 'Playfair Display', serif; font-style: italic; font-size: 1.25rem; color: #57534e; }
        .finale-mode .clue-box { padding: 0; background: transparent; border: none; }
        .finale-mode .clue-text { font-family: 'Inter', sans-serif; font-weight: 400; color: #292524; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 2px; }
        
        /* Modalo fonas finale lieka tamsus, kad gražiai kontrastuotų */
    </style>
</head>
<body class="<?php echo $is_finale ? 'finale-mode' : 'game-mode'; ?>">

    <div class="top-bar">
        <div class="progress-text">Progresas: <?php echo $step; ?> / <?php echo $total_steps; ?></div>
        <div class="progress-bg">
            <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
        </div>
    </div>

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

    <div class="bottom-nav">
        <button class="nav-item active" onclick="closeAllModals()">
            <span class="icon">🧭</span>
            <span>Užduotis</span>
        </button>
        <button class="nav-item" onclick="openModal('faqModal')">
            <span class="icon">💡</span>
            <span>DUK</span>
        </button>
        <button class="nav-item" onclick="openModal('contactModal')">
            <span class="icon">📞</span>
            <span>Pagalba</span>
        </button>
    </div>

    <div class="modal-overlay" id="faqModal">
        <div class="modal-card">
            <button class="modal-close" onclick="closeAllModals()">✕</button>
            <h2 class="modal-title">💡 Dažniausi klausimai</h2>
            
            <div class="faq-item">
                <div class="faq-q">📍 Kaip rasti sekančią vietą?</div>
                <div class="faq-a">Perskaityk užuominą juodame laukelyje. Nukeliavus į nurodytą vietą, rasi kitą paslėptą QR kodą.</div>
            </div>
            <div class="faq-item">
                <div class="faq-q">⏳ Ar yra laiko limitas?</div>
                <div class="faq-a">Niekur skubėti nereikia! Tai asmeninis nuotykis, tad mėgaukis miestu ir procesu savo tempu.</div>
            </div>
            <div class="faq-item">
                <div class="faq-q">🗺 Ką daryti visiškai pasiklydus?</div>
                <div class="faq-a">Jei vieta turi mygtuką „Nerandu vietos...“, paspausk jį – atsidarys Google žemėlapis. Arba eik į „Pagalbos“ skiltį.</div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="contactModal">
        <div class="modal-card">
            <button class="modal-close" onclick="closeAllModals()">✕</button>
            <h2 class="modal-title">📞 Pagalbos linija</h2>
            
            <p style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 20px;">
                Jei visiškai užstrigai, nerandi kodo arba kažkas neveikia – skambink organizatoriui (Slaptajam Agentui).
            </p>
            
            <div class="contact-box">
                <span style="font-size: 0.85rem; color: #94a3b8; text-transform: uppercase;">Tiesioginis numeris</span>
                <a href="tel:+37060000000" class="phone-number">+370 600 00000</a> 
                <a href="tel:+37060000000" class="btn-call">Skambinti dabar</a>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            closeAllModals(); // Uždaro kitus prieš atidarant
            document.getElementById(modalId).classList.add('active');
            
            // Atnaujina mygtukų spalvas
            document.querySelectorAll('.nav-item').forEach(btn => btn.classList.remove('active'));
            if(modalId === 'faqModal') document.querySelectorAll('.nav-item')[1].classList.add('active');
            if(modalId === 'contactModal') document.querySelectorAll('.nav-item')[2].classList.add('active');
        }

        function closeAllModals() {
            document.querySelectorAll('.modal-overlay').forEach(modal => modal.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.nav-item')[0].classList.add('active'); // Grąžina aktyvumą pirmajam mygtukui
        }

        // Uždaro modalą paspaudus už jo ribų (ant tamsaus fono)
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if(e.target === this) closeAllModals();
            });
        });
    </script>
</body>
</html>