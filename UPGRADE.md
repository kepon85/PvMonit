# Upgrade

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

