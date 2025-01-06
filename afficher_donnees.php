<?php
// Informations de connexion à la base de données
$servername = "localhost"; // Ou l'adresse de ton serveur de base de données
$username = "root";        // Remplace par ton nom d'utilisateur
$password = "";            // Remplace par ton mot de passe
$dbname = "meteo_db"; // Nom de ta base de données

// Créer la connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier si la connexion a échoué
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Préparer la requête SQL pour insérer les données
$yesterday = new \DateTime('1 day ago');
echo $yesterday->format('Y-m-d H:i:s');
$sql = "SELECT * FROM data WHERE timestamp > '" . $yesterday->format('Y-m-d H:i:s') . "';";

if ($datas = $conn->query($sql)) {
    foreach ($datas as $data) {
        echo $data['temperature']. "<br>";
    }
}