#!/bin/bash
######################### INCLUSION LIB ##########################
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
wget https://raw.githubusercontent.com/NebzHB/dependance.lib/master/dependance.lib -O $BASEDIR/dependance.lib &>/dev/null
PLUGIN=$(basename "$(realpath $BASEDIR/..)")
. ${BASEDIR}/dependance.lib
##################################################################
wget https://raw.githubusercontent.com/NebzHB/nodejs_install/main/install_nodejs.sh -O $BASEDIR/install_nodejs.sh &>/dev/null

installVer='14' 	#NodeJS major version to be installed

pre
step 0 "Vérification des droits"
silent sudo killall easy-server
DIRECTORY="/var/www"
if [ ! -d "$DIRECTORY" ]; then
	silent sudo mkdir $DIRECTORY
fi
silent sudo chown -R www-data $DIRECTORY

step 5 "Mise à jour APT et installation des packages nécessaires"
try sudo apt-get update
try sudo DEBIAN_FRONTEND=noninteractive apt-get install -y lsb-release

#install nodejs, steps 10->50
. ${BASEDIR}/install_nodejs.sh ${installVer}

step 60 "Nettoyage anciens modules"
sudo npm ls -g --depth 0 2>/dev/null | grep "homebridge@" >/dev/null 
if [ $? -ne 1 ]; then
  echo "[Suppression easy global"
  silent sudo npm rm -g homebridge
fi
cd ${BASEDIR};
#remove old local modules
sudo rm -rf node_modules &>/dev/null
sudo rm -f package-lock.json &>/dev/null

step 70 "Installation de Nefit Easy Server, veuillez patienter svp"
#need to be sudoed because of recompil
silent sudo mkdir node_modules
silent sudo chown -R www-data:www-data .

sudo npm install -g nefit-easy-http-server
serverversion=`easy-server -v`;
step 80 "Nefit Easy HTTP Server version ${serverversion} installé."


silent sudo chown -R www-data:www-data .


post
