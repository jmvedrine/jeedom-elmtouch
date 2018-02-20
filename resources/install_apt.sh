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
type nodejs &>/dev/null
if [ $? -eq 0 ]; then actual=`nodejs -v`; fi
echo "Version actuelle : ${actual}"
arch=`arch`;

#if [[ $actual == *"4."* || $actual == *"5."*  || $actual == *"6."* || $actual == *"8."* || $actual == *"10."* ]]
minVer='v5'
testVer=`php -r "echo version_compare('${actual}','${minVer}','>=');"`
if [[ $testVer == "1" ]]
then
  echo "Ok, version suffisante";
else
  echo 20 > ${PROGRESS_FILE}
  echo "--20%"
  echo "KO, version obsolète à upgrader";
  echo "Suppression du Nodejs existant et installation du paquet recommandé"
  type npm &>/dev/null
  if [ $? -eq 0 ]; then
    sudo npm rm -g nefit-easy-http-server --save
    cd `npm root -g`;
    sudo npm rebuild
  fi
  sudo rm -f /usr/bin/homebridge &>/dev/null
  sudo rm -f /usr/local/bin/homebridge &>/dev/null
  
  sudo apt-get -y --purge autoremove nodejs npm
  echo 30 > ${PROGRESS_FILE}
  echo "--30%"
  
  if [[ $arch == "armv6l" ]]
  then
    echo "Raspberry 1 détecté, utilisation du paquet pour armv6l"
    sudo rm -f /etc/apt/sources.list.d/nodesource.list &>/dev/null
    wget http://node-arm.herokuapp.com/node_latest_armhf.deb
    sudo dpkg -i node_latest_armhf.deb
    sudo ln -s /usr/local/bin/node /usr/local/bin/nodejs
    rm node_latest_armhf.deb
    
    #echo "Raspberry zéro détecté, utilisation du paquet pour armv6l"
    #wget https://nodejs.org/dist/v5.12.0/node-v5.12.0-linux-armv6l.tar.gz
    #tar -xvf node-v5.12.0-linux-armv6l.tar.gz
    #cd node-v5.12.0-linux-armv6l
    #sudo cp -R * /usr/local/
    #cd ..
    #rm -fR node-v5.12.0-linux-armv6l/
    #rm -f node-v5.12.0-linux-armv6l.tar.gz
    #upgrade to recent npm
    #sudo npm install -g npm
  fi
  
  if [[ $arch == "aarch64" ]]
  then
    echo "Utilisation du dépot exotique car paquet officiel non existant en V5"
    sudo rm -f /etc/apt/sources.list.d/nodesource.list &>/dev/null
    wget http://dietpi.com/downloads/binaries/c2/nodejs_5-1_arm64.deb
    sudo dpkg -i nodejs_5-1_arm64.deb
    sudo ln -s /usr/local/bin/node /usr/local/bin/nodejs &>/dev/null
    rm nodejs_5-1_arm64.deb
  fi
  
  if [[ $arch != "aarch64" && $arch != "armv6l" ]]
  then
    echo "Utilisation du dépot officiel"
    curl -sL https://deb.nodesource.com/setup_5.x | sudo -E bash -
    sudo apt-key update
    sudo apt-get install -y nodejs  
  fi
#  echo "Utilisation du dépot officiel"
#  curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
#  sudo apt-key update
#  sudo apt-get install -y nodejs  

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