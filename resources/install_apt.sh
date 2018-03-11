#!/bin/bash
installVer='8' 	#NodeJS major version to be installed
minVer='8'	#min NodeJS major version to be accepted

PROGRESS_FILE=/tmp/dependancy_elmtouch_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
echo 0 > ${PROGRESS_FILE}
echo "--0%"
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
DIRECTORY="/var/www"
if [ ! -d "$DIRECTORY" ]; then
  echo "Création du home www-data pour npm"
  sudo mkdir $DIRECTORY
fi
sudo chown -R www-data $DIRECTORY

sudo killall easy-server &>/dev/null

sudo apt-get update

echo 5 > ${PROGRESS_FILE}
echo "--5%"
type nodejs &>/dev/null
if [ $? -eq 0 ]; then actual=`nodejs -v`; fi
echo "Version actuelle : ${actual}"
arch=`arch`;
testVer=`php -r "echo version_compare('${actual}','v${minVer}','>=');"`
if [[ $testVer == "1" ]]
then
  echo "Ok, version suffisante";
  new=$actual
else
  echo 20 > ${PROGRESS_FILE}
  echo "--20%"
  echo "KO, version obsolète à upgrader";
  echo "Suppression du Nodejs existant et installation du paquet recommandé"
  #if npm exists
  type npm &>/dev/null
  if [ $? -eq 0 ]; then
    sudo npm rm -g nefit-easy-http-server --save
    cd `npm root -g`;
    sudo npm rebuild &>/dev/null
    npmPrefix=`npm prefix -g`
  else
    npmPrefix="/usr"
  fi
  sudo apt-get -y --purge autoremove nodejs npm
  
  echo 45 > ${PROGRESS_FILE}
  echo "--45%"
  if [[ $arch == "armv6l" ]]
  then
    echo "Raspberry 1, 2 ou zéro détecté, utilisation du paquet v${installVer} pour ${arch}"
    wget https://nodejs.org/download/release/latest-v${installVer}.x/node-*-linux-${arch}.tar.gz
    tar -xvf node-*-linux-${arch}.tar.gz
    cd node-*-linux-${arch}
    sudo cp -R * /usr/local/
    cd ..
    rm -fR node-*-linux-${arch}*
    #upgrade to recent npm
    sudo npm install -g npm
  else
    echo "Utilisation du dépot officiel"
    curl -sL https://deb.nodesource.com/setup_${installVer}.x | sudo -E bash -
    wget --quiet -O - https://deb.nodesource.com/gpgkey/nodesource.gpg.key | sudo apt-key add -
    sudo apt-get install -y nodejs  
  fi
  
  npm config set prefix ${npmPrefix}

  new=`nodejs -v`;
  echo "Version actuelle : ${new}"
fi

echo 70 > ${PROGRESS_FILE}
echo "--70%"
# Remove old globals
sudo rm -f /usr/bin/easy-server &>/dev/null
sudo rm -f /usr/local/bin/easy-server &>/dev/null
sudo npm rm -g easy-server --save &>/dev/null
cd `npm root -g`;
sudo npm rebuild &>/dev/null
cd ${BASEDIR};
#remove old local modules
sudo rm -rf node_modules
echo "Installation de Nefit Easy HTTP Server"
sudo npm i robertklep/nefit-easy-http-server

echo 100 > ${PROGRESS_FILE}
echo "--100%"
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}