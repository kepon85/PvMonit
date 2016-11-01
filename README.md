# PvMonit

Il s'agit d'un petit projet de monitoring photovoltaique pour matériel Victron. Il permet une vue "en direct" et un export de l'historique vers [emoncms](https://openenergymonitor.org/emon/emoncms) (une branche d'[OpenEnergyMonitor project](http://openenergymonitor.org)).
 
Mon usage de PvMonit : je dipose d'un RaspberryPi connecté avec des câbles VE.Direct sur mes appareil Victron (MPTT, BMV). PvMonit est installé sur ce RaspberryPi et me permet : 
    - D'afficher les informations en temps réel sur une page web.
    - De collecter les données toutes les 5 minutes, les mettres en caches et les expédier vers emoncms quand internet est là (le wifi n'étant allumé qu'au besoin)

PvMonit support tout le matériel Victron compatible Ve Direct (via USB) : 
  *  BMV : 600, 700, 702, 700H
  *  BlueSolar MPPT 75/10, 70/15, 75/14, 100/15, 100/30 rev1, 100/30 rev2, 150/35 rev1, 150/35 rev2, 150/45, 75/50, 100/50 rev1, 100/50 rev2, 150/60, 150/70, 150/85, 150/100
  *  SmartSolar MPPT 150/100,  250/100
  *  Phoenix Inverter 12V 250VA 230V, 24V 250VA 230V, 48V 250VA 230V, 12V 375VA 230V, 24V 375VA 230V, 48V 375VA 230V, 12V 500VA 230V, 24V 500VA 230V, 48V 500VA 230V

### Requis

  * Linux
  * PHP (5.5-5.6 recomended)
  * Lighttpd/Apache (au autre serveur web)
  * Perl
  * Python

### Installation

Je vais distinguer 2 partie :
  * Interface en temps réèl
  * Export vers emoncms

Il y a bien sûr une bonne partie de commun là dedans

#### La base / le socle

```sh
aptitude install php5-cli git python-serial
cd /opt
git clone https://github.com/kepon85/PvMonit.git
cp config-default.php config.php```

visudo vedirect.py

test vedirect

##### Sonde température
# Source : http://www.generation-linux.fr/index.php?post/2014/06/21/Relever-et-grapher-la-temp%C3%A9rature-de-sa-maison-sur-Debian
apt-get install libusb-dev libusb-1.0-0-dev unzip
cd /opt
wget http://dev-random.net/wp-content/uploads/2013/08/temperv14.zip
#ou un miroir
#wget http://www.generation-linux.fr/public/juin14/temperv14.zip
unzip temperv14.zip
cd temperv14/
make

visudo 
##### Pince amphèrpétrique 


```sh
aptitde install  libdevice-serialport-perl
```

visudo

#### Interface web en temps réèl

A faire...

```sh
aptitude lighttpd php5-cgi 
```

lighttpd-enable-mod fastcgi
lighttpd-enable-mod fastcgi-php


# lighttpd.conf : 

server.document-root        = "/opt/PvMonit/www"
server.pid-file             = "/var/run/lighttpd.pid"
server.username             = "www-data"
server.groupname            = "www-data"
server.port                 = 80
index-file.names            = ( "index.html", "index.php")
url.access-deny             = ( "~", ".inc" )
include_shell "/usr/share/lighttpd/use-ipv6.pl " + server.port
include_shell "/usr/share/lighttpd/create-mime.assign.pl"
include_shell "/usr/share/lighttpd/include-conf-enabled.pl"

service lighttpd restart


input api http://emoncms.mercereau.info/input/api
(SCREENSHOT)

#### Export vers emoncms

```sh
aptitde install  lynx 
```




mettre en tâche cron


### Todos

 - écrire un article sur l'installation
 - améliorer/passer en fonction pour les "progress" bar
 - Traduction en anglais, autres langues

### Documentation

  - Victron VE Direct Protocol documentation : https://www.victronenergy.fr/support-and-downloads/whitepapers

### License BEERWARE

Tant que vous conservez cet avertissement, vous pouvez faire ce que vous voulez de ce truc. Si on se rencontre un jour et que vous pensez que ce truc vaut le coup, vous pouvez me payer une bière en retour. 
