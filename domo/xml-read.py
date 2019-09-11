from lxml import etree
from urllib.request import urlopen


[smtp]
subscriber[] = aaa@hotmail.com
subscriber[] = bbb@XX.webmail
subscriber[] = ccc@test.org

# fichier de conf : 
#https://docs.python.org/3/library/configparser.html

url = 'http://192.168.1.1/data-xml.php'
with open('/tmp/pvmonit-data-xml.php.tmp', 'wb') as tmpxml:
    tmpxml.write(urlopen(url).read())

dataprint = ['SOC', 'P', 'PPVT', 'CONSO']
datatotal = len(dataprint)

datacount = 0
tree = etree.parse("/tmp/pvmonit-data-xml.php.tmp")
for datas in tree.xpath("/devices/device/datas/data"):
    if datas.get("id") in dataprint:
        datacount = datacount + 1
        #print(datas.get("id"))
        for data in datas.getchildren():
            if data.tag == "value":
                if datas.get("id") == 'SOC':
                    print(data.text + '%')
                    if float(data.text) > 95:
                        print('vert !!!')
                    elif float(data.text) > 90:
                        print('orange !!!')
                    elif float(data.text) < 90:
                        print('rouge !!!')
                elif datas.get("id") == 'P':
                    print(data.text + 'W')
                elif datas.get("id") == 'PPVT':
                    print(data.text + 'W')
                elif datas.get("id") == 'CONSO':
                    print(data.text + 'W')
#if datacount != datatotal:
#    print('Erreur sur quelques valeurs..')
