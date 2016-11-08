#!/usr/bin/perl
use Device::SerialPort;
my $file = "/dev/ttyACM0";
my $port = Device::SerialPort -> new($file);
$port -> baudrate(19200);
$port->write_settings();
open(DEV, "<$file") or die;
$port->write(); 
if ($_ = <DEV>) { print $_ ; }

