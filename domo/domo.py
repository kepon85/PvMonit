import yaml
import time
from smbus2 import SMBus
import os
from lxml import etree
from urllib.request import urlopen
import wget
from past.builtins import execfile
import re
import time

## for debug :
import pprint

with open('../config-default.yaml') as f1:
    config = yaml.load(f1, Loader=yaml.FullLoader)
with open('../config.yaml') as f2:
    config_perso = yaml.load(f2, Loader=yaml.FullLoader)

def configGet(key1, key2=None, key3=None, key4=None):
    if key4 != None:
        try:
            return config_perso[key1][key2][key3][key4]
        except:
            return config[key1][key2][key3][key4]
    elif key3 != None:
        try:
            return config_perso[key1][key2][key3]
        except:
            return config[key1][key2][key3]
    elif key2 != None:
        try:
            return config_perso[key1][key2]
        except:
            return config[key1][key2]
    else:
        try:
            return config_perso[key1]
        except:
            return config[key1]

# Cherche a savoir si le MPPT est en Absorption ou en Float (en fin de charge)
def MpptAbsOrFlo(cs):
    patternAbsFlo = re.compile(r"Absorption|Float")
    if patternAbsFlo.match(cs):
        return True;
    else : 
        return False;

# Function for log
def logMsg(level, msg):
    if level <= configGet('printMessage') :
        print(time.strftime ('%m/%d/%Y %H:%M') ," - ",msg)
    return -1

# i2c write
def writeNumber(value):
    bus.write_byte(configGet('domo', 'i2c', 'adress'), value)
    return -1

def download_data():
    # téléchargement des données
    logMsg(3, 'Download data')
    with open(configGet('tmpFileDataXml'), 'wb') as tmpxml:
        tmpxml.write(urlopen(configGet('urlDataXml')).read())
    return time.time()

logMsg(1, 'Lancement du script domo.py')

heartLastCheck=0
relayDataLastCheck=0
scriptExecLast=0
xmlLastCheck=0
xmlfileCheckError=0
xmlData = {}
spoolAction=None
spoolActionSend=False

bus=SMBus(configGet('domo', 'i2c', 'device'));

logMsg(5, "Début de la boucle")
while 1:
    # XML data recup
    t=int(time.time())
    if xmlLastCheck+configGet('domo', 'dataCheckTime') < t:
        if not os.path.isfile(configGet('tmpFileDataXml')):
            logMsg(1, "Le fichier XML de donnée " + configGet('tmpFileDataXml') + " n'existe pas.")
            download_data()
            xmlfileCheckError=xmlfileCheckError+1
        elif os.path.getmtime(configGet('tmpFileDataXml'))+configGet('domo', 'fileExpir') < t :
            logMsg(1, "Le fichier data est périmé !")
            download_data()
            xmlfileCheckError=xmlfileCheckError+1
        else:
            logMsg(3, "Récupération des données XML (état de l'installation solaire")
            xmlfileCheckError=0
            tree = etree.parse(configGet('tmpFileDataXml'))
            datacount=0
            for datas in tree.xpath("/devices/device/datas/data"):
                if datas.get("id") in configGet('domo', 'valueUse'):
                    datacount = datacount + 1
                    for data in datas.getchildren():
                        if data.tag == "value":
                            xmlData[datas.get("id")]=data.text
            logMsg(5, pprint.pprint(xmlData))
            xmlLastCheck=t

    # S'il y a trop d'erreur : 
    if xmlfileCheckError >= configGet('domo', 'fileCheckError'):
        logMsg(1, 'Trop d\'erreur, on patiente 10 secondes')
        time.sleep(10)
        xmlfileCheckError=0
    else:
        #########################
        # Le heartbeat
        #########################
        if heartLastCheck+configGet('domo', 'heartbeatTime') < t:
            writeNumber(int(ord("H")))
            logMsg(5, 'Heardbeat envoyé')
            heartLastCheck=t
        #########################
        # Data Relay
        #########################
        if relayDataLastCheck+configGet('domo', 'relay', 'dataFreq') < t:
            logMsg(4, 'On récupère les données des relay (via i2c arduino)')
            # A FAIRE
            # Simulation monsieur l'arbitre
            #// Etat : 
            #//  - 0 : off force
            #//  - 1 : off auto
            #//  - 2 : on auto
            #//  - 3 : on force
            #// Mode
            #//  - 0 : Null
            #//  - 1 : Off 
            #//  - 2 : Auto
            #//  - 3 : On
            
            time.sleep(0.3)
            # Requête i2c pour demande de data (état et mode des relay)
            i2cResults = bus.read_i2c_block_data(configGet('domo', 'i2c', 'adress'), int(ord('D')), configGet('domo', 'relay', 'nb')*2+1)
            # Remise à 0
            relayEtat=[]
            relayMod=[]
            x=0
            dataOrdre=1
            logEtat=""
            logMod=""
            for i2cDatas in i2cResults:
                # Si les données sont présentes
                if i2cDatas !=  255:
                    if i2cDatas == 29:  # C'est le sépartateur : https://fr.wikibooks.org/wiki/Les_ASCII_de_0_%C3%A0_127/La_table_ASCII
                        dataOrdre=2
                        x=0
                    elif dataOrdre == 1:
                        relayEtat.insert(x,i2cDatas)
                        logEtat=logEtat+","+str(i2cDatas)
                    else:
                        relayMod.insert(x,i2cDatas)
                        logMod=logMod+","+str(i2cDatas)
                    x=x+1
            logMsg(5, "DATA reçu : Etat " + logEtat)
            logMsg(5, "DATA reçu : Mod " + logMod)
            relayDataLastCheck=t
        #########################
        # On joue les scripts
        #########################
        if scriptExecLast+configGet('domo', 'relay', 'scriptExecInterval') < t:
            logMsg(4, 'On joue les script des relay en mode auto')
            relayId=0
            print(relayEtat[0])
            for mod in relayMod:
                # Si la file d'attente des actions est vide on que le relay est en automatique
                if spoolAction == None and mod == 2:
                    scriptFile=configGet('dir','domo')  + configGet('domo','relay', 'scriptDir') + "/" + str(relayId) + ".py"
                    logMsg(4, 'Lecture du script ' + scriptFile)
                    if not os.path.isfile(scriptFile): 
                        logMsg(2, 'Erreur, pas de script ' + scriptFile)
                    else:
                        returnEtat=None
                        execfile(scriptFile)
                        if returnEtat != None and returnEtat != relayEtat[relayId]:
                            logMsg(2, 'Un changement d\'état vers ' + str(returnEtat) + ' de est demandé pour le relay ' + str(relayId))
                            spoolAction=[relayId, returnEtat, t]
                        else:
                            logMsg(4, 'Pas de changement d\'état demandé pour le relay ' + str(relayId))
                relayId=relayId+1
            scriptExecLast=t
        
        # Traitement de la file d'attente
        if spoolAction != None and spoolActionSend == False:
            logMsg(3, 'Traitement du spool, envoi de l\'ordre')
            logMsg(5, pprint.pprint(spoolAction))
            spoolActionSend=t
            # A FAIRE
            # Lancer l'ordre sur l'aduino 
        # Vérifier que l'arduino a bien exécuté l'ordre
        if spoolAction != None and spoolActionSend != False:
            # Est-ce que le relay est dans l'état attendu par l'ordre
            if relayEtat[spoolAction[0]] == spoolAction[1]:
                logMsg(5, 'Le relay ' + spoolAction[0] + ' n\'est pas encore dans l\'état attendu ' + spoolAction[1])
        #if spoolAction != None and spoolActionSend+configGet('domo', 'spoolTimeout') < t:
        #    spoolAction=
        # !! Faut faire un truc vide le spoolAction quand c'est fait... hummmm ... 
            
    # Pour être gentil avec le système
    time.sleep(0.01)
    
