#!/bin/bash

echo " - - - -";
echo "Source $1";
echo "Destination $2";
echo " - - - - ";
touch "$2";
chown -R apache: "$2";


/usr/bin/java -jar /opt/eclipse-workspace/bcm_svn/trunk/apps/tika/tika-app-1.5.jar --text  "$1" > "$2";
/usr/bin/java -jar /opt/eclipse-workspace/bcm_svn/trunk/apps/tika/tika-app-1.5.jar --text  "$1" | tee "$2 __2";
/usr/bin/java -jar /opt/eclipse-workspace/bcm_svn/trunk/apps/tika/tika-app-1.5.jar --text  "$1" >&1 > "$2 __3";
#/usr/bin/java -jar /opt/eclipse-workspace/bcm_svn/trunk/apps/tika/tika-app-1.5.jar --text  "$1" ;


