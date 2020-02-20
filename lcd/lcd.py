# https://learn.adafruit.com/adafruit-16x2-character-lcd-plus-keypad-for-raspberry-pi/python-usage
# https://circuitpython.readthedocs.io/projects/charlcd/en/latest/examples.html
import time
import board
import busio
import yaml
import adafruit_character_lcd.character_lcd_rgb_i2c as character_lcd
from lxml import etree
from urllib.request import urlopen
from past.builtins import execfile
import os
import re
import pprint
from pwd import getpwnam  
import grp

with open('/opt/PvMonit/config-default.yaml') as f1:
    config = yaml.load(f1, Loader=yaml.FullLoader)
with open('/opt/PvMonit/config.yaml') as f2:
    config_perso = yaml.load(f2, Loader=yaml.FullLoader)

def configGet(key1, key2=None, key3=None, key4=None):
    if key4 != None:
        try:
            return config_perso[key1][key2][key3][key4]
        except:
            return config[key1][key2][key3][key4]
    elif key3 != None:
        try:
            return config_perso[key1][key2][key3]
        except:
            return config[key1][key2][key3]
    elif key2 != None:
        try:
            return config_perso[key1][key2]
        except:
            return config[key1][key2]
    else:
        try:
            return config_perso[key1]
        except:
            return config[key1]

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
lcd.color = [100, 100, 100]
# on dit bonjour (si si)
lcd.message = "Bonjour !\nOn boot ..."
# ~ time.sleep(2)

# Fonction de debug
def debugTerm(msg) :
    print(time.strftime ('%d/%m/%Y %H:%M'),' : ',msg)

# Détection du dodo (pour extinction de l'écran)
def est_ce_la_nuit() :
    if int(time.strftime ('%H')) >= configGet('lcd','offAt') or int(time.strftime ('%H')) <= configGet('lcd','onAt'):
        return True
    else:
        return False

def print_wait():
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

def download_data():
    # téléchargement des données
    if not os.path.isfile(configGet('tmpFileDataXml')) or os.path.getctime(configGet('tmpFileDataXml'))+configGet('lcd', 'dataUpdate') < time.time():
        debugTerm('Download data')
        with open(configGet('tmpFileDataXml'), 'wb') as tmpxml:
            tmpxml.write(urlopen(configGet('urlDataXml')).read())
            os.chown(configGet('tmpFileDataXml'), getpwnam('pvmonit').pw_uid, grp.getgrnam('pvmonit')[2]) 
    else:
        debugTerm('Pas de download, le fichier temporaire est déjà frais..')
    return time.time()

def update_menu(number):
    print_wait()
    debugTerm('Update du menu '+str(number))
    execfile(configGet('dir','lcd') + "Menu" + str(number) + ".py")
    return time.time()


# Au lancement on allume ou on éteind si c'est la nuit ou pas...
if est_ce_la_nuit() == True:
    lcd.color = [0, 0, 0]
    etat_lcd=False
else:
    lcd.color = [100, 0, 0]
    etat_lcd=True
est_ce_la_nuit_last=time.time()

#1er lancement
download_data_last=download_data()
update_data_last=update_menu(0)

# Nombre de menu 
patternMenu = re.compile(r"Menu[0-9]+.py")
files = os.listdir(configGet('dir','lcd'))
nbMenu=0
for name in files:
    if patternMenu.match(name):
        nbMenu=nbMenu+1
nbMenu=nbMenu-1 # pour le 0...

# Compteur si on force l'écran à On alors que c'est la nuit
force_lcd_on=False
menuEnCours=0
while True:

    if lcd.right_button:
        debugTerm("Boutton Right : LCD control")
        force_lcd_on=False
        # Si c'est éteind on allume
        if etat_lcd == False:
            lcd.color = [100, 100, 100]
            etat_lcd = True
            debugTerm('LCD à ON')
            # Si c'est la nuit alors on met un timer pour l'extinction
            if est_ce_la_nuit() == True:
                force_lcd_on=configGet('lcd','onTimer')
                debugTerm("Force LCD à ON pendant " + str(configGet('lcd','onTimer')) + "s")
        # Si c'est allumé on éteind
        elif etat_lcd == True:
            lcd.color = [0, 0, 0]
            etat_lcd = False
            debugTerm('LCD à Off')
        time.sleep(1)

    elif lcd.left_button:
        debugTerm("Left : update data !")
        download_data_last=download_data()
        update_data_last=update_menu(menuEnCours)

    elif lcd.up_button:
        menuEnCours=menuEnCours+1
        if menuEnCours > nbMenu:
            menuEnCours=0
        debugTerm("Up : Menu +, vers ")
        debugTerm(menuEnCours)
        update_data_last=update_menu(menuEnCours)
        # Evite le rebond
        time.sleep(0.5)
    elif lcd.down_button:
        menuEnCours=menuEnCours-1
        if menuEnCours < 0:
            menuEnCours=nbMenu
        debugTerm("Down : Menu -, vers ")
        debugTerm(menuEnCours)
        update_data_last=update_menu(menuEnCours)
        # Evite le rebond
        time.sleep(0.5)
    elif lcd.select_button:
        debugTerm("Select : Update data")
        download_data_last=download_data()
        update_data_last=update_menu(menuEnCours)
    else:
        time.sleep(configGet('lcd','rafraichissement'))
                
        download_data_time=download_data_last+configGet('lcd','dataUpdate')
        if download_data_time < time.time():
                download_data_last=download_data()

        # Update data frequence
        update_data_time=update_data_last+configGet('lcd','dataUpdate')
        if update_data_time < time.time():
            debugTerm('Refresh data')
            update_data_last=update_menu(menuEnCours)
        
        # On se repose la question de la nuit...
        est_ce_la_nuit_time=est_ce_la_nuit_last+configGet('lcd','estCeLaNuitTimer')
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
            force_lcd_on=force_lcd_on-configGet('lcd','rafraichissement')
        if force_lcd_on != False and force_lcd_on < 0:
            lcd.color = [0, 0, 0]
            etat_lcd = False
            force_lcd_on = False
            debugTerm("Extinction de l'écran au bout des" + str(configGet('lcd','onTimer')) + "s c'est la nuit...")
    
    time.sleep(0.1)
    
# On supprime le fichier temporaire (pour la forme)
remove(configGet('tmpFileDataXml')) 
