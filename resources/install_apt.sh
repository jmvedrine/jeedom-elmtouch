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
    sudo npm rebuild &>/dev/null
    npmPrefix=`npm prefix -g`
  else
    npmPrefix="/usr"
  fi
  sudo rm -f /usr/bin/esay-server &>/dev/null
  sudo rm -f /usr/local/bin/easy-server &>/dev/null
  sudo DEBIAN_FRONTEND=noninteractive apt-get -y --purge autoremove nodejs npm

  echo 30 > ${PROGRESS_FILE}
  echo "--30%"

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
    if [ -f /media/boot/multiboot/meson64_odroidc2.dtb.linux ]; then
      sudo DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs
    else
      echo "Utilisation du dépot officiel"
      curl -sL https://deb.nodesource.com/setup_${installVer}.x | sudo -E bash -
      sudo DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs  
    fi
  fi

  npm config set prefix ${npmPrefix}
  new=`nodejs -v`;
  echo "Version actuelle : ${new}"
fi

echo 70 > ${PROGRESS_FILE}
echo "--70%"
echo "Installation de Nefit Easy HTTP Server"
sudo npm install -g nefit-easy-http-server
serverversion=`easy-server -v`;
echo "Nefit Easy HTTP Server version ${serverversion} installé."
echo 100 > ${PROGRESS_FILE}
echo "--100%"
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}