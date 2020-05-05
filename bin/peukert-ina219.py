import yaml
import re
import time
import pprint
import json
import os
from lxml import etree
# ~ import board
# ~ from adafruit_ina219 import ADCResolution, BusVoltageRange, INA219

###  Définition
CapNomi=220          # Capacité de peukert nominale en Ah a déterminer par l'utilisateur
Ct=20
Coef=1.17          # Coefficient de Peukert a déterminer par l'utilisateur              // 1,2 pour le plomb AGM
ChargeEfficiencyFactor=85
DischargeMax=100   # Pourcentage max de décharge sous lequel il ne faut pas descendre pour ne pas détériorer la batterie  (100%, on affiche tout le niveau de batterie)
printMessage=5      # 5=debug, 1=silence
sleepInterval=8     # Temps entre 2 pasage (en s)
importDataYaml='/tmp/PvMonit_getSerialArduino.data.yaml'
# Pour les test on peur importer des valeurs : 
#importDataYaml='/opt/PvMonit/bin/peukert-simu-datatest.yaml'
exportData='/tmp/peukert-export.json'
IreversCurent=True   # Inversé le courant dans les formule ( -1A devient 1A, 1A devient -1A
# Loin de pouvoir faire ça toutes les 500ms sur le raspberry parce que j'ai le retour du BMV toutes les 8 secondes (structruellement il faudrait que ça soit directement sur l'Arduino chez moi encore une fois..)
# Bon même si on peut se dire que l'imprécision ça permet de tester...


### ina219
i2c_bus = board.I2C()
ina219 = INA219(i2c_bus)
print("ina219 test")
# display some of the advanced field (just to test)
print("Config register:")
print("  bus_voltage_range:    0x%1X" % ina219.bus_voltage_range)
print("  gain:                 0x%1X" % ina219.gain)
print("  bus_adc_resolution:   0x%1X" % ina219.bus_adc_resolution)
print("  shunt_adc_resolution: 0x%1X" % ina219.shunt_adc_resolution)
print("  mode:                 0x%1X" % ina219.mode)
print("")
# optional : change configuration to use 32 samples averaging for both bus voltage and shunt voltage
ina219.bus_adc_resolution = ADCResolution.ADCRES_12BIT_32S
ina219.shunt_adc_resolution = ADCResolution.ADCRES_12BIT_32S
# optional : change voltage range to 32V
ina219.bus_voltage_range = BusVoltageRange.RANGE_32V



with open('/opt/PvMonit/config-default.yaml') as f1:
    config = yaml.load(f1)
with open('/opt/PvMonit/config.yaml') as f2:
    config_perso = yaml.load(f2)

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
    if level <= printMessage :
        print(time.strftime ('%m/%d/%Y %H:%M') ," - ",msg)
    return -1
# On invers le courant si -x on affiche x, si x on affiche -x
def reversCurent(n):
    return n*-1

def export2json(exp_soc, exp_cap):
    data = []
    data.append({
        'SOC': round(exp_soc, 2),
        'CAP': round(exp_cap, 2)
    })
    with open(exportData, 'w') as outfile:
        json.dump(data, outfile)

# Initialisation
time_messure_precedente=0
synchro=False

#capacité de peukert nominale
CapNomiPeuk=Ct*(pow((CapNomi/Ct),(Coef)))

# Capacité de décharge maximum
# c'est le calcul de 80% de la capacité de peukert nominale en Ah
# donc la capacité réelle en Ah sans détérioration
CapDischargeMax=int(CapNomiPeuk/100*DischargeMax);
logMsg(1, "Capacité de décharge max : " + str(CapDischargeMax))

export2json(False, False)

def download_data():
    # téléchargement des données
    if not os.path.isfile(configGet('tmpFileDataXml')) or os.path.getctime(configGet('tmpFileDataXml'))+configGet('lcd', 'dataUpdate') < time.time():
        logMsg(2, 'Download data')
        with open(configGet('tmpFileDataXml'), 'wb') as tmpxml:
            tmpxml.write(urlopen(configGet('urlDataXml')).read())
            # ~ os.chown(configGet('tmpFileDataXml'), getpwnam('pvmonit').pw_uid, grp.getgrnam('pvmonit')[2]) 
    else:
        logMsg(2, 'Pas de download, le fichier temporaire est déjà frais..')
    return time.time()

download_data_last=download_data()

while True:
    ReguFloat=False
    # Téléchargement des données XML pour le régulateur
    download_data_time=download_data_last+60
    if download_data_time < time.time():
        download_data_last=download_data()
    # Parcour du XML
    tree = etree.parse(configGet('tmpFileDataXml'))
    for datas in tree.xpath("/devices/device/datas/data"):
        for data in datas.getchildren():
            if data.tag == "value":
                if datas.get("id") == 'CS' and data.text == 'Float':
                    logMsg(5, data.text)
                    ReguFloat=True
    
    # Si mes régulateurs sont en float : 
    # Synchronisation batterie chargée
    if ReguFloat == True:
        CapRest=int(CapDischargeMax)
        logMsg(4, "Régulateur en float")
        synchro=True
        Cap=CapRest;
        logMsg(3, "Cap restante  = " + str(Cap) + "Ah")
        SOC=100;
        logMsg(3, "SOC  = " + str(SOC) + "%")
        
        # Export
        export2json(SOC, Cap)
    else:
        if synchro == True:
            
            CapRest=Cap
            logMsg(3, "Capacité restante  = " + str(CapRest))
            
            # On récupère le courant du ina219
            # ~ bus_voltage = ina219.bus_voltage  # voltage on V- (load side)
            # ~ shunt_voltage = ina219.shunt_voltage  # voltage between V+ and V- across the shunt
            current = ina219.current  # current in mA
            
            I=float(current)/1000 # Courant (en mA)
            logMsg(3, "Courant  = " + str(I))
            
            # On applique le facteur d'efficacité
            if I > 0:
                I=float(I/100*ChargeEfficiencyFactor) # Courant (en mA)
                logMsg(3, "Courant real = " + str(I))
            else:
                I=float(I)
                logMsg(3, "Courant real = " + str(I))

            # Moment de la mesure :
            time_messure=time.time()
            
            if time_messure_precedente == 0:
                logMsg(3, 'Première messure, on ignore le calcul sinon ça fait tout merdé... Faut comprendre pourquoi...')
            else: 
                # Temps écoulé entre 2 mesures en heures, donc l'heure qu'il est MOINS l'heure qu'il était,
                # Le tout divisé par 3600 pour avoir des heures et non des secondes
                logMsg(5, "Temps messure  = " + str(time_messure) + "s")
                logMsg(5, "Temps messure précédente = " + str(time_messure_precedente) + "s")
                TempEntre2passage=(float(time_messure)-float(time_messure_precedente))/3600;
                
                logMsg(1, "Temps entre 2 passage  = " + str(TempEntre2passage) + "H")

                # Si c'est demandé, on invers le courant
                if IreversCurent == True:
                    I=reversCurent(I)
                    logMsg(5, "Valeur pour le courant inversé, I  = " + str(I) + "A")
                
                # Capacité  a l'instant T en fonction de Peukert
                # Donc l’ampérage a l'instant T EXPOSANT coefficient de peukert MULTIPLIER par le temps $T (temps entre 2 mesures)
                if I < 0:
                    CapT=float(pow(abs(I),Coef)*TempEntre2passage)
                    CapT=CapT*-1
                else:
                    CapT=float(pow(I,Coef)*TempEntre2passage)
                logMsg(3, "CapT  = " + str(CapT) + "Ah")
                
                # Capacité restante de peukert en Ah
                # donc capacité restante MOINS la capacité calculé a l'instant T trouvée plus haut
                # La valeur de $Cap est donc la valeur réelle en Ah a afficher et a comparer avec ce que dis le BVM
                Cap=CapRest-CapT;
                CapReel=Cap/CapNomiPeuk*CapNomi;
                logMsg(3, "Cap restante  = " + str(CapReel) + "Ah")
                
                # Produit en croix qui donne la valeur de $Cap en %
                SOC=Cap/CapDischargeMax*100;
                logMsg(3, "SOC  = " + str(SOC) + "%")
                
                if Cap >= CapDischargeMax:
                    logMsg(1, "CAP est >= à " + str(CapDischargeMax) + "Ah (CapDischargeMax) c'est une erreur, on corrige")
                    Cap=CapDischargeMax;
                    logMsg(3, "Correction : Cap restante  = " + str(Cap) + "Ah")
                    SOC=100;
                    logMsg(3, "Correction : SOC  = " + str(SOC) + "%")

                # Si les valeur sont bien des float
                if isinstance(SOC, float) and isinstance(CapReel, float):
                    # Export
                    export2json(SOC, CapReel)
                
            # On enregistre le moment de la messure précédente
            time_messure_precedente=time_messure
            
        else:
            export2json(False, False)
            logMsg(3, "Aucune synchro pour le moment, patientez jusqu'au prochain float du régulateur")
        
    time.sleep(sleepInterval)
