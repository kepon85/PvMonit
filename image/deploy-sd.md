# Déployer l'image de la carte SD Raspbian "PvMonit"

Pré-requis : carte SD de 4Go

Pour télécharger l'image de PvMonit il faut vous rendre sur https://david.mercereau.info/pvmonit/#shop

Une fois télécharger déployer l'image comme si c'était une Raspbian "normal"

* Linux : https://www.raspberrypi.org/documentation/installation/installing-images/linux.md
* Mac OS : https://www.raspberrypi.org/documentation/installation/installing-images/mac.md
* Windows : https://www.raspberrypi.org/documentation/installation/installing-images/windows.md
* Chrome OS : https://www.raspberrypi.org/documentation/installation/installing-images/chromeos.md

## Configuration Wifi

Par défaut le Wifi tentera de se connecter à 

* SSID : MONWIFI
* Clef/mot de passe : monMotDePasseWifi"

Pour modifier ces paramètres déposé un fichier wpa_supplicant.conf dans la partition "boot" de la carte SD. Au démarrage celui-ci sera lu / déplacé dans le système avant un reboot.

Ce fichier wpa_supplicant.conf  doit ressemblé à ceci : 

```
country=fr
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
network={
    ssid="<nom de votre connexion>"
    scan_ssid=1 # nécessaire quand le ssid n'est pas diffusé
    psk="<mot de passe de votre connexion>"
}
```

(plus de détail sur [ce fichier ici](https://debian-facile.org/doc:reseau:wpasupplicant#ajouter-un-profil-wifi))

Vous pouvez maintenant mettre la carte SD dans votre PI et le mettre sous tension.

Vous pouvez joindre votre raspberry pi avec son nom "**pvmonit.local**"

Si pvmonit.local ne répond pas vous pouvez chercher l'adresse IP de celui-ci avec https://angryip.org/download/#linux

## Administrer votre raspberry pi

Les paramètres

```
Hostname : pvmonit.local
Port : 22 (standard)
Utilisateur : pvmonit (ou root)
Mot de passe : pvmonit
```

### En ligne de commande SSH

Avec un client (SSH) de votre choix :  ssh pvmonit@pvmonit.local

### En graphique avec un client SSH SCP

Le logiciel [FileZilla](https://filezilla-project.org/) (dans ce cas l'ĥôte c'est sftp://pvmonit.local ) est multi-plateforme, [Winscp](https://winscp.net/) sous Windows

### Configurer 

Dans /opt/PvMonit

Copier config-default.yaml en config.yaml puis modifier le config.yaml selon vos besoins

### Read-only file system

Si vous avez un message comme "Read-only file system" c'est que 

En root (sudo -i) vous pouvez passer en mode écriture avec la commande : 

```bash
rw
```

Pour repasser en mode "lecteur seul" : 

```bash
ro
```

## Accéder à l'interface PvMonit

Si pvmonit.local répond correctement vous pouvez vous connecter avec http://pvmonit.local