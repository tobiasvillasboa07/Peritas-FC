#!/bin/sh
# Limpia espacios y caracteres invisibles de las claves
export LITESTREAM_ACCESS_KEY_ID="$(printf '%s' "$LITESTREAM_ACCESS_KEY_ID" | tr -cd '[:graph:]')"
export LITESTREAM_SECRET_ACCESS_KEY="$(printf '%s' "$LITESTREAM_SECRET_ACCESS_KEY" | tr -cd '[:graph:]')"
# Si hay una copia en el bucket, la baja
litestream restore -config /etc/litestream.yml -if-db-not-exists -if-replica-exists /app/database.sqlite
# Si no hay base (primera vez), la crea desde schema.sql
if [ ! -f /app/database.sqlite ]; then
  php -r '$p = new PDO("sqlite:/app/database.sqlite"); $p->exec(file_get_contents("/app/schema.sql"));'
fi
# Da rol admin a la cuenta indicada en ADMIN_EMAIL (si esa cuenta existe)
if [ -n "$ADMIN_EMAIL" ]; then
  php -r '$p = new PDO("sqlite:/app/database.sqlite"); $s = $p->prepare("UPDATE users SET role = ? WHERE lower(email) = lower(?)"); $s->execute(["admin", getenv("ADMIN_EMAIL")]);'
fi
# Arranca la página y sube los cambios de la base al bucket
exec litestream replicate -config /etc/litestream.yml -exec "php -S 0.0.0.0:${PORT:-8000}"