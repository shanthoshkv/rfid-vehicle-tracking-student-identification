#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <WiFi.h>
#include <HTTPClient.h>

// RFID Pins
#define SS_PIN 5
#define RST_PIN 4

// LED Pins
#define GREEN_LED 15
#define YELLOW_LED 2

// OLED Display Settings
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64  // Change to 32 if you have smaller OLED
#define OLED_RESET -1
#define SCREEN_ADDRESS 0x3C

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// RFID Instance
MFRC522 mfrc522(SS_PIN, RST_PIN);

// WiFi Credentials
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
const char* serverName = "http://192.168.0.102/rfid_system/verify.php";

bool oledWorking = false;

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  Serial.println("\n\n=================================");
  Serial.println("   RFID SYSTEM INITIALIZING");
  Serial.println("=================================\n");
  
  pinMode(GREEN_LED, OUTPUT);
  pinMode(YELLOW_LED, OUTPUT);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(YELLOW_LED, LOW);
  
  Serial.println("Step 1: Initializing I2C...");
  Wire.begin(21, 22);
  delay(200);
  
  Serial.println("\nStep 2: Scanning I2C bus...");
  byte address;
  int nDevices = 0;
  for(address = 1; address < 127; address++) {
    Wire.beginTransmission(address);
    byte error = Wire.endTransmission();
    if (error == 0) {
      Serial.print(">>> I2C device found at 0x");
      if (address < 16) Serial.print("0");
      Serial.println(address, HEX);
      nDevices++;
    }
  }
  
  if (nDevices == 0) {
    Serial.println("No I2C devices found!");
  }
  
  Serial.println("\nStep 3: Initializing OLED...");
  
  // Try to initialize OLED
  if(!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println("OLED allocation failed!");
    oledWorking = false;
    
    for(int i=0; i<5; i++) {
      digitalWrite(YELLOW_LED, HIGH);
      delay(200);
      digitalWrite(YELLOW_LED, LOW);
      delay(200);
    }
  } else {
    Serial.println("OLED initialized successfully!");
    oledWorking = true;
    
    // Configure and test display
    display.setRotation(0);
    display.setContrast(255);
    display.clearDisplay();
    
    // Test pattern
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(0, 0);
    display.println("TEST");
    display.println("I2C: 0x3C");
    display.println("Size: 128x64");
    display.display();
    
    Serial.println("Display test shown!");
    delay(2000);
    
    // Startup screen
    display.clearDisplay();
    display.setTextSize(2);
    display.setCursor(10, 10);
    display.println(F("RFID"));
    display.setCursor(10, 35);
    display.println(F("SYSTEM"));
    display.display();
    delay(1000);
  }
  
  Serial.println("\nStep 4: Initializing RFID...");
  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("RFID initialized");
  
  Serial.println("\nStep 5: Connecting to WiFi...");
  WiFi.begin(ssid, password);
  
  if (oledWorking) {
    display.clearDisplay();
    display.setTextSize(1);
    display.setCursor(0, 0);
    display.println(F("Connecting WiFi"));
    display.display();
  }
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✓ WiFi Connected!");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());
    
    digitalWrite(GREEN_LED, HIGH);
    delay(1000);
    digitalWrite(GREEN_LED, LOW);
    
    if (oledWorking) {
      display.clearDisplay();
      display.setTextSize(1);
      display.setCursor(0, 0);
      display.println(F("WiFi OK!"));
      display.setCursor(0, 15);
      display.println(WiFi.localIP());
      display.setTextSize(2);
      display.setCursor(0, 35);
      display.println(F("READY"));
      display.display();
      delay(2000);
    }
  }
  
  displayScanPrompt();
  
  Serial.println("\n=================================");
  Serial.println("OLED: " + String(oledWorking ? "Working" : "Not Working"));
  Serial.println("Ready! Scan card...");
  Serial.println("=================================\n");
}

void loop() {
  if (!mfrc522.PICC_IsNewCardPresent()) return;
  if (!mfrc522.PICC_ReadCardSerial()) return;
  
  String uidString = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) uidString += "0";
    uidString += String(mfrc522.uid.uidByte[i], HEX);
  }
  uidString.toUpperCase();
  
  Serial.println("━━━━━━━━━━━━━━━━━━━━━━━━");
  Serial.println("Card: " + uidString);
  
  if (oledWorking) {
    display.clearDisplay();
    display.setTextSize(1);
    display.setCursor(0, 0);
    display.println(F("Card Detected!"));
    display.setCursor(0, 15);
    display.println(uidString);
    display.setCursor(0, 35);
    display.println(F("Verifying..."));
    display.display();
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverName);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    int code = http.POST("uid=" + uidString);
    
    if (code > 0) {
      String response = http.getString();
      int sep = response.indexOf('|');
      
      if (sep > 0) {
        String status = response.substring(0, sep);
        String name = response.substring(sep + 1);
        name.trim();
        
        if (status == "GRANTED") {
          Serial.println("✓ GRANTED: " + name);
          
          digitalWrite(GREEN_LED, HIGH);
          digitalWrite(YELLOW_LED, LOW);
          
          if (oledWorking) {
            display.clearDisplay();
            display.setTextSize(2);
            display.setCursor(5, 5);
            display.println(F("ACCESS"));
            display.println(F("GRANTED"));
            display.setTextSize(1);
            display.setCursor(0, 45);
            if (name.length() > 16) name = name.substring(0, 16);
            display.println(name);
            display.display();
          }
          
          delay(3000);
          digitalWrite(GREEN_LED, LOW);
          
        } else {
          Serial.println("✗ DENIED");
          
          digitalWrite(YELLOW_LED, HIGH);
          digitalWrite(GREEN_LED, LOW);
          
          if (oledWorking) {
            display.clearDisplay();
            display.setTextSize(2);
            display.setCursor(5, 10);
            display.println(F("ACCESS"));
            display.println(F("DENIED"));
            display.display();
          }
          
          delay(3000);
          digitalWrite(YELLOW_LED, LOW);
        }
      }
    }
    http.end();
  }
  
  Serial.println("━━━━━━━━━━━━━━━━━━━━━━━━\n");
  displayScanPrompt();
  mfrc522.PICC_HaltA();
  delay(1000);
}

void displayScanPrompt() {
  if (oledWorking) {
    display.clearDisplay();
    display.setTextSize(2);
    display.setCursor(10, 15);
    display.println(F("SCAN"));
    display.setCursor(10, 40);
    display.println(F("CARD"));
    display.display();
  }
}
