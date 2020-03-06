#!/bin/bash

pidOfHelpProcess=0
fileChek='/tmp/PvMonit_HELP'

while (true); do
    if [ -f ${fileChek} ]; then
        if [ "${pidOfHelpProcess}" == "0" ]; then
            echo "Lancement de la commande SSH d'aide"
            #screen -A -m -d -S help-now /opt/PvMonit/bin/pvmonitHelp.sh &
            /opt/PvMonit/bin/pvmonitHelp.sh &
            pidOfHelpProcess=$!
            sleep 2
        fi
        pgrep pvmonitHelp.sh &>/dev/null
        if (($?)) ; then
            echo -n 0 > ${fileChek}
            echo "La connexoin d'assistance n'est pas établie, le lancement du script /opt/PvMonit/bin/pvmonitHelp.sh a été demandé mais n'est pas lancé"
        else 
            echo -n 1 > ${fileChek}
            echo "La connexoin d'assistance est établie"
        fi
    elif [ "${pidOfHelpProcess}" != "0" ]; then
        echo "Arrêt de la demande d'assistance"
        pkill -P ${pidOfHelpProcess}
        pidOfHelpProcess=0
    fi 
    sleep 1
done
