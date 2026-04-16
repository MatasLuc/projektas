<?php
// valdymas/index.php
require '../db.php';

$domain = 'https://7istorijos.lt';

if (isset($_POST['save_content'])) {
    $stmt = $pdo->prepare("UPDATE hunt_content SET title = ?, description = ?, clue = ?, maps_url = ? WHERE stage = ?");
    $stmt->execute([ $_POST['title'], $_POST['description'], $_POST['clue'], $_POST['maps_url'], $_POST['stage_id'] ]);
    $msg = "Stotelė #" . (int)$_POST['stage_id'] . " išsaugota sėkmingai!";
}

if (isset($_POST['reset'])) {
    $pdo->exec("TRUNCATE TABLE hunt_progress");
    header("Location: index.php");
    exit;
}

// GAUNAME PROGRESĄ, GRUPUOTĄ PAGAL VARDUS
$all_progress = $pdo->query("SELECT player_name, stage, visited_at FROM hunt_progress ORDER BY player_name, stage ASC")->fetchAll();

$players = [];
foreach ($all_progress as $row) {
    $players[$row['player_name']][$row['stage']] = $row['visited_at'];
}

$contents = $pdo->query("SELECT * FROM hunt_content ORDER BY stage ASC")->fetchAll();

// Susikuriame žemėlapių kodų masyvą
$stage_maps = [];
foreach ($contents as $c) {
    $stage_maps[$c['stage']] = $c['maps_url'];
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Valdymas - 7 Istorijos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-surface: #1e293b;
            --bg-elevated: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --radius: 12px;
            --radius-sm: 8px;
            --font: 'Inter', sans-serif;
        }

        body { font-family: var(--font); background: var(--bg-main); color: var(--text-main); margin: 0; padding: 0; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        
        /* Layout */
        .app-container { display: flex; flex-direction: column; min-height: 100vh; }
        .app-header { background: var(--bg-surface); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; padding: 0 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header-top { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .logo { font-size: 1.25rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px; }
        
        /* Navigation Tabs */
        .app-nav { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; }
        .app-nav::-webkit-scrollbar { display: none; }
        .nav-btn { background: transparent; border: none; color: var(--text-muted); padding: 10px 16px; font-size: 0.95rem; font-weight: 600; cursor: pointer; border-radius: var(--radius-sm); white-space: nowrap; transition: all 0.2s; font-family: var(--font); }
        .nav-btn:hover { color: var(--text-main); background: rgba(255, 255, 255, 0.05); }
        .nav-btn.active { color: #fff; background: var(--accent); }

        /* Main Content */
        .app-main { padding: 20px; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; }
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Buttons & Forms */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; font-size: 0.95rem; font-weight: 600; font-family: var(--font); border-radius: var(--radius-sm); border: none; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-success { background: var(--success); color: #fff; width: 100%; margin-top: 10px;}
        .btn-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-danger:hover { background: var(--danger); color: #fff; }
        .btn-sm { padding: 8px 12px; font-size: 0.85rem; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
        .form-control { width: 100%; padding: 12px 16px; background: var(--bg-main); border: 1px solid var(--border); border-radius: var(--radius-sm); color: #fff; font-family: var(--font); font-size: 1rem; box-sizing: border-box; transition: border-color 0.2s; resize: vertical; }
        .form-control:focus { outline: none; border-color: var(--accent); }

        /* Sections Headers */
        .section-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 24px; }
        .section-header h2 { margin: 0; font-size: 1.5rem; font-weight: 700; }
        .subtitle { color: var(--text-muted); font-size: 0.9rem; margin-top: -15px; margin-bottom: 20px; line-height: 1.5; }

        /* Radar Tab Styles */
        .player-card { background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .player-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
        .player-name { font-size: 1.1rem; font-weight: 700; color: var(--accent); margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .player-map-wrapper { width: 100%; margin-top: 10px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--border); }
        .player-map-wrapper iframe { width: 100% !important; height: 200px !important; display: block; border: none; }
        .btn-map { background: rgba(59, 130, 246, 0.1); color: var(--accent); padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 0.85rem; font-weight: 600; border: 1px solid rgba(59, 130, 246, 0.3); transition: 0.2s; }
        .btn-map:hover { background: var(--accent); color: #fff; }

        .stage-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
        .stage-item { background: rgba(0,0,0,0.2); padding: 12px; border-radius: var(--radius-sm); text-align: center; border: 1px solid transparent; }
        .stage-item.done { background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3); }
        .stage-title { font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px; }
        .stage-time { font-size: 0.9rem; font-weight: 500; color: #fff; display: block; }
        .stage-item.done .stage-time { color: var(--success); }

        /* Editor Tab Styles */
        .editor-item { background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 16px; overflow: hidden; transition: all 0.2s; }
        .editor-summary { padding: 16px 20px; cursor: pointer; font-weight: 600; font-size: 1.05rem; display: flex; justify-content: space-between; align-items: center; user-select: none; }
        .editor-summary::-webkit-details-marker { display: none; }
        .editor-summary::after { content: '▼'; font-size: 0.8rem; color: var(--text-muted); transition: transform 0.3s; }
        .editor-item[open] .editor-summary::after { transform: rotate(180deg); }
        .editor-item[open] { border-color: var(--accent); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .editor-form { padding: 0 20px 20px 20px; border-top: 1px solid var(--border); margin-top: 10px; padding-top: 15px; }

        /* QR Tab Styles */
        .qr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .qr-card { background: var(--bg-surface); padding: 20px; border-radius: var(--radius); border: 1px solid var(--border); text-align: center; display: flex; flex-direction: column; align-items: center; }
        .qr-card h3 { margin: 0 0 15px 0; font-size: 1.1rem; color: #fff; }
        .qr-img-box { background: #fff; padding: 10px; border-radius: 8px; margin-bottom: 15px; width: 100%; max-width: 180px; box-sizing: border-box; }
        .qr-img-box img { width: 100%; height: auto; display: block; }
        .qr-link { font-size: 0.75rem; color: var(--text-muted); word-break: break-all; margin-bottom: 15px; line-height: 1.4; }
        .qr-actions { display: flex; gap: 10px; width: 100%; justify-content: center; }

        /* Toast Message */
        .toast { position: fixed; bottom: 20px; right: 20px; background: var(--success); color: #fff; padding: 12px 24px; border-radius: 30px; font-weight: 600; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); z-index: 1000; animation: slideUp 0.3s ease, fadeOut 0.5s ease 3s forwards; pointer-events: none; }
        @keyframes slideUp { from { transform: translateY(100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }

        /* Print Styles */
        @media print {
            body { background: #fff; color: #000; padding: 0; }
            .app-header, .btn, .subtitle, .section-header { display: none !important; }
            .tab-content { display: none !important; }
            #qr { display: block !important; }
            .qr-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20mm; }
            .qr-card { border: none; background: transparent; page-break-inside: avoid; }
            .qr-img-box { border: 2px solid #000; }
            .qr-link { color: #000; font-size: 10pt; }
            .qr-actions { display: none; }
        }
    </style>
</head>
<body>

    <?php if(isset($msg)): ?>
        <div class="toast">✅ <?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="app-container">
        <header class="app-header">
            <div class="header-top">
                <div class="logo">⚙️ Valdymas</div>
            </div>
            <nav class="app-nav">
                <button class="nav-btn active" onclick="switchTab('radar')">📡 Radaras</button>
                <button class="nav-btn" onclick="switchTab('editor')">✍️ Redaktorius</button>
                <button class="nav-btn" onclick="switchTab('qr')">🔲 QR Kodai</button>
            </nav>
        </header>

        <main class="app-main">

            <section id="radar" class="tab-content active">
                <div class="section-header">
                    <h2>Sekimo radaras</h2>
                    <form method="post" onsubmit="return confirm('Ar tikrai ištrinti visą vizitų istoriją? Šio veiksmo atšaukti negalima.');">
                        <button type="submit" name="reset" class="btn btn-danger">🗑️ Išvalyti istoriją</button>
                    </form>
                </div>
                
                <div id="radar-data">
                    <?php if (empty($players)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted); background: var(--bg-surface); border-radius: var(--radius); border: 1px dashed var(--border);">
                            🔍 Dar nėra jokių žaidėjų duomenų.
                        </div>
                    <?php else: ?>
                        <?php foreach ($players as $name => $stages): 
                            $latest_stage = max(array_keys($stages));
                            $latest_map_url = isset($stage_maps[$latest_stage]) ? trim($stage_maps[$latest_stage]) : '';
                        ?>
                            <div class="player-card">
                                <div class="player-card-header">
                                    <h3 class="player-name">👤 <?php echo htmlspecialchars($name); ?> <span style="color:var(--text-muted); font-size:0.9rem;">(Stotelė #<?php echo $latest_stage; ?>)</span></h3>
                                    
                                    <?php if (!empty($latest_map_url)): ?>
                                        <?php if (strpos($latest_map_url, '<iframe') === false): ?>
                                            <a href="<?php echo htmlspecialchars($latest_map_url); ?>" target="_blank" class="btn-map">🗺️ Atidaryti žemėlapį</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($latest_map_url) && strpos($latest_map_url, '<iframe') !== false): ?>
                                    <div class="player-map-wrapper">
                                        <?php echo $latest_map_url; ?>
                                    </div>
                                    <br>
                                <?php endif; ?>

                                <div class="stage-grid">
                                    <?php for ($i = 1; $i <= 7; $i++): ?>
                                        <div class="stage-item <?php echo isset($stages[$i]) ? 'done' : ''; ?>">
                                            <span class="stage-title">Stotelė <?php echo $i; ?></span>
                                            <span class="stage-time">
                                                <?php echo isset($stages[$i]) ? date("H:i:s", strtotime($stages[$i])) : '⏳ Laukiama'; ?>
                                            </span>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section id="editor" class="tab-content">
                <div class="section-header">
                    <h2>Turinio redaktorius</h2>
                </div>
                <p class="subtitle">Naudokite tekstą <b>{vardas}</b>, kad jis automatiškai pasikeistų į žaidėjo vardą. Į <b>Google Maps Embed</b> galite įklijuoti tiesioginę nuorodą arba visą <code>&lt;iframe&gt;</code> kodą.</p>
                
                <?php foreach ($contents as $c): ?>
                    <details class="editor-item" <?php if(isset($_POST['stage_id']) && $_POST['stage_id'] == $c['stage']) echo 'open'; ?>>
                        <summary class="editor-summary">
                            <span><?php echo $c['stage']; ?>. <?php echo htmlspecialchars($c['title'] ?: 'Bevardė stotelė'); ?></span>
                        </summary>
                        <form method="post" class="editor-form">
                            <input type="hidden" name="stage_id" value="<?php echo $c['stage']; ?>">
                            
                            <div class="form-group">
                                <label>Antraštė</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($c['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Pagrindinis tekstas (istorija)</label>
                                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($c['description']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Užuomina toliau (paryškintas tekstas)</label>
                                <textarea name="clue" class="form-control" rows="2"><?php echo htmlspecialchars($c['clue']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Google Maps Embed (nuoroda arba iframe)</label>
                                <textarea name="maps_url" class="form-control" rows="3" placeholder='<iframe src="..."></iframe>'><?php echo htmlspecialchars($c['maps_url']); ?></textarea>
                            </div>
                            
                            <button type="submit" name="save_content" class="btn btn-success">💾 Išsaugoti pakeitimus</button>
                        </form>
                    </details>
                <?php endforeach; ?>
            </section>

            <section id="qr" class="tab-content">
                <div class="section-header">
                    <h2>QR Kodų Generatorius</h2>
                    <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ Spausdinti visus</button>
                </div>
                
                <div class="qr-grid">
                    <?php for ($i = 1; $i <= 7; $i++): 
                        $url = $domain . '/?step=' . $i;
                        $qr_image_url = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=' . urlencode($url);
                    ?>
                        <div class="qr-card">
                            <h3><?php echo $i; ?> Stotelė</h3>
                            <div class="qr-img-box">
                                <img src="<?php echo $qr_image_url; ?>" alt="QR <?php echo $i; ?>" id="qr-img-<?php echo $i; ?>" crossorigin="anonymous">
                            </div>
                            <div class="qr-link"><?php echo $url; ?></div>
                            <div class="qr-actions">
                                <button type="button" class="btn btn-sm btn-primary" onclick="downloadQR('<?php echo $qr_image_url; ?>', 'Stotele_<?php echo $i; ?>.png')">
                                    ⬇️ Atsisiųsti
                                </button>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </section>

        </main>
    </div>

    <script>
        // Tab perjungimo logika
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            document.querySelector(`.nav-btn[onclick="switchTab('${tabId}')"]`).classList.add('active');
            
            // Išsaugome naršyklės atmintyje, kad po perkrovimo liktų tas pats
            localStorage.setItem('activeAdminTab', tabId);
        }

        // Užkraunant puslapį grąžinamas paskutinis aktyvus tab'as
        document.addEventListener("DOMContentLoaded", () => {
            let savedTab = localStorage.getItem('activeAdminTab') || 'radar';
            // Jei buvo rodomas išsaugojimo pranešimas (toast), priverstinai rodom redaktorių
            if (document.querySelector('.toast')) {
                savedTab = 'editor';
            }
            switchTab(savedTab);
        });

        // Radaro auto-atnaujinimas (tik jei atidarytas Radaro skirtukas)
        setInterval(() => {
            if (document.getElementById('radar').classList.contains('active')) {
                fetch(location.href)
                .then(res => res.text())
                .then(html => {
                    let doc = new DOMParser().parseFromString(html, 'text/html');
                    let newData = doc.getElementById('radar-data');
                    if (newData) {
                        document.getElementById('radar-data').innerHTML = newData.innerHTML;
                    }
                });
            }
        }, 5000);

        // QR kodų atsisiuntimo funkcija
        async function downloadQR(url, filename) {
            try {
                // Bandome parsiųsti paveikslėlį ir konvertuoti į naršyklei suprantamą failą (Blob)
                const response = await fetch(url);
                const blob = await response.blob();
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (error) {
                // Jei išorinis serveris (CORS) blokuoja fetch užklausą, atidarome QR naujame lange
                window.open(url, '_blank');
            }
        }
    </script>
</body>
</html>