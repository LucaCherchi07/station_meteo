<?php
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'meteo_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Dernière mesure
$queryLatest = $pdo->query("SELECT * FROM data ORDER BY timestamp DESC LIMIT 1");
$latestData = $queryLatest->fetch(PDO::FETCH_ASSOC);

// Statut du capteur (dernière donnée < 5 min ?)
$capteurOk = false;
$lastTimestamp = null;
if ($latestData) {
    $lastTimestamp = new DateTime($latestData['timestamp']);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $lastTimestamp->getTimestamp();
    $capteurOk = $diff < 300;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Station Météo</title>
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <style>
        :root {
            --bg: #0a0e1a;
            --bg2: #111827;
            --card: #161d2e;
            --border: #1e2d45;
            --accent-temp: #ff6b35;
            --accent-hum: #38bdf8;
            --text: #e2e8f0;
            --text-muted: #64748b;
            --green: #22c55e;
            --red: #ef4444;
            --radius: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Syne', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 24px 16px 40px;
            background-image:
                radial-gradient(ellipse at 20% 0%, rgba(56, 189, 248, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 10%, rgba(255, 107, 53, 0.06) 0%, transparent 50%);
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 12px;
        }

        .header-left h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .header-left p {
            font-family: 'DM Mono', monospace;
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: 999px;
            font-family: 'DM Mono', monospace;
            font-size: 0.7rem;
            font-weight: 500;
            border: 1px solid;
            white-space: nowrap;
        }

        .status-badge.ok {
            background: rgba(34,197,94,0.1);
            border-color: rgba(34,197,94,0.3);
            color: var(--green);
        }

        .status-badge.ko {
            background: rgba(239,68,68,0.1);
            border-color: rgba(239,68,68,0.3);
            color: var(--red);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-badge.ok .status-dot {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* CARDS */
        .cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 24px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
        }

        .card:hover { transform: translateY(-2px); }

        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
        }

        .card.temp::before { background: linear-gradient(90deg, var(--accent-temp), transparent); }
        .card.hum::before  { background: linear-gradient(90deg, var(--accent-hum), transparent); }

        .card-icon {
            font-size: 1.4rem;
            margin-bottom: 10px;
            display: block;
        }

        .card-label {
            font-family: 'DM Mono', monospace;
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 6px;
        }

        .card-value {
            font-size: 2.4rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
        }

        .card.temp .card-value { color: var(--accent-temp); }
        .card.hum  .card-value { color: var(--accent-hum); }

        .card-unit {
            font-size: 1rem;
            font-weight: 400;
            opacity: 0.6;
        }

        .card-timestamp {
            font-family: 'DM Mono', monospace;
            font-size: 0.6rem;
            color: var(--text-muted);
            margin-top: 10px;
        }

        /* STATS ROW */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 24px;
        }

        .stat-box {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            text-align: center;
        }

        .stat-label {
            font-family: 'DM Mono', monospace;
            font-size: 0.6rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .stat-value.temp { color: var(--accent-temp); }
        .stat-value.hum  { color: var(--accent-hum); }

        /* PERIOD BUTTONS */
        .period-selector {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .period-btn {
            font-family: 'DM Mono', monospace;
            font-size: 0.72rem;
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.18s;
            font-weight: 500;
        }

        .period-btn:hover {
            border-color: var(--accent-hum);
            color: var(--accent-hum);
        }

        .period-btn.active {
            background: var(--accent-hum);
            border-color: var(--accent-hum);
            color: #0a0e1a;
        }

        /* GRAPH */
        .graph-wrap {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 16px 10px;
            margin-bottom: 16px;
            position: relative;
        }

        .graph-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 14px;
            font-family: 'DM Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        #chart-container {
            width: 100%;
            height: 320px;
        }

        /* LOADING */
        .loading-overlay {
            position: absolute;
            inset: 0;
            background: var(--card);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }

        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .spinner {
            width: 28px;
            height: 28px;
            border: 2px solid var(--border);
            border-top-color: var(--accent-hum);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* REFRESH INFO */
        .refresh-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'DM Mono', monospace;
            font-size: 0.62rem;
            color: var(--text-muted);
            margin-top: 12px;
        }

        .refresh-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-family: 'DM Mono', monospace;
            font-size: 0.62rem;
            padding: 5px 12px;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.18s;
        }

        .refresh-btn:hover {
            border-color: var(--accent-hum);
            color: var(--accent-hum);
        }

        /* NO DATA */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
            font-family: 'DM Mono', monospace;
            font-size: 0.8rem;
        }

        /* MOBILE */
        @media (max-width: 480px) {
            body { padding: 16px 12px 32px; }
            .card-value { font-size: 2rem; }
            #chart-container { height: 260px; }
            .stats-row { grid-template-columns: repeat(3, 1fr); gap: 8px; }
            .stat-box { padding: 10px 8px; }
            .stat-value { font-size: 0.9rem; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <h1>⛅ Station Météo</h1>
        <p id="last-update-header">
            <?= $lastTimestamp ? 'Dernière donnée : ' . $lastTimestamp->format('d/m/Y H:i:s') : 'Aucune donnée' ?>
        </p>
    </div>
    <div class="status-badge <?= $capteurOk ? 'ok' : 'ko' ?>" id="status-badge">
        <span class="status-dot"></span>
        <?= $capteurOk ? 'En ligne' : 'Hors ligne' ?>
    </div>
</header>

<!-- CARTES PRINCIPALES -->
<div class="cards">
    <div class="card temp">
        <span class="card-icon">🌡️</span>
        <div class="card-label">Température</div>
        <div class="card-value" id="val-temp">
            <?= $latestData ? $latestData['temperature'] : '--' ?><span class="card-unit">°C</span>
        </div>
        <div class="card-timestamp" id="ts-temp">
            <?= $latestData ? $latestData['timestamp'] : '' ?>
        </div>
    </div>
    <div class="card hum">
        <span class="card-icon">💧</span>
        <div class="card-label">Humidité</div>
        <div class="card-value" id="val-hum">
            <?= $latestData ? $latestData['humidity'] : '--' ?><span class="card-unit">%</span>
        </div>
        <div class="card-timestamp" id="ts-hum">
            <?= $latestData ? $latestData['timestamp'] : '' ?>
        </div>
    </div>
</div>

<!-- STATS MIN/MAX/MOYENNE -->
<div class="stats-row" id="stats-row">
    <div class="stat-box">
        <div class="stat-label">🔻 Min temp</div>
        <div class="stat-value temp" id="stat-temp-min">--</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">🔺 Max temp</div>
        <div class="stat-value temp" id="stat-temp-max">--</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">⌀ Moy temp</div>
        <div class="stat-value temp" id="stat-temp-avg">--</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">🔻 Min hum</div>
        <div class="stat-value hum" id="stat-hum-min">--</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">🔺 Max hum</div>
        <div class="stat-value hum" id="stat-hum-max">--</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">⌀ Moy hum</div>
        <div class="stat-value hum" id="stat-hum-avg">--</div>
    </div>
</div>

<!-- SÉLECTEUR DE PÉRIODE -->
<div class="period-selector">
    <button class="period-btn" onclick="loadData(1)">1h</button>
    <button class="period-btn" onclick="loadData(6)">6h</button>
    <button class="period-btn" onclick="loadData(12)">12h</button>
    <button class="period-btn active" onclick="loadData(24)">24h</button>
</div>

<!-- GRAPHIQUE -->
<div class="graph-wrap">
    <div class="graph-title" id="graph-title">Dernières 24 heures</div>
    <div id="chart-container"></div>
    <div class="loading-overlay" id="loading">
        <div class="spinner"></div>
    </div>
</div>

<!-- BARRE DE REFRESH -->
<div class="refresh-bar">
    <span id="auto-refresh-label">Actualisation auto dans <span id="countdown">60</span>s</span>
    <button class="refresh-btn" onclick="manualRefresh()">↻ Actualiser</button>
</div>

<script>
let chart;
let currentHours = 24;
let countdownVal = 60;
let countdownInterval;
let autoRefreshTimeout;

// Initialisation du graphique Highcharts
chart = Highcharts.chart('chart-container', {
    chart: {
        type: 'line',
        backgroundColor: 'transparent',
        style: { fontFamily: "'DM Mono', monospace" },
        animation: { duration: 400 }
    },
    title: { text: null },
    credits: { enabled: false },
    legend: {
        itemStyle: { color: '#94a3b8', fontSize: '11px', fontWeight: '400' },
        itemHoverStyle: { color: '#e2e8f0' }
    },
    xAxis: {
        type: 'category',
        labels: {
            style: { color: '#64748b', fontSize: '10px' },
            rotation: -35,
            step: 'auto'
        },
        lineColor: '#1e2d45',
        tickColor: '#1e2d45',
        gridLineColor: 'transparent'
    },
    yAxis: [
        {
            title: { text: null },
            labels: { style: { color: '#ff6b35', fontSize: '10px' }, format: '{value}°C' },
            gridLineColor: '#1e2d45',
            gridLineDashStyle: 'Dot'
        },
        {
            title: { text: null },
            labels: { style: { color: '#38bdf8', fontSize: '10px' }, format: '{value}%' },
            opposite: true,
            gridLineColor: 'transparent'
        }
    ],
    tooltip: {
        shared: true,
        backgroundColor: '#161d2e',
        borderColor: '#1e2d45',
        borderRadius: 10,
        style: { color: '#e2e8f0', fontSize: '12px' },
        pointFormatter: function() {
            const unit = this.series.name.includes('Temp') ? '°C' : '%';
            return `<span style="color:${this.color}">●</span> ${this.series.name}: <b>${this.y}${unit}</b><br/>`;
        }
    },
    plotOptions: {
        line: {
            marker: { enabled: false, symbol: 'circle', radius: 3 },
            lineWidth: 2,
            states: { hover: { lineWidth: 3 } }
        }
    },
    series: [
        {
            name: 'Température',
            color: '#ff6b35',
            yAxis: 0,
            data: [],
            zones: [
                { value: 0, color: '#38bdf8' },
                { value: 20, color: '#ff6b35' },
                { value: 30, color: '#ef4444' }
            ]
        },
        {
            name: 'Humidité',
            color: '#38bdf8',
            yAxis: 1,
            data: [],
            dashStyle: 'ShortDash'
        }
    ]
});

function loadData(hours) {
    currentHours = hours;

    // Mise à jour des boutons
    document.querySelectorAll('.period-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    // Titre du graphique
    const labels = { 1: '1 heure', 6: '6 heures', 12: '12 heures', 24: '24 heures' };
    document.getElementById('graph-title').textContent = `Dernières ${labels[hours]}`;

    // Afficher le loader
    document.getElementById('loading').classList.add('active');

    fetch(`get_data.php?hours=${hours}`)
        .then(r => r.json())
        .then(data => {
            if (!data || data.length === 0) {
                chart.series[0].setData([]);
                chart.series[1].setData([]);
                document.getElementById('loading').classList.remove('active');
                return;
            }

            const timestamps = data.map(d => {
                const dt = new Date(d.timestamp);
                return dt.getHours().toString().padStart(2,'0') + ':' + dt.getMinutes().toString().padStart(2,'0');
            });

            const temps = data.map(d => parseFloat(d.temperature));
            const hums  = data.map(d => parseFloat(d.humidity));

            // Mise à jour du graphique
            chart.series[0].setData(timestamps.map((t, i) => [t, temps[i]]), false);
            chart.series[1].setData(timestamps.map((t, i) => [t, hums[i]]), true);

            // Calcul stats
            const minTemp = Math.min(...temps).toFixed(1);
            const maxTemp = Math.max(...temps).toFixed(1);
            const avgTemp = (temps.reduce((a,b) => a+b, 0) / temps.length).toFixed(1);
            const minHum  = Math.min(...hums).toFixed(0);
            const maxHum  = Math.max(...hums).toFixed(0);
            const avgHum  = (hums.reduce((a,b) => a+b, 0) / hums.length).toFixed(0);

            document.getElementById('stat-temp-min').textContent = minTemp + '°C';
            document.getElementById('stat-temp-max').textContent = maxTemp + '°C';
            document.getElementById('stat-temp-avg').textContent = avgTemp + '°C';
            document.getElementById('stat-hum-min').textContent  = minHum + '%';
            document.getElementById('stat-hum-max').textContent  = maxHum + '%';
            document.getElementById('stat-hum-avg').textContent  = avgHum + '%';

            document.getElementById('loading').classList.remove('active');
        })
        .catch(() => {
            document.getElementById('loading').classList.remove('active');
        });
}

function refreshLatest() {
    fetch('get_latest.php')
        .then(r => r.json())
        .then(d => {
            if (!d) return;

            document.getElementById('val-temp').innerHTML = d.temperature + '<span class="card-unit">°C</span>';
            document.getElementById('val-hum').innerHTML  = d.humidity    + '<span class="card-unit">%</span>';
            document.getElementById('ts-temp').textContent = d.timestamp;
            document.getElementById('ts-hum').textContent  = d.timestamp;
            document.getElementById('last-update-header').textContent = 'Dernière donnée : ' + d.timestamp;

            // Statut capteur
            const now = Date.now();
            const last = new Date(d.timestamp).getTime();
            const badge = document.getElementById('status-badge');
            const ok = (now - last) < 300000;
            badge.className = 'status-badge ' + (ok ? 'ok' : 'ko');
            badge.innerHTML = `<span class="status-dot"></span>${ok ? 'En ligne' : 'Hors ligne'}`;
        });
}

function manualRefresh() {
    resetCountdown();
    refreshLatest();
    loadDataSilent(currentHours);
}

function loadDataSilent(hours) {
    fetch(`get_data.php?hours=${hours}`)
        .then(r => r.json())
        .then(data => {
            if (!data || data.length === 0) return;

            const timestamps = data.map(d => {
                const dt = new Date(d.timestamp);
                return dt.getHours().toString().padStart(2,'0') + ':' + dt.getMinutes().toString().padStart(2,'0');
            });
            const temps = data.map(d => parseFloat(d.temperature));
            const hums  = data.map(d => parseFloat(d.humidity));

            chart.series[0].setData(timestamps.map((t, i) => [t, temps[i]]), false);
            chart.series[1].setData(timestamps.map((t, i) => [t, hums[i]]), true);

            const minTemp = Math.min(...temps).toFixed(1);
            const maxTemp = Math.max(...temps).toFixed(1);
            const avgTemp = (temps.reduce((a,b) => a+b, 0) / temps.length).toFixed(1);
            const minHum  = Math.min(...hums).toFixed(0);
            const maxHum  = Math.max(...hums).toFixed(0);
            const avgHum  = (hums.reduce((a,b) => a+b, 0) / hums.length).toFixed(0);

            document.getElementById('stat-temp-min').textContent = minTemp + '°C';
            document.getElementById('stat-temp-max').textContent = maxTemp + '°C';
            document.getElementById('stat-temp-avg').textContent = avgTemp + '°C';
            document.getElementById('stat-hum-min').textContent  = minHum + '%';
            document.getElementById('stat-hum-max').textContent  = maxHum + '%';
            document.getElementById('stat-hum-avg').textContent  = avgHum + '%';
        });
}

function resetCountdown() {
    clearInterval(countdownInterval);
    clearTimeout(autoRefreshTimeout);
    countdownVal = 60;
    document.getElementById('countdown').textContent = countdownVal;
    startCountdown();
}

function startCountdown() {
    countdownInterval = setInterval(() => {
        countdownVal--;
        document.getElementById('countdown').textContent = countdownVal;
        if (countdownVal <= 0) {
            clearInterval(countdownInterval);
        }
    }, 1000);

    autoRefreshTimeout = setTimeout(() => {
        refreshLatest();
        loadDataSilent(currentHours);
        resetCountdown();
    }, 60000);
}

// Chargement initial
loadData({ target: document.querySelector('.period-btn.active') }, 24);

// On redéfinit loadData pour gérer le cas initial sans event
function loadData(hours) {
    currentHours = hours;
    document.querySelectorAll('.period-btn').forEach(btn => {
        const btnHours = parseInt(btn.textContent);
        btn.classList.toggle('active', btnHours === hours);
    });

    const labels = { 1: '1 heure', 6: '6 heures', 12: '12 heures', 24: '24 heures' };
    document.getElementById('graph-title').textContent = `Dernières ${labels[hours]}`;
    document.getElementById('loading').classList.add('active');

    fetch(`get_data.php?hours=${hours}`)
        .then(r => r.json())
        .then(data => {
            if (!data || data.length === 0) {
                chart.series[0].setData([]);
                chart.series[1].setData([]);
                document.getElementById('loading').classList.remove('active');
                return;
            }

            const timestamps = data.map(d => {
                const dt = new Date(d.timestamp);
                return dt.getHours().toString().padStart(2,'0') + ':' + dt.getMinutes().toString().padStart(2,'0');
            });
            const temps = data.map(d => parseFloat(d.temperature));
            const hums  = data.map(d => parseFloat(d.humidity));

            chart.series[0].setData(timestamps.map((t, i) => [t, temps[i]]), false);
            chart.series[1].setData(timestamps.map((t, i) => [t, hums[i]]), true);

            const minTemp = Math.min(...temps).toFixed(1);
            const maxTemp = Math.max(...temps).toFixed(1);
            const avgTemp = (temps.reduce((a,b) => a+b, 0) / temps.length).toFixed(1);
            const minHum  = Math.min(...hums).toFixed(0);
            const maxHum  = Math.max(...hums).toFixed(0);
            const avgHum  = (hums.reduce((a,b) => a+b, 0) / hums.length).toFixed(0);

            document.getElementById('stat-temp-min').textContent = minTemp + '°C';
            document.getElementById('stat-temp-max').textContent = maxTemp + '°C';
            document.getElementById('stat-temp-avg').textContent = avgTemp + '°C';
            document.getElementById('stat-hum-min').textContent  = minHum + '%';
            document.getElementById('stat-hum-max').textContent  = maxHum + '%';
            document.getElementById('stat-hum-avg').textContent  = avgHum + '%';

            document.getElementById('loading').classList.remove('active');
        })
        .catch(() => document.getElementById('loading').classList.remove('active'));
}

loadData(24);
startCountdown();
</script>
</body>
</html>
