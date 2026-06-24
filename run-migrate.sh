#!/bin/bash
# Applica SOLO le migrazioni pendenti (le 3 nuove tabelle dei Gruppi) al MySQL Aruba.
# Additivo e sicuro: solo CREATE TABLE. Cancella questo file dopo l'uso (contiene la password).
cd /Users/alfonso/nutrimente/nutrimente || { echo "Cartella progetto non trovata"; exit 1; }

DB_CONNECTION=mysql \
DB_HOST=31.11.39.220 \
DB_PORT=3306 \
DB_DATABASE=Sql1945528_1 \
DB_USERNAME=Sql1945528 \
DB_PASSWORD='KirettA1606!' \
php artisan migrate --force
