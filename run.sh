#!/bin/sh
# Limpia espacios y caracteres invisibles de las claves
export LITESTREAM_ACCESS_KEY_ID="$(printf '%s' "$LITESTREAM_ACCESS_KEY_ID" | tr -cd '[:graph:]')"
export LITESTREAM_SECRET_ACCESS_KEY="$(printf '%s' "$LITESTREAM_SECRET_ACCESS_KEY" | tr -cd '[:graph:]')"
# Muestra solo el largo de las claves (no su contenido) para diagnosticar
echo "largo keyID: ${#LITESTREAM_ACCESS_KEY_ID} / largo applicationKey: ${#LITESTREAM_SECRET_ACCESS_KEY}"
# Si hay una copia en el bucket, la baja
litestream restore -config /etc/litestream.yml -if-db-not-exists -if-replica-exists /app/database.sqlite
# Si no hay base (primera vez), la crea desde schema.sql
if [ ! -f /app/database.sqlite ]; then
  php -r '$p = new PDO("sqlite:/app/database.sqlite"); $p->exec(file_get_contents("/app/schema.sql"));'
fi
# Arranca la página y sube los cambios de la base al bucket
exec litestream replicate -config /etc/litestream.yml -exec "php -S 0.0.0.0:${PORT:-8000}"