# PvMonit Domotique - Ou comment utilisé le surplus d'une installation solaire autonome

Article / vidéo lier à ce module domotique : 

* https://david.mercereau.info/?p=5703
* https://vimeo.com/385514728

**Prés-requis: être un peu matheux sinon avoir des bases en programmation sommaire**. En effet pour programmer des évènement sur les relais, il faut soit coder en PHP, soit utiliser l'interface simplifié blockly (démonstration ici : http://demo.zici.fr/PvMonit/domo-edit-script.php) Des scripts d'exemples à votre disposition vous pouvez vous en inspiré.

## Câblage / Matériel 

Matériel : 

- Le raspbery pi (zéro ça suffit) sur lequel est installé PvMonit (expliqué [ici](https://david.mercereau.info/pvmonit-v1-0-monitoring-de-mon-installation-photovoltaique-autonome/))
- Une plaque de 8 relais (mais vous pouvez envisagez en avoir autant que vous voulez… ça correspond à mon besoin…) qui allume tel ou tel appareil pour (9€)
- (option) [Un afficheur 8 chiffres + 8 leds + 8 boutons (tm1638)](https://os.mbed.com/components/TM1638-LED-controller-80-LEDs-max-Keyboa/) nous permet d’interagir avec le système sans allumer un ordinateur (ou sans smartphone)  : forcé l’allumage, interdire l'allumage… (~6€). 

<img src="https://david.mercereau.info/wp-content/uploads/2020/01/PvMonit-Domo-v2_bb.png" alt="https://david.mercereau.info/wp-content/uploads/2020/01/PvMonit-Domo-v2_bb.png" style="zoom:30%;" />

## Daemon relay action

Un daemon est utilisé pour mettre en actions les relais selon les différents périphérique qui pourrait modifier leur état (les boutons du tm1638, l'interface web, le daemon d'automatisation "domo")

Dépendance du script, la commande "gpio" : 

```bash
aptitude install gpio-utils
```

Cette commande doit pouvoir s’exécute avec les droits "root", pour ça on va la lancer en sudo, il faut donc lancer la commande :

```sh
visudo
```

Et ajouter : 

```diff
+ pvmonit ALL=(ALL) NOPASSWD:/usr/bin/gpio *
```

Ensuite on peut lancement du script à la main :

```bash
/opt/PvMonit/domo/relay-actions-launch.sh
```

Pour le lancement au démarrage, assurez vous que dans votre fichier config.yaml la section domo/daemon soit à true

```yaml
domo:
    daemon: true
```

Et relancer le daemon : 

```bash
systemctl stop pvmonit
systemctl start pvmonit
```

## Daemon "domo"

C'est le daemon qui regard l'état du système (batterie, régulateur) et qui déclenche des actions selon des scripts

Pour la configuration, regarder le fichier config.yaml, dans la partie "domo:"

Dépendance du script : 

```bash
aptitude install php-sqlite3 php-xml
```

Indiquer les bonnes permissions : 

```bash
chown pvmonit:pvmonit /opt/PvMonit/domo -R
```

Lancement du script à la main

```bash
/opt/PvMonit/domo/domo-launch.sh
```

Pour le lancement au démarrage, assurez vous que dans votre fichier config.yaml la section domo/daemon soit à true

```yaml
domo:
    daemon: true
```

Et relancer le daemon : 

```bash
systemctl stop pvmonit
systemctl start pvmonit
```

Il vous faut ensuite configurer les scripts pour l'automatisme, il se trouve dans /opt/PvMonit/domo/relay.script.d/ et il faut les nommer X.php (X étant le numéro du relai)

Voir /opt/PvMonit/domo/relay.script.d/ID.php.exemple pour connaître le champs des possibles / des fonctions.

## Activation dans l'interface web PvMonit

Dans le ficheir config.yaml activer "domo" :

```yaml
www:
    domo: true
```

Mettre les bon droits : 

```bash
chown pvmonit:pvmonit /opt/PvMonit/domo -R
```

Ceci vous permet d’interagir avec les relais via l'interface web :

[<img src="https://david.mercereau.info/wp-content/uploads/2020/01/Screenshot_2020-01-07-Pv-Monit1.png" alt="https://david.mercereau.info/wp-content/uploads/2020/01/PvMonit-Domo-v2_bb.png" style="zoom:60%;" />](https://david.mercereau.info/wp-content/uploads/2020/01/Screenshot_2020-01-07-Pv-Monit1.png)

## (Option) Afficheur 8 chiffres + 8 leds + 8 boutons (tm1638)

Nous permet d’interagir avec le système sans allumer un ordinateur (ou sans smartphone).

Dépendance du script : 

```bash
aptitude install python3 python3-yaml python3-json python3-pip
pip3 install rpi-TM1638
```

Lancement du script à la main

```bash
/opt/PvMonit/domo/tm1638-launch.sh
```

Pour le lancement au démarrage, assurez vous que dans votre fichier config.yaml la section domo/tm1638/daemon soit à true

```yaml
domo:
    tm1638: 
        daemon: true
```

Et relancer le daemon : 

```bash
systemctl stop pvmonit
systemctl start pvmonit
```

## Configuration

La configuration ce fait dans le même fichier que pvmonit /opt/PvMonit/config.yaml, vous avez une section "domo". 

## Créer vos scripts / ordres pour déclencher les relai

Ensuite il vous faut faire les scripts qui correspondent à vos usages. 2 possibilités pour ça : 

* Ecrire du code PHP. Les scripts sont contenu dans /opt/PvMonit/domo/relay.script.d/NUMEROduRELAIS.php vous pouvez lire /opt/PvMonit/domo/relay.script.d/README.md
* Utiliser l'interface simplifié blockly (démonstration ici : http://demo.zici.fr/PvMonit/domo-edit-script.php) Des scripts d'exemples à votre disposition vous pouvez vous en inspiré. Vous pouvez l'attendre avec l'adresse http://votreraspbery/domo-edit-script.php

## Pour interagir avec d'autres scripts maisons

Seul les mod son modifiés, les état en son déduit par le script domo/relay-actions si vous voulez interagir avec d'autres applications il vous suffit de modifier le fichier json "/tmp/PvMonit_domo_mod"  (par défaut, modifiable dans le config.yaml : domo / jsonFile / modPath: /tmp/PvMonit_domo_mod)
