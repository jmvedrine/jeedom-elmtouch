#!/bin/bash
PROGRESS_FILE=/tmp/dependancy_elmtouch_in_progress

touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "--0%"
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
DIRECTORY="/var/www"
if [ ! -d "$DIRECTORY" ]; then
  echo "Création du home www-data pour npm"
  sudo mkdir $DIRECTORY
fi
sudo chown -R www-data $DIRECTORY

echo 5 > ${PROGRESS_FILE}
echo "--5%"
echo "Lancement de l'installation/mise à jour des dépendances elmtouch"
sudo killall easy-server &>/dev/null

sudo apt-get update

echo 10 > ${PROGRESS_FILE}
echo "--10%"
type nodejs &>/dev/null
if [ $? -eq 0 ]; then actual=`nodejs -v`; fi
echo "Version actuelle : ${actual}"
arch=`arch`;

if [[ $actual == "v8."* || $actual == "v9."* || $actual == "v10."* ]]
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
  sudo rm -f /usr/bin/esay-server &>/dev/null
  sudo rm -f /usr/local/bin/easy-server &>/dev/null
  
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
    
  else
    echo "Utilisation du dépot officiel"
    curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
    sudo apt-key update
    sudo apt-get install -y nodejs  
  fi

  new=`nodejs -v`;
  echo "Version actuelle : ${new}"
fi

echo 70 > ${PROGRESS_FILE}
echo "--70%"
echo "Installation de Nefit Easy HTTP Server"
sudo npm install -g nefit-easy-http-server

echo 100 > ${PROGRESS_FILE}
echo "--100%"
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}