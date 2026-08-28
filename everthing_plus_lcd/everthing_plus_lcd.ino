#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <HTTPClient.h>

// RFID Pins
#define SS_PIN 5
#define RST_PIN 4

// LED Pins
#define GREEN_LED 15
#define YELLOW_LED 2

// LCD Settings - Common addresses are 0x27 or 0x3F
// 16x2 LCD: LiquidCrystal_I2C lcd(0x27, 16, 2);
// 20x4 LCD: LiquidCrystal_I2C lcd(0x27, 20, 4);
LiquidCrystal_I2C lcd(0x27, 16, 2);  // Change to your LCD type

// RFID Instance
MFRC522 mfrc522(SS_PIN, RST_PIN);

// WiFi Credentials
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
const char* serverName = "http://192.168.0.102/rfid_system/verify.php";

bool lcdWorking = false;

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  Serial.println("\n\n=================================");
  Serial.println("   RFID SYSTEM INITIALIZING");
  Serial.println("=================================\n");
  
  // Initialize LEDs
  pinMode(GREEN_LED, OUTPUT);
  pinMode(YELLOW_LED, OUTPUT);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(YELLOW_LED, LOW);
  
  Serial.println("Step 1: Initializing I2C...");
  Wire.begin(21, 22); // SDA=21, SCL=22
  delay(200);
  
  // Scan I2C bus
  Serial.println("\nStep 2: Scanning I2C bus for LCD...");
  Serial.println("-------------------------------------");
  byte error, address;
  int nDevices = 0;
  
  for(address = 1; address < 127; address++) {
    Wire.beginTransmission(address);
    error = Wire.endTransmission();
    
    if (error == 0) {
      Serial.print(">>> I2C device found at 0x");
      if (address < 16) Serial.print("0");
      Serial.print(address, HEX);
      Serial.println(" <<<");
      nDevices++;
    }
  }
  
  Serial.println("-------------------------------------");
  if (nDevices == 0) {
    Serial.println("ERROR: No I2C devices found!");
    Serial.println("Check LCD connections!");
    
    for(int i=0; i<5; i++) {
      digitalWrite(YELLOW_LED, HIGH);
      delay(200);
      digitalWrite(YELLOW_LED, LOW);
      delay(200);
    }
  } else {
    Serial.print("Found ");
    Serial.print(nDevices);
    Serial.println(" I2C device(s)");
  }
  
  // Initialize LCD
  Serial.println("\nStep 3: Initializing LCD display...");
  
  lcd.begin();

  lcd.backlight();
  lcd.clear();
  
  // Test LCD
  lcd.setCursor(0, 0);
  lcd.print("RFID System");
  lcd.setCursor(0, 1);
  lcd.print("Starting...");
  
  Serial.println("LCD initialized!");
  lcdWorking = true;
  
  delay(2000);
  
  // Initialize RFID
  Serial.println("\nStep 4: Initializing RFID reader...");
  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("RFID initialized");
  
  // Connect to WiFi
  Serial.println("\nStep 5: Connecting to WiFi...");
  Serial.print("SSID: ");
  Serial.println(ssid);
  
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Connecting WiFi");
  lcd.setCursor(0, 1);
  lcd.print(ssid);
  
  WiFi.begin(ssid, password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✓ WiFi Connected!");
    Serial.print("ESP32 IP: ");
    Serial.println(WiFi.localIP());
    Serial.print("Server: ");
    Serial.println(serverName);
    
    // Green LED success
    digitalWrite(GREEN_LED, HIGH);
    delay(1000);
    digitalWrite(GREEN_LED, LOW);
    
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("WiFi Connected!");
    lcd.setCursor(0, 1);
    lcd.print(WiFi.localIP());
    delay(2000);
    
  } else {
    Serial.println("\n✗ WiFi Failed!");
    
    for(int i=0; i<3; i++) {
      digitalWrite(YELLOW_LED, HIGH);
      delay(300);
      digitalWrite(YELLOW_LED, LOW);
      delay(300);
    }
    
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("WiFi Failed!");
    lcd.setCursor(0, 1);
    lcd.print("Check Settings");
    delay(3000);
  }
  
  // Ready screen
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("   SCAN CARD   ");
  lcd.setCursor(0, 1);
  lcd.print("     READY      ");
  
  Serial.println("\n=================================");
  Serial.println("   SYSTEM READY!");
  Serial.println("=================================");
  Serial.println("LCD Status: Working");
  Serial.println("Scan your RFID card now...\n");
}

void loop() {
  // Check for RFID card
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
  
  // Display on LCD
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Card Detected!");
  lcd.setCursor(0, 1);
  lcd.print(uidString);
  delay(1000);
  
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Verifying...");
  
  // Send to server
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverName);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    String httpRequestData = "uid=" + uidString;
    int httpResponseCode = http.POST(httpRequestData);
    
    Serial.println("Response Code: " + String(httpResponseCode));
    
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Server Response: " + response);
      
      // Parse response (format: "STATUS|Name")
      int separatorIndex = response.indexOf('|');
      if (separatorIndex > 0) {
        String status = response.substring(0, separatorIndex);
        String userName = response.substring(separatorIndex + 1);
        userName.trim();
        
        if (status == "GRANTED") {
          // ACCESS GRANTED
          Serial.println("✓ ACCESS GRANTED");
          Serial.println("Welcome, " + userName + "!");
          
          digitalWrite(GREEN_LED, HIGH);
          digitalWrite(YELLOW_LED, LOW);
          
          lcd.clear();
          lcd.setCursor(0, 0);
          lcd.print("ACCESS GRANTED!");
          lcd.setCursor(0, 1);
          // Truncate name if too long for 16 char display
          if (userName.length() > 16) {
            userName = userName.substring(0, 16);
          }
          lcd.print(userName);
          
          delay(3000);
          digitalWrite(GREEN_LED, LOW);
          
        } else {
          // ACCESS DENIED
          Serial.println("✗ ACCESS DENIED");
          Serial.println("Unknown Card");
          
          digitalWrite(YELLOW_LED, HIGH);
          digitalWrite(GREEN_LED, LOW);
          
          lcd.clear();
          lcd.setCursor(0, 0);
          lcd.print("ACCESS  DENIED!");
          lcd.setCursor(0, 1);
          lcd.print("Unknown Card");
          
          delay(3000);
          digitalWrite(YELLOW_LED, LOW);
        }
      }
    } else {
      // Connection error
      Serial.println("✗ Error: " + String(httpResponseCode));
      
      for(int i=0; i<5; i++) {
        digitalWrite(YELLOW_LED, HIGH);
        delay(100);
        digitalWrite(YELLOW_LED, LOW);
        delay(100);
      }
      
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Server Error!");
      lcd.setCursor(0, 1);
      lcd.print("Code: ");
      lcd.print(httpResponseCode);
      delay(2000);
    }
    
    http.end();
  } else {
    // WiFi disconnected
    Serial.println("✗ WiFi Disconnected!");
    
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("WiFi Error!");
    lcd.setCursor(0, 1);
    lcd.print("Reconnecting...");
    
    for(int i=0; i<3; i++) {
      digitalWrite(YELLOW_LED, HIGH);
      delay(200);
      digitalWrite(YELLOW_LED, LOW);
      delay(200);
    }
    
    WiFi.begin(ssid, password);
    delay(3000);
  }
  
  Serial.println("━━━━━━━━━━━━━━━━━━━━━━━━\n");
  
  // Reset to scan screen
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("   SCAN CARD   ");
  lcd.setCursor(0, 1);
  lcd.print("     READY      ");
  
  mfrc522.PICC_HaltA();
  delay(1000);
}
