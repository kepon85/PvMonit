import time
import board
import sys
from adafruit_ina260 import INA260, AveragingCount, Mode

ICAL=0.0082                  # Coeficient de calibrage en foncction du shunt utilisé (a determiner pas l'utilisateur ou a l'aide d'une pince ampermetrique calibrée)
VCAL=1.009                  # Correction/calibration voltage

i2c = board.I2C()
ina260 = INA260(i2c)

# Raise the averaging count to a larger number to smooth out the results
#ina260.averaging_count = AveragingCount.COUNT_1
# ina260.averaging_count = AveragingCount.COUNT_4
ina260.averaging_count = AveragingCount.COUNT_16
ina260.mode = Mode.CONTINUOUS
#ina260.mode = Mode.TRIGGERED

while True:
    print ('{', end='')
    print ('"A": ' + str(ina260.current*ICAL) + ', ', end='')
    print ('"V": ' + str(ina260.voltage*VCAL), end='')
    print ('}')
#    time.sleep(1)
    sys.exit()


