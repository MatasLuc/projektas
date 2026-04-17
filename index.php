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
        <title>Pradėti iššūkį - 7 Istorijos</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            :root { --bg: #121212; --surface: #1e1e1e; --accent: #10b981; --text: #e0e0e0; }
            body { 
                margin: 0; padding: 20px; min-height: 100vh; 
                display: flex; flex-direction: column; align-items: center; justify-content: center; 
                background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; 
                box-sizing: border-box; -webkit-font-smoothing: antialiased;
            }
            .brand-header { 
                width: 100%; text-align: center; font-size: 1.4rem; color: var(--text); 
                font-weight: 700; letter-spacing: 2px; text-transform: uppercase; 
                animation: fadeInUp 0.8s ease forwards; margin-bottom: 30px; 
            }
            .card { 
                background: var(--surface); border: 1px solid rgba(255, 255, 255, 0.05); 
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); padding: 40px 30px; 
                border-radius: 16px; text-align: center; width: 100%; max-width: 350px; 
                animation: fadeInUp 0.8s ease forwards; box-sizing: border-box; margin-top: 0;
            }
            @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
            h1 { color: var(--accent); font-size: 1.6rem; margin-bottom: 20px; }
            .intro-text { color: #a0a0a0; font-size: 1rem; line-height: 1.6; margin-bottom: 30px; }
            .intro-text p { margin: 0 0 12px 0; }
            .intro-text p:last-child { margin: 0; }
            input { width: 100%; padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: #2a2a2a; color: white; font-size: 1.1rem; box-sizing: border-box; margin-bottom: 20px; text-align: center; outline: none; transition: 0.2s; }
            input:focus { border-color: var(--accent); }
            button { width: 100%; padding: 15px; background: var(--accent); color: #000; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.2s; text-transform: uppercase; letter-spacing: 1px;}
            button:active { transform: scale(0.98); }
        </style>
    </head>
    <body>
        <div class="brand-header">7 istorijos. Žvėrynas</div>
        <div class="card">
            <h1>Žvėryno iššūkis</h1>
            <div class="intro-text">
                <p>Labas! Džiaugiamės, kad pasiryžai priimti šį nuotykį.</p>
                <p>Taisyklės labai paprastos: vadovaudamasis užuominomis, turėsi surasti 7 paslėptas vietas ir jose nuskaityti QR kodus.</p>
                <p>Jei kartais užstrigsi – nieko baisaus, lokaciją visada galėsi pasitikrinti žemėlapyje. Tačiau patariame juo nepiktnaudžiauti, nes tai atsilieps tavo galutiniam rezultatui! 😉</p>
            </div>
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

$title_text = str_replace('{vardas}', $player_name, $data['title']);
$desc_text = str_replace('{vardas}', $player_name, $data['description']);
$clue_text = str_replace('{vardas}', $player_name, $data['clue']);
$secret_symbol = isset($data['secret_symbol']) ? trim($data['secret_symbol']) : '';

$progress_percent = ($step / $total_steps) * 100;

$maps_code = isset($data['maps_url']) ? trim($data['maps_url']) : '';
$is_iframe = (strpos($maps_code, '<iframe') !== false);
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($title_text); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-game: #121212;
            --surface: #1e1e1e;
            --accent: #10b981; /* Emerald Green */
            --accent-hover: #059669;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --nav-bg: rgba(18, 18, 18, 0.95);
        }

        body { 
            margin: 0; padding: 20px 20px 100px 20px; 
            min-height: 100vh; 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            box-sizing: border-box; -webkit-font-smoothing: antialiased;
            background: var(--bg-game); color: var(--text-main); font-family: 'Inter', sans-serif;
        }
        
        .top-bar {
            position: relative; width: 100%; padding: 0 0 20px 0; margin-bottom: 20px;
            box-sizing: border-box; text-align: center;
        }

        .brand-header {
            font-size: 1.1rem;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 15px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .progress-text { font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;}
        .progress-bg { background: #2a2a2a; height: 4px; border-radius: 4px; width: 100%; max-width: 300px; margin: 0 auto; overflow: hidden; }
        .progress-fill { background: var(--accent); height: 100%; border-radius: 4px; transition: width 0.5s ease; }

        .card { 
            width: 100%; max-width: 420px; padding: 40px 30px; border-radius: 16px; text-align: center; box-sizing: border-box;
            background: var(--surface); border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3); 
            animation: fadeInUp 0.8s ease-out forwards; opacity: 0; transform: translateY(20px);
            margin-top: 0; 
        }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        h1 { margin: 0 0 15px 0; font-size: 1.75rem; font-weight: 700; color: var(--accent); }
        p { line-height: 1.7; font-size: 1.1rem; margin-bottom: 30px; color: var(--text-muted); }
        
        .secret-box {
            margin: 0 auto 25px auto;
            padding: 15px;
            background: rgba(16, 185, 129, 0.05);
            border: 1px dashed var(--accent);
            border-radius: 8px;
            display: inline-block;
            min-width: 60%;
        }
        .secret-label {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .secret-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
            font-family: monospace;
            letter-spacing: 2px;
        }

        .clue-box { background: #2a2a2a; padding: 25px 20px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.02); }
        .clue-text { font-weight: 600; color: #fff; font-size: 1.15rem; line-height: 1.5; }
        
        .btn-help { 
            display: inline-block; margin-top: 20px; padding: 12px 24px; 
            background: transparent; color: var(--text-main); text-decoration: none; border-radius: 8px; font-size: 0.95rem; font-weight: 600;
            border: 1px solid #404040; transition: all 0.2s ease; cursor: pointer; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-help:active { background: #404040; }

        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%; background: var(--nav-bg);
            backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 12px 0;
            padding-bottom: env(safe-area-inset-bottom, 12px); border-top: 1px solid rgba(255,255,255,0.05); z-index: 900;
        }
        .nav-item { color: #666; text-decoration: none; display: flex; flex-direction: column; align-items: center; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: 0.2s; border: none; background: none; text-transform: uppercase; letter-spacing: 1px;}
        .nav-item span.icon { font-size: 1.4rem; margin-bottom: 4px; }
        .nav-item.active { color: var(--accent); }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); backdrop-filter: blur(5px);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: 0.3s ease; z-index: 1000; padding: 20px; box-sizing: border-box;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-card {
            background: var(--surface); border-radius: 16px; width: 100%; max-width: 400px; padding: 30px 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5); transform: translateY(50px); transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            border: 1px solid rgba(255,255,255,0.1); position: relative; color: var(--text-main); text-align: left; max-height: 80vh; overflow-y: auto;
        }
        .modal-overlay.active .modal-card { transform: translateY(0); }
        .modal-close { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.05); border: none; color: #fff; width: 32px; height: 32px; border-radius: 8px; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;}
        .modal-title { font-size: 1.3rem; color: var(--accent); margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;}
        
        .faq-item { margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;}
        .faq-q { font-weight: 600; margin-bottom: 8px; color: var(--text-main); font-size: 1.05rem;}
        .faq-a { color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin: 0;}
        
        .contact-box { background: #2a2a2a; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 15px;}
        .phone-number { font-size: 1.5rem; color: var(--accent); font-weight: 700; margin: 10px 0; text-decoration: none; display: block;}
        .btn-call { display: block; background: var(--accent); color: #000; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px;}

        .map-container { background: #121212; border-radius: 8px; overflow: hidden; margin-top: 10px; border: 1px solid rgba(255,255,255,0.05); }
        .map-container iframe { width: 100% !important; height: 350px !important; border: none; display: block; }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="brand-header">7 istorijos. Žvėrynas</div>
        <div class="progress-text">Progresas: <?php echo $step; ?> / <?php echo $total_steps; ?></div>
        <div class="progress-bg">
            <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
        </div>
    </div>

    <div class="card">
        <h1><?php echo htmlspecialchars($title_text); ?></h1>
        <p><?php echo nl2br(htmlspecialchars($desc_text)); ?></p>
        
        <?php if (!empty($secret_symbol)): ?>
            <div class="secret-box">
                <span class="secret-label">Įsimink šį simbolį:</span>
                <span class="secret-value"><?php echo htmlspecialchars($secret_symbol); ?></span>
            </div>
        <?php endif; ?>

        <div class="clue-box">
            <div class="clue-text"><?php echo nl2br(htmlspecialchars($clue_text)); ?></div>
            
            <?php if (!empty($maps_code)): ?>
                <?php if ($is_iframe): ?>
                    <button type="button" class="btn-help" onclick="openModal('mapModal')">Parodyti žemėlapyje 📍</button>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($maps_code); ?>" target="_blank" class="btn-help" onclick="return confirm('Rodyti tikslią vietą žemėlapyje?');">Parodyti žemėlapyje 📍</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="bottom-nav">
        <button class="nav-item active" onclick="closeAllModals()">
            <span class="icon">🎯</span>
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
                <div class="faq-a">Jei vieta turi mygtuką „Parodyti žemėlapyje“, paspausk jį – atsidarys Google žemėlapis. Arba eik į „Pagalbos“ skiltį.</div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="contactModal">
        <div class="modal-card">
            <button class="modal-close" onclick="closeAllModals()">✕</button>
            <h2 class="modal-title">📞 Pagalbos linija</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px;">Jei visiškai užstrigai, nerandi kodo arba kažkas neveikia – skambink organizatoriui.</p>
            <div class="contact-box">
                <span style="font-size: 0.85rem; color: #888; text-transform: uppercase;">Tiesioginis numeris</span>
                <a href="tel:+37060000000" class="phone-number">+370 600 00000</a> 
                <a href="tel:+37060000000" class="btn-call">Skambinti dabar</a>
            </div>
        </div>
    </div>

    <?php if ($is_iframe): ?>
    <div class="modal-overlay" id="mapModal">
        <div class="modal-card">
            <button class="modal-close" onclick="closeAllModals()">✕</button>
            <h2 class="modal-title">📍 Žemėlapis</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 15px;">Tiksli vietos lokacija:</p>
            <div class="map-container">
                <?php echo $maps_code; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openModal(modalId) {
            closeAllModals(); 
            document.getElementById(modalId).classList.add('active');
            
            document.querySelectorAll('.nav-item').forEach(btn => btn.classList.remove('active'));
            if(modalId === 'faqModal') document.querySelectorAll('.nav-item')[1].classList.add('active');
            if(modalId === 'contactModal') document.querySelectorAll('.nav-item')[2].classList.add('active');
        }

        function closeAllModals() {
            document.querySelectorAll('.modal-overlay').forEach(modal => modal.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.nav-item')[0].classList.add('active'); 
        }

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if(e.target === this) closeAllModals();
            });
        });
    </script>
</body>
</html>