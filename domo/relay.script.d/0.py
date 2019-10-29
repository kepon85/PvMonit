# Script pour ma box internet

# config
pingHost=["192.168.1.10","192.168.1.12"]

def checkIfComputerIsUp(pingHost) :
    onlineHost=0
    pingBin="ping -i 0.2 -W 1 "
    if os.path.isfile('/usr/bin/fping'): 
        pingBin='/usr/bin/fping -c1 -t500 '
    for host in pingHost:
        response = os.system(pingBin + host + '>/dev/null 2>/dev/null')
        #and then check the response...
        if response == 0:
            onlineHost=onlineHost+1
    #return onlineHost
    return 0

# Si il est éteind, faut-il l'allumer ?
if relayEtat[relayId] == 1:
    # Si le régulateur dit que c'est bientôt la fin de charge et qu'il est plus de 11h c'est qu'il va faire beau !
    if MpptAbsOrFlo(xmlData['CS']) or (xmlData['SOC'] > 93 and int(time.strftime ('%H')) > 11):
        returnEtat=2
# Si il est allumé, faut-il l'éteindre ?
elif relayEtat[relayId] == 2:
    # S'il n'y a plus d'ordinateur d'allumé et que les batterie sont sous les 95% ou qu'il est après 17h on éteind
    if (checkIfComputerIsUp(pingHost) == 0 and float(xmlData['SOC']) < 95) or (checkIfComputerIsUp(pingHost) == 0 and int(time.strftime ('%H')) >= 17) :
        returnEtat=1
        
