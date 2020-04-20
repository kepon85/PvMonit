import yaml
import re
import time
import pprint
import json

# Définition
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

while True:
    # Lecture des données
    with open(importDataYaml) as f1:
        data = yaml.load(f1)
    try:
        data['Serial1']['CS']
        data['Serial2']['CS']
        data['Serial3']['I']
    except KeyError:
        logMsg(1, "Erreur : Il manque une donnée, on passe cette messure")
        export2json(False, False)
        time.sleep(sleepInterval)
        continue
    except TypeError:
        logMsg(1, "Erreur : Il manque une donnée, on passe cette messure")
        export2json(False, False)
        time.sleep(sleepInterval)
        continue
        
    # Si mes régulateurs sont en float : 
    # Synchronisation batterie chargée
    if int(data['Serial1']['CS']) == 5 and int(data['Serial2']['CS']) == 5:
        CapRest=int(CapDischargeMax)
        logMsg(4, "Les régulateurs sont en float")
        synchro=True
        Cap=CapRest;
        logMsg(3, "Cap restante  = " + str(Cap) + "Ah")
        SOC=100;
        logMsg(3, "SOC  = " + str(SOC) + "%")
        
        # Export
        export2json(SOC, Cap)
    elif int(data['Serial1']['CS']) < 5 and int(data['Serial2']['CS']) < 5:
        if synchro == True:
            
            CapRest=Cap
            logMsg(3, "Capacité restante  = " + str(CapRest))
            
            # On récupère le courant
            I=float(data['Serial3']['I'])/1000 # Courant (en mA)
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
