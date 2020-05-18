# Changelog
  * V3.4 (05/2020)
	    * Prévision météo
	  * Prévision de production déduite de la météo
	  * Prévision de l'état de charge batterie au couché du soleil à J+0 et J+1 
	        * Doc : https://pvmonit.zici.fr/doc/fr:howto:weather
  * V3.3 (05/2020)
	    * Ajour de <valueBeast> dans le XML généré
	  * Changement dans l'attitude de getEmoncms pour l'expédition des fichiers, maintenant il utilise (lui aussi) le XML pour lire les données
  * V3.2 (05/2020)
	  * Amélioration à la génération du fichier XML (système de cache et de vérification des données minimum) pour limiter les risques d'erreurs de génération dû a l'interface Serial vers les appareils solaires
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
