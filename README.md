# PvMonit

Il s'agit d'un petit projet de monitoring photovoltaique pour matériel Victron compatible Ve.direct particulièrement adapté pour les installations autonômes. Il permet une vue "en direct" et un export de l'historique vers [emoncms](https://openenergymonitor.org/emon/emoncms) (une branche d'[OpenEnergyMonitor project](http://openenergymonitor.org)).

![Screenshot PvMonit](http://david.mercereau.info/wp-content/uploads/2016/11/banPvMonit.jpeg) 
 
Exemple d'usage de PvMonit : je dispose d'un RaspberryPi, mes appareils Victron (MPTT, BMV) sont connectés avec des câbles VE.Direct. PvMonit est installé sur ce RaspberryPi et me permet : 

  - D'afficher les informations en temps réel sur une page web (local)
  - De collecter les données toutes les X minutes et les expédier vers [emoncms](https://openenergymonitor.org/emon/node/90) quand internet est là (le wifi n'étant pas toujours allumé)

![Schéma exemple utilisation PvMonit avec Raspberry](https://framapic.org/l2kmekL7AOzq/4nao9uYjviAW.png)

PvMonit support tout le matériel Victron compatible Ve Direct (via USB) : 

  - BMV : 600, 700, 702, 700H
  - BlueSolar MPPT 75/10, 70/15, 75/14, 100/15, 100/30 rev1, 100/30 rev2, 150/35 rev1, 150/35 rev2, 150/45, 75/50, 100/50 rev1, 100/50 rev2, 150/60, 150/70, 150/85, 150/100
  - SmartSolar MPPT 150/100,  250/100
  - Phoenix Inverter 12V 250VA 230V, 24V 250VA 230V, 48V 250VA 230V, 12V 375VA 230V, 24V 375VA 230V, 48V 375VA 230V, 12V 500VA 230V, 24V 500VA 230V, 48V 500VA 230V

### Requis

  * Linux (le tutoriel ci-dessous est prévu pour Debian/Rasbian/Ubuntu like)
  * PHP (5.5-5.6 recomended)
  * Lighttpd/Apache (ou autre serveur web)
  * Perl
  * Python

### Installation

PvMonit dispose de deux fonctions dissociées et indépendantes que je vais distinguer :

  * Interface en temps réel
  * Export vers emoncms

Il y a bien sûr, une base commune :

#### La base / le socle

Installation de PvMonit via le dépôt git et de ses dépendances :

```bash
aptitude install php5-cli git python-serial sudo
cd /opt
git clone https://github.com/kepon85/PvMonit.git
cp config-default.php config.php```
```

Vous pouvez maintenant éditer le fichier config.php à votre guise !

Test du script vedirect.py : brancher un appareil Victron avec un Câble Ve.Direct USB et voici un exemple de ce que vous devriez obtenir (Ici un MPTT BlueSolare branché sur le ttyUS0)

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

Pour comprendre chaque valeur, téléchargez la documentation *Victron VE Direct Protocol documentation* : https://www.victronenergy.fr/support-and-downloads/whitepapers

#### Interface web en temps réel

Installation des dépendances : 

```bash
aptitude lighttpd php5-cgi 
lighttpd-enable-mod fastcgi
lighttpd-enable-mod fastcgi-php
```

Configuration du serveur http, avec le fichier /etc/lighttpd/lighttpd.conf : 

    server .document-root        = "/opt/PvMonit/www"
    server.pid-file             = "/var/run/lighttpd.pid"
    server.username             = "www-data"
    server.groupname            = "www-data"
    server.port                 = 80
    index-file.names            = ( "index.html", "index.php")
    url.access-deny             = ( "~", ".inc" )
    include_shell "/usr/share/lighttpd/use-ipv6.pl " + server.port
    include_shell "/usr/share/lighttpd/create-mime.assign.pl"
    include_shell "/usr/share/lighttpd/include-conf-enabled.pl"

On applique la configuration :

```bash
service lighttpd restart
```

On ajoute ensuite la possibilité à l'utilisateur exécutant lighttpd de lancer les script avec sudo sans mot de passe : 

Lancer la commande :

```sh
visudo
```

Ajouter la ligne suivante : 

```diff
+ www-data ALL=(ALL) NOPASSWD: /usr/bin/perl /opt/PvMonit/bin/ampermetre.pl, /opt/temperv14/temperv14 -c, /usr/bin/python /opt/PvMonit/bin/vedirect.py /dev/tty*
```

C'est terminé, vous pouvez vous connecter sur votre IP local pour joindre votre serveur web : 

![Screenshot PvMonit](http://david.mercereau.info/wp-content/uploads/2016/11/PvMonit_Full.png)

#### Export vers emoncms

Connectez-vous à votre interface emoncms hébergée ou créez un compte sur [emoncms.org](https://emoncms.org/) et rendez-vous sur la page "Input api" https://emoncms.org/input/api :

![Screenshot input API emoncms](http://david.mercereau.info/wp-content/uploads/2016/11/Sélection_011.png)

Récupérez la valeur "Accès en écriture" et ajoutez-la dans le fichier de configuration Pvmonit  */opt/PvMonit/config.php* :

```diff
- $EMONCMS_URL_INPUT_JSON_POST='https://emoncms.chezvous.org/input/post.json';
- $EMONCMS_API_KEY='XXXXXXXXXXXXXXXXXXXXXXXX';
+ $EMONCMS_URL_INPUT_JSON_POST='https://emoncms.org/input/post.json';
+ $EMONCMS_API_KEY='????VOTRE API KEY?????';
```

Création d'un utilisateur dédié avec pouvoir restreint 

```bash
adduser --shell /bin/bash pvmonit
```

Installation des dépendances :

```bash
aptitude install lynx 
```

On ajoute ensuite la possibilité à l'utilisateur exécutant l'export de lancer les scripts avec sudo sans mot de passe : 

Lancer la commande :

```sh
visudo
```

Ajouter la ligne suivante : 

```diff
+ pvmonit ALL=(ALL) NOPASSWD: /opt/temperv14/temperv14 -c, /usr/bin/perl /opt/PvMonit/bin/ampermetre.pl, /usr/bin/python /opt/PvMonit/bin/vedirect.py /dev/tty*Ajout de celle-ci dans le fichier  */opt/PvMonit/config.php* :
```

Test de collecte :

    $ su - pvmonit -c /opt/PvMonit/getForEmoncms.php
    2016-11-02T10:55:30+01:00 - C'est un MPTT, modèle "BlueSolar MPPT 100/30 rev2" du nom de MpttBleu
    2016-11-02T10:55:30+01:00 - Les données sont formatées comme ceci : V:26180,I:800,VPV:56360,PPV:21,CS:3,ERR:0,H19:3352,H20:5,H21:51,H22:33,H23:167
    2016-11-02T10:55:31+01:00 - C'est un MPTT, modèle "BlueSolar MPPT 100/30 rev2" du nom de MpttBlanc
    2016-11-02T10:55:31+01:00 - Les données sont formatées comme ceci : V:26200,I:600,VPV:53630,PPV:18,CS:3,ERR:0,H19:1267,H20:4,H21:46,H22:17,H23:201
    2016-11-02T10:55:31+01:00 - Après correction, la température est de 11.88°C
    2016-11-02T10:55:31+01:00 - Tentative 1 de récupération de consommation
    2016-11-02T10:55:32+01:00 - Trouvé à la tentative 1 : la La consommation trouvé est 00.1A
    2016-11-02T10:55:32+01:00 - La consommation est de 00.1A soit 23W

Test d'envoi des données :

    $ su - pvmonit -c /opt/PvMonit/sendToEmoncms.php 
    2016-11-02T10:56:44+01:00 - Données correctements envoyées : 1, données en erreurs : 0

Mettre les scripts en tâche planifiée

```bash
crontab -e -u pvmonit
```
Ajouter :
```diff
+# Script de récupération des données, toutes les 5 minutes
+/5 * * * * /usr/bin/php /opt/PvMonit/getForEmoncms.php >> /tmp/PvMonit.getForEmoncms.log
+# Script d'envoi des données, ici toutes les 1/2 heures
+3,33 * * * * /usr/bin/php /opt/PvMonit/sendToEmoncms.php >> /tmp/PvMonit.sendToEmoncms.log
```

Je n'explique pas ici comment configurer emoncms, les flux pour obtenir de beaux dashboard, je vous laisse lire la documentation...

![Screenshot source config emoncms](http://david.mercereau.info/wp-content/uploads/2016/11/emoncms_source_config.png)

Voici, pour exemple, mon dashboard : http://emoncms.mercereau.info/dashboard/view?id=1

Une capture : 
![Screenshot emoncms dashboard](http://david.mercereau.info/wp-content/uploads/2016/11/emoncms-mon-dashboard-pvmonit.png)

#### Sonde température (option)

J'utilise la sonde *thermomètre USB TEMPer*, cette sonde fonctionne avec le logiciel temperv14 qui est plutôt simple à installer

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

Ajout de celle-ci dans le fichier  */opt/PvMonit/config.php* :

```diff
- $TEMPERV14_BIN='';
+ $TEMPERV14_BIN='/usr/bin/sudo /opt/temperv14/temperv14';
```

Autres documentations à propos de cette sonde :

  - http://www.generation-linux.fr/index.php?post/2014/06/21/Relever-et-grapher-la-temp%C3%A9rature-de-sa-maison-sur-Debian
  - http://dev-random.net/temperature-measuring-using-linux-and-raspberry-pi/

#### Pince ampèremétrique (option)

J'utilise la pince ampèremétrique USB Aviosys 8870 pour mesurer ma consommation électrique. 

Le petit script perl (/opt/PvMonit/bin/ampermetre.pl) est très simple pour lire la pince ampèremétrique qui sera branchée en USB et apparaîtra dans votre système sur le port /dev/ttyACM0

Celui-ci dépend de la librairie serialport : 

```bash
aptitde install libdevice-serialport-perl
```

Test : :
```bash
$ /opt/PvMonit/bin/ampermetre.pl 
00.1A
```

Ajout de celle-ci dans le fichier  */opt/PvMonit/config.php* :

```diff
- $AMPEREMETRE_BIN = '';
+ $AMPEREMETRE_BIN = '/usr/bin/sudo /usr/bin/perl /opt/PvMonit/bin/ampermetre.pl';
```

#### Ecran LED

J'utilise un petit afficheur USB de chez [Dream Cheeky](http://www.dreamcheeky.com/index.php?pagename=product&pid=52)

  - Documentation : http://www.last-outpost.com/~malakai/dcled/

```bash
aptitude install libusbhid-common
cd /opt
wget http://www.last-outpost.com/~malakai/dcled/dcled-2.2.tgz
tar -xzvf dcled-2.2.tgz
mv dcled-2.2 dcled
cd dcled
make
# test : 
./dcled --clock
```

Ajout de celle-ci dans le fichier  */opt/PvMonit/config.php* :

```diff
-$LED_BIN='';
+$LED_BIN='/opt/dcled/dcled';
```

Lancement du script 

```bash
/opt/PvMonit/getToLed.php &
```

### Todos

 - sendToEmonCMS : collect data error, retry 3x (ajouter une valeur à la fin du bash "type export retry=1" ;
 - shell en couleur avec des jauges et tout ;
 - Traduction en anglais (tu veux le faire ?) ;

### Documentation

  - Victron VE Direct Protocol documentation : https://www.victronenergy.fr/support-and-downloads/whitepapers

### Auteur

  - David Mercereau [david #arobase# mercereau #point# info](http://david.mercereau.info/contact/)

### License BEERWARE

Tant que vous conservez cet avertissement, vous pouvez faire ce que vous voulez de ce truc. Si on se rencontre un jour et que vous pensez que ce truc vaut le coup, vous pouvez me payer une bière en retour. 

> Written with [StackEdit](https://stackedit.io/).




