# Construction des ordres pour les relais

Documentation pour construire des ordres pour les relais

Les scripts qui donne les ordres sont dans /opt/PvMonit/domo/relay.script.d/

Ils sont rédigé en PHP mais peuvent être édité avec Blockly si vous ne savez pas programmé. Ensuite Blockly génère du PHP... (démonstration ici : http://demo.zici.fr/PvMonit/domo-edit-script.php)   Vous pouvez l'attendre avec l'adresse http://votreraspbery/domo-edit-script.php

## Les relais

### Les modes

Les relais comportes 4 niveaux de modes (mod) ils sont définies par l'utilisateur soit de façon manuel ou automatique (par les ordres/scripts)

* 0 :  Éteint forcé
  * Off forcé (action de l'utilisateur, ne peut être changé par les ordres/scripts)
* 1 : Éteint
  * Off automatique (paramètre de base)
* 2 : Allumé
  * On automatique (allumé par les scripts
* 3 : Allumé forcé
  * On forcé (action de l'utilisateur, ne sera ne peut être changé par les ordres/scripts)

### Les états

Les relais on un "état" :

* 0 : Relai éteint
* 1 : Relai allumé

## Donner un ordre

Pour un changement sur le relai, fin du script doit contenir au minimum 1 variable de retour : 

### $return['mod'] 

<u>Cette variable est indispensable sinon le relai ne change pas d'état</u>

Cette variable doit être à 1 ou 2

* 1 le relai sera éteint si ce n'est pas déjà le cas
* 2 le relai sera allumé si ce n'est pas déjà le cas

Un exemple en php d'un script minimum :

```php
$return['mod'] = 2;    // Allume le relai sans condition...
return $return;        // Indispensable en fin de script 
```

### $return['log'] 

C'est une option, cela permet d'indiquer dans le log pourquoi le mod à changé. Cela permet une trace en cas de débug...

Un exemple en php :

```php
$return['mod'] = 2;    // Allume le relai sans condition...
$return['log'] = "Allumage du relai";    // Indique dans le log (pour trace )
return $return;        // Indispensable en fin de script 
```

Un exemple avec une condition d'heure en PHP

```php
$return['mod'] = 1;    // Par défaut le relai est éteint
$return['log'] = null
// Si il est plus de 8h
if (date('G') > 8) {  // https://www.php.net/manual/fr/function.date.php
    $return['mod'] = 2;    // On allume le relai
    $return['log'] = "Allumage du relai, il est plus de 8h du matin";    // Indique dans le log (pour trace )
}
return $return;        // Indispensable en fin de script 
```

## Les variables

### $thisId

Renvoi l'ID du relai. Par exemple "2" si vous êtes dans le scripts qui gère le relai "2"

Retour : 1 à X (x = nombre de relai installé)

### $thisEtat

Renvoi l'état de ce relai à l'instant (C.F. doc plus haut sur l'état des relai) 

Retour  : 0 ou 1

### $relayEtat[x]

Renvoi l'état du relai choisie (nommé par x ici)

Paramètre :  x = id d'un relai

Retour : 0 ou 1 

Exemple en PHP : 

```php
// Si le relai 3 est allumé 
if ($relayEtat[3] == 1) {
    // action à mener...
}
```

### $thisMod

Renvoi le mode de ce relai à l'instant (C.F. doc plus haut sur les mods des relai) 

Retour : 0, 1, 2 ou 3

### $data[nom] - donnée d'une sonde / d'un appareil

C'est toutes données récupéré/visible dans PvMonit. Cela peut être la valeur d'un appareil victron, d'une sonde de température, d'un capteur de courant... A configurer dans le fichier config.yaml (valeur : domo / valueUse) par exemple : 

* $data['SOC'] =  % de batterie  (si vous avez un BMV)
* $data['P'] : puissance instantané (négatif ou positif) (si vous avez un BMV)
* $data['PPV'] : production solaire (si vous avez un régulateur)
* $data['CS'] : mode du régulateur (float, abs...) (si vous avez un régulateur)

## Fonctions

### relayUpToday(X)

Renvoi VRAI (true) si le relai X à déjà été allumé ce jour ou FAUX (false) s'il n'a pas déjà été allumé ce jour

Ce jour = dans les 12 dernières heures

### relayUpDownToday(X) 

Renvoi VRAI (true) si le relai X à déjà été allumé puis éteint ce jour ou FAUX (false) s'il n'a pas déjà été allumé ce jour

Exemple en PHP : 

```php
// Si le relai 3 a été allumé puis éteint dans les 12 dernières heures
if (relayUpDownToday(3) == true) {
    // action à mener...
}
```

### relayLastUp(X) 

Renvoi la date (en [timestamp](http://www.timestamp-tool.fr/)) du dernier allumage du relai X passé en paramètre. 

### relayLastUpAuto(X) 

Renvoi la date (en [timestamp](http://www.timestamp-tool.fr/))  du dernier allumage en automatique du relai X passé en paramètre.

### relayLastDown($idRelai) 

Renvoi la date (en [timestamp](http://www.timestamp-tool.fr/)) de la dernière extinction du relai X passé en paramètre. 

### timeUpMin(X, Y) 

Paramètre : 

* X : Id du relai 
* Y : Temps du timer

Renvoi VRAI (true) si le temps minimum Y (en seconde) n'est pas encore écoulé depuis l'allumage.

Exemple en PHP : 

```php
// Si le relai 3 n'est pas allumé depuis plus de 300 secondes
if (timeUpMin(3, 300) == true) {
    // action à mener...
}
```

### timeUpMax(X, Y)

Inverse de timeUpMin

### MpptAbsOrFlo($data['CS'])

Renvoi VRAI (true) si le régulateur est en Float ou en Absorption et FAUX (false) si ce n'est pas le cas

Dépend de $data['CS']

Exemple en PHP : 

```php
// Si le régulateur est en Absorption ou Float
if (MpptAbsOrFlo($data['CS']) == true) {
    // action à mener...
}
```

### MpptFlo($data['CS']) : retourne true si le régulateur est en Float

Renvoi VRAI (true) si le régulateur est en Float et FAUX (false) si ce n'est pas le cas

Dépend de $data['CS']

Exemple en PHP : 

```php
// Si le régulateur est en Float
if (MpptAbsOrFlo($data['CS']) == true) {
    // action à mener...
}
```

### 