# PvMonit

Il s'agit d'un petit projet de monitoring photovoltaique pour matériel Victron. Il permet une vue "en direct" et un export de l'historique vers [emoncms](https://openenergymonitor.org/emon/emoncms) (une branche d'[OpenEnergyMonitor project](http://openenergymonitor.org)).
 
Mon usage de PvMonit : je dipose d'un RaspberryPi connecté avec des câbles VE.Direct sur mes appareil Victron (MPTT, BMV). PvMonit est installé sur ce RaspberryPi et me permet : 
    - D'afficher les informations en temps réel sur une page web.
    - De collecter les données toutes les 5 minutes, les mettres en caches et les expédier vers emoncms quand internet est là (le wifi n'étant allumé qu'au besoin)

PvMonit support : 
  *  BMV : 700, 702, 700H
  *  BlueSolar MPPT 75/10, 70/15, 75/14, 100/15, 100/30 rev1, 100/30 rev2, 150/35 rev1, 150/35 rev2, 150/45, 75/50, 100/50 rev1, 100/50 rev2, 150/60, 150/70, 150/85, 150/100
  *  SmartSolar MPPT 150/100,  250/100
  *  Phoenix Inverter 12V 250VA 230V, 24V 250VA 230V, 48V 250VA 230V, 12V 375VA 230V, 24V 375VA 230V, 48V 375VA 230V, 12V 500VA 230V, 24V 500VA 230V, 48V 500VA 230V

### Requis

  * PHP (5.5-5.6 recomended)
  * Lighttpd/Apache (au autre serveur web)
  * Perl
  * Bash
  * Python

### Installation

A faire...

```sh
$ cd exemple 
$ aptitude install toto
```

### Todos

 - expedition.sh en PHP
 - écrire un article sur l'installation
 - Supporté tout les modèles possibles
 - Traduction en anglais, autres langues

### License BEERWARE

Tant que vous conservez cet avertissement, vous pouvez faire ce que vous voulez de ce truc. Si on se rencontre un jour et que vous pensez que ce truc vaut le coup, vous pouvez me payer une bière en retour. 
