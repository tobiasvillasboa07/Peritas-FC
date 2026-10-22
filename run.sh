#!/bin/sh
# Si hay una copia en el bucket, la baja y reemplaza la base del repo
litestream restore -config /etc/litestream.yml -if-replica-exists -o /tmp/restored.sqlite /app/database.sqlite
if [ -f /tmp/restored.sqlite ]; then
  mv /tmp/restored.sqlite /app/database.sqlite
fi
# Arranca la página y sube los cambios de la base al bucket
exec litestream replicate -config /etc/litestream.yml -exec "php -S 0.0.0.0:${PORT:-8000}"