<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class elmtouch extends eqLogic {
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */
    public static function deamon_info() {
        $return = [];
        $return['log'] = 'elmtouch';
        $return['state'] = 'nok';

        $result = exec("ps -eo pid,command | grep 'easy-server' | grep -v grep | awk '{print $1}'");
        if ($result <> 0) {
            $return['state'] = 'ok';
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start() {
        if(log::getLogLevel('elmtouch')==100) $_debug=true;
        // log::add('elmtouch', 'debug', 'logLevel : ' . log::getLogLevel('elmtouch'));
        // log::add('elmtouch', 'info', 'Mode debug : ' . $_debug);
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        $serial = config::byKey('serialNumber','elmtouch');
        $access = config::byKey('accessKey','elmtouch');
        $password = config::byKey('password','elmtouch');
        $easyserver = ' easy-server --serial=' . $serial . ' --access-key=' . $access . ' --password=' . $password;
        // check easy-server started, if not, start
        $cmd = 'if [ $(ps -ef | grep -v grep | grep "easy-server" | wc -l) -eq 0 ]; then ' . system::getCmdSudo() . $easyserver . ';echo "Démarrage easy-server";sleep 1; fi';
        // log::add('elmtouch', 'debug', $cmd);
        if ($_debug) {
            exec($cmd . ' >> ' . log::getPathToLog('elmtouch') . ' 2>&1 &');
        } else {
            $result = exec($cmd . ' > /dev/null 2>&1 &');
        }

        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add('elmtouch', 'error', 'Impossible de lancer le démon Elm Touch, relancez le démon en debug et vérifiez la log', 'unableStartDeamon');
            return false;
        }
        message::removeAll('elmtouch', 'unableStartDeamon');
        log::add('elmtouch', 'info', 'Démon Elm Touch lancé');

        return true;
    }

    public static function deamon_stop() {
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] <> 'ok') {
            return true;
        }

        $pid = exec("ps -eo pid,command | grep 'easy-server' | grep -v grep | awk '{print $1}'");
        // log::add('elmtouch', 'debug', 'pid=' . $pid);

        if ($pid) {
            system::kill($pid);
        }
        system::kill('easy-server');
        system::fuserk(3000);

        $check = self::deamon_info();
        $retry = 0;
        while ($deamon_info['state'] == 'ok') {
           $retry++;
            if ($retry > 10) {
                return;
            } else {
                sleep(1);
            }
        }
        return self::deamon_info();
    }

    public static function cron() {
        foreach (self::byType('elmtouch') as $elmtouch) {
            $cron_isEnable = $elmtouch->getConfiguration('cron_isEnable', 0);
            $autorefresh = $elmtouch->getConfiguration('autorefresh', '');
            $serial = config::byKey('serialNumber','elmtouch');
            $access = config::byKey('accessKey','elmtouch');
            $password = config::byKey('password','elmtouch');
            if ($elmtouch->getIsEnable() == 1 && $cron_isEnable == 1 && $serial != '' && $access != '' && $password != '' && $autorefresh != '') {
                try {
                    $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                    if ($c->isDue()) {
                        try {
                            $elmtouch->getThermostatStatus();
                            $elmtouch->getOutdoorTemp();
                            $elmtouch->getActualSupplyTemp();
                            $elmtouch->refreshWidget();
                        } catch (Exception $exc) {
                            log::add('elmtouch', 'error', __('Error in ', __FILE__) . $elmtouch->getHumanName() . ' : ' . $exc->getMessage());
                        }
                    }
                } catch (Exception $exc) {
                    log::add('elmtouch', 'error', __('Expression cron non valide pour ', __FILE__) . $elmtouch->getHumanName() . ' : ' . $autorefresh);
                }
            }
        }
    }

    public static function cron15() {
        log::add('elmtouch', 'debug', 'Début cron15');
        foreach (self::byType('elmtouch') as $elmtouch) {
            if (floatval($elmtouch->getConfiguration('convkwhm3', 0)) == 0 || floatval($elmtouch->getConfiguration('prixgazkwh', 0)) == 0) {
                // Si les coefficients ne sont pas rentrés, on ne fait rien.
                return;
            }
            $cache = cache::byKey('elmtouch::lastgaspage::'.$elmtouch->getId());
            $page = $cache->getValue();
            log::add('elmtouch', 'debug', 'page = ' . $page);
            if ($page == 8400) {
                // Plus rien à faire.
                log::add('elmtouch', 'debug', 'Récupération terminée');
                return;
            }
            $page++;
            $lastPage = $elmtouch->getGasLastPage();
            log::add('elmtouch', 'debug', 'lastPage = ' . $lastPage);
            if ($page > $lastPage) {
                // Job terminé.
                log::add('elmtouch', 'debug', 'Fini !');
                cache::set('elmtouch::lastgaspage::'.$elmtouch->getId(), 8400, 0);
            } else {
                log::add('elmtouch', 'debug', 'On récupère la page ' . $page);
                $result = $elmtouch->getGasPage($page);
                if ($result > 0) {
                    cache::set('elmtouch::lastgaspage::'.$elmtouch->getId(), $page, 0);
                }
            }
        }
        log::add('elmtouch', 'debug', 'Fin cron15');
    }

    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'elmtouch_update';
        $return['progress_file'] = jeedom::getTmpFolder('elmtouch') . '/dependance';
        if (shell_exec('ls /usr/bin/easy-server 2>/dev/null | wc -l') == 1 || shell_exec('ls /usr/local/bin/easy-server 2>/dev/null | wc -l') == 1) {
            $state = 'ok';
        }else{
            $state = 'nok';
        }
        $return['state'] = $state;
        return $return;
    }

    public static function dependancy_install() {
        log::remove(__CLASS__ . '_update');
        return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('elmtouch') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
    }

    public static function getGasDaily() {
        foreach (self::byType('elmtouch') as $elmtouch) {
            log::add('elmtouch', 'info', 'Debut de récupération des consommations journalières');
            if ($elmtouch->getIsEnable() == 1) {
                $result = $elmtouch->getGasConsommation();
            }
            log::add('elmtouch', 'info', 'Fin de récupération des consommations journalières');
        }
    }

    /*
     * Fonction pour permettre de relancer la récupération de tout l'historique.
     */
    public static function resetHistory() {
        foreach (self::byType('elmtouch') as $elmtouch) {
            cache::set('elmtouch::lastgaspage::'.$elmtouch->getId(), 0, 0);
        }
    }

    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
        $this->setConfiguration('convkwhm3', 8.125);
        $this->setConfiguration('prixgazkwh', 0.05);
    }

    public function postInsert() {
        // Récupérer les consos à partir de la page 1.
        self::resetHistory();
    }

    public function preSave() {
        if ($this->getConfiguration('convkwhm3') == '') {
            $this->setConfiguration('convkwhm3', 8.125);
        }
        if ($this->getConfiguration('prixgazkwh') == '') {
            $this->setConfiguration('prixgazkwh', 0.05);
        }
        if ($this->getConfiguration('autorefresh') == '') {
            $this->setConfiguration('autorefresh', '*/5 * * * *');
        }
        if ($this->getConfiguration('cron_isEnable',"initial") == 'initial') {
            $this->setConfiguration('cron_isEnable', 1);
        }
        // Force la categorie "chauffage".
        $this->setCategory('heating', 1);
    }

    public function postSave() {
        if ($this->getIsEnable() == 1) {
            // Température de consigne (info).
            $order = $this->getCmd(null, 'order');
            if (!is_object($order)) {
                $order = new elmtouchCmd();
                $order->setIsVisible(0);
                $order->setUnite('°C');
                $order->setName(__('Consigne', __FILE__));
                $order->setConfiguration('historizeMode', 'none');
                $order->setIsHistorized(1);
            }
            $order->setDisplay('generic_type', 'THERMOSTAT_SETPOINT');
            $order->setEqLogic_id($this->getId());
            $order->setType('info');
            $order->setSubType('numeric');
            $order->setLogicalId('order');
            $order->setConfiguration('maxValue', 30);
            $order->setConfiguration('minValue', 5);
            $order->save();

            // Température de consigne (action)
            $thermostat = $this->getCmd(null, 'thermostat');
            if (!is_object($thermostat)) {
                $thermostat = new elmtouchCmd();
                $thermostat->setTemplate('dashboard', 'thermostat');
                $thermostat->setTemplate('mobile', 'thermostat');
                $thermostat->setUnite('°C');
                $thermostat->setName(__('Thermostat', __FILE__));
                $thermostat->setIsVisible(1);
            }
            $thermostat->setDisplay('generic_type', 'THERMOSTAT_SET_SETPOINT');
            $thermostat->setEqLogic_id($this->getId());
            $thermostat->setConfiguration('maxValue', 30);
            $thermostat->setConfiguration('minValue', 5);
            $thermostat->setType('action');
            $thermostat->setSubType('slider');
            $thermostat->setLogicalId('thermostat');
            $thermostat->setValue($order->getId());
            $thermostat->save();

            // Température de la pièce.
            $temperature = $this->getCmd(null, 'temperature');
            if (!is_object($temperature)) {
                $temperature = new elmtouchCmd();
                $temperature->setTemplate('dashboard', 'line');
                $temperature->setTemplate('mobile', 'line');
                $temperature->setName(__('Température', __FILE__));
                $temperature->setIsVisible(1);
                $temperature->setIsHistorized(1);
            }
            $temperature->setEqLogic_id($this->getId());
            $temperature->setType('info');
            $temperature->setSubType('numeric');
            $temperature->setLogicalId('temperature');
            $temperature->setUnite('°C');
            $temperature->setDisplay('generic_type', 'THERMOSTAT_TEMPERATURE');
            $temperature->save();

            // User mode.
            $clockmode = $this->getCmd(null, 'clockmode');
            if (!is_object($clockmode)) {
                $clockmode = new elmtouchCmd();
                $clockmode->setTemplate('dashboard', 'clockmode');
                $clockmode->setTemplate('mobile', 'clockmode');
                $clockmode->setName(__('Mode programme', __FILE__));
                $clockmode->setIsVisible(1);
                $clockmode->setIsHistorized(1);
            }
            $clockmode->setEqLogic_id($this->getId());
            $clockmode->setType('info');
            $clockmode->setSubType('binary');
            $clockmode->setLogicalId('clockmode');
            $clockmode->setDisplay('generic_type', 'DONT');
            $clockmode->save();

            // Température extérieure.
            $temperature_outdoor = $this->getCmd(null, 'temperature_outdoor');
            if (!is_object($temperature_outdoor)) {
                $temperature_outdoor = new elmtouchCmd();
                $temperature_outdoor->setTemplate('dashboard', 'line');
                $temperature_outdoor->setTemplate('mobile', 'line');
                $temperature_outdoor->setIsVisible(1);
                $temperature_outdoor->setIsHistorized(1);
                $temperature_outdoor->setName(__('Température extérieure', __FILE__));
            }
            $temperature_outdoor->setEqLogic_id($this->getId());
            $temperature_outdoor->setType('info');
            $temperature_outdoor->setSubType('numeric');
            $temperature_outdoor->setLogicalId('temperature_outdoor');
            $temperature_outdoor->setUnite('°C');

            // TODO Retrouver la valeur
            // $temperature_outdoor->setValue($value);
            $temperature_outdoor->setDisplay('generic_type', 'THERMOSTAT_TEMPERATURE_OUTDOOR');
            $temperature_outdoor->save();

            // Température eau de chauffage en sortie de chaudière.
            $heatingsupplytemp = $this->getCmd(null, 'heatingsupplytemp');
            if (!is_object($heatingsupplytemp)) {
                $heatingsupplytemp = new elmtouchCmd();
                $heatingsupplytemp->setTemplate('dashboard', 'line');
                $heatingsupplytemp->setTemplate('mobile', 'line');
                $heatingsupplytemp->setIsVisible(1);
                $heatingsupplytemp->setIsHistorized(1);
                $heatingsupplytemp->setName(__('Température eau de chauffage', __FILE__));
            }
            $heatingsupplytemp->setEqLogic_id($this->getId());
            $heatingsupplytemp->setType('info');
            $heatingsupplytemp->setSubType('numeric');
            $heatingsupplytemp->setLogicalId('heatingsupplytemp');
            $heatingsupplytemp->setUnite('°C');
            $heatingsupplytemp->setDisplay('generic_type', 'DONT');
            $heatingsupplytemp->save();

            // Conso gaz chauffage jour kwh (info).
            $heatingdaykwh = $this->getCmd(null, 'heatingdaykwh');
            if (!is_object($heatingdaykwh)) {
                $heatingdaykwh = new elmtouchCmd();
                $heatingdaykwh->setIsVisible(0);
                $heatingdaykwh->setUnite('kWh');
                $heatingdaykwh->setName(__('Consommation chauffage en kWh', __FILE__));
                $heatingdaykwh->setConfiguration('historizeMode', 'none');
                $heatingdaykwh->setIsHistorized(1);
            }
            $heatingdaykwh->setDisplay('generic_type', 'DONT');
            $heatingdaykwh->setEqLogic_id($this->getId());
            $heatingdaykwh->setType('info');
            $heatingdaykwh->setSubType('numeric');
            $heatingdaykwh->setLogicalId('heatingdaykwh');
            $heatingdaykwh->save();

            // Conso gaz eau chaude jour kwh (info).
            $hotwaterdaykwh = $this->getCmd(null, 'hotwaterdaykwh');
            if (!is_object($hotwaterdaykwh)) {
                $hotwaterdaykwh = new elmtouchCmd();
                $hotwaterdaykwh->setIsVisible(0);
                $hotwaterdaykwh->setUnite('kWh');
                $hotwaterdaykwh->setName(__('Consommation eau chaude en kWh', __FILE__));
                $hotwaterdaykwh->setConfiguration('historizeMode', 'none');
                $hotwaterdaykwh->setIsHistorized(1);
            }
            $hotwaterdaykwh->setDisplay('generic_type', 'DONT');
            $hotwaterdaykwh->setEqLogic_id($this->getId());
            $hotwaterdaykwh->setType('info');
            $hotwaterdaykwh->setSubType('numeric');
            $hotwaterdaykwh->setLogicalId('hotwaterdaykwh');
            $hotwaterdaykwh->save();

            // Conso gaz totale jour kwh (info).
            $totaldaykwh = $this->getCmd(null, 'totaldaykwh');
            if (!is_object($totaldaykwh)) {
                $totaldaykwh = new elmtouchCmd();
                $totaldaykwh->setIsVisible(0);
                $totaldaykwh->setUnite('kWh');
                $totaldaykwh->setName(__('Consommation jour en kWh', __FILE__));
                $totaldaykwh->setConfiguration('historizeMode', 'none');
                $totaldaykwh->setIsHistorized(1);
            }
            $totaldaykwh->setDisplay('generic_type', 'DONT');
            $totaldaykwh->setEqLogic_id($this->getId());
            $totaldaykwh->setType('info');
            $totaldaykwh->setSubType('numeric');
            $totaldaykwh->setLogicalId('totaldaykwh');
            $totaldaykwh->save();

            // Température extérieure moyenne jour (info).
            $averageoutdoortemp = $this->getCmd(null, 'averageoutdoortemp');
            if (!is_object($averageoutdoortemp)) {
                $averageoutdoortemp = new elmtouchCmd();
                $averageoutdoortemp->setIsVisible(0);
                $averageoutdoortemp->setUnite('°C');
                $averageoutdoortemp->setName(__('Température extérieure moyenne', __FILE__));
                $averageoutdoortemp->setConfiguration('historizeMode', 'none');
                $averageoutdoortemp->setIsHistorized(1);
            }
            $averageoutdoortemp->setDisplay('generic_type', 'DONT');
            $averageoutdoortemp->setEqLogic_id($this->getId());
            $averageoutdoortemp->setType('info');
            $averageoutdoortemp->setSubType('numeric');
            $averageoutdoortemp->setLogicalId('averageoutdoortemp');
            $averageoutdoortemp->save();
            
            // Conso gaz chauffage jour m3 (info).
            $heatingdaym3 = $this->getCmd(null, 'heatingdaym3');
            if (!is_object($heatingdaym3)) {
                $heatingdaym3 = new elmtouchCmd();
                $heatingdaym3->setIsVisible(0);
                $heatingdaym3->setUnite('m3');
                $heatingdaym3->setName(__('Consommation chauffage en m3', __FILE__));
                $heatingdaym3->setConfiguration('historizeMode', 'none');
                $heatingdaym3->setIsHistorized(1);
            }
            $heatingdaym3->setDisplay('generic_type', 'DONT');
            $heatingdaym3->setEqLogic_id($this->getId());
            $heatingdaym3->setType('info');
            $heatingdaym3->setSubType('numeric');
            $heatingdaym3->setLogicalId('heatingdaym3');
            $heatingdaym3->save();

            // Conso gaz eau chaude jour m3 (info).
            $hotwaterdaym3 = $this->getCmd(null, 'hotwaterdaym3');
            if (!is_object($hotwaterdaym3)) {
                $hotwaterdaym3 = new elmtouchCmd();
                $hotwaterdaym3->setIsVisible(0);
                $hotwaterdaym3->setUnite('m3');
                $hotwaterdaym3->setName(__('Consommation eau chaude en m3', __FILE__));
                $hotwaterdaym3->setConfiguration('historizeMode', 'none');
                $hotwaterdaym3->setIsHistorized(1);
            }
            $hotwaterdaym3->setDisplay('generic_type', 'DONT');
            $hotwaterdaym3->setEqLogic_id($this->getId());
            $hotwaterdaym3->setType('info');
            $hotwaterdaym3->setSubType('numeric');
            $hotwaterdaym3->setLogicalId('hotwaterdaym3');
            $hotwaterdaym3->save();

            // Conso gaz totale jour m3 (info).
            $totaldaym3 = $this->getCmd(null, 'totaldaym3');
            if (!is_object($totaldaym3)) {
                $totaldaym3 = new elmtouchCmd();
                $totaldaym3->setIsVisible(0);
                $totaldaym3->setUnite('m3');
                $totaldaym3->setName(__('Consommation jour en m3', __FILE__));
                $totaldaym3->setConfiguration('historizeMode', 'none');
                $totaldaym3->setIsHistorized(1);
            }
            $totaldaym3->setDisplay('generic_type', 'DONT');
            $totaldaym3->setEqLogic_id($this->getId());
            $totaldaym3->setType('info');
            $totaldaym3->setSubType('numeric');
            $totaldaym3->setLogicalId('totaldaym3');
            $totaldaym3->save();
            
            // Conso gaz chauffage jour euro (info).
            $heatingdayeuro = $this->getCmd(null, 'heatingdayeuro');
            if (!is_object($heatingdayeuro)) {
                $heatingdayeuro = new elmtouchCmd();
                $heatingdayeuro->setIsVisible(0);
                $heatingdayeuro->setUnite('euro');
                $heatingdayeuro->setName(__('Consommation chauffage en euro', __FILE__));
                $heatingdayeuro->setConfiguration('historizeMode', 'none');
                $heatingdayeuro->setIsHistorized(1);
            }
            $heatingdayeuro->setDisplay('generic_type', 'DONT');
            $heatingdayeuro->setEqLogic_id($this->getId());
            $heatingdayeuro->setType('info');
            $heatingdayeuro->setSubType('numeric');
            $heatingdayeuro->setLogicalId('heatingdayeuro');
            $heatingdayeuro->save();

            // Conso gaz eau chaude jour euro (info).
            $hotwaterdayeuro = $this->getCmd(null, 'hotwaterdayeuro');
            if (!is_object($hotwaterdayeuro)) {
                $hotwaterdayeuro = new elmtouchCmd();
                $hotwaterdayeuro->setIsVisible(0);
                $hotwaterdayeuro->setUnite('euro');
                $hotwaterdayeuro->setName(__('Consommation eau chaude en euro', __FILE__));
                $hotwaterdayeuro->setConfiguration('historizeMode', 'none');
                $hotwaterdayeuro->setIsHistorized(1);
            }
            $hotwaterdayeuro->setDisplay('generic_type', 'DONT');
            $hotwaterdayeuro->setEqLogic_id($this->getId());
            $hotwaterdayeuro->setType('info');
            $hotwaterdayeuro->setSubType('numeric');
            $hotwaterdayeuro->setLogicalId('hotwaterdayeuro');
            $hotwaterdayeuro->save();

            // Conso gaz totale jour euro (info).
            $totaldayeuro = $this->getCmd(null, 'totaldayeuro');
            if (!is_object($totaldayeuro)) {
                $totaldayeuro = new elmtouchCmd();
                $totaldayeuro->setIsVisible(0);
                $totaldayeuro->setUnite('euro');
                $totaldayeuro->setName(__('Consommation jour en euro', __FILE__));
                $totaldayeuro->setConfiguration('historizeMode', 'none');
                $totaldayeuro->setIsHistorized(1);
            }
            $totaldayeuro->setDisplay('generic_type', 'DONT');
            $totaldayeuro->setEqLogic_id($this->getId());
            $totaldayeuro->setType('info');
            $totaldayeuro->setSubType('numeric');
            $totaldayeuro->setLogicalId('totaldayeuro');
            $totaldayeuro->save();
            
            // Boiler Indicator.
            $boilerindicator = $this->getCmd(null, 'boilerindicator');
            if (!is_object($boilerindicator)) {
                $boilerindicator = new elmtouchCmd();
                $boilerindicator->setIsVisible(1);
                $boilerindicator->setName(__('Etat chaudière', __FILE__));
            }
            $boilerindicator->setDisplay('generic_type', 'DONT');
            $boilerindicator->setEqLogic_id($this->getId());
            $boilerindicator->setType('info');
            $boilerindicator->setSubType('string');
            $boilerindicator->setLogicalId('boilerindicator');
            $boilerindicator->save();
            
            // Eau chaude active.
            $hotwateractive = $this->getCmd(null, 'hotwateractive');
            if (!is_object($hotwateractive)) {
                $hotwateractive = new elmtouchCmd();
                $hotwateractive->setIsVisible(1);
                $hotwateractive->setName(__('Eau chaude', __FILE__));
                $hotwateractive->setConfiguration('historizeMode', 'none');
                $hotwateractive->setIsHistorized(1);
            }
            $hotwateractive->setTemplate('dashboard', 'hotwateractive');
            $hotwateractive->setTemplate('mobile', 'hotwateractive');
            $hotwateractive->setDisplay('generic_type', 'DONT');
            $hotwateractive->setEqLogic_id($this->getId());
            $hotwateractive->setType('info');
            $hotwateractive->setSubType('binary');
            $hotwateractive->setLogicalId('hotwateractive');
            $hotwateractive->save();
        } else {
            // TODO supprimer crons et listeners
        }
    }

    public function preUpdate() {

    }

    public function postUpdate() {

    }

    public function preRemove() {

    }

    public function postRemove() {

    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
    public function getThermostatStatus() {
        // log::add('elmtouch', 'debug', 'Running getThermostatStatus');
        $url = 'http://127.0.0.1:3000/api/status';
        $request_http = new com_http($url);
        $request_http->setNoReportError(true);
        $json_string = $request_http->exec(30);
        if ($json_string === false) {
            log::add('elmtouch', 'debug', 'Problème de lecture status');
            return;
        }
        $parsed_json = json_decode($json_string, true);
        log::add('elmtouch', 'debug', 'getThermostatStatus : ' . print_r($json_string, true));

        $inhousetemp = floatval($parsed_json['in house temp']);
        if ( $inhousetemp >= 5 && $inhousetemp <= 30) {
            log::add('elmtouch', 'info', 'Température intérieure : ' . $inhousetemp);
            $this->checkAndUpdateCmd('temperature', $inhousetemp);
        } else {
            log::add('elmtouch', 'debug', 'temp incorrecte ' . $inhousetemp);
        }

        $tempsetpoint = floatval($parsed_json['temp setpoint']);
        if ( $tempsetpoint >= 5 && $tempsetpoint <= 30) {
            log::add('elmtouch', 'info', 'Consigne : ' . $tempsetpoint);
            $this->checkAndUpdateCmd('order', $tempsetpoint);
        } else {
            log::add('elmtouch', 'debug', 'tempsetpoint incorrecte ' . $tempsetpoint);
        }
        $clockmode = $parsed_json['user mode'];
        log::add('elmtouch', 'info', 'user mode ' . $clockmode);
        if ($clockmode =='clock') {
            $this->checkAndUpdateCmd('clockmode', true);
        } else {
            $this->checkAndUpdateCmd('clockmode', false);
        }
        $boilerindicator = $parsed_json['boiler indicator'];
        log::add('elmtouch', 'info', 'boiler indicator ' . $boilerindicator);
        switch ($boilerindicator) {
            case 'central heating' :
                $this->checkAndUpdateCmd('boilerindicator', __('Chauffage', __FILE__));
                break;
            case 'hot water' :
                $this->checkAndUpdateCmd('boilerindicator', __('Eau chaude', __FILE__));
                break;
            case 'off' :
                $this->checkAndUpdateCmd('boilerindicator', __('Arrêt', __FILE__));
                break;
            default :
                $this->checkAndUpdateCmd('boilerindicator', __('Inconnu', __FILE__));
                log::add('elmtouch', 'debug', 'Boiler indicator inconnu : ' . $boilerindicator);
                break;
        }
        $hotwateractive = $parsed_json['hot water active'];
        log::add('elmtouch', 'info', 'hot water active ' . $hotwateractive);
        if ($hotwateractive =='true') {
            $this->checkAndUpdateCmd('hotwateractive', true);
        } else {
            $this->checkAndUpdateCmd('hotwateractive', false);
        }
         //   $this->toHtml('mobile');
         //   $this->toHtml('dashboard');
    }

    public function getOutdoorTemp() {
        // log::add('elmtouch', 'debug', 'Running getOutdoorTemp');
        $url = 'http://127.0.0.1:3000/bridge/system/sensors/temperatures/outdoor_t1';
        $request_http = new com_http($url);
        $request_http->setNoReportError(true);
        $json_string = $request_http->exec(30);
        if ($json_string === false) {
            log::add('elmtouch', 'debug', 'Problème de lecture outdoortemp');
            return;
        }
        $parsed_json = json_decode($json_string, true);
        // log::add('elmtouch', 'debug', 'Réponse serveur getOutdoorTemp : ' . print_r($json_string, true));
        $outdoortemp = floatval($parsed_json['value']);
        if ( $outdoortemp >= -40 && $outdoortemp <= 50) {
            log::add('elmtouch', 'info', 'Température extérieure : ' . $outdoortemp);
            $this->checkAndUpdateCmd('temperature_outdoor', $outdoortemp);
        } else {
            log::add('elmtouch', 'debug', 'outdoortemp incorrecte ' . $outdoortemp);
        }
         //   $this->toHtml('mobile');
         //   $this->toHtml('dashboard');
    }

    public function getActualSupplyTemp() {
        // log::add('elmtouch', 'debug', 'Running getActualSupplyTemp');
        $url = 'http://127.0.0.1:3000/bridge/heatingCircuits/hc1/actualSupplyTemperature';
        $request_http = new com_http($url);
        $request_http->setNoReportError(true);
        $json_string = $request_http->exec(30);
        if ($json_string === false) {
            log::add('elmtouch', 'debug', 'Problème de lecture actualSupplyTemp');
            return;
        }
        $parsed_json = json_decode($json_string, true);
        // log::add('elmtouch', 'debug', 'Réponse serveur getActualSupplyTemp : ' . print_r($json_string, true));
        $supplytemp = floatval($parsed_json['value']);
        if ( $supplytemp >= 0 && $supplytemp <= 100) {
            log::add('elmtouch', 'info', 'Température eau de chauffage : ' . $supplytemp);
            $this->checkAndUpdateCmd('heatingsupplytemp', $supplytemp);
        } else {
            log::add('elmtouch', 'debug', 'supplytemp incorrecte ' . $supplytemp);
        }
         //   $this->toHtml('mobile');
         //   $this->toHtml('dashboard');
    }

    public function writeThermostatData($endpoint, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:3000/bridge' . $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
        log::add('elmtouch', 'debug', 'writeThermostatData '. $endpoint . ' ' . $data . ' > ' . $server_output);
    }

    public function readThermostatData($endpoint) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:3000/bridge' . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
        log::add('elmtouch', 'debug', 'readThermostatData '. $endpoint . ' > ' . $server_output);
        return $server_output;
    }

    public function setTemperature($value) {
        $this->writeThermostatData('/heatingCircuits/hc1/temperatureRoomManual', '{ "value" : ' .$value . ' }');
        $this->writeThermostatData('/heatingCircuits/hc1/manualTempOverride/status', '{ "value" : "on" }');
        $this->writeThermostatData('/heatingCircuits/hc1/manualTempOverride/temperature', '{ "value" : ' . $value . ' }');
    }

    public function getGasLastPage() {
        $json_string = $this->readThermostatData('/ecus/rrc/recordings/gasusagePointer');
        $pointer = intval(json_decode($json_string, true)['value']);
        $page = (int)($pointer / 32) + 1;
        return $page;
    }

    public function getGasPage($pagenum) {
        $cmdheatingdaykwh = $this->getCmd(null, 'heatingdaykwh');
        $cmdheatingdaym3 = $this->getCmd(null, 'heatingdaym3');
        $cmdheatingdayeuro = $this->getCmd(null, 'heatingdayeuro');
        $cmdhotwaterdaykwh = $this->getCmd(null, 'hotwaterdaykwh');
        $cmdhotwaterdaym3 = $this->getCmd(null, 'hotwaterdaym3');
        $cmdhotwaterdayeuro = $this->getCmd(null, 'hotwaterdayeuro');
        $cmdtotaldaykwh = $this->getCmd(null, 'totaldaykwh');
        $cmdtotaldaym3 = $this->getCmd(null, 'totaldaym3');
        $cmdtotaldayeuro = $this->getCmd(null, 'totaldayeuro');
        $cmdaverageoutdoortemp = $this->getCmd(null, 'averageoutdoortemp');

        // Nombre de jours récupérés.
        $count = 0;
        if ($pagenum >= 1 && $pagenum < 6400) {
            $json_string = $this->readThermostatData('/ecus/rrc/recordings/gasusage?page=' . $pagenum);
            // log::add('elmtouch', 'debug', 'Réponse serveur gasconsojson : ' . print_r($json_string, true));
            $parsed_json = json_decode($json_string, true);
            // log::add('elmtouch', 'debug', 'Réponse serveur gasconsojsonparsed : ' . print_r($parsed_json, true));
            foreach($parsed_json['value'] as $dailyconso) {
                $invalid = "255-256-65535";
                if ($dailyconso['d'] !== $invalid) {
                    log::add('elmtouch', 'debug', 'Daily : ' . print_r($dailyconso, true));
                    $server_date = date_create_from_format('d-m-Y', $dailyconso['d']);
                    if ($server_date !== false) {
                        $count++;
                        $jeedom_event_date = $server_date->format("Y-m-d");
                        $heatingday_kwh = floatval($dailyconso['ch']);
                        $cmdheatingdaykwh->event($heatingday_kwh, $jeedom_event_date);
                        $heatingday_m3 = round($heatingday_kwh / floatval($this->getConfiguration('convkwhm3', '8.125')), 1);
                        $cmdheatingdaym3->event($heatingday_m3, $jeedom_event_date);
                        $heatingday_euro = round($heatingday_kwh * floatval($this->getConfiguration('prixgazkwh', '0.05')), 2);
                        $cmdheatingdayeuro->event($heatingday_euro, $jeedom_event_date);
                        $hotwaterday_kwh = floatval($dailyconso['hw']);
                        $cmdhotwaterdaykwh->event($hotwaterday_kwh, $jeedom_event_date);
                        $hotwaterday_m3 = round($hotwaterday_kwh / floatval($this->getConfiguration('convkwhm3', '8.125')), 1);
                        $cmdhotwaterdaym3->event($hotwaterday_m3, $jeedom_event_date);
                        $hotwaterday_euro = round($hotwaterday_kwh * floatval($this->getConfiguration('prixgazkwh', '0.05')), 2);
                        $cmdhotwaterdayeuro->event($hotwaterday_euro, $jeedom_event_date);
                        $totalday_kwh = $heatingday_kwh + $hotwaterday_kwh;
                        $cmdtotaldaykwh->event($totalday_kwh, $jeedom_event_date);
                        $totalday_m3 = $heatingday_m3 + $hotwaterday_m3;
                        $cmdtotaldaym3->event($totalday_m3, $jeedom_event_date);
                        $totalday_euro = $heatingday_euro + $hotwaterday_euro;
                        $cmdtotaldayeuro->event($totalday_euro, $jeedom_event_date);
                        $outdoortemp_value = floatval($dailyconso['T']) / 10;
                        $cmdaverageoutdoortemp->event($outdoortemp_value, $jeedom_event_date);
                    }
                }
            }
        } else {
            log::add('elmtouch', 'debug', 'Numéro de page incorrect : '. $pagenum);
        }
        return $count;
    }

    public function getGasConsommation() {
        // On ne récupère que la dernière page.
        $lastPage = $this->getGasLastPage();
        log::add('elmtouch', 'debug', 'getGasConsommation page : '. $lastPage);
        $this->getGasPage($lastPage);
    }

    public function getGasHistory() {
        $lastPage = $this->getGasLastPage();
        for ($page = 1; $page <= $lastPage; $page++) {
            log::add('elmtouch', 'debug', 'getGasHistory page : '. $page);
            $this->getGasPage($page);
        }
    }
}

class elmtouchCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /**
     * Indique que les commandes obligatoires ne peuvent pas être supprimée.
     * @return boolean
     */
    public function dontRemoveCmd() {
        if ($this->getLogicalId() == 'order') {
            return true;
        }
        if ($this->getLogicalId() == 'thermostat') {
            return true;
        }
        if ($this->getLogicalId() == 'temperature') {
            return true;
        }
        if ($this->getLogicalId() == 'clockmode') {
            return true;
        }
        if ($this->getLogicalId() == 'temperature_outdoor') {
            return true;
        }
        if ($this->getLogicalId() == 'heatingsupplytemp') {
            return true;
        }
        if ($this->getLogicalId() == 'heatingdaykwh') {
            return true;
        }
        if ($this->getLogicalId() == 'hotwaterdaykwh') {
            return true;
        }
        if ($this->getLogicalId() == 'totaldaykwh') {
            return true;
        }
        return false;
    }


    public function execute($_options = array()) {
        if ($this->getType() == '') {
            return '';
        }
        $eqLogic = $this->getEqlogic();
        $action= $this->getLogicalId();
        if ($action == 'thermostat') {
            log::add('elmtouch', 'debug', 'action thermostat');
            log::add('elmtouch', 'debug', print_r($_options, true));
            if (!isset($_options['slider']) || $_options['slider'] == '' || !is_numeric(intval($_options['slider']))) {
                log::add('elmtouch', 'debug', 'mauvaise valeur du slider dans execute thermostat');
            }
            if ($_options['slider'] > 30) {
                $_options['slider'] = 30;
            }
            if ($_options['slider'] < 5) {
                $_options['slider'] = 5;
            }
            $eqLogic->getCmd(null, 'order')->event($_options['slider']);

            $eqLogic->setTemperature(floatval($_options['slider']));
            return true;
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}