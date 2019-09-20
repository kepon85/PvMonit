import serial
import yaml
import re
import time
#import pprint

# Read the conf file
with open('../config.yaml') as f:
    config = yaml.load(f, Loader=yaml.FullLoader)

# Function for log
def logMsg(level, msg):
    if level <= config['PrintMessage'] :
        print(time.strftime ('%m/%d/%Y %H:%M') ," - ",msg)
    return -1

ser = serial.Serial(config['serial']['port'], config['serial']['baudRate'], timeout=config['serial']['timeout'])
line=''
dataFile={}
while True:
        # Lecture du caractère en UTF 8
        c = ser.read().decode('utf-8')
        # Si un caractère est présent
        if c:
                # Si c'est un saut de ligne, la ligne est complète
                if c == "\n":
                        isStop=line[0:4]
                        isSonde=line[0:2]
                        isSerial=line[0:3]
                        patternSerial = re.compile(r"S:[1-3]")
                        logMsg(4, config['serial']['port'] + " : " + line)
                        # le stop
                        if isStop == "STOP":
                                logMsg(2, "Détection du STOP, on write le fichier")
                                with open(config['dataPath'], 'w') as yaml_file:
                                        yaml.dump(dataFile, yaml_file, default_flow_style=False)
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
