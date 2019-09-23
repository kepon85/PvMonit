# https://learn.adafruit.com/adafruit-16x2-character-lcd-plus-keypad-for-raspberry-pi/python-usage
# https://circuitpython.readthedocs.io/projects/charlcd/en/latest/examples.html
import time
import board
import busio
import adafruit_character_lcd.character_lcd_rgb_i2c as character_lcd
from lxml import etree
from urllib.request import urlopen

# conf
data_url = 'http://192.168.1.2/data-xml.php'
tmp_data_file='/tmp/pvmonit-data-xml.php.tmp'
rafraichissement=0.1 # en seconde pour les boutons
data_update=30 # en seconde pour le rafraichissement des données
lcd_on_timer=60 # en seconde le temps que l'écran reste allumé si par défaut éteind
est_ce_la_nuit_timer=600 # détection de la nuit tout les x secondes
dataprint = ['SOC', 'P', 'PPVT', 'CONSO']
lcd_on_at=8 # heure d'allumage du LCD
lcd_off_at=21 # heure d'extinction du LCD


## LCD prepar :
# Modify this if you have a different sized Character LCD
lcd_columns = 16
lcd_rows = 2
# Initialise I2C bus.
i2c = busio.I2C(board.SCL, board.SDA)
# Initialise the LCD class
lcd = character_lcd.Character_LCD_RGB_I2C(i2c, lcd_columns, lcd_rows)

lcd.clear()
# on allume
lcd.color = [100, 0, 0]
# on dit bonjour (si si)
lcd.message = "Bonjour !\nOn boot ..."
time.sleep(2)

# Fonction de debug
def debugTerm(msg) :
    print(time.strftime ('%d/%m/%Y %H:%M'),' : ',msg)

# Détection du dodo (pour extinction de l'écran)
def est_ce_la_nuit() :
    if int(time.strftime ('%H')) >= lcd_off_at or int(time.strftime ('%H')) <= lcd_on_at:
        return True
    else:
        return False

def update_data():
    # Récupération des données
    debugTerm('Update data')
    
    lcd.clear()
    lcd.message = '|'
    lcd.clear()
    lcd.message = '/'
    lcd.clear()
    lcd.message = '-'
    lcd.clear()
    lcd.message = '\\'
    lcd.clear()
    lcd.message = '-'
    lcd.clear()
    lcd.message = '|'
    with open(tmp_data_file, 'wb') as tmpxml:
        tmpxml.write(urlopen(data_url).read())

    datatotal = len(dataprint)

    datacount = 0
    tree = etree.parse(tmp_data_file)
    msg_soc='BA:?'
    msg_p='P:?'
    msg_ppvt='PV:?'
    msg_conso='CON:?'
    for datas in tree.xpath("/devices/device/datas/data"):
        if datas.get("id") in dataprint:
            datacount = datacount + 1
            for data in datas.getchildren():
                if data.tag == "value":
                    if datas.get("id") == 'SOC':
                        # ~ debugTerm(data.text + '%')
                        # ~ if float(data.text) > 95:
                            # ~ debugTerm('vert !!!')
                        # ~ elif float(data.text) > 90:
                            # ~ debugTerm('orange !!!')
                        # ~ elif float(data.text) < 90:
                            # ~ debugTerm('rouge !!!')
                        msg_soc='BA:'+data.text + '%'
                    elif datas.get("id") == 'P':
                        msg_p = 'P:'+data.text + 'W'
                    elif datas.get("id") == 'PPVT':
                        msg_ppvt = 'PV:'+data.text + 'W'
                    elif datas.get("id") == 'CONSO' and data.text != 'NODATA':
                        msg_conso = 'CON:'+data.text + 'W'
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
    return time.time()

#1er lancement
update_data_last=update_data()

# Au lancement on allume ou on éteind si c'est la nuit ou pas...
if est_ce_la_nuit() == True:
    lcd.color = [0, 0, 0]
    etat_lcd=False
else:
    lcd.color = [100, 0, 0]
    etat_lcd=True
est_ce_la_nuit_last=time.time()

# Compteur si on force l'écran à On alors que c'est la nuit
force_lcd_on=False
while True:

    if lcd.right_button:
        debugTerm("Boutton Right : LCD control")
        force_lcd_on=False
        # Si c'est éteind on allume
        if etat_lcd == False:
            lcd.color = [100, 0, 0]
            etat_lcd = True
            debugTerm('LCD à ON')
            # Si c'est la nuit alors on met un timer pour l'extinction
            if est_ce_la_nuit() == True:
                force_lcd_on=lcd_on_timer
                debugTerm("Force LCD à ON pendant " + str(lcd_on_timer) + "s")
        # Si c'est allumé on éteind
        elif etat_lcd == True:
            lcd.color = [0, 0, 0]
            etat_lcd = False
            debugTerm('LCD à Off')
        time.sleep(1)

    elif lcd.left_button:
        debugTerm("Left : update data !")
        update_data_last=update_data()

    # ~ elif lcd.up_button:
        # ~ debugTerm("Up : update data !")
        # ~ update_data_last=update_data()

    # ~ elif lcd.down_button:
        # ~ debugTerm("Down : update data !")
        # ~ update_data_last=update_data()

    elif lcd.select_button:
        debugTerm("Select : L'heure SVP !")
        lcd.clear()
        lcd.message = time.strftime ('%m/%d/%Y %H:%M');
        time.sleep(5)
        update_data_last=update_data()

    else:
        time.sleep(rafraichissement)
        
        # Update data frequence
        update_data_time=update_data_last+data_update
        if update_data_time < time.time():
            update_data_last=update_data()
        
        # On se repose la question de la nuit...
        est_ce_la_nuit_time=est_ce_la_nuit_last+est_ce_la_nuit_timer
        if est_ce_la_nuit_time < time.time():
            debugTerm('c est la nuit ou bien ?')
            est_ce_la_nuit_last = time.time()
            if est_ce_la_nuit() == True:
                lcd.color = [0, 0, 0]
                etat_lcd=False
            else:
                lcd.color = [100, 0, 0]
                etat_lcd=True

        # Timer d'extinction si c'est la nuit
        if force_lcd_on != False:
            force_lcd_on=force_lcd_on-rafraichissement
        if force_lcd_on != False and force_lcd_on < 0:
            lcd.color = [0, 0, 0]
            etat_lcd = False
            force_lcd_on = False
            debugTerm("Extinction de l'écran au bout des" + str(lcd_on_timer) + "s c'est la nuit...")
    
    time.sleep(0.1)
    
# On supprime le fichier temporaire (pour la forme)
remove(tmp_data_file) 
