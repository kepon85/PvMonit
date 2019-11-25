# Script pour mon téléphone fixe

# Par défaut on le laisse éteind 
returnEtat=1
timeUp=1800

# Si la box est allumé automatiquement ou de force
if relayEtat[0] >= 2:
    # Si le régulateur dit que c'est bientôt la fin de charge et qu'il est plus de 11h c'est qu'il va faire beau !
    if MpptFlo(xmlData['CS']):
        returnLog='UP Le régulateur est en mode float'
        returnEtat=2
    if float(xmlData['SOC']) > 95 and int(time.strftime ('%H')) > 11 and int(time.strftime ('%H')) < 19:
        returnLog='UP La batterie est chargé à plus de 95% et il est enre 11h et 19h'
        returnEtat=2
    if timeUpMin(timeUp):
        returnLog='On maintient allumé, '
        returnEtat=2
