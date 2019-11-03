/*
 * 
 * ht2000 CO2 meter
 * Based on https://github.com/tomvanbraeckel/slab_ht2000/blob/master/ht2000.c
 * 
 */

/* Linux */
#include <linux/types.h>
#include <linux/input.h>
#include <linux/hidraw.h>

/*
 * Ugly hack to work around failing compilation on systems that don't
 * yet populate new version of hidraw.h to userspace.
 */
#ifndef HIDIOCSFEATURE
#warning Please have your distro update the userspace kernel headers
#define HIDIOCSFEATURE(len)    _IOC(_IOC_WRITE|_IOC_READ, 'H', 0x06, len)
#define HIDIOCGFEATURE(len)    _IOC(_IOC_WRITE|_IOC_READ, 'H', 0x07, len)
#endif

/* Unix */
#include <sys/ioctl.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <unistd.h>

/* C */
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <errno.h>
#include <time.h>

const char *bus_str(int bus);

int main(int argc, char **argv)
{
	int fd;
	int i, res, desc_size = 0;
	char buf[256];
	unsigned char temp[2];
	unsigned char rh[2];
	unsigned char co2[2];
	unsigned char epoch[4];
	struct hidraw_report_descriptor rpt_desc;
	struct hidraw_devinfo info;
	char *device = "/dev/hidraw0";

	if (argc > 1) {
		device = argv[1];
	} else {
		puts("Usage: ht2000 path_to_hidraw_device");
		puts("Example: ht2000 /dev/hidraw0\n");
		puts("Output example: 1470923902, 11-08-2016 15:58:22, 25.700000, 52.700000, 1309.000000");
		puts("Output columns: epoch timestamp, human readable timestamp, temperature in degrees celsius, relative humidity in percent, CO2 level in PPM");
		return 1;
	}
	

	/* Open the Device with non-blocking reads. In real life,
	   don't use a hard coded path; use libudev instead. */
	fd = open(device, O_RDWR|O_NONBLOCK);

	if (fd < 0) {
		perror("Unable to open device");
		return 1;
	}

	memset(&rpt_desc, 0x0, sizeof(rpt_desc));
	memset(&info, 0x0, sizeof(info));
	memset(buf, 0x0, sizeof(buf));

	/* Set Feature */
	buf[0] = 0x5; /* Report Number */
	buf[1] = 0xff;
	buf[2] = 0xff;
	buf[3] = 0xff;
	res = ioctl(fd, HIDIOCSFEATURE(4), buf);
	if (res < 0)
		perror("HIDIOCSFEATURE");
	else {
		// Too much information...
		// printf("ioctl HIDIOCGFEATURE returned: %d\n", res);
	}

	/* Get Feature */
	buf[0] = 0x5; /* Report Number */
	res = ioctl(fd, HIDIOCGFEATURE(256), buf);
	if (res < 0) {
		perror("HIDIOCGFEATURE");
	} else {
		if (res >= 30) {
			memcpy(co2, buf+24, 2);
			double carbon = co2[0] * 256 + co2[1];
			printf("%.0lf\n", carbon);
		} else {
			puts("ERROR: report number 5 is too small so not all data is there...\n");
		}
	}

	close(fd);
	return 0;
}

const char *
bus_str(int bus)
{
	switch (bus) {
	case BUS_USB:
		return "USB";
		break;
	case BUS_HIL:
		return "HIL";
		break;
	case BUS_BLUETOOTH:
		return "Bluetooth";
		break;
	case BUS_VIRTUAL:
		return "Virtual";
		break;
	default:
		return "Other";
		break;
	}
}
