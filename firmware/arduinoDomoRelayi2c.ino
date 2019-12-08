
#include <TM1638.h>    // bibliothèque de rjbatista
#include <Wire.h>

// Documentation afficheur + bouton 
// http://electroniqueamateur.blogspot.com/2017/07/afficheur-8-chiffres-8-leds-8-boutons.html

// Documentation module relais : 
// http://wiki.mchobby.be/index.php?title=Module_Relais

#define DEBUG 1
#define VEROSE 1
//#define MASTERSIMU 1

//Slave Address for the Communication
#define SLAVE_ADDRESS 0x04

// Config : 

// Afficheur TM1638 lib : 
// DIO 3, CLK 2 , STB 4:
TM1638 afficheur(3, 2, 4);

// Relay number
byte relayNb=8;    // Coherence avec le config.yaml
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
bool RelayChange[] = {false, false, false, false, false, false, false, false, };

// Police d'afficheur
const byte MES_FONTS[] = {
  0b01000000, // 0 null
  0b00001000, // 1 Down for off force
  0b00000000, // 2 Middle for on/off auto
  0b00000001 // 3 UP for on force
};
/* Plus pertinant mais l'afficheur fait du bruit alors on limite l'affichage 
const byte MES_FONTS[] = {
  0b00000000, // 0 null
  0b00001000, // 1 Down for off force
  0b01000000, // 2 Middle for on/off auto
  0b00000001 // 3 UP for on force
};
*/


String RelayOrderCode = "RO";
bool MasterPresent = false;

int HeartbeatCheckFreq=500;
int HeartbeatCheckCount=0;
int RelayChangeFreq=60;
int RelayChangeCount=0;
int AfficheurRefreshFreq=500;
int AfficheurRefreshCount=0;

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
  #ifdef VEROSE
    Serial.println((String)"Etat pass to "+relayEtat[id]+" for "+id+" relay");
  #endif
}

int afficheurRefresh() {
  #ifdef DEBUG
    Serial.println((String)"Rafraichissement de l'afficheur");
  #endif
  for (int id = 0; id < relayNb; id++) {
      switch (relayEtat[id]) {
        // off force
        case 0:
          afficheur.setLED(TM1638_COLOR_NONE, id);
        break;
        // off auto
        case 1:
          afficheur.setLED(TM1638_COLOR_NONE, id);
        break;
        // on auto 
        case 2:
          afficheur.setLED(TM1638_COLOR_RED, id);
        break;
        //  on force
        case 3:
          afficheur.setLED(TM1638_COLOR_RED, id);
        break;
      }
      switch (relayMod[id]) {
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
  }
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
  #ifdef VEROSE
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
    #ifdef DEBUG
      Serial.print(" ");
    #endif
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
      Serial.println("Hearbeat Reçu !");
    #endif
    HeartbeatCheckCount=0;
    // Si on récupère le master :  On remet les relay en auto off (1) s'il sont à 0
    if (MasterPresent == false) {
      for (int i = 0; i < relayNb; i++) {
        if (relayMod[i] == 0) {
           changeMod(i, 2);
           RelayChange[i] = true;
        }
      }
      MasterPresent = true;
    }
  }
  if(i2cReceiveData[0]== 79)  { // Réception d'un O (pour Ordre)
    #ifdef VEROSE
      Serial.println("Ordre du Pi de changement d'état pour le relay " + String(i2cReceiveData[1]) + " à " + String(i2cReceiveData[2]));
    #endif
    // Vérification des données
    if (i2cReceiveData[1] >= 0 && i2cReceiveData[1] <= relayNb && (i2cReceiveData[2] == 1 || i2cReceiveData[2] == 2)) {
      relayEtat[int(i2cReceiveData[1])] = i2cReceiveData[2];
      // Passer l'état à Auto On
      if (i2cReceiveData[2] == 2) {
        afficheur.setLED(TM1638_COLOR_RED, i2cReceiveData[1]);
        digitalWrite(relayPin[i2cReceiveData[1]],LOW);
      // Passer l'état à Auto Off
      } else if (i2cReceiveData[2] == 1) {
        afficheur.setLED(TM1638_COLOR_NONE, i2cReceiveData[1]);
        digitalWrite(relayPin[i2cReceiveData[1]],HIGH);
      } else {
        #ifdef VEROSE
          Serial.println("Erreur, ordre incorrect 1");
        #endif
      }
    } else {
      #ifdef VEROSE
        Serial.println("Erreur, ordre incorrect 2");
      #endif
    }
  }
}

// callback for sending data
void sendData() {
  //  ------------------ Data Send
  if(i2cReceiveData[0] = "D")  {
    #ifdef DEBUG
      Serial.println("Data send : ");
    #endif
    // Relay etat
    for (byte i = 0; i < relayNb; i = i + 1) {
      Wire.write(relayEtat[i]);
      #ifdef DEBUG
        Serial.print(relayEtat[i]);
      #endif
    }
    #ifdef DEBUG
      Serial.print(" - "); 
    #endif
    Wire.write(29); // Séparrateur de groupe : https://fr.wikibooks.org/wiki/Les_ASCII_de_0_%C3%A0_127/La_table_ASCII
    // Relay mode
    for (byte i = 0; i < relayNb; i = i + 1) {
      Wire.write(relayMod[i]);
      #ifdef DEBUG
        Serial.print(relayMod[i]);
      #endif
    }
    #ifdef DEBUG
      Serial.println();
    #endif
  }
}

// Setup :
void setup() {
  #ifdef DEBUG
    // Mise en route du serial
    Serial.begin(9600); 
    Serial.println("Debug Actif sur le serial");
  #elif VEROSE
    // Mise en route du serial
    Serial.begin(9600); 
    Serial.println("Verbose Actif sur le serial");
  #endif
  // Déclaration des PIN pour les relays
  for (byte i = 0; i < relayNb; i = i + 1) {
    pinMode(relayPin[i], OUTPUT);
    digitalWrite(relayPin[i],HIGH);
  }
  // Mise à 0 de l'afficheur
  afficheurRefresh();
  
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

  AfficheurRefreshCount=AfficheurRefreshCount+1; 
  if (AfficheurRefreshCount > AfficheurRefreshFreq) {
    afficheurRefresh();
    AfficheurRefreshCount=0;
  }
  
  
  HeartbeatCheckCount=HeartbeatCheckCount+1; 
  if (HeartbeatCheckCount > HeartbeatCheckFreq && MasterPresent == true) {
    #ifdef VEROSE
      Serial.println("Hearbeat non reçu, l'arduino est débranché");
    #endif
    HeartbeatCheckCount=0;
    MasterPresent = false;
    // Si on pert le master :  On éteind les relays qui était en auto
    for (int i = 0; i < relayNb; i++) {
      if (relayMod[i] == 2) {
          changeMod(i, 0);
          RelayChange[i] = true;
      }
    }
  }

  // Bouton Action
  byte etatBoutons;
  etatBoutons = afficheur.getButtons();
  // Anti bug 
  int nbActionButton = 0;
  byte relayModOld[] {9,9,9,9,9,9,9,9};
  // On sauvegarde le mod des relay
  for (int i = 0; i < relayNb; i++) {
    relayModOld[i]=relayMod[i];
  }
  for (int i = 0; i < relayNb; i++) {
    if (bitRead(etatBoutons, i)) {
        nbActionButton = 1 + nbActionButton;
        #ifdef VEROSE
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
      
      RelayChange[i] = true;
      
      delay(500); // Evite le rebond des bouttons
      RelayChangeCount=0;
    }
    
  }
  // Eviter bug bagotage ?
  // S'il y a trop d'action, on annule tout 
  if (nbActionButton >= relayNb) {
    #ifdef VEROSE
      Serial.println("BUG, TOUT LES BOUTTONS ONT ETE POUSSE, on annule tout");
    #endif
    for (int i = 0; i < relayNb; i++) {
      // Retour aux ancien mode
      RelayChange[i] = false;
      changeMod(i, relayModOld[i]);
    }
    #ifdef VEROSE
      Serial.println("BUG, TOUT LES BOUTTONS ONT ETE POUSSE, fin");
    #endif
  }   

  // Changement d'etat des relay  
  RelayChangeCount=RelayChangeCount+1;
  if (RelayChangeCount > RelayChangeFreq) {
    for (int i = 0; i < relayNb; i++) {
      if (RelayChange[i] == true) {
        changeEtat(i);
        RelayChange[i]=false;
      }
    }
    RelayChangeCount=0;
  }
  delay(50);
}
