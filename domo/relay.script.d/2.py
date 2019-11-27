# Pompe de rellevage

# Par défaut on laisse éteind
returnEtat=1

# Temps d'allumage
timeUp=300

# Si elle a démarré aujourd'hui et que le temps d'allumage maxium est passé alors on le laisse à down
if relayEtat[relayId] == 2 and relayUpToday(relayId) and timeUpMax(timeUp):
    returnLog='DOWN, le temps d allumage est passé'
    returnEtat=1
# Sinon on le lance si la batterie est à 100%
elif float(xmlData['SOC']) > 100:
        returnLog='UP La batterie est chargé à 100%'
        returnEtat=2
