import time
import board
import sys
from adafruit_ina260 import INA260, AveragingCount, Mode


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
    print ('"A": ' + str(ina260.current*0.008) + ', ', end='')
    print ('"V": ' + str(ina260.voltage), end='')
    print ('}')
#    time.sleep(1)
    sys.exit()
