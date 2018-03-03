#!/bin/bash
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

sudo killall easy-server &>/dev/null

sudo apt-get update

echo 5 > ${PROGRESS_FILE}
echo "--5%"
installVer='6'
minVer='v6'
testVer=`php -r "echo version_compare('${actual}','${minVer}','>=');"`
if [[ $testVer == "1" ]]
then
  echo "Ok, version suffisante";
  new=$actual
else
  echo 20 > ${PROGRESS_FILE}
  echo "--20%"
  echo "KO, version obsolète à upgrader";
  echo "Suppression du Nodejs existant et installation du paquet recommandé"
  type npm &>/dev/null
  if [ $? -eq 0 ]; then
    sudo npm rm -g homebridge-camera-ffmpeg --save
    sudo npm rm -g homebridge-jeedom --save
    sudo npm rm -g homebridge --save
    sudo npm rm -g request --save
    sudo npm rm -g node-gyp --save
    cd `npm root -g`;
    sudo npm rebuild
    npmPrefix=`npm prefix -g`
  else
    npmPrefix="/usr"
  fi
  sudo rm -f /usr/bin/homebridge &>/dev/null
  sudo rm -f /usr/local/bin/homebridge &>/dev/null
  
  sudo apt-get -y --purge autoremove nodejs npm
  echo 30 > ${PROGRESS_FILE}
  echo "--30%"
  
  if [[ $arch == "armv6l" ]]
  then
    echo "Raspberry 1 ou zéro détecté, utilisation du paquet pour ${arch}"
    wget https://nodejs.org/download/release/v6.9.5/node-v6.9.5-linux-${arch}.tar.gz
    tar -xvf node-v6.9.5-linux-${arch}.tar.gz
    cd node-v6.9.5-linux-${arch}
    sudo cp -R * /usr/local/
    cd ..
    rm -fR node-v6.9.5-linux-${arch}*
    #upgrade to recent npm
    sudo npm install -g npm
  else
    echo "Utilisation du dépot officiel"
    curl -sL https://deb.nodesource.com/setup_${installVer}.x | sudo -E bash -
    sudo apt-key update
    sudo apt-get install -y nodejs  
  fi
  
  npm config set prefix ${npmPrefix}

  new=`nodejs -v`;
  echo "Version actuelle : ${new}"
fi

echo 70 > ${PROGRESS_FILE}
echo "--70%"
echo "Installation de Nefit Easy HTTP Server"
sudo npm i nefit-easy-http-server -g

echo 100 > ${PROGRESS_FILE}
echo "--100%"
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}