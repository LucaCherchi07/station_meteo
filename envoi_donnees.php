<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "meteo_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die("Erreur de connexion");
}

if (isset($_POST['temperature']) && isset($_POST['humidity'])) {
    $stmt = $conn->prepare("INSERT INTO data (temperature, humidity) VALUES (?, ?)");
    $stmt->bind_param("dd", $_POST['temperature'], $_POST['humidity']);

    if ($stmt->execute()) {
        echo "OK";
    } else {
        http_response_code(500);
        echo "Erreur d'insertion";
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo "Données manquantes";
}

$conn->close();
?>