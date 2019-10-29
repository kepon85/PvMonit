# Script pour mon téléphone fixe

# Si il est éteind, faut-il l'allumer ?
if relayEtat[relayId] == 1:
    # Si la box est allumé automatiquement ou de force
    if relayEtat[0] >= 2:
        # Si le régulateur dit que c'est bientôt la fin de charge et qu'il est plus de 11h c'est qu'il va faire beau !
        if MpptAbsOrFlo(xmlData['CS']) or (xmlData['SOC'] > 93 and int(time.strftime ('%H')) > 11):
            returnEtat=2
# Si il est allumé, faut-il l'éteindre ?
elif relayEtat[relayId] == 2:
    # Si la box est éteinte automatiquement ou de force
    # Si les batterie sont sous les 95% ou qu'il est après 21h on éteind
    if relayEtat[0] <= 1 or float(xmlData['SOC']) < 95 or int(time.strftime ('%H')) >= 21:
        returnEtat=1
        
