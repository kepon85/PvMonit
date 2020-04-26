# PvMonit - monitoring solaire autonome et gestion du surplus

Il s'agit d'un projet de monitoring photovoltaique pour matériel Victron compatible Ve.direct & WKS particulièrement adapté pour les installations autonomes. Il permet une vue "en direct" et un export de l'historique vers [emoncms](https://openenergymonitor.org/emon/emoncms) (une branche d'[OpenEnergyMonitor project](http://openenergymonitor.org)). Il est aussi possible de gérer le surplus d'énergie solaire non utilisé [avec le module domotique](https://david.mercereau.info/pvmonit-v2-0-domotique-gestion-surplus-electrique-solaire-en-autonomie/).

Démonstration :

* Interface de visualisation / monitoring : http://demo.zici.fr/PvMonit/
* Interface de conception des scripts pour la gestions du surplus électrique : http://demo.zici.fr/PvMonit/domo-edit-script.php

![Screenshot PvMonit](http://david.mercereau.info/wp-content/uploads/2016/11/banPvMonit.jpeg) 

Exemple d'usage de PvMonit : je dispose d'un RaspberryPi, mes appareils Victron (MPTT, BMV) sont connectés avec des câbles VE.Direct. PvMonit est installé sur ce RaspberryPi et me permet : 

  - D'afficher les informations en temps réel sur une page web (local)
  - D'afficher les informations sur un écran LCD
  - De collecter les données toutes les X minutes et les expédier vers [emoncms](https://openenergymonitor.org/emon/node/90) ce qui permet de faire des graph de l'histoire. Quand internet est là (le wifi n'étant pas toujours allumé)

![Schéma exemple utilisation PvMonit avec Raspberry](https://david.mercereau.info/wp-content/uploads/2019/10/PvMonitV1_USB.png)

## Support Matériel 

PvMonit support tout le matériel **Victron** compatible Ve Direct ainsi que les **WKS** avec port USB

## Documentation / installation

Tout se trouve sur le wiki officiel : http://pvmonit.zici.fr/doc/fr:start#tutoriel

## Soutenir le projet : Service / Commander

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

## Auteur

  - David Mercereau [david #arobase# mercereau #point# info](http://david.mercereau.info/contact/)

## Support

http://pvmonit.zici.fr/doc/fr:start#support

## License BEERWARE

Tant que vous conservez cet avertissement, vous pouvez faire ce que vous voulez de ce truc. Si on se rencontre un jour et que vous pensez que ce truc vaut le coup, vous pouvez me payer une bière en retour. 

> Written with [StackEdit](https://stackedit.io/).




