# Test DD externe par exemple

# Si il est éteind, faut-il l'allumer ?
if relayEtat[relayId] == 1:
    if os.path.isfile('/tmp/domo7up'): 
        returnLog='UP Présence du fichier /tmp/domo7up'
        returnEtat=2
# Si il est allumé, faut-il l'éteindre ?
elif relayEtat[relayId] == 2:
    if not os.path.isfile('/tmp/domo7up'): 
        returnLog='DOWN Le fichier n est /tmp/domo7up n existe plus'
        returnEtat=1
        
