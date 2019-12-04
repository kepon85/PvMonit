
# Installation
Deux type d'installation possible : 
-   Une version Raspbery PI 3B, si vous avez un point wifi actif (même occasionnellement) et que votre matériel solaire est à porté de wifi. C'est une solution plutôt simple (si on touche un peu sous linux).
![Schéma de câblage PI3B et ve.direct USB officiel](https://david.mercereau.info/wp-content/uploads/2019/10/PvMonitV1_USB.png)-   Une version Raspbery Pi 0 + Arduino : plus complexe à mettre en oeuvre (il faut savoir souder et avoir plus de connaissance) mais beaucoup plus souple et moins chère. Particulièrement adapté si votre installation réseau est loin (max 60m) de votre maison
![Schéma de câblage avec Pi0 et Arduino Mega (ve.direct Diy)](https://david.mercereau.info/wp-content/uploads/2019/10/PvMonitV1_Arduino.png)

PvMonit support tout le matériel Victron compatible Ve Direct (via USB) : 

Les fonctionnalités de PvMonit sont dissociable : 
  * Interface web en temps réel
  * Export vers emoncms
  * Affichage LCD

#### La base / le socle

Installation de PvMonit via le dépôt git et de ses dépendances :
```bash
apt-get install php-cli php-yaml git python-serial sudo screen
cd /opt
git clone https://github.com/kepon85/PvMonit.git
cd PvMonit
cp config-default.yaml config.yaml
```
Vous pouvez maintenant éditer le fichier config.yaml à votre guise !

### Ve.direct via USB

Dans le fichier config.yaml mentionner : 
```yaml
vedirect:
    by: usb 
```
Test du script vedirect.py : brancher un appareil Victron avec un Câble Ve.Direct USB et voici un exemple de ce que vous devriez obtenir (Ici un MPTT BlueSolare branché sur le ttyUS0)

```
$ /opt/PvMonit/bin/vedirect.py /dev/ttyUSB0 
PID:0xA04A
FW:119
SER#:HQ********
V:25660
I:500
VPV:53270
PPV:14
CS:3
ERR:0
LOAD:ON
H19:3348
H20:1
H21:17
H22:33
H23:167
HSDS:52
```
Pour comprendre chaque valeur, téléchargez la documentation *Victron VE Direct Protocol documentation* : https://www.victronenergy.fr/support-and-downloads/whitepapers ([disponible aussi à cet url](https://david.mercereau.info/wp-content/uploads/2019/10/VE.Direct-Protocol.pdf))

Lancer la commande :

```sh
visudo
```

Si vous utilisez l'interface web pvmonit, ajouter :

```diff
+ www-data ALL=(ALL) NOPASSWD:/usr/bin/python /opt/PvMonit/bin/vedirect.py *
```

Si vous utilisez l'export vers emoncms, ajouter : 

```diff
+ pvmonit ALL=(ALL) NOPASSWD:/usr/bin/python /opt/PvMonit/bin/vedirect.py *
```

### Ve.direct via Arduino

Avec l'Arduino IDE, uploader le firmware "ArduinoMegaVeDirect.ino" contenu dans le dossier "firmware" 

Faites vos câble ve.direct avec les connecteur JST-PH. De la documentation à ce sujet : 
 - https://beta.ivc.no/wiki/index.php/Victron_VE_Direct_DIY_Cable
 - http://www.svpartyoffive.com/2018/02/28/victron-monitors-technical/
 - https://store.volts.ca/media/attachment/file/v/e/ve.direct-protocol-3.23.pdf
 - http://jeperez.com/connect-bmv-victron-computer/
 - http://www.mjlorton.com/forum/index.php?topic=238.0
 - BMV e 3,3V : https://github.com/winginitau/VictronVEDirectArduino
 - https://www.victronenergy.com/live/vedirect_protocol:faq#q4is_the_vedirect_interface_33_or_5v

Conseil : utiliser des connecteur MOLEX (pratique pour que les câbles soit dé-connectable) : https://arduino103.blogspot.com/2013/07/connecteur-molex-comment-utiliser-le-kit.html

Connecté l'arduino en série (utiliser 3 fils d'un câble téléphonie/RJ45) avec le raspbery pi comme sur le schéma ci-après : 
![Schéma de câble Pi0-ArduinoMega](https://david.mercereau.info/wp-content/uploads/2019/10/PvMonit-1.0_bb.png)
Sur le Raspbery pi, il faut que le port série soit actif

```bash
raspi-config
    # Interfacing Option / P6 Serial / 
    # Login shell : NO
    # Serial port harware enable : Yes
reboot
```

Pour être sûr que cela fonctionne vous pouvez lancer la commande  suivante sur le pi : 
```bash
screen /dev/ttyAMA0 4800
```
Vous devriez obtenir quelque chose comme : 
```
S:3_P   -95
S:3_CE  -39101
S:3_SOC 889
S:3_TTG 1597
S:3_Alarm       OFF
S:3_Relay       OFF
S:3_AR  0
S:3_BMV 700
S:3_FW  0308
S:3_Checksum    8
S:3_H1  -102738
S:3_H2  -45215
S:3_H3  -102738
S:3_H4  1
S:3_H5  0
S:3_H6  -21450007
S:3_H7  21238
S:3_H8  29442
S:3_H9  362593
S:3_H10 103
S:3_H11 0
S:3_H12 0
S:3_H17 53250
S:3_H18 62805
S:3_Checksum    �
S:3_PID 0x203
STOP
```

Dans le fichier config.yaml mentionner : 

```yaml
vedirect:
    by: arduino 
```

```bash
apt-get install python3 python3-pip python3-yaml
pip3 install pyserial
```

Ajouter dans le fichier /etc/rc.local :(avant le exit 0) 
```bash
screen -A -m -d -S arduino /opt/PvMonit/bin/getSerialArduino-launch.sh
```

Vous pouvez le lancer "à la main" avec la commande :
```bash
python3 /opt/PvMonit/bin/getSerialArduino.py
```
Et vous assurez que le fichier /tmp/PvMonit_getSerialArduino.data.yaml existe bien et que les données sont justes. 


#### Interface web en temps réel

Installation des dépendances : 

```bash
aptitude install lighttpd php-cgi  php-xml php7.3-json
lighttpd-enable-mod fastcgi
lighttpd-enable-mod fastcgi-php
```

Configuration du serveur http, avec le fichier /etc/lighttpd/lighttpd.conf : 
```diff
- server.document-root        = "/var/www/html/"
+ server.document-root        = "/opt/PvMonit/www/"
```

On applique la configuration :

```bash
service lighttpd restart
```

C'est terminé, vous pouvez vous connecter sur votre IP local pour joindre votre serveur web : 

Attention : dans la configuration l'appel du fichier data (urlDataXml) doit contenir un nom de domaine, quand vous joingné l'interface ce nom de domaine doit être identique à celui. Exemple vous ateignez l'interface par pv.chezmoi.fr, dans urlDataXml il doit y avoir urlDataXml: http://pv.chezmoi.fr/data-xml.php (modifier le fichier /etc/hosts au besoin...)

![Screenshot PvMonit](http://david.mercereau.info/wp-content/uploads/2016/11/PvMonit_Full.png)

#### Export vers emoncms

Connectez-vous à votre interface emoncms hébergée ou créez un compte sur [emoncms.org](https://emoncms.org/) et rendez-vous sur la page "Input api" https://emoncms.org/input/api :

![Screenshot input API emoncms](http://david.mercereau.info/wp-content/uploads/2016/11/Sélection_011.png)
Récupérez la valeur "Accès en écriture" et ajoutez-la dans le fichier de configuration Pvmonit  */opt/PvMonit/config.yaml* :

```yaml
emoncms:
    urlInputJsonPost: https://emoncms.org/input/post.json
    apiKey: XXXXXXXXXXXXXXXXXXXXXXXX
```

Création d'un utilisateur dédié avec pouvoir restreint 

```bash
adduser --shell /bin/bash pvmonit
```

Installation des dépendances :

```bash
aptitude install lynx 
```

Test de collecte :

```
$ su - pvmonit -c /opt/PvMonit/getForEmoncms.php
2016-11-02T10:55:30+01:00 - C'est un MPTT, modèle "BlueSolar MPPT 100/30 rev2" du nom de MpttBleu
2016-11-02T10:55:30+01:00 - Les données sont formatées comme ceci : V:26180,I:800,VPV:56360,PPV:21,CS:3,ERR:0,H19:3352,H20:5,H21:51,H22:33,H23:167
2016-11-02T10:55:31+01:00 - C'est un MPTT, modèle "BlueSolar MPPT 100/30 rev2" du nom de MpttBlanc
2016-11-02T10:55:31+01:00 - Les données sont formatées comme ceci : V:26200,I:600,VPV:53630,PPV:18,CS:3,ERR:0,H19:1267,H20:4,H21:46,H22:17,H23:201
2016-11-02T10:55:31+01:00 - Après correction, la température est de 11.88°C
2016-11-02T10:55:31+01:00 - Tentative 1 de récupération de consommation
2016-11-02T10:55:32+01:00 - Trouvé à la tentative 1 : la La consommation trouvé est 00.1A
2016-11-02T10:55:32+01:00 - La consommation est de 00.1A soit 23W
```

Test d'envoi des données :
```
$ su - pvmonit -c /opt/PvMonit/sendToEmoncms.php 
    2016-11-02T10:56:44+01:00 - Données correctements envoyées : 1, données en erreurs : 0
```

Mettre les scripts en tâche planifiée

```bash
crontab -e -u pvmonit
```

Ajouter :
```diff
+# Script de récupération des données, toutes les 5 minutes
+*/5 * * * * /usr/bin/php /opt/PvMonit/getForEmoncms.php >> /tmp/PvMonit.getForEmoncms.log
+# Script d'envoi des données, ici toutes les 1/2 heures
+3,33 * * * * /usr/bin/php /opt/PvMonit/sendToEmoncms.php >> /tmp/PvMonit.sendToEmoncms.log
```

Je n'explique pas ici comment configurer emoncms, les flux pour obtenir de beaux dashboard, je vous laisse lire la documentation...

![Screenshot source config emoncms](http://david.mercereau.info/wp-content/uploads/2016/11/emoncms_source_config.png)

Voici, pour exemple, mon dashboard : http://emoncms.mercereau.info/dashboard/view?id=1

Une capture : 
![Screenshot emoncms dashboard](http://david.mercereau.info/wp-content/uploads/2016/11/emoncms-mon-dashboard-pvmonit.png)

#### Sonde température/humidité (DHT) sur GPIO (sur raspberi pi)

Installation des dépendances : 
```
pip3 install Adafruit_DHT
```

Lancer la commande :

```sh
visudo
```

Si vous utilisez l'interface web pvmonit, ajouter :

```diff
+ www-data ALL=(ALL) NOPASSWD:/usr/bin/python3 /opt/PvMonit/bin/DHT.py *
```

Si vous utilisez l'export vers emoncms, ajouter : 

```diff
+ pvmonit ALL=(ALL) NOPASSWD:  /usr/bin/python3 /opt/PvMonit/bin/DHT.py *
```
Puis activer la sonde : 
```bash
ln -s /opt/PvMonit/bin-available/DhtGpio.php /opt/PvMonit/bin-enabled/OTHER-THome.php 
```

#### Sonde température/humidité (DHT) récupéré sur l'arduino

/!\ Uniquement si vous avez un Arduino pour récolter les donnée

```bash
ln -s /opt/PvMonit/bin-available/TempHumByArduino.php /opt/PvMonit/bin-enabled/OTHER-TSol.php 
```

#### Sonde de courant (type ACS712) récupéré sur l'arduino

/!\ Uniquement si vous avez un Arduino pour récolter les donnée

```bash
ln -s /opt/PvMonit/bin-available/CurrentByArduino.php/opt/PvMonit/bin-enabled/OTHER-CONSO.php 
```
#### Sonde température USB (option)

La sonde *thermomètre USB TEMPer*, cette sonde fonctionne avec le logiciel temperv14 qui est plutôt simple à installer

```bash
apt-get install libusb-dev libusb-1.0-0-dev unzip
cd /opt
wget http://dev-random.net/wp-content/uploads/2013/08/temperv14.zip
#ou un miroir
#wget http://www.generation-linux.fr/public/juin14/temperv14.zip
unzip temperv14.zip
cd temperv14/
make
```

Test de la sonde : 

```bash
$ /opt/temperv14/temperv14 -c
18.50
```

On ajoute ensuite la possibilité à des utilisateurs "restrint" d'exécutant de lancer les script avec sudo sans mot de passe : 

Lancer la commande :

```sh
visudo
```

Si vous utilisez l'interface web pvmonit, ajouter :

```diff
+ www-data ALL=(ALL) NOPASSWD:  /opt/temperv14/temperv14 -c
```

Si vous utilisez l'export vers emoncms, ajouter : 

```diff
+ pvmonit ALL=(ALL) NOPASSWD:  /opt/temperv14/temperv14 -c
```

Activer le script (et l'éditer au besoin)

```bash
ln -s /opt/PvMonit/bin-available/TemperatureUSB.php /opt/PvMonit/bin-enabled/other-TEMP.php
```

Autres documentations à propos de cette sonde :

  - http://www.generation-linux.fr/index.php?post/2014/06/21/Relever-et-grapher-la-temp%C3%A9rature-de-sa-maison-sur-Debian
  - http://dev-random.net/temperature-measuring-using-linux-and-raspberry-pi/

#### Pince ampèremétrique USB  (option)

/!\ Uniquement si vous n'avez pas d'Arduino

J'utilise la pince ampèremétrique USB Aviosys 8870 pour mesurer ma consommation électrique. 

Le petit script perl (/opt/PvMonit/bin/ampermetre.pl) est très simple pour lire la pince ampèremétrique qui sera branchée en USB et apparaîtra dans votre système sur le port /dev/ttyACM0

Celui-ci dépend de la librairie serialport : 

```bash
aptitde install libdevice-serialport-perl
```

Test : :
```bash
$ /opt/PvMonit/bin-available/ampermetre.pl 
00.1A
```

Si vous utilisez l'interface web pvmonit, ajouter :

```diff
+ www-data ALL=(ALL) NOPASSWD: /opt/PvMonit/bin/*
```

Si vous utilisez l'export vers emoncms, ajouter : 

```diff
+ pvmonit ALL=(ALL) NOPASSWD:  /opt/PvMonit/bin/*
```

Activer le script (et l'éditer au besoin)

```bash
ln -s /opt/PvMonit/bin-available/AmpermetreUSB.php /opt/PvMonit/bin-enabled/other-CONSO.php
```

#### Co² Meter 

Il s'agit le ht2000 co² meter. Je ne l'utilise que pour le co² ayant des sondes ailleur mais il peut aussi donner l'humidité et la température. Si vous voulez aussi ces informations vous pouvez regarder de ce côté : https://github.com/tomvanbraeckel/slab_ht2000

Pour ma part (uniquement pour le co²) il faut compile le script : 

```bash
cd /opt/PvMonit/bin
gcc ht2000.c -o ht2000
```

Tester : 

```bash
./ht2000 /dev/hidraw0
```

Doit retourner une valeur numérique

Ensuite (vu qu'il faut le lancer en root) vous devez le mettre dans le sudo : 

Si vous utilisez l'interface web pvmonit, ajouter :

```diff
+ www-data ALL=(ALL) NOPASSWD: /opt/PvMonit/bin/ht2000 *
```

Si vous utilisez l'export vers emoncms, ajouter : 

```diff
+ pvmonit ALL=(ALL) NOPASSWD:  /opt/PvMonit/bin/ht2000 *
```

#### Raspberry Adafruit LCD RGB - 16x2 + Keypad (option)

Uniquement pour les Raspbery Pi

![Ecran Adafruit LCD](https://david.mercereau.info/wp-content/uploads/2019/10/P1030685-e1571260899411.jpg)
Permet d'afficher les informations principales sur le raspbery pi (Etat des batteries, puissance en cours...)

```bash
raspi-config
    # Interfacing Option / P6 Serial / 
    # Login shell : NO
    # Serial port harware enable : Yes
reboot
aptitude install i2c-tools
i2cdetect 1
```

La dernière commande (i2cdetect 1) doit afficher quelque chose comme : 

```     0  1  2  3  4  5  6  7  8  9  a  b  c  d  e  f
00:          *-- -- -- -- -- -- -- -- -- -- -- -- -- 
10: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
20: 20 -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
30: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
40: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
50: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
60: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
70: -- -- -- -- -- -- -- --   
```

Pour tester le LCD lancer la commande : 

```bash
pip3 install adafruit-circuitpython-charlcd lxml
python3 /opt/PvMonit/lcd/lcd.py
```

Pour que le LCD fonctionne au démarrage, ajouter avant "exit 0" dans le fichier /etc/rc.local la ligne suivant
```bash
screen -A -m -d -S lcd /opt/PvMonit/lcd/lcd-launch.sh
```
