
####################################
# Ce script est un exemple pour vous
# il est a adapté a vos besoin
####################################

# Script pour ma box internet

# config
pingHost=["192.168.1.10","192.168.1.12"]

def checkIfComputerIsUp(pingHost) :
    onlineHost=0
    pingBin="ping -i 0.2 -W 1 -c1 "
    if os.path.isfile('/usr/bin/fping'): 
        pingBin='/usr/bin/fping -c1 -t500 '
    logMsg(5, "Ping bin utilisé : " + pingBin)
    for host in pingHost:
        logMsg(5, pingBin + host + '>/dev/null 2>/dev/null')
        response = os.system(pingBin + host + '>/dev/null 2>/dev/null')
        #and then check the response...
        if response == 0:
            onlineHost=onlineHost+1
    logMsg(5, 'onlineHost ' + str(onlineHost))
    return onlineHost

timeUp=600

# Si il est éteind, faut-il l'allumer ?
if relayEtat[relayId] == 1:
    # Si le régulateur dit que c'est bientôt la fin de charge et qu'il est plus de 11h c'est qu'il va faire beau !
    if MpptAbsOrFlo(xmlData['CS']):
        returnLog='UP Le régulateur est en mode abs ou float'
        returnEtat=2
    if float(xmlData['SOC']) > 93 and int(time.strftime ('%H')) > 11 and int(time.strftime ('%H')) < 17:
        returnLog='UP La batterie est chargé à plus de 93% et qu\'il est entre 11 et 17h'
        returnEtat=2
# Si il est allumé, faut-il l'éteindre ?
elif relayEtat[relayId] == 2:
    nbComputerUp=checkIfComputerIsUp(pingHost)
    # S'il n'y a plus d'ordinateur d'allumé et que les batterie sont sous les 95% ou qu'il est après 17h on éteind
    if (nbComputerUp == 0 and float(xmlData['SOC']) <= 93):
        returnLog='DOWN pas d`ordinateur connecté et les batterie sous 93%'
        returnEtat=1
    if (nbComputerUp == 0 and int(time.strftime ('%H')) >= 17) :
        returnLog='DOWN pas d`ordinateur connecté et il est plus de 17h'
        returnEtat=1
    if timeUpMin(timeUp):
        returnLog='On maintient allumé, '
        returnEtat=2
