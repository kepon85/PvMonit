msg_soc='BA:?'
msg_p='P:?'
msg_ppvt='PV:?'
msg_conso='CON:?'

try:
    tree = etree.parse(configGet('tmpFileDataXml'))
    for datas in tree.xpath("/devices/device/datas/data"):
        if datas.get("id") in configGet('lcd','dataPrint'):
            for data in datas.getchildren():
                if data.tag == "value":
                    if datas.get("id") == 'SOC':
                        msg_soc='BA:'+data.text + '%'
                        soc=data.text
                    elif datas.get("id") == 'P':
                        msg_p = 'P:'+data.text + 'W'
                    elif datas.get("id") == 'PPVT':
                        msg_ppvt = 'PV:'+data.text + 'W'
                    elif datas.get("id") == 'CONSO' and data.text != 'NODATA':
                        msg_conso = 'CON:'+data.text + 'W'
except:
    debugTerm("Erreur dans la lecture du XML la syntax n'est pas bonne ?")
    
# Construction de l'affichage
lcd.clear()
nb_ligne1_space=lcd_columns-len(msg_soc)-len(msg_p)
ligne1_msg=msg_soc
for nb_space1 in range(nb_ligne1_space):
    ligne1_msg=ligne1_msg+' '
ligne1_msg=ligne1_msg+msg_p

nb_ligne2_space=lcd_columns-len(msg_ppvt)-len(msg_conso)
ligne2_msg=msg_ppvt
for nb_space2 in range(nb_ligne2_space):
    ligne2_msg=ligne2_msg+' '
ligne2_msg=ligne2_msg+msg_conso

lcd.message = ligne1_msg+'\n'+ligne2_msg
debugTerm('Affichage\n' + ligne1_msg+'\n'+ligne2_msg)

if etat_lcd == True:
    if int(soc) >= 94:
        # Vert
        lcd.color = [0, 100, 0]
    elif int(soc) <= 85:
        # Rouge
        lcd.color = [100, 0, 0]
    elif int(soc) > 85:
        # Jaune
        lcd.color = [100, 100, 0]
    else:
        lcd.color = [100, 100, 100]
    
    
