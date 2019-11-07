
#include <TM1638.h>    // bibliothèque de rjbatista
#include <Wire.h>

// Documentation afficheur + bouton 
// http://electroniqueamateur.blogspot.com/2017/07/afficheur-8-chiffres-8-leds-8-boutons.html

// Documentation module relais : 
// http://wiki.mchobby.be/index.php?title=Module_Relais

#define DEBUG 1
//#define MASTERSIMU 1

//Slave Address for the Communication
#define SLAVE_ADDRESS 0x04

// Config : 

// Afficheur TM1638 lib : 
// DIO 3, CLK 2 , STB 4:
TM1638 afficheur(3, 2, 4);

// Relay number
byte relayNb=8;
// Relay pin number 
byte relayPin[] = {5, 6, 7, 8, 9, 10, 11, 12};
// ####### Relay 
// Etat : 
//  - 0 : off force
//  - 1 : off auto
//  - 2 : on auto
//  - 3 : on force
byte relayEtat[] = {0, 0, 0, 0, 0, 0, 0, 0};
// Mode
//  - 0 : Null
//  - 1 : Off 
//  - 2 : Auto
//  - 3 : On
byte relayMod[] = {0, 0, 0, 0, 0, 0, 0, 0};

// Police d'afficheur
const byte MES_FONTS[] = {
  0b00000000, // 0 null
  0b00001000, // 1 Down for off force
  0b01000000, // 2 Middle for on/off auto
  0b00000001 // 3 UP for on force
};


String RelayOrderCode = "RO";
bool MasterPresent = false;
bool RelayChange = true;
int HeartbeatCheckFreq=40;
int HeartbeatCheckCount=0;
int RelayChangeFreq=120;
int RelayChangeCount=0;

int changeEtat(int id) {
  switch (relayMod[id]) {
    // Null
    case 0:
      afficheur.setLED(TM1638_COLOR_NONE, id);
      digitalWrite(relayPin[id],HIGH);
      relayEtat[id]=0;
    break;
    // Off
    case 1:
      afficheur.setLED(TM1638_COLOR_NONE, id);
      digitalWrite(relayPin[id],HIGH);
      relayEtat[id]=0;
    break;
    // Auto 
    case 2:
      afficheur.setLED(TM1638_COLOR_NONE, id);
      digitalWrite(relayPin[id],HIGH);
      relayEtat[id]=1;
    break;
    // On
    case 3:
      afficheur.setLED(TM1638_COLOR_RED, id);
      digitalWrite(relayPin[id],LOW);
      relayEtat[id]=3;
    break;
  }
  #ifdef DEBUG
    Serial.println((String)"Etat pass to "+relayEtat[id]+" for "+id+" relay");
  #endif
}





int changeMod(int id, int newMod) {
  switch (newMod) {
    case 0:
      afficheur.setDisplayDigit(0, id, false, MES_FONTS);
    break;
    case 1:
      afficheur.setDisplayDigit(1, id, false, MES_FONTS);
    break;
    case 2:
      afficheur.setDisplayDigit(2, id, false, MES_FONTS);
    break;
    case 3:
      afficheur.setDisplayDigit(3, id, false, MES_FONTS);
    break;
  }
  #ifdef DEBUG
    Serial.println((String)"Mod change to "+newMod+" for "+id+" relay");
  #endif
  relayMod[id]=newMod;
}

int i2cReceiveData[50];

// callback for received data
void receiveData(int byteCount) {
  int i = 0;
  #ifdef DEBUG
    Serial.print("Donnée Reçu :");
  #endif
  while (Wire.available()) {
    Serial.print(" ");
    i2cReceiveData[i] = Wire.read();
    #ifdef DEBUG
      Serial.print(i2cReceiveData[i]);
    #endif
    i++;
  }
  #ifdef DEBUG
    Serial.println();
  #endif
  /*
  #ifdef DEBUG
    Serial.print("Conséquence : ");
    Serial.println(i2cReceiveData[0]);
  #endif
  */
  // Heartbeat
  if(i2cReceiveData[0]=='H')  {
    #ifdef DEBUG
      Serial.println("Hearbeat de nouveau reçu !");
    #endif
    HeartbeatCheckCount=0;
    // Si on récupère le master :  On remet les relay en auto off (1) s'il sont à 0
    if (MasterPresent == false) {
      for (int i = 0; i < relayNb; i++) {
        if (relayMod[i] == 0) {
            changeMod(i, 2);
        }
      }
      RelayChange = true;
      MasterPresent = true;
    }
  }
  if(i2cReceiveData[0]== 79)  { // Réception d'un O (pour Ordre)
    #ifdef DEBUG
      Serial.println("Ordre du Pi de changement d'état pour le relay " + String(i2cReceiveData[1]) + " à " + String(i2cReceiveData[2]));
    #endif
    relayEtat[i2cReceiveData[1]] = i2cReceiveData[2];
    // Passer l'état à Auto On
    if (i2cReceiveData[2] == 2) {
      afficheur.setLED(TM1638_COLOR_RED, i2cReceiveData[1]);
      digitalWrite(relayPin[i2cReceiveData[1]],LOW);
    // Passer l'état à Auto Off
    } else if (i2cReceiveData[2] == 1) {
      afficheur.setLED(TM1638_COLOR_NONE, i2cReceiveData[1]);
      digitalWrite(relayPin[i2cReceiveData[1]],HIGH);
    } else {
      Serial.println("Erreur, ordre incorrect");
    }
  }
}

// callback for sending data
void sendData() {
  //  ------------------ Data Send
  if(i2cReceiveData[0] = "D")  {
    Serial.println("Data send : ");
    // Relay etat
    for (byte i = 0; i < relayNb; i = i + 1) {
      Wire.write(relayEtat[i]);
      Serial.print(relayEtat[i]);
    }
    Serial.print(" - "); 
    Wire.write(29); // Séparrateur de groupe : https://fr.wikibooks.org/wiki/Les_ASCII_de_0_%C3%A0_127/La_table_ASCII
    // Relay mode
    for (byte i = 0; i < relayNb; i = i + 1) {
      Wire.write(relayMod[i]);
      Serial.print(relayMod[i]);
    }
     Serial.println();
  }
}

// Setup :
void setup() {
  #ifdef DEBUG
    // Mise en route du serial
    Serial.begin(9600); 
    Serial.println("Debug Actif sur le serial");
  #endif
  // Déclaration des PIN pour les relays
  for (byte i = 0; i < relayNb; i = i + 1) {
    pinMode(relayPin[i], OUTPUT);
    digitalWrite(relayPin[i],HIGH);
  }
  
  Wire.begin(SLAVE_ADDRESS);
  // define callbacks for i2c communication
  Wire.onReceive(receiveData);
  Wire.onRequest(sendData);

  // Pour le debug : 
  #ifdef DEBUG
  #ifdef MASTERSIMU
      for (int i = 0; i < relayNb; i++) {
        if (relayEtat[i] != 3 || relayEtat[i] != 0) {
            changeMod(i, 2);
        }
      }
  #endif
  #endif
  
}


int relayOrdreId;
int relayOrdreEtat;


// Loop : 
void loop() {

  // pour le debug 
  #ifdef DEBUG
  #ifdef MASTERSIMU
        MasterPresent = true;
        HeartbeatCheckCount=0;
  #endif
  #endif
  
  // Lecture / réception données
  HeartbeatCheckCount=HeartbeatCheckCount+1;
  if ( Serial.available() ) {

    String lu = Serial.readStringUntil('\n');
    
    // Ordre sur les relay
    if(lu.substring(0,2) == RelayOrderCode)  {
      if (relayNb <= 10) {
        relayOrdreId=lu.substring(3,4).toInt();
        relayOrdreEtat=lu.substring(5,6).toInt();
      } else {
        relayOrdreId=lu.substring(3,5).toInt();
        relayOrdreEtat=lu.substring(6,7).toInt();
      }
      #ifdef DEBUG
        // Exemple d'ordre : RO:2=1   (le relay 2 passe à l'état 1)
        Serial.print("Reception d'un ordre pour les relay : ");
        Serial.print(relayOrdreId);
        Serial.print(" à passer en état ");
        Serial.print(relayOrdreEtat);
      #endif
      switch (relayOrdreEtat) {
        // Auto Off
        case 1:
          afficheur.setLED(TM1638_COLOR_NONE, relayOrdreId);
          digitalWrite(relayPin[relayOrdreId],HIGH);
          relayEtat[relayOrdreId]=1;
        break;
        // Auto On
        case 2:
          afficheur.setLED(TM1638_COLOR_RED, relayOrdreId);
          digitalWrite(relayPin[relayOrdreId],LOW);
          relayEtat[relayOrdreId]=2;
        break;
      }
    }
    
    
    
    
  } 
  
  if (HeartbeatCheckCount > HeartbeatCheckFreq && MasterPresent == true) {
    #ifdef DEBUG
      Serial.println("Hearbeat non reçu, l'arduino est débranché");
    #endif
    HeartbeatCheckCount=0;
    MasterPresent = false;
    // Si on pert le master :  On éteind les relays qui était en auto
    for (int i = 0; i < relayNb; i++) {
      if (relayMod[i] == 2) {
          changeMod(i, 0);
      }
    }
    RelayChange = true;
  }

  // Bouton Action
  byte etatBoutons;
  etatBoutons = afficheur.getButtons();
  for (int i = 0; i < relayNb; i++) {
    if (bitRead(etatBoutons, i)) {
        #ifdef DEBUG
          Serial.print("Action button : ");
          Serial.println(i);
        #endif
      switch (relayMod[i]) {
        case 0:
            changeMod(i, 1);
        break;
        case 1:
          if (MasterPresent == true) {
            changeMod(i, 2);
          } else {
            changeMod(i, 3);
          }
        break;
        case 2:
          changeMod(i, 3);
        break;
        case 3:
          if (MasterPresent == true) {
            changeMod(i, 1);
          } else {
           changeMod(i, 0);
          }
        break;
      }
      RelayChange = true;
      delay(500); // Evite le rebond des bouttons
    }
    
  }

  // Changement d'etat des relay  
  RelayChangeCount=RelayChangeCount+1;
  if (RelayChangeCount > RelayChangeFreq) {
    if (RelayChange == true) {
      RelayChangeCount=0;
      #ifdef DEBUG
        Serial.println((String)"Change relay etat");
      #endif
      for (int i = 0; i < relayNb; i++) {
          changeEtat(i);
      }
      RelayChange = false;
    } else {
      RelayChangeCount=0;
    }
  }
  delay(50);
}
