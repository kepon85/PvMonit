#!/bin/bash

###################################
# Script sous licence BEERWARE
# Version 0.1	2016
###################################

# joué 1/2 heures par un utilisateur restraint (non root)

##### Paramètres
TESTHOST="emoncms.mercereau.info"
TESTPORT=80
DATA_COLLECTE="/tmp/PvMonit.collecteData"
DATA_COLLECTE_NOK="${DATA_COLLECTE}_erreur"
PRINTMESSAGE=5		# 0=0	5=debug

##### Début du script
function trucAdir {
	if [ "$PRINTMESSAGE" -ge "$1"  ] ; then
		echo "`date` - ${2}"
	fi
}
function cleanup {
	trucAdir 5 "Trap exit..."
}
trap cleanup EXIT

# Gentil avec le système
ionice -c3 -p$$ &>/dev/null
renice -n 19 -p $$ &>/dev/null

timeout 1 bash -c "cat < /dev/null > /dev/tcp/${TESTHOST}/${TESTPORT}" 2>/dev/null
if (($?)) ; then
	trucAdir 5 "Pas internet, on arrête là"
	exit 1
fi

if ! [ -d "${DATA_COLLECTE}" ] ; then
	trucAdir 5 "Le répertoire ${DATA_COLLECTE} n'existe pas, il ne doit pas y avoir de données collectées"
fi
if ! [ -d "${DATA_COLLECTE}_erreur" ] ; then
	mkdir -p "${DATA_COLLECTE}_erreur"
fi

dataOk=0
dataNok=0
for fichierData in ${DATA_COLLECTE}/*
do
	if [ "${fichierData}" != "${DATA_COLLECTE}/*" ] ; then
		nbLigneDansLeFichier=`cat ${fichierData} | wc -l`
		okAttendu=`for i in \`seq 1 ${nbLigneDansLeFichier}\`; do echo -n ok ; done`
		resultat_expedition=`bash ${fichierData}`
		if [ "$resultat_expedition" == "$okAttendu" ] ; then
			trucAdir 5 "Donnée de ${fichierData} correctement envoyé"
			rm ${fichierData}
			let dataOk=1+$dataOk
		else
			trucAdir 5 "Problème avec le fichier ${fichierData}, le retour est : ${resultat_expedition}"
			mv ${fichierData} ${DATA_COLLECTE_NOK}
			let dataNok=1+$dataNok
		fi
	fi
done 

trucAdir 2 "Données correctements envoyées : ${dataOk}, données en erreurs : ${dataNok}"


exit 0
