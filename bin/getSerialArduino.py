import serial
import yaml
import re
import time
#import pprint


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

ser = serial.Serial(configGet('vedirect', 'arduino', 'serial', 'port'), configGet('vedirect', 'arduino', 'serial', 'baudRate'), timeout=configGet('vedirect', 'arduino', 'serial', 'timeout'))
line=''
dataFile={}
while True:
        time.sleep(configGet('vedirect', 'arduino', 'serial', 'whileSleep'))
        try:
                # Lecture du caractère en UTF 8
                c = ser.read().decode('utf-8')
                readSerial=1
        except:
                logMsg(3, "Erreur sur le read ")
                readSerial=0
        # Si un caractère est présent
        if c and readSerial == 1:
                # Si c'est un saut de ligne, la ligne est complète
                if c == "\n":
                        isStop=line[0:4]
                        isSonde=line[0:2]
                        isSerial=line[0:3]
                        patternSerial = re.compile(r"S:[1-3]")
                        logMsg(4, configGet('vedirect', 'arduino', 'serial', 'port') + " : " + line)
                        # le stop
                        if isStop == "STOP":
                                logMsg(2, "Détection du STOP, on write le fichier")
                                with open(configGet('tmpFileDataXml'), 'w') as yaml_file:
                                        yaml.dump(dataFile, yaml_file, default_flow_style=False)
                                time.sleep(1)
                        # Le serial
                        elif patternSerial.match(isSerial):
                                logMsg(4, "Un serial ;")
                                lineSplit=line.split("_")
                                dataName=lineSplit[0]
                                dataNameSplit=dataName.split(":")
                                nom="Serial" + dataNameSplit[1]
                                # Nom déclaré s'il ne l'est pas déjà
                                if nom not in dataFile:
                                        dataFile[nom]={}
                                dataValuesSplit=lineSplit[1].split("\t")
                                # Valeur
                                if dataValuesSplit[0] != "Checksum":
                                        dataFile[nom].update({dataValuesSplit[0] : dataValuesSplit[1].replace('\r','')})
                        # Les sondes
                        elif isSonde == "S:" :
                                logMsg(4, "Une sonde ;")
                                lineSplit=line.split("_")
                                dataName=lineSplit[0]
                                dataNameSplit=dataName.split(":")
                                nom=dataNameSplit[1]
                                dataFile[nom]={}
                                dataValues=lineSplit[1]
                                dataValuesSplit=dataValues.split(",")
                                for values in dataValuesSplit:
                                        value=values.split(":")
                                        dataFile[nom].update({value[0] : value[1].replace('\r','')})
                        else:
                                logMsg(1, "Erreur ligne non pris en charge :" + line)
                        # ~ # Remise à 0
                        line=""
                        
                else:
                        # Sinon on concataine
                        line+=c
