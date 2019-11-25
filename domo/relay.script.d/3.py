# Script pour la rechage makita

# Temps sans bagotter  (en secondes)
upMini=600 

# Après la pompe de relevage  (2)
if relayUpDownToday(2):
    returnEtat=1
    if float(xmlData['SOC']) > 98:
        returnLog='UP La batterie est chargé à 100%'
        returnEtat=2
    # Minimum de temps sur l'état haut
    if relayEtat[relayId] == 2 && relayLastUp(relayId) < t+upMini:
        returnLog='[1] UP La batterie est chargé à 100%'
        returnEtat=2
