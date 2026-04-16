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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valdymas - 7 Istorijos</title>
    <style>
        :root {
            --bg-main: #0f172a; --bg-panel: #1e293b; --bg-item: #334155; --bg-input: #0f172a;
            --text-main: #f8fafc; --text-muted: #94a3b8;
            --accent: #3b82f6; --success: #10b981; --danger: #ef4444;
            --radius: 16px; --radius-sm: 8px;
        }

        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg-main); color: var(--text-main); padding: 20px; margin: 0; line-height: 1.5; }
        .container { max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start; }
        
        .panel { 
            flex: 1 1 320px; background: var(--bg-panel); padding: 24px; 
            border-radius: var(--radius); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); 
            box-sizing: border-box; border: 1px solid rgba(255,255,255,0.05);
        }
        
        h2 { margin-top: 0; font-size: 1.25rem; font-weight: 600; margin-bottom: 20px; color: #fff; }
        
        /* Radaras - Grupuotas */
        .player-group { background: rgba(0,0,0,0.2); border-radius: var(--radius-sm); padding: 15px; margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.05);}
        .player-header { margin-bottom: 12px; }
        .player-name { font-size: 1rem; color: var(--accent); margin: 0 0 10px 0; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;}
        .item { padding: 14px; margin-bottom: 8px; border-radius: var(--radius-sm); background: var(--bg-item); display: flex; justify-content: space-between; align-items: center; font-weight: 500; font-size: 0.9rem;}
        .done { border-left: 4px solid var(--success); background: rgba(16, 185, 129, 0.1); color: #fff; }
        
        /* Įterptas žemėlapis */
        .player-map { margin-bottom: 12px; overflow: hidden; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); }
        .player-map iframe { width: 100% !important; height: 220px !important; display: block; border: none; }
        .btn-map { display: inline-block; background: rgba(59, 130, 246, 0.1); color: var(--accent); padding: 6px 12px; border-radius: 20px; text-decoration: none; font-size: 0.8rem; font-weight: bold; border: 1px solid rgba(59, 130, 246, 0.3); transition: 0.2s; margin-bottom: 12px;}
        .btn-map:hover { background: var(--accent); color: #fff; }

        /* Redaktorius */
        details { background: var(--bg-item); margin-bottom: 12px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid rgba(255,255,255,0.05); transition: all 0.2s; }
        summary { padding: 16px; cursor: pointer; font-weight: 600; list-style: none; display: flex; align-items: center; }
        summary::-webkit-details-marker { display: none; }
        summary::before { content: '▸'; margin-right: 10px; color: var(--text-muted); transition: transform 0.2s; }
        details[open] summary::before { transform: rotate(90deg); }
        details[open] { background: var(--bg-panel); border-color: var(--accent); }
        
        .form-content { padding: 0 16px 16px 16px; display: flex; flex-direction: column; gap: 16px; }
        label { font-size: 0.85rem; font-weight: 500; color: var(--text-muted); margin-bottom: -10px; display: block; }
        
        input, textarea { 
            padding: 14px; border-radius: var(--radius-sm); border: 1px solid #475569; 
            background: var(--bg-input); color: #fff; font-size: 1rem; width: 100%; 
            box-sizing: border-box; font-family: inherit; outline: none; transition: all 0.2s;
        }
        input:focus, textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        
        /* Mygtukai */
        .btn { width: 100%; padding: 14px; border: none; border-radius: var(--radius-sm); cursor: pointer; font-weight: 600; font-size: 1rem; transition: opacity 0.2s; }
        .btn:active { opacity: 0.8; }
        .btn-red { background: rgba(239, 68, 68, 0.1); color: var(--danger); margin-top: 10px; border: 1px solid rgba(239, 68, 68, 0.2); }
        .btn-green { background: var(--success); color: white; margin-top: 8px; }
        
        .msg { background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 20px; text-align: center; font-weight: 500; border: 1px solid rgba(16, 185, 129, 0.2); }

        /* QR Panelė */
        .qr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 16px; }
        .qr-card { background: #fff; padding: 16px; border-radius: var(--radius-sm); text-align: center; }
        .qr-card img { max-width: 100%; height: auto; border-radius: 4px; }
        .qr-card h3 { margin: 0 0 8px 0; font-size: 1rem; color: #0f172a; }
        .qr-card p { font-size: 0.75em; word-break: break-all; margin: 8px 0 0 0; color: #64748b; }
        
        @media print {
            body { background: #fff; color: #000; padding: 0; }
            .panel:not(.qr-panel) { display: none; }
            .qr-panel { background: #fff; padding: 0; box-shadow: none; border: none; }
            .qr-card { border: 1px dashed #ccc; page-break-inside: avoid; }
        }
    </style>
    <script>
        setInterval(() => {
            fetch(location.href)
            .then(res => res.text())
            .then(html => {
                let doc = new DOMParser().parseFromString(html, 'text/html');
                document.getElementById('radar').innerHTML = doc.getElementById('radar').innerHTML;
            });
        }, 5000);
    </script>
</head>
<body>
    <div class="container">
        
        <div class="panel">
            <h2>Sekimo radaras</h2>
            <div id="radar">
                <?php if (empty($players)): ?>
                    <p style="color:var(--text-muted); text-align:center;">Dar nėra jokių žaidėjų.</p>
                <?php else: ?>
                    <?php foreach ($players as $name => $stages): 
                        $latest_stage = max(array_keys($stages));
                        $latest_map_url = isset($stage_maps[$latest_stage]) ? trim($stage_maps[$latest_stage]) : '';
                    ?>
                        <div class="player-group">
                            <div class="player-header">
                                <h3 class="player-name">👤 <?php echo htmlspecialchars($name); ?> (Stotelė #<?php echo $latest_stage; ?>)</h3>
                                
                                <?php if (!empty($latest_map_url)): ?>
                                    <?php 
                                    // Tikriname, ar įvestas kodas prasideda " <iframe "
                                    if (strpos($latest_map_url, '<iframe') !== false): 
                                    ?>
                                        <div class="player-map">
                                            <?php echo $latest_map_url; // Išvedame patį iframe ?>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($latest_map_url); ?>" target="_blank" class="btn-map">🗺️ Atidaryti žemėlapį</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <?php for ($i = 1; $i <= 7; $i++): ?>
                                <div class="item <?php echo isset($stages[$i]) ? 'done' : ''; ?>">
                                    <span>Stotelė <?php echo $i; ?></span>
                                    <span><?php echo isset($stages[$i]) ? date("H:i:s", strtotime($stages[$i])) : '⏳ Laukiama'; ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form method="post" onsubmit="return confirm('Ar tikrai ištrinti vizitų istoriją?');">
                <button type="submit" name="reset" class="btn btn-red">Išvalyti istoriją</button>
            </form>
        </div>

        <div class="panel" style="flex-basis: 420px;">
            <h2>Turinio redaktorius</h2>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:-15px; margin-bottom:20px;">
                Naudokite tekstą <b>{vardas}</b> ir jis automatiškai pasikeis į žaidėjo vardą! Į laukelį <b>Google Maps Embed</b> galite įklijuoti visą <code><iframe></code> kodą.
            </p>
            <?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>
            
            <?php foreach ($contents as $c): ?>
                <details <?php if(isset($_POST['stage_id']) && $_POST['stage_id'] == $c['stage']) echo 'open'; ?>>
                    <summary><?php echo $c['stage']; ?>. <?php echo htmlspecialchars($c['title']); ?></summary>
                    <form method="post" class="form-content">
                        <input type="hidden" name="stage_id" value="<?php echo $c['stage']; ?>">
                        
                        <label>Antraštė</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($c['title']); ?>" required>
                        
                        <label>Pagrindinis tekstas</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($c['description']); ?></textarea>
                        
                        <label>Užuomina toliau</label>
                        <textarea name="clue" rows="2"><?php echo htmlspecialchars($c['clue']); ?></textarea>
                        
                        <label>Google Maps Embed (nuoroda arba iframe)</label>
                        <textarea name="maps_url" rows="3" placeholder='<iframe src="https://www.google.com/maps/embed?..." ...></iframe>'><?php echo htmlspecialchars($c['maps_url']); ?></textarea>
                        
                        <button type="submit" name="save_content" class="btn btn-green">Išsaugoti pakeitimus</button>
                    </form>
                </details>
            <?php endforeach; ?>
        </div>

        <div class="panel qr-panel">
            <h2>QR Kodai</h2>
            <div class="qr-grid">
                <?php for ($i = 1; $i <= 7; $i++): 
                    $url = $domain . '/?step=' . $i;
                    $qr_image_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
                ?>
                    <div class="qr-card">
                        <h3><?php echo $i; ?> Stotelė</h3>
                        <img src="<?php echo $qr_image_url; ?>" alt="QR <?php echo $i; ?>">
                        <p><?php echo $url; ?></p>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

    </div>
</body>
</html>