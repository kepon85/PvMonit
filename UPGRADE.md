# Upgrade

Un upgrade se passe simplement en remplaçant les fichiers : 

```bash
cd /opt
# sauvegarde fichier de conf
cp PvMonit/config.yaml ./
rm -rf ./PvMonit
git clone https://github.com/kepon85/PvMonit.git
# restauration fichier de conf
cp ./config.yaml ./PvMonit/
```

## Upgrade V1.0 > V2.0

Le principale changement réside dans le fait que lighttpd (le serveur web) est lancé avec l'utilisateur "pvmonit" et non plus "www-data" par défaut. : 

Pour cela  :

```bash
service lighttpd stop
```

Configuration du serveur http, avec le fichier /etc/lighttpd/lighttpd.conf 

```diff
- server.username             = "www-data"
- server.groupname            = "www-data"
+ server.username             = "pvmonit"
+ server.groupname            = "pvmonit"
```

Modifier le fichier /etc/init.d/lighttpd et remplacer tout "www-data" par "pvmonit"

```diff
-     owner=www-data
-     group=www-data
+     owner=pvmonit
+     group=pvmonit
```

Changer les utilisateurs : 

```
chown -R pvmonit:pvmonit /var/log/lighttpd
chown -R pvmonit:pvmonit /var/cache/lighttpd
chown pvmonit:pvmonit /var/run/lighttpd
```

On applique la configuration (si lighttpd ne démarre pas il faut creuser la question, regarder les logs...) :

```bash
service lighttpd start
```

Plus de doc sur le changement de user : [ici](https://alexanderhoughton.co.uk/blog/lighttpd-changing-default-user-raspberry-pi/) ou [ici](https://redmine.lighttpd.net/boards/2/topics/6247)

## Upgrade V2.0 > V2.1

* Retirer les crontab getForEmoncms et getForEmoncms de l'utilisateur pvmonit (crontab -u pvmonit -e)
* Retirer le contenu commenançant par la commande "screen" du fichier /etc/rc.local

Ajouter dans votre fichier de config.yaml tout les daemons que vous souhaitez lancer en ajoutant "daemon: true" (voir le fichier config-default.yaml pour comprendre

```
emoncms:
    daemon: true
```

+ systemd
/etc/systemd/system/pvmonit.service

systemctl enable pvmonit
systemctl start pvmonit

