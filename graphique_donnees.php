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

// Préparer les données pour le graphique
$timestamps = [];
$temperatures = [];
$humidities = [];

foreach ($data24h as $row) {
    $timestamps[] = $row['timestamp'];
    $temperatures[] = $row['temperature'];
    $humidities[] = $row['humidity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    h1, h2 {
        text-align: center;
    }

    p {
        text-align: center;
        margin: 10px 0;
    }

    #container {
        max-width: 100%;
        margin: 0 auto;
    }

    @media screen and (max-width: 768px) {
        h1, h2 {
            font-size: 18px;
        }

        p {
            font-size: 14px;
        }

        #container {
            height: 300px;
        }
    }
</style>

<link rel="manifest" href="manifest.json">
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('service-worker.js')
            .then(() => {
                console.log('Service Worker enregistré.');
            })
            .catch(error => {
                console.error('Service Worker échec de l\'enregistrement :', error);
            });
    }
</script>

    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    h1, h2 {
        text-align: center;
    }

    p {
        text-align: center;
        margin: 5px 0;
    }

    #container {
        max-width: 100%;
        margin: 0 auto;
    }

    @media screen and (max-width: 768px) {
        h1, h2 {
            font-size: 18px;
        }

        p {
            font-size: 14px;
        }

        #container {
            height: 300px;
        }
    }
    </style>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Météo - Données</title>
    <script src="https://code.highcharts.com/highcharts.js"></script>
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

    <h2>Graphique des 24 dernières heures</h2>
    <div id="container" style="width: 100%; height: 400px;"></div>

    <script>
        // Préparer les données pour Highcharts
        const timestamps = <?= json_encode($timestamps) ?>;
        const temperatures = <?= json_encode($temperatures) ?>;
        const humidities = <?= json_encode($humidities) ?>;

        Highcharts.chart('container', {
            chart: {
                type: 'line'
            },
            title: {
                text: 'Données des 24 dernières heures'
            },
            xAxis: {
                categories: timestamps,
                title: {
                    text: 'Horodatage'
                }
            },
            yAxis: {
                title: {
                    text: 'Valeurs'
                }
            },
            series: [{
                name: 'Température (°C)',
                data: temperatures
            }, {
                name: 'Humidité (%)',
                data: humidities
            }]
        });
    </script>
</body>
</html>
