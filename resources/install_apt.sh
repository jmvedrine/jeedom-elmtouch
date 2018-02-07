PROGRESS_FILE=/tmp/dependancy_elmtouch_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
sudo apt-get update
pip install pyaes
echo 20 > ${PROGRESS_FILE}
pip install sleekxmpp
echo 35 > ${PROGRESS_FILE}
echo "Installation de nefit-client-python"
echo 50 > ${PROGRESS_FILE}
cd "$(dirname "$0")/elmtouchd"
sudo rm -R nefit-client-python
sudo git clone --depth 1 https://github.com/patvdleer/nefit-client-python.git
cd nefit-client-python
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}