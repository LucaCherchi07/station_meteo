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

// Vérifier si les données POST sont présentes
if (isset($_POST['temperature']) && isset($_POST['humidity'])) {
  $temperature = $_POST['temperature'];
  $humidity = $_POST['humidity'];

  // Préparer la requête SQL pour insérer les données
  $sql = "INSERT INTO data (temperature, humidity) VALUES ('$temperature', '$humidity')";

  // Exécuter la requête et vérifier si elle réussit
  if ($conn->query($sql) === TRUE) {
    echo "Données envoyées avec succès";
  } else {
    echo "Erreur: " . $sql . "<br>" . $conn->error;
  }
} else {
  echo "Données non reçues";
}

$conn->close();
?>
