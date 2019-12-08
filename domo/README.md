# PvMonit Domotique - Ou comment utilisé le surplus d'une installation solaire autonome

Dans le cas d'une installation solaire autonome (non raccordé au réseau EDF), une fois que les batteries sont rechargé (ce qui se produit au alentour de 11h-12h pour moi 80% du temps) il y a de l'énergie potentiel de perdu. Plus précisément si je n'utilise pas cette énergie au moment ou il y a du soleil (de la production) cette énergie n'est pas utilisé.  On peut augmenter le stockage mais c'est infini, coûteux en argent en ressource environnementale. 

Du coup m'a semblé pertinent de réfléchir à un moyen d'automatisé certaine tâche qui me permette d'utilisé ce surplus d'électricité quand il est là. Actuellement je le fait de façon tout à fait manuel : quand les batteries sont pleine et qu'il y a du soleil, je lance une machin à laver, je lance la pompe de relevage de la phyto,  je recharge mes batterie d'outil portatif….  Cette automatisation va aussi me permettre d'aller plus loin & d'envisagé d'installé un petit chauffe eau électrique de camion (~10L) ou autres…

Grâce à [PvMonit](https://david.mercereau.info/pvmonit-v1-0-monitoring-de-mon-installation-photovoltaique-autonome/) j'avais déjà une remonté d'information sur l'état du système solaire, des batteries, de la production qui m'arrivait sur un Raspbery PI. il ne me restait plus qu'a "piloter des prises électrique" en fonction de l'état du système solaire et de conditions que je donne au programme.

Le cahier des charges c'était : 

- De pouvoir piloter ce que je veux, mon choix c'est donc porté vers un système de contrôle de relais (en gros des interrupteur contrôlé de façon électronique) 
- Que le système consomme très peu. C'est réussi le système consomme ~0,153W (tout les relais d'éteint), 0,4W avec 1 relais d'allumé (hors PvMonit…)
- Que je puisse passé certain appareil en "marche forcé" ou en "stop forcé" 
- Que le système soit résilient, qu'il puisse encore fonctionné sans l'apport d'information du raspbery pi  en cas de panne

## Câblage / Matériel 

Matériel : 

- Le raspbery pi (zéro ça suffit) sur lequel est installé PvMonit (expliqué [ici](https://david.mercereau.info/pvmonit-v1-0-monitoring-de-mon-installation-photovoltaique-autonome/))
- Un [arduino](https://fr.wikipedia.org/wiki/Arduino) UNO qui reçois de potentiel ordre du Raspbery PI avec le protocole i2c.  (6€)
- [Un afficheur 8 chiffres + 8 leds + 8 boutons (tm1638)](https://os.mbed.com/components/TM1638-LED-controller-80-LEDs-max-Keyboa/) nous permet d'interagire avec le système (forcé l'alumage, interdir l'allumage…) (~6€)
- Une plaque de 8 relais (mais vous pouvez envisagez en avoir autant que vous voulez… ça correspond à mon besoin…) qui allume tel ou tel appareil pour (9€)

<img src="https://david.mercereau.info/wp-content/uploads/2019/11/PvMonit-Domo_bb.png" alt="https://david.mercereau.info/wp-content/uploads/2019/11/PvMonit-Domo_bb.png" style="zoom:20%;" />

## Installation

Dépendance du script : 

```bash
aptitude install python3 python3-yaml pip3
pip3 install lxml pysqlite3 json smbus2 wget 
```

Lancement du script à la main

```bash
cd /opt/PvMonit/domo/
python3 domo.py 
```

Pour le lancement au démarrage, ajouter avant "exit 0" dans le fichier /etc/rc.local la ligne suivant

```bash
screen -A -m -d -S domo /opt/PvMonit/domo/domo-launch.sh
```

## Configuration

La configuration ce fait dans le même fichier que pvmonit /opt/PvMonit/config.yaml, vous avez une secion "domo". Ensuite il vous faut faire les scripts qui correspondent à vos usages, c'est en Python que ça ce joue. Les scripts sont contenu dans /opt/PvMonit/domo/relay.script.d/NUMEROduRELAIS.py 

## Explication

Les interupteur on 3 modes : 

- Off forcé
- Mode Automatique  (uniquement possible si le PI est présent)
- On forcé

L'affichange des 8 segments change quand le PI est présent et que le script domo.py est lancé.

Les leds au dessus des 8 segments sont synchronisé avec l'état des relais