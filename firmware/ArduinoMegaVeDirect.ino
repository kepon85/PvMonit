#include "DHT.h"    // Librairie des capteurs DHT
#include "ACS712.h" // Librairie des capteur de courant ACS712

// Pour le debug
//#define DEBUG 1

// Délais d'attente entre 2 transmission (1000 = 1s)
#define DELAYWAIT 5000 

// ---------- Serial Ve.direct parse
// Nombre de port série à parser (à connecter dans l'ordre 1, 2, 3)
#define NBSERIAL 3    // 0 (no serial port data), 1, 2 or 3
// Débit du serial 0 (diffère selon la distance de câble
    // Débit   Longueur (m)
    // 2400   60
    // 4 800   30
    // 9 600   15
    // 19 200  7,6
    // https://fr.wikipedia.org/wiki/RS-232#Limites
#define DEBITSERIAL 4800

// ---------- Capteur de courant

// Nom de la sonde DHT 1 : 
const String ACS712name = "CONSO";
// Analogique PIN 
#define ACS712_PIN A0  
// Type de sonde Supports ACS712-05B, ACS712-10A, ACS712-30A
#define ACS712_TYPE ACS712_05B

// ---------- Sonde DHT 1 : 
// Nom de la sonde DHT 1 : 
const String DHT1name = "TSol";
// Changer le pin sur lequel est branché le DHT
#define DHT1PIN 52   
//#define DHTTYPE DHT11     // DHT 11 
#define DHT1TYPE DHT22       // DHT 22  (AM2302)
//#define DHTTYPE DHT21     // DHT 21 (AM2301)

// ---------- Sonde DHT 2
// Nom de la sonde DHT 2 : 
const String DHT2name= "TExt";
// Changer le pin sur lequel est branché le DHT
#define DHT2PIN 53   
// Dé-commentez la ligne qui correspond à votre capteur 
//#define DHT2TYPE DHT11     // DHT 11 
#define DHT2TYPE DHT22       // DHT 22  (AM2302)
//#define DHT2TYPE DHT21     // DHT 21 (AM2301)

ACS712 current_sensor(ACS712_TYPE, ACS712_PIN);
DHT dht1(DHT1PIN, DHT1TYPE); 
DHT dht2(DHT2PIN, DHT2TYPE); 

void setup() {
  Serial.begin(DEBITSERIAL);
  Serial.println("Arduino PvMonit Ready");
  Serial1.begin(19200);
  Serial2.begin(19200);
  Serial3.begin(19200);
  dht1.begin();
  dht2.begin();
  current_sensor.calibrate();
}

String serialRead1;
String serialRead2;
String serialRead3;
String serialReadFull1;
String serialReadFull2;
String serialReadFull3;
int serialTour = 0;
int serialNumber = NBSERIAL;
int nbPid=0;

void loop() {
  if (serialTour == 0) {
    // cool...
    Serial.println("STOP");
    delay(DELAYWAIT);
    // Go
    dht2serial();
    current2serial();
    if (serialTour == serialNumber) {
      serialTour = 0;
    } else {
      serialTour = 1;
    }
  } else if (serialTour == 1) {
    if (Serial1.available()) {
      // get the new byte:
      char inChar = (char)Serial1.read();
      serialRead1 += inChar;
      // La chaine est une ligne : 
      if (inChar == '\n') {
        //Serial.print("S1  ");
        //Serial.print(serialRead2);
        if (nbPid == 1) {
          serialReadFull1 += "S:1_" + serialRead1;
        }
        if (serialRead1.substring(0,3) == "PID") {
          funcSerialTour();
        }
        serialRead1 = "";
      }
      if (serialTour != 1) {
        // Fin du tour pour ce port série donc on print
        //Serial.print("S1 d'un coup :  ");
        Serial.print(serialReadFull1);
        serialReadFull1="";
      }
    } 
  } else if (serialTour == 2) {
    if (Serial2.available()) {
      // get the new byte:
      char inChar = (char)Serial2.read();
      serialRead2 += inChar;
      // La chaine est une ligne : 
      if (inChar == '\n') {
        //Serial.print("S2  ");
        //Serial.print(serialRead2);
        if (nbPid == 1) {
          serialReadFull2 += "S:2_" + serialRead2;
        }
        if (serialRead2.substring(0,3) == "PID") {
          funcSerialTour();
        }
        serialRead2 = "";
      }
      if (serialTour != 2) {
        // Fin du tour pour ce port série donc on print
        //Serial.print("S2 d'un coup :  ");
        Serial.print(serialReadFull2);
        serialReadFull2="";
      }
    }
  } else if (serialTour == 3) {
    if (Serial3.available()) {
      // get the new byte:
      char inChar = (char)Serial3.read();
      serialRead3 += inChar;
      // La chaine est une ligne : 
      if (inChar == '\n') {
        //Serial.print("S3  ");
        //Serial.print(serialRead3);
        if (nbPid == 1) {
          serialReadFull3 += "S:3_" + serialRead3;
        }
        if (serialRead3.substring(0,3) == "PID") {
          funcSerialTour();
        }
        serialRead3 = "";
      }
      if (serialTour != 3) {
        // Fin du tour pour ce port série donc on print
        //Serial.print("S3 d'un coup :  ");
        Serial.print(serialReadFull3);
        serialReadFull3="";
      }
    }
  }
  
} 


int current2serial() {
  // Documentation https://github.com/muratdemirtas/ACS712-arduino-1
  float I = current_sensor.getCurrentAC();
  Serial.println("S:" + ACS712name + "_I:" + I );
}

int dht2serial() {
  // https://github.com/adafruit/DHT-sensor-library
  float h1 = dht1.readHumidity();
  float t1 = dht1.readTemperature();
  float f1 = dht1.readTemperature(true);
  if (isnan(h1) || isnan(t1) || isnan(f1)) {
    #ifdef DEBUG
      Serial.println("DEBUG Echec de lecture DHT 1");
    #endif
  }else{
    // Calcul la température ressentie. Il calcul est effectué à partir de la température en Fahrenheit
    // On fait la conversion en Celcius dans la foulée
    float hi1 = dht1.computeHeatIndex(f1, h1);
    Serial.println("S:" + DHT1name + "_H:" + h1 + ",T:" + t1 + ",TR:" + dht1.convertFtoC(hi1));
  }

  float h2 = dht2.readHumidity();
  float t2 = dht2.readTemperature();
  float f2 = dht2.readTemperature(true);
  if (isnan(h2) || isnan(t2) || isnan(f2)) {
    #ifdef DEBUG
      Serial.println("DEBUG Echec de lecture DHT 2");
    #endif
  }else{
    float hi2 = dht2.computeHeatIndex(f2, h2);
    Serial.println("S:" + DHT2name + "_H:" + h2 + ",T:" + t2 + ",TR:" + dht2.convertFtoC(hi2));
  }
}

int funcSerialTour() {
    nbPid=nbPid+1;
    #ifdef DEBUG
      Serial.print("PID trouve");
      Serial.println(nbPid);
    #endif
    if (nbPid == 2) {
      if (serialTour == serialNumber) {
        serialTour = 0;
      } else {
        serialTour = serialTour + 1;
      }
      #ifdef DEBUG
        Serial.print("DEBUG : retour au debut, suivant : Serial");
        Serial.println(serialTour);
      #endif
      nbPid=0;          
    }
}
