# coding=utf-8
from rpi_TM1638 import TMBoards
# https://github.com/thilaire/rpi-TM1638
import json
import yaml
import sys
import time
import os
import hashlib
from pwd import getpwnam  
import grp

def file_as_bytes(file):
    with file:
        return file.read()


# my GPIO settings (two TM1638 boards connected on GPIO19 and GPIO13 for DataIO and Clock; and on GPIO06 and GPIO26 for the STB)
DIO = 19
CLK = 13
STB = 26, # S'il y a plusieurs TM1638 à la suite, vous pouvez les indiquer après la virgules 

### aptitude install python-yaml

## for debug :
import pprint

with open('/opt/PvMonit/config-default.yaml') as f1:
    config = yaml.load(f1, Loader=yaml.FullLoader)
with open('/opt/PvMonit/config.yaml') as f2:
    config_perso = yaml.load(f2, Loader=yaml.FullLoader)

TM = TMBoards(DIO, CLK, STB, 0)

TM.clearDisplay()

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

def ledAction(idRelay, action):
	idRelay=idRelay-1
	TM.leds[idRelay] = action
	
def modChangeTo(idRelay, mod):
	idRelay=idRelay-1
	TM.segments[idRelay] = ' '
	if (mod == 0):
		TM.segments[idRelay,3] = True
	elif (mod == 3):
		TM.segments[idRelay,0] = True

# Function for log
def logMsg(level, msg):
    if level <= configGet('printMessage') :
        print(time.strftime ('%m/%d/%Y %H:%M')  + " - " + str(msg))
    return -1

def waitLcd(timeSleep):
	wait=0.2
	nombreDeTour=timeSleep/wait
	etat=0
	for i in range(int(nombreDeTour)):
		if (etat == 0):
			TM.segments[7,7] = True
			etat = 1
		else:
			TM.segments[7,7] = False
			etat = 0
		time.sleep(wait)
	TM.segments[7,7] = False


logMsg(3, "Lancement du script ")


TM.segments[0] = 'boot'

for i in range(configGet('domo', 'relayNb')):
	TM.leds[i] = True

######## waitLcd(3)

def refreshEtat():
	logMsg(3, "Rafraichissement de l'état")
	with open(configGet('domo', 'jsonFile', 'etatPath')) as f3:
		JsonEtatRelay = json.load(f3)
	pprint.pprint(JsonEtatRelay);
	idRelay=1
	for etat in JsonEtatRelay:
		if (JsonEtatRelay[etat] == 1):
			logMsg(2, "UP relay " + str(idRelay+1))
			ledAction(idRelay, True)
		else:
			logMsg(2, "DOWN relay " + str(idRelay))
			ledAction(idRelay, False)
		idRelay=idRelay+1
	return t

def refreshMod(JsonModRelay):
	logMsg(3, "Rafraichissement des mods")
	idRelay=1
	for mod in JsonModRelay:
		logMsg(5, "Mod relay " + str(idRelay) + " = " + str(JsonModRelay[mod]))
		modChangeTo(idRelay, JsonModRelay[mod])
		idRelay=idRelay+1
	return t

def genDefaultJsonFile():
	logMsg(1, "Init des fichiers json")
	dataEtat={}
	dataMod={}
	for i in range(configGet('domo', 'relayNb')):
		dataEtat[str(i+1)] = 0
		dataMod[str(i+1)] = 1
	with open(configGet('domo', 'jsonFile', 'etatPath'), 'w') as etatFile:
		json.dump(dataEtat, etatFile)
		os.chown(configGet('domo', 'jsonFile', 'etatPath'), getpwnam('pvmonit').pw_uid, grp.getgrnam('pvmonit')[2]) 
	with open(configGet('domo', 'jsonFile', 'modPath'), 'w') as modFile:
		json.dump(dataMod, modFile)
		os.chown(configGet('domo', 'jsonFile', 'modPath'), getpwnam('pvmonit').pw_uid, grp.getgrnam('pvmonit')[2]) 
	waitLcd(2)
	
# Init des json si inexistant
if (not os.path.isfile(configGet('domo', 'jsonFile', 'modPath')) or not os.path.isfile(configGet('domo', 'jsonFile', 'etatPath'))):
	genDefaultJsonFile()

t=int(time.time())
lastRefreshEtat = refreshEtat() 
lastHashEtat = hashlib.md5(file_as_bytes(open(configGet('domo', 'jsonFile', 'etatPath'), 'rb'))).hexdigest()
with open(configGet('domo', 'jsonFile', 'modPath')) as f4:
	JsonModRelay = json.load(f4)
lastRefreshMod = refreshMod(JsonModRelay) 
lastHashMod = hashlib.md5(file_as_bytes(open(configGet('domo', 'jsonFile', 'modPath'), 'rb'))).hexdigest()


logMsg(3, "Début de la boucle")
JsonModRelayChange=False;
while True:
	t=int(time.time())
	
	# Etat
	if (os.path.getctime(configGet('domo', 'jsonFile', 'etatPath')) > lastRefreshEtat): 
		logMsg(5, "Fichier etat modifié")
		lastRefreshEtat=t
		if (lastHashEtat != hashlib.md5(file_as_bytes(open(configGet('domo', 'jsonFile', 'etatPath'), 'rb'))).hexdigest()):
			logMsg(4, "Fichier etat est différent (hash md5)")
			lastRefreshEtat = refreshEtat() 
			lastHashEtat = hashlib.md5(file_as_bytes(open(configGet('domo', 'jsonFile', 'etatPath'), 'rb'))).hexdigest()
			waitLcd(1)

	# Mod
	if (os.path.getctime(configGet('domo', 'jsonFile', 'modPath')) > lastRefreshMod): 
		logMsg(5, "Fichier mod modifié")
		lastRefreshMod=t
		if (lastHashMod != hashlib.md5(file_as_bytes(open(configGet('domo', 'jsonFile', 'modPath'), 'rb'))).hexdigest()):
			logMsg(4, "Fichier mod est différent (hash md5)")
			with open(configGet('domo', 'jsonFile', 'modPath')) as f4:
				JsonModRelay = json.load(f4)
			lastRefreshMod = refreshMod(JsonModRelay) 
			lastHashMod = hashlib.md5(file_as_bytes(open(configGet('domo', 'jsonFile', 'modPath'), 'rb'))).hexdigest()
			waitLcd(1)

	# Gestion boutton (en attente de la lib ?)
	bouttonArray =TM.getData(0)
	idButton=0
	for etatBoutton in bouttonArray:
		if (etatBoutton != 0):
			if (etatBoutton == 16):
				idButtonReal=idButton+4+1
			else:
				idButtonReal=idButton+1
			# Etat Suivant : 
			modNow=JsonModRelay[str(idButtonReal)]
			if modNow == 0:
				modNext=1
			elif modNow == 3:
				modNext=0
			else:
				modNext=3
			logMsg(2, "Boutton relay " + str(idButtonReal) + " pressé pour un passage sur le mod " + str(modNext))
			modChangeTo(idButtonReal, modNext)
			JsonModRelay[str(idButtonReal)] = modNext
			JsonModRelayChange=t+configGet('domo', 'tm1638', 'actionButtonDelay');
		idButton=idButton+1
	
	if (JsonModRelayChange != False and JsonModRelayChange < t):
		logMsg(2, "Enregistrement / prise en compte des changements de mod")
		with open(configGet('domo', 'jsonFile', 'modPath'), 'w') as modFile:
			json.dump(JsonModRelay, modFile)
			os.chown(configGet('domo', 'jsonFile', 'etatPath'), getpwnam('pvmonit').pw_uid, grp.getgrnam('pvmonit')[2]) 
		waitLcd(2)
		JsonModRelayChange=False
	
	time.sleep(configGet('domo', 'tm1638', 'refresh'))
 	
