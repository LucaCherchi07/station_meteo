<?php
// Connexion à la base de données
$host = 'localhost';
$dbname = 'meteo_db'; // Nom de la base de données
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Récupérer la dernière mesure
$query = $pdo->query("SELECT * FROM data ORDER BY timestamp DESC LIMIT 1");
$latestData = $query->fetch(PDO::FETCH_ASSOC);

// Récupérer les mesures des dernières 24 heures
$query24h = $pdo->query("SELECT * FROM data WHERE timestamp >= NOW() - INTERVAL 1 DAY");
$data24h = $query24h->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les mesures des dernières 12 heures
$query12h = $pdo->query("SELECT * FROM data WHERE timestamp >= NOW() - INTERVAL 12 HOUR");
$data12h = $query12h->fetchAll(PDO::FETCH_ASSOC);

// Préparer les données pour les graphiques
$timestamps24h = [];
$temperatures24h = [];
$humidities24h = [];

foreach ($data24h as $row) {
    $timestamps24h[] = $row['timestamp'];
    $temperatures24h[] = $row['temperature'];
    $humidities24h[] = $row['humidity'];
}

// Préparer les données pour le graphique des 12 dernières heures
$timestamps12h = [];
$temperatures12h = [];
$humidities12h = [];

foreach ($data12h as $row) {
    $timestamps12h[] = $row['timestamp'];
    $temperatures12h[] = $row['temperature'];
    $humidities12h[] = $row['humidity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Météo - Données</title>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
        }

        h1, h2 {
            text-align: center;
            margin: 10px 0;
            font-size: 1.5em;
        }

        p {
            text-align: center;
            margin: 5px 0;
            font-size: 1em;
        }

        #container, #container12h {
            width: 100%;
            height: 500px; /* Agrandi l'espace du graphique */
            margin: 10px 0;
            border: 1px solid #ddd;
            background-color: #fff;
        }

        #slider {
            width: 80%;
            margin: 10px auto;
            text-align: center;
        }

        .slider-label {
            display: inline-block;
            margin: 10px;
            font-size: 1.1em;
            color: #333;
        }

        input[type="range"] {
            width: 100%;
            height: 10px;
            -webkit-appearance: none;
            appearance: none;
            background: #ddd;
            border-radius: 5px;
        }

        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            background: #007bff;
            border-radius: 50%;
        }

        input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            background: #007bff;
            border-radius: 50%;
        }

        @media screen and (max-width: 768px) {
            h1, h2 {
                font-size: 1.2em;
            }

            p {
                font-size: 1em;
            }

            #container, #container12h {
                height: 350px; /* Ajustement pour mobile */
                margin: 10px 0;
            }

            #slider {
                width: 90%;
            }

            .slider-label {
                font-size: 1em;
            }

            input[type="range"] {
                height: 8px;
            }

            input[type="range"]::-webkit-slider-thumb,
            input[type="range"]::-moz-range-thumb {
                width: 18px;
                height: 18px;
            }
        }
    </style>
</head>
<body>
    <h1>Station Météo</h1>

    <h2>Dernière mesure</h2>
    <?php if ($latestData): ?>
        <p><strong>Température :</strong> <?= $latestData['temperature'] ?> °C</p>
        <p><strong>Humidité :</strong> <?= $latestData['humidity'] ?> %</p>
        <p><strong>Date :</strong> <?= $latestData['timestamp'] ?></p>
    <?php else: ?>
        <p>Aucune donnée disponible.</p>
    <?php endif; ?>

    <!-- Curseur pour sélectionner la période -->
    <div id="slider">
        <label for="timeRange" class="slider-label">Sélectionner la période:</label>
        <input type="range" id="timeRange" min="12" max="24" value="24" step="12" onchange="updateChart()">
        <span id="rangeLabel">24 heures</span>
    </div>

    <h2>Graphique des données</h2>
    <div id="container"></div>

    <script>
        // Préparer les données pour Highcharts
        const timestamps24h = <?= json_encode($timestamps24h) ?>;
        const temperatures24h = <?= json_encode($temperatures24h) ?>;
        const humidities24h = <?= json_encode($humidities24h) ?>;

        const timestamps12h = <?= json_encode($timestamps12h) ?>;
        const temperatures12h = <?= json_encode($temperatures12h) ?>;
        const humidities12h = <?= json_encode($humidities12h) ?>;

        let chart;

        // Fonction pour mettre à jour le graphique en fonction du curseur
        function updateChart() {
            const timeRange = document.getElementById('timeRange').value;
            const rangeLabel = document.getElementById('rangeLabel');
            rangeLabel.textContent = timeRange + " heures";

            if (timeRange == 24) {
                chart.update({
                    xAxis: {
                        categories: timestamps24h,
                    },
                    series: [{
                        name: 'Température (°C)',
                        data: temperatures24h
                    }, {
                        name: 'Humidité (%)',
                        data: humidities24h
                    }]
                });
            } else {
                chart.update({
                    xAxis: {
                        categories: timestamps12h,
                    },
                    series: [{
                        name: 'Température (°C)',
                        data: temperatures12h
                    }, {
                        name: 'Humidité (%)',
                        data: humidities12h
                    }]
                });
            }
        }

        // Initialisation du graphique avec les données par défaut (24 heures)
        chart = Highcharts.chart('container', {
            chart: {
                type: 'line',
                marginTop: 40, // Réduit l'espace au dessus du graphique
                marginBottom: 40 // Réduit l'espace en bas du graphique
            },
            title: {
                text: 'Données des 24 dernières heures',
                style: {
                    fontSize: '1.2em', // Réduit la taille du titre
                }
            },
            xAxis: {
                categories: timestamps24h,
                title: {
                    text: 'Horodatage'
                },
                labels: {
                    enabled: false // Désactive l'affichage des heures sous le graphique
                }
            },
            yAxis: {
                title: {
                    text: 'Valeurs'
                },
                labels: {
                    style: {
                        fontSize: '0.9em', // Réduit la taille des labels
                    }
                }
            },
            tooltip: {
                crosshairs: true, // Active le curseur vertical
                shared: true, // Affiche les valeurs des séries en même temps
                valueSuffix: '°C' // Un suffixe pour la température
            },
            series: [{
                name: 'Température (°C)',
                data: temperatures24h
            }, {
                name: 'Humidité (%)',
                data: humidities24h
            }]
        });
    </script>
</body>
</html>
