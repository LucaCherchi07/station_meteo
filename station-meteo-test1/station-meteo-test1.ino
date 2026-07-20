#include <SPI.h>
#include <WiFi.h>
#include <SimpleDHT.h>
#include <LiquidCrystal.h>
#include "config.h"

// Configuration WiFi
const char* ssid     = WIFI_SSID;
const char* password = WIFI_PASSWORD;
const char* server   = SERVER_IP;

// Initialisation du capteur DHT11
#define DHT_PIN 2
SimpleDHT11 dht11(DHT_PIN);

// Initialisation du LCD1602 (mode parallèle 4 bits)
// RS, E, D4, D5, D6, D7
LiquidCrystal lcd(7, 8, 9, 10, 11, 12);

WiFiClient client;

int keyIndex = 0;            // your network key index number (needed only for WEP)

int status = WL_IDLE_STATUS;

unsigned long lastConnectionTime = 0;            // last time you connected to the server, in milliseconds
const unsigned long postingInterval = 10L * 1000L; // delay between updates, in milliseconds


void setup() {
  //Initialize serial and wait for port to open:

  Serial.begin(9600);

  while (!Serial) {
    ; // wait for serial port to connect. Needed for native USB port only
  }

  // Initialisation de l'écran LCD
  lcd.begin(16, 2);
  lcd.print("Station Meteo");
  lcd.setCursor(0, 1);
  lcd.print("Demarrage...");

  // check for the WiFi module:

  if (WiFi.status() == WL_NO_MODULE) {

    Serial.println("Communication with WiFi module failed!");

    // don't continue

    while (true);

  }

  String fv = WiFi.firmwareVersion();

  if (fv < WIFI_FIRMWARE_LATEST_VERSION) {

    Serial.println("Please upgrade the firmware");

  }

  // attempt to connect to Wifi network:

  while (status != WL_CONNECTED) {

    Serial.print("Attempting to connect to SSID: ");

    Serial.println(ssid);

    lcd.clear();
    lcd.print("Connexion WiFi");

    // Connect to WPA/WPA2 network. Change this line if using open or WEP network:

    status = WiFi.begin(ssid, password);

    // wait 10 seconds for connection:
    delay(10000);

  }

  Serial.println("Connected to wifi");

  lcd.clear();
  lcd.print("WiFi connecte !");
  delay(1500);
}

void loop() {
  byte temperature = 0;
  byte humidity = 0;

  // Lire les données du capteur DHT11
  int err = dht11.read(&temperature, &humidity, NULL);
  if (err != SimpleDHTErrSuccess) {
    Serial.println("Erreur de lecture du capteur DHT11");
    lcd.clear();
    lcd.print("Erreur capteur");
    lcd.setCursor(0, 1);
    lcd.print("DHT11 !");
    delay(2000);
    return;
  }

  // Afficher les valeurs sur le moniteur série
  Serial.print("Température: ");
  Serial.print(temperature);
  Serial.print(" C, Humidité: ");
  Serial.print(humidity);
  Serial.println(" %");

  // Affichage sur le LCD (fonctionne meme si le serveur/PC est eteint)
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Temp: ");
  lcd.print(temperature);
  lcd.print((char)223); // symbole degre
  lcd.print("C");
  lcd.setCursor(0, 1);
  lcd.print("Humidite: ");
  lcd.print(humidity);
  lcd.print("%");

  // Connexion au serveur pour envoyer les données
  if (client.connect(server, 80)) {
    String postData = "temperature=" + String(temperature) + "&humidity=" + String(humidity);
    
    // Envoi des données en POST
    client.println("POST /station_meteo/envoi_donnees.php HTTP/1.1");
    client.println("Host: " + String(SERVER_IP));
    client.println("Content-Type: application/x-www-form-urlencoded");
    client.println("Connection: close");
    client.print("Content-Length: ");
    client.println(postData.length());
    client.println();
    client.print(postData);

    // Attendre la réponse du serveur
    while (client.available()) {
      String line = client.readStringUntil('\r');
      Serial.print(line);
    }
  } else {
    Serial.println("Erreur de connexion au serveur");
    // Le LCD affiche quand meme temp/humidite ci-dessus, indépendamment de cette erreur
  }

  client.stop(); // Fermer la connexion
  delay(1000*30); // Attendre 5 minutes avant la prochaine lecture (300 000 ms)
}
