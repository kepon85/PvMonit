#!/bin/bash

# A mettre avant le exit 0 dans le fichier rc.local

# Corrige le problème de droit de changement de user sur lighttpd

chown -R pvmonit:pvmonit /var/log/lighttpd
chown -R pvmonit:pvmonit /var/cache/lighttpd
chown pvmonit:pvmonit /var/run/lighttpd
/etc/init.d/lighttpd restart

