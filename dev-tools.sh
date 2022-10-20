#!/usr/bin/env bash

if [ -f .env ]
then
  export $(cat .env | sed 's/#.*//g' | xargs)
fi


title="Select a tool to execute: 1 => Static Analisys, 2 => Mess Detector, 3 => Code Sniffer, 4 => Reset Test Database, 5 => Coding standards in HTML, 6 => RunTest, 7 => Exit"

echo $title

# Operating system names are used here as a data source
select opt in Phpstan Mess PhpCs1 ResetTestDatabase PhpCs2 RunTest Exit
do

case $opt in
"Phpstan")
./vendor/bin/phpstan analyse > analisys.log
echo "Check the analisys on analisys.log"
break
;;
"Mess")
./vendor/bin/phpmd app text phpmd.xml > mess.log
echo "Check the mess analisys on mess.log"
break
;;
"PhpCs1")
./vendor/bin/phpcs -w --report-file=phpcs.log --standard=phpcs.xml
echo "Check the PHP Code Sniffer analisys on phpcs.log"
break
;;
"ResetTestDatabase")
./tests/install_db.sh
break
;;
"PhpCs2")
./vendor/bin/phpcs --generator=HTML app --standard=phpcs.xml > coding-standards.html
echo "Check coding-standards.html"
break
;;
"RunTest")
./vendor/bin/phpunit --stop-on-failure --stop-on-error
break
;;
"Exit")
echo "Bye"
break
;;
esac

echo $title
done
