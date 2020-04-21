# PvMonit - monitoring solaire autonome et gestion du surplus

Il s'agit d'un projet de monitoring photovoltaique pour matériel Victron compatible Ve.direct & WKS particulièrement adapté pour les installations autonomes. Il permet une vue "en direct" et un export de l'historique vers [emoncms](https://openenergymonitor.org/emon/emoncms) (une branche d'[OpenEnergyMonitor project](http://openenergymonitor.org)). Il est aussi possible de gérer le surplus d'énergie solaire non utilisé [avec le module domotique](https://david.mercereau.info/pvmonit-v2-0-domotique-gestion-surplus-electrique-solaire-en-autonomie/).

Démonstration :

* Interface de visualisation / monitoring : http://demo.zici.fr/PvMonit/
* Interface de conception des scripts pour la gestions du surplus électrique : http://demo.zici.fr/PvMonit/domo-edit-script.php

![Screenshot PvMonit](http://david.mercereau.info/wp-content/uploads/2016/11/banPvMonit.jpeg) 

Exemple d'usage de PvMonit : je dispose d'un RaspberryPi, mes appareils Victron (MPTT, BMV) sont connectés avec des câbles VE.Direct. PvMonit est installé sur ce RaspberryPi et me permet : 

  - D'afficher les informations en temps réel sur une page web (local)
  - D'afficher les informations sur un écran LCD
  - De collecter les données toutes les X minutes et les expédier vers [emoncms](https://openenergymonitor.org/emon/node/90) quand internet est là (le wifi n'étant pas toujours allumé)

![Schéma exemple utilisation PvMonit avec Raspberry](https://david.mercereau.info/wp-content/uploads/2019/10/PvMonitV1_USB.png)

PvMonit support tout le matériel Victron compatible Ve Direct : 

  - BMV : 600, 700, 702, 700H
  - BlueSolar MPPT 75/10, 70/15, 75/14, 100/15, 100/30 rev1, 100/30 rev2, 150/35 rev1, 150/35 rev2, 150/45, 75/50, 100/50 rev1, 100/50 rev2, 150/60, 150/70, 150/85, 150/100
  - SmartSolar MPPT 250/100, 150/100*, 150/85*, 75/15, 75/10, 100/15, 100/30, 100/50, 150/35, 150/100 rev2, 150/85 rev2, 250/70, 250/85, 250/60, 250/45, 100/20, 100/20 48V, 150/45, 150/60, 150/70, 250/85 rev2, 250/100 rev2"
  - Phoenix Inverter 12V 250VA 230V*, 24V 250VA 230V*, 48V 250VA 230V*, 12V 375VA 230V*, 24V 375VA 230V*, 48V 375VA 230V*, 12V 500VA 230V*, 24V 500VA 230V*, 48V 500VA 230V*, 12V 250VA 230V, 24V 250VA 230V, 48V 250VA 230V, 12V 250VA 120V, 24V 250VA 120V, 48V 250VA 120V, 12V 375VA 230V, 24V 375VA 230V, 48V 375VA 230V, 12V 375VA 120V, 24V 375VA 120V, 48V 375VA 120V, 12V 500VA 230V, 24V 500VA 230V, 48V 500VA 230V, 12V 500VA 120V, 24V 500VA 120V, 48V 500VA 120V, 12V 800VA 230V, 24V 800VA 230V, 48V 800VA 230V, 12V 800VA 120V, 24V 800VA 120V, 48V 800VA 120V, 12V 1200VA 230V, 24V 1200VA 230V, 48V 1200VA 230V, 12V 1200VA 120V, 24V 1200VA 120V, 48V 1200VA 120V

Ainsi que les WKS avec port USB

## Service / Commander / soutenir le projet

### Faire un don

Pour soutenir le projet vous pouvez faire un don libre à l'auteur parce que ça représente des centaines et des centaines d'heures de travail un machin comme ça : https://david.mercereau.info/soutenir/

### Livré sur mesure 

Vous pouvez aussi vous commander PvMonit avec ou sans Raspbery Pi tout prêt à l'emploi : https://david.mercereau.info/pvmonit/#shop sur mesure pour votre projet.

### Téléchargé prêt à l'emploi

Pour 35€, télécharger [raspbian](https://www.raspberrypi.org/downloads/raspbian/) + PvMonit d'installé prêt à l'emploi. Il n'y a plus qu'a copier le tout sur une carte SD.

Note : Cette version faite pour être installé sur un Raspbery Pi et pour être connecté avec du matériel Victron par câble USB ve.direct (découvert automatiquement)

Recevez un lien de téléchargement par email : https://david.mercereau.info/pvmonit/#shop

### Service cloud

Pour 15€/ans un service de cloud est à votre disposition. L'interface PvMonit est exporté sur un serveur distant ce qui permet son accès depuis n'importe quel ordinateur connecté a Internet. 

* 2 niveau de mot de passe son possible pour la connexion au service si vous souhaitez rester discret
* Si vous utilisez la domotique pour le surplus d'énergie vous pouvez activer ou désactiver les relais à distance

Exemple avec mon installation : http://david.pvmonit.zici.fr/

Commander par ici : https://david.mercereau.info/pvmonit/#shop

## Changelog

  * V3.1 (04/2020)
        * Support des WKS (via USB)
  * V3.0 (04/2020)
	  * Intégration de [Blockly](https://developers.google.com/blockly/) pour la conception des scripts de gestion du surplus électrique / domotique
	  * Service de cloud permet un export de vos données temps réel sur une interface accessible depuis internet (même si vous êtes derrière un routeur xG)
  * V2.1 (03/2020)
	* Changement structurelle pour le passage par un daemon
	* Prise de main à distance possible
  * V2.0 (01/2020)
	* Domotique pour gérer le surplus d'énergie via des relais
	    * https://vimeo.com/385514728
  * V1.0 (08/2019)
	* Collecte des informations via un XML tout les scripts (page web & getForEmoncms le récupère)
	* Chargement de la page en ajax, récupération des infos via le XML
	* Support d'un LCD adafruit 16*2 pour l'affichage des informations
  * V0.X
	  * Affichage dans interface web en temps réel
	  * Support câble Ve.direct USB 
	  * Export vers EmonCMS

## Auteur

  - David Mercereau [david #arobase# mercereau #point# info](http://david.mercereau.info/contact/)

## Support

**Aucun support ne sera fait pour la conception des scripts "domotiques" pour la gestion du surplus d'énergie. Mais je peux vous les concevoir sur mesure.**

Le support se fait par ici : https://framagit.org/kepon/PvMonit/issues

Pour activer le mode debug, modifier le fichier config.yaml avec ces valeurs : 

```
printMessage: 5                                         # 0=0	5=debug
printMessageLogfile: /tmp/pvmonitdebug.log              # path or fase
```

Lancer l'action qui ne fonctionne pas et envoyez le contenu nouvellement apparu de /tmp/pvmonitdebug.log dans la demande de support. 

Dans tout les cas, dans une demande de support il faut du détail (image/schéma d'installation/log...), le plus de détail possible

## License BEERWARE

Tant que vous conservez cet avertissement, vous pouvez faire ce que vous voulez de ce truc. Si on se rencontre un jour et que vous pensez que ce truc vaut le coup, vous pouvez me payer une bière en retour. 

> Written with [StackEdit](https://stackedit.io/).




