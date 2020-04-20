# Création carte SD PvMonit

## Préparer l'image

Installer raspbian sur la carte SD

```bash
sudo dd bs=4M if=2020-02-13-raspbian-buster-lite.img of=/dev/sdb conv=fsync
```

Ajouter une partition de 100Mo entre la boot et la racine pour /opt

Activer le ssh : placer fichier "ssh" vide dans /boot

Mettre le wifi : https://desertbot.io/blog/headless-raspberry-pi-3-bplus-ssh-wifi-setup

/boot/wpa_supplicant.conf 

```
country=fr
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1

network={
	ssid="LESPAILLEUX"
	psk=828fd05f1a5a773d5f450be2e8c961a58e0019f3c0295349b15fe385fab7be1d
}
```

## Configurer

raspi-config : hostname

/etc/ssh/sshd_config

```
PermitRootLogin yes
```

Balancer sa clef SSH (authorized_keys) pour root et pi

Indiquer la partition opt dans le fstab (formaté en vfat pour que ça soit lisible par windows...)

Ajouter la partition opt dans le fstab : 

```diff
proc            /proc           proc    defaults          0       0
PARTUUID=738a4d67-01  /boot           vfat    defaults          0       2
PARTUUID=738a4d67-02  /               ext4    defaults,noatime  0       1
+ LABEL="PVMONIT"           /opt            vfat    defaults,users,rw,umask=022,uid=1001,gid=1001  0       0
```

Monter : mount -a

Passer en Read Only le FS : https://david.mercereau.info/raspberrypi-raspbian-en-lecture-seul-readonly-pour-preserver-la-carte-sd/

Mise à jour : 

```
apt-get update && apt-get safe-upgrade
```

## Installer PvMonit

Suivre le tuto : https://framagit.org/kepon/PvMonit/

```bash
apt-get install aptitude php-cli php-yaml git python-serial sudo screen sshpass python3 python3-pip python3-yaml lighttpd php-cgi  php-xml php7.3-json lynx gpio-utils php-sqlite3 php-xml python3 python3-yaml python3-pip htop iftop iotop screen nmap tcpdump lsof iptraf dnsutils mc ncdu whois rsync tree
```

## Créer le fichier image

Faire le ménage partition log et tmp

remetttre le fichier /etc/wpa_supplicant/wpa_supplicant.conf avec : 

network={
       ssid="MONWIFI"
       psk="monMotDePasseWifi"
}

NE FONCTIONNE PAS : Après resize partition racine (/) au plus "petit" ~3,8Go remettre le script init_resize dans la première ligne du fichier boot/cmdline.txt

```
console=serial0,115200 console=tty1 root=PARTUUID=738a4d67-02 rootfstype=ext4 elevator=deadline fsck.repair=yes rootwait quiet init=/usr/lib/raspi-config/init_resize.sh
```

Générer l'image

```bash
sudo umount /dev/sdb*
sudo dd bs=4M if=/dev/sdb conv=fsync | split -b 3000m - ./sdb.img
```

# Changelog

* V1.0 17/04/20 1 no resize
  * PvMonit 3.0 
  * Raspbian : 2020-02-13-raspbian-buster-lite 
  
  Merde pas changé wifi...

* V1.1 18/04/20
  * wpa_supplicant.conf  dans le boot possible
  * partition /opt en fat32 pour l'accès depuis un windows...

* a faire

En fait c'est /opt/PvMonit qu'on monte :

```
umount /opt
mkdir /opt/PvMonit
vi /etc/fstab 
mount -a
cd /opt/PvMonit/
mv PvMonit/* .
mv PvMonit/.gi* .
rmdir PvMonit/
```

/etc/fstab

```diff
- LABEL="PVMONIT"           /opt            vfat    defaults,users,rw,umask=022,uid=1001,gid=1001  0       0
+ LABEL="PVMONIT"           /opt/PvMonit            vfat    defaults,users,exec,rw,umask=022,uid=1001,gid=1001  0       0
```

version image dans /etc/pvmonit_version 

pip3 install pyserial

aptitude install i2c-tools

pip3 install Adafruit_DHT

pip3 install adafruit-circuitpython-charlcd lxml

aptitude install wiringpi

aptitude install php-sqlite3 php-xml

aptitude install python3 python3-yaml python3-json python3-pip

pip3 install rpi-TM1638

