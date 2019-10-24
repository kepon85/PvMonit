import yaml
import time
from smbus2 import SMBus
import os
from lxml import etree
import wget

## for debug :
import pprint

with open('/opt/PvMonit/config-default.yaml') as f1:
    config = yaml.load(f1, Loader=yaml.FullLoader)
with open('/opt/PvMonit/config.yaml') as f2:
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


# Function for log
def logMsg(level, msg):
    if level <= configGet('printMessage') :
        print(time.strftime ('%m/%d/%Y %H:%M') ," - ",msg)
    return -1

# i2c write
def writeNumber(value):
    bus.write_byte(configGet('domo', 'i2c', 'adress'), value)
    return -1

logMsg(1, 'Lancement du script domo.py')

heartLastCheck=0
xmlLastCheck=0
xmlCheckError=0
xmlData = {}
logMsg(5, "Début de la boucle")
while 1:
    # XML data recup
    t=int(time.time())
    if xmlLastCheck+configGet('domo', 'dataCheckTime') < t:
        if not os.path.isfile(configGet('tmpFileDataXml')):
            logMsg(1, "Le fichier XML de donnée " + configGet('tmpFileDataXml') + " n'existe pas.")
            xmlCheckError=xmlCheckError+1
        elif os.path.getmtime(configGet('tmpFileDataXml'))+configGet('domo', 'fileExpir') < t :
            logMsg(1, "Le fichier data est périmé !")
            xmlCheckError=xmlCheckError+1
        else:
            xmlCheckError=0
            tree = etree.parse(configGet('tmpFileDataXml'))
            datacount=0
            for datas in tree.xpath("/devices/device/datas/data"):
                if datas.get("id") in configGet('domo', 'valueUse'):
                    datacount = datacount + 1
                    for data in datas.getchildren():
                        if data.tag == "value":
                            xmlData[datas.get("id")]=data.text
            pprint.pprint(xmlData)
            xmlLastCheck=t
            
    # S'il y a trop d'erreur : 
    if xmlCheckError >= configGet('domo', 'checkError'):
        logMsg(1, 'Trop d\'erreur, on patiente 10 secondes')
        time.sleep(10)
        xmlCheckError=0
    else
        # Le heartbeat
        if heartLastCheck+configGet('domo', 'heartbeatTime') < t:
            bus=SMBus(configGet('domo', 'i2c', 'device'));
            writeNumber(int(ord("H")))
            logMsg(5, 'Heardbeat envoyé')
            heartLastCheck=t
        
    # Pour être gentil avec le système
    time.sleep(0.01)
    
