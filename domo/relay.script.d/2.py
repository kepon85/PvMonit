# Test DD externe par exemple

# Si il est éteind, faut-il l'allumer ?
if relayEtat[relayId] == 1:
    if os.path.isfile('/tmp/domo2up'): 
        returnEtat=2
# Si il est allumé, faut-il l'éteindre ?
elif relayEtat[relayId] == 2:
    if not os.path.isfile('/tmp/domo2up'): 
        returnEtat=1
        
