PROGRESS_FILE=/tmp/dependancy_elmtouch_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
echo $1
echo $0
sudo apt-get update
pip uninstall pyaes -y
echo 20 > ${PROGRESS_FILE}
pip uninstall sleekxmpp -y
echo 35 > ${PROGRESS_FILE}

echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}