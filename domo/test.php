<?php

include('./PhpSerial.php');
    // Let's start the class
    $serial = new phpSerial();

    // First we must specify the device. This works on both Linux and Windows (if
    // your Linux serial device is /dev/ttyS0 for COM1, etc.)
    $serial->deviceSet("/dev/ttyUSB0");

    // Set for 9600-8-N-1 (no flow control)
    $serial->confBaudRate(9600); //Baud rate: 9600
    $serial->confParity("none");  //Parity (this is the "N" in "8-N-1")
    $serial->confCharacterLength(8); //Character length     (this is the "8" in "8-N-1")
    $serial->confStopBits(1);  //Stop bits (this is the "1" in "8-N-1")
    $serial->confFlowControl("none");

    // Then we need to open it
    $serial->deviceOpen();
while (true) {
    // Read data
    $read = $serial->readPort();

    // Print out the data
    echo $read;
        // print exec("echo 'r9g9b9' > /dev/ttyACM0");
    sleep(0.5);
}
    // If you want to change the configuration, the device must be closed.
    $serial->deviceClose();
    
    ?>
