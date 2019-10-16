# PvMonit

Il s'agit d'un petit projet de monitoring photovoltaique pour matériel Victron compatible Ve.direct particulièrement adapté pour les installations autonômes. Il permet une vue "en direct" et un export de l'historique vers [emoncms](https://openenergymonitor.org/emon/emoncms) (une branche d'[OpenEnergyMonitor project](http://openenergymonitor.org)).

![Screenshot PvMonit](http://david.mercereau.info/wp-content/uploads/2016/11/banPvMonit.jpeg) 
 
Exemple d'usage de PvMonit : je dispose d'un RaspberryPi, mes appareils Victron (MPTT, BMV) sont connectés avec des câbles VE.Direct. PvMonit est installé sur ce RaspberryPi et me permet : 

  - D'afficher les informations en temps réel sur une page web (local)
  - D'afficher les informations sur un écran LCD
  - De collecter les données toutes les X minutes et les expédier vers [emoncms](https://openenergymonitor.org/emon/node/90) quand internet est là (le wifi n'étant pas toujours allumé)

![Schéma exemple utilisation PvMonit avec Raspberry](https://david.mercereau.info/wp-content/uploads/2019/10/PvMonitV1_USB.png)

PvMonit support tout le matériel Victron compatible Ve Direct : 

  - BMV : 600, 700, 702, 700H
  - BlueSolar MPPT 75/10, 70/15, 75/14, 100/15, 100/30 rev1, 100/30 rev2, 150/35 rev1, 150/35 rev2, 150/45, 75/50, 100/50 rev1, 100/50 rev2, 150/60, 150/70, 150/85, 150/100
  - SmartSolar MPPT 150/100,  250/100
  - Phoenix Inverter 12V 250VA 230V, 24V 250VA 230V, 48V 250VA 230V, 12V 375VA 230V, 24V 375VA 230V, 48V 375VA 230V, 12V 500VA 230V, 24V 500VA 230V, 48V 500VA 230V

### Changelog

  * V1.0 (08/2019)
	* Collecte des informations via un XML tout les scripts (page web & getForEmoncms le récupère)
	* Chargement de la page en ajax, récupération des infos via le XML
	* Support d'un LCD adafruit 16*2 pour l'affichage des informations
  * V0.X
	  * Affichage dans interface web en temps réel
	  * Support câble Ve.direct USB 
	  * Export vers EmonCMS

### Auteur

  - David Mercereau [david #arobase# mercereau #point# info](http://david.mercereau.info/contact/)

### License BEERWARE

Tant que vous conservez cet avertissement, vous pouvez faire ce que vous voulez de ce truc. Si on se rencontre un jour et que vous pensez que ce truc vaut le coup, vous pouvez me payer une bière en retour. 

> Written with [StackEdit](https://stackedit.io/).




