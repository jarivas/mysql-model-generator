#!/usr/bin/env bash

cd /projects/workspace

# use .env vars
if [ ! -f .env ]
then
    ls -l
  echo ".env not found \n"
  exit 1
fi

export $(cat .env | sed 's/#.*//g' | xargs)

cd tests

if [ ! -d world-db ]
then
    wget https://downloads.mysql.com/docs/world-db.tar.gz
    tar -xf world-db.tar.gz
    rm world-db.tar.gz
fi

cd world-db
mysql -u root -h localhost < world.sql