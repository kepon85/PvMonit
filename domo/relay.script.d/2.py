# Pompe de rellevage

# Par défaut on laisse éteind
returnEtat=1

# Temps d'allumage
timeUp=300

if relayEtat[relayId] == 2 and not timeUpMax(timeUp):
    returnLog='UP, maintient allume, le temps n est pas passé'
    returnEtat=2
# Si elle a démarré aujourd'hui et que le temps d'allumage maxium est passé alors on le laisse à down
elif relayEtat[relayId] == 2 and timeUpMax(timeUp):
    returnLog='DOWN, le temps d allumage est passé'
    returnEtat=1
# Sinon on le lance si la batterie est à 100%
elif float(xmlData['SOC']) > 99 and not relayUpToday(relayId) :
        returnLog='UP La batterie est chargé à 100% et pas lancé aujourd hui'
        returnEtat=2
