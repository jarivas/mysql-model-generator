#!/usr/bin/env bash

service mysql start

mysql -u root -e "create user if not exists '${MYSQL_USER}'@'%' identified WITH mysql_native_password by '${MYSQL_PASSWORD}'"
mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO'${MYSQL_USER}'@'%'"
mysql -u root -e "FLUSH PRIVILEGES"

echo "User created"

cp .env.example .env

if [ -f .git/hooks/pre-commit ]
then
  rm .git/hooks/pre-commit
fi

cp pre-commit-hook .git/hooks/pre-commit

chmod +x .git/hooks/pre-commit

while true; do sleep 1; done
