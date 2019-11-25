# Script pour la rechage makita

# Par défaut on laisse éteind
returnEtat=1

# Temps d'allumage
timeUp=3600

# Si démarré aujourd'hui et que le temps d'allumage maxium est passé alors on le laisse à down
if relayUpToday(relayId) and timeUpMax(timeUp):
    returnLog='DOWN, le temps d allumage est passé'
    returnEtat=1
# Sinon on le lance si la batterie est à 100% & que la pome de relevage c'est lancé aujourd'hui
elif float(xmlData['SOC']) > 100 and relayUpDownToday(2):
        returnLog='UP La batterie est chargé à 100%'
        returnEtat=2

