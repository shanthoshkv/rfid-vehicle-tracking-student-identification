#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>

// RFID Pins
#define SS_PIN 5
#define RST_PIN 22

// RFID Instance
MFRC522 mfrc522(SS_PIN, RST_PIN);

// WiFi Credentials - CHANGE THESE
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// Server URL - CHANGE IP TO YOUR PC'S LOCAL IP
const char* serverName = "http://192.168.0.102/rfid_system/verify.php";

void setup() {
  Serial.begin(115200);
  delay(10);
  
  // Initialize SPI and RFID
  SPI.begin();
  mfrc522.PCD_Init();
  
  Serial.println("\n=========================");
  Serial.println("RFID System Initializing");
  Serial.println("=========================");
  
  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println("\n✓ WiFi Connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
  Serial.println("=========================");
  Serial.println("Ready! Scan your card...");
  Serial.println("=========================\n");
}

void loop() {
  // Look for new cards
  if (!mfrc522.PICC_IsNewCardPresent()) {
    return;
  }
  
  if (!mfrc522.PICC_ReadCardSerial()) {
    return;
  }
  
  // Read UID
  String uidString = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) {
      uidString += "0";
    }
    uidString += String(mfrc522.uid.uidByte[i], HEX);
  }
  uidString.toUpperCase();
  
  Serial.println("━━━━━━━━━━━━━━━━━━━━━━━━");
  Serial.println("Card Detected!");
  Serial.println("UID: " + uidString);
  
  // Send to server and verify
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    http.begin(serverName);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    String httpRequestData = "uid=" + uidString;
    int httpResponseCode = http.POST(httpRequestData);
    
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Server Response: " + response);
      
      // Parse response (format: "STATUS|Name")
      int separatorIndex = response.indexOf('|');
      if (separatorIndex > 0) {
        String status = response.substring(0, separatorIndex);
        String userName = response.substring(separatorIndex + 1);
        
        if (status == "GRANTED") {
          Serial.println("✓ ACCESS GRANTED");
          Serial.println("Welcome, " + userName + "!");
        } else {
          Serial.println("✗ ACCESS DENIED");
          Serial.println("Unknown Card");
        }
      }
    } else {
      Serial.println("✗ Error: " + String(httpResponseCode));
      Serial.println("Check if XAMPP is running!");
    }
    
    http.end();
  } else {
    Serial.println("✗ WiFi Disconnected!");
  }
  
  Serial.println("━━━━━━━━━━━━━━━━━━━━━━━━\n");
  
  mfrc522.PICC_HaltA();
  delay(2000);
}
