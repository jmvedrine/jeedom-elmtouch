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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

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
        $easyserver = ' easy-server --serial=' . $serial . ' --access-key=' . $access . ' --password="' . $password . '"';
        // check easy-server started, if not, start
        $cmd = 'if [ $(ps -ef | grep -v grep | grep "easy-server" | wc -l) -eq 0 ]; then ' . system::getCmdSudo() . $easyserver . ';echo "Démarrage easy-server";sleep 1; fi';
        log::add('elmtouch', 'debug', str_replace($password,'****',$cmd));
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
                            // $elmtouch->getOutdoorTemp();  plus nécesaire récupérée par getThermostatStatus().
                            $elmtouch->getActualSupplyTemp();
                            $elmtouch->getYearlyTotalGas();
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
            if (floatval($elmtouch->getConfiguration('convkwhm3', 0)) != 0 && floatval($elmtouch->getConfiguration('prixgazkwh', 0)) != 0) {
                $cache = cache::byKey('elmtouch::lastgaspage::'.$elmtouch->getId());
                $page = $cache->getValue();
                // log::add('elmtouch', 'debug', 'page = ' . $page);
                /* if ($page == 8400) {
                    // Plus rien à faire.
                    log::add('elmtouch', 'debug', 'Récupération terminée');
                    return;
                } */
                $page++;
                $lastPage = $elmtouch->getGasLastPage();
                log::add('elmtouch', 'debug', 'lastPage = ' . $lastPage);
                if ($page > $lastPage) {
                    // Job terminé.
                    // log::add('elmtouch', 'debug', 'Pas de page de conso à récupérer');
                    // cache::set('elmtouch::lastgaspage::'.$elmtouch->getId(), 8400, 0);
                } else {
                    // log::add('elmtouch', 'debug', 'On récupère la page ' . $page);
                    $result = $elmtouch->getGasPage($page);
                    if ($result > 0) {
                        // On incrémente la valeur en cache si on a récupéré au moins un jour.
                        cache::set('elmtouch::lastgaspage::'.$elmtouch->getId(), $page, 0);
                        // log::add('elmtouch', 'debug', 'Nouvelle valeur en cache : ' . $page);
                    }
                }
            }

            // Récupération de la consommation totale.
            // $elmtouch->getYearlyTotalGas();
            // Récupération de la pression.
            $elmtouch->getSystemPressure();
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

    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
        $this->setConfiguration('convkwhm3', 8.125);
        $this->setConfiguration('prixgazkwh', 0.05);
    }

    public function postInsert() {
        // Récupérer les consos à partir de la page 1.
        foreach (self::byType('elmtouch') as $elmtouch) {
            $elmtouch->resetHistory();
        }
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

            // User mode (deprecated), use string info command mode.
            /* $clockmode = $this->getCmd(null, 'clockmode');
            if (!is_object($clockmode)) {
                $clockmode = new elmtouchCmd();
                $clockmode->setTemplate('dashboard', 'clockmode');
                $clockmode->setTemplate('mobile', 'clockmode');
                $clockmode->setName(__('Mode programme', __FILE__));
                $clockmode->setIsVisible(0);
                $clockmode->setIsHistorized(0);
            }
            $clockmode->setEqLogic_id($this->getId());
            $clockmode->setType('info');
            $clockmode->setSubType('binary');
            $clockmode->setLogicalId('clockmode');
            $clockmode->setDisplay('generic_type', 'DONT');
            $clockmode->save(); */

            $clockmode = $this->getCmd(null, 'clockmode');
            if (is_object($clockmode)) {
                $clockmode->remove();
            }

            // Température extérieure.
            $temperature_outdoor = $this->getCmd(null, 'temperature_outdoor');
            if (!is_object($temperature_outdoor)) {
                $temperature_outdoor = new elmtouchCmd();
                $temperature_outdoor->setTemplate('dashboard', 'line');
                $temperature_outdoor->setTemplate('mobile', 'line');
                $temperature_outdoor->setIsVisible(1);
                $temperature_outdoor->setIsHistorized(1);
                $temperature_outdoor->setConfiguration('historizeMode', 'none');
                $temperature_outdoor->setName(__('Température extérieure', __FILE__));
            }
            $temperature_outdoor->setEqLogic_id($this->getId());
            $temperature_outdoor->setType('info');
            $temperature_outdoor->setSubType('numeric');
            $temperature_outdoor->setLogicalId('temperature_outdoor');
            $temperature_outdoor->setUnite('°C');
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
                $heatingsupplytemp->setConfiguration('historizeMode', 'none');
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

            // Conso gaz totale depuis debut de l'année kWh (info).
            $totalyearkwh = $this->getCmd(null, 'totalyearkwh');
            if (!is_object($totalyearkwh)) {
                $totalyearkwh = new elmtouchCmd();
                $totalyearkwh->setIsVisible(1);
                $totalyearkwh->setUnite('kWh');
                $totalyearkwh->setName(__('Consommation annuelle', __FILE__));
                $totalyearkwh->setTemplate('dashboard', 'line');
                $totalyearkwh->setTemplate('mobile', 'line');
            }
            // Pas de lissage pour le calcul de la puissance correct
            $totalyearkwh->setIsHistorized(1);
            $totalyearkwh->setConfiguration('historizeMode', 'none');
            $totalyearkwh->setDisplay('generic_type', 'DONT');
            $totalyearkwh->setEqLogic_id($this->getId());
            $totalyearkwh->setType('info');
            $totalyearkwh->setSubType('numeric');
            $totalyearkwh->setLogicalId('totalyearkwh');
            $totalyearkwh->save();

            // Puissance en W (info).
            $boilerpower = $this->getCmd(null, 'boilerpower');
            if (!is_object($boilerpower)) {
                $boilerpower = new elmtouchCmd();
                $boilerpower->setIsVisible(1);
                $boilerpower->setUnite('W');
                $boilerpower->setName(__('Puissance', __FILE__));
                $boilerpower->setTemplate('dashboard', 'line');
                $boilerpower->setTemplate('mobile', 'line');
                $boilerpower->setIsHistorized(0);
            }
            $boilerpower->setDisplay('generic_type', 'DONT');
            $boilerpower->setEqLogic_id($this->getId());
            $boilerpower->setType('info');
            $boilerpower->setSubType('numeric');
            $boilerpower->setLogicalId('boilerpower');
            $boilerpower->save();

            // Pression en bar (info).
            $systempressure = $this->getCmd(null, 'systempressure');
            if (!is_object($systempressure)) {
                $systempressure = new elmtouchCmd();
                $systempressure->setIsVisible(1);
                $systempressure->setUnite('bar');
                $systempressure->setName(__('Pression', __FILE__));
                $systempressure->setTemplate('dashboard', 'line');
                $systempressure->setTemplate('mobile', 'line');
                $systempressure->setIsHistorized(0);
            }
            $systempressure->setDisplay('generic_type', 'DONT');
            $systempressure->setEqLogic_id($this->getId());
            $systempressure->setType('info');
            $systempressure->setSubType('numeric');
            $systempressure->setLogicalId('systempressure');
            $systempressure->save();

            // Boiler Indicator (info)
            // Prend les valeurs 'Chauffage', 'Eau chaude', 'Arrêt'.
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

            // Eau chaude active (info).
            $hotwateractive = $this->getCmd(null, 'hotwateractive');
            if (!is_object($hotwateractive)) {
                $hotwateractive = new elmtouchCmd();
                $hotwateractive->setIsVisible(0);
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

            $actif = $this->getCmd(null, 'actif');
            if (!is_object($actif)) {
                $actif = new elmtouchCmd();
                $actif->setName(__('Chauffage actif', __FILE__));
                $actif->setIsVisible(0);
                $actif->setIsHistorized(1);
            }
            $actif->setDisplay('generic_type', 'DONT');
            $actif->setEqLogic_id($this->getId());
            $actif->setType('info');
            $actif->setSubType('binary');
            $actif->setLogicalId('actif');
            $actif->save();

            // Action eau chaude arrêt.
            $hotwater_Off = $this->getCmd(null, 'hotwater_Off');
            if (!is_object($hotwater_Off)) {
                $hotwater_Off = new elmtouchCmd();
                $hotwater_Off->setTemplate('dashboard', 'hotwater');
                $hotwater_Off->setTemplate('mobile', 'hotwater');
                $hotwater_Off->setName('hotwater_Off');
            }
            $hotwater_Off->setEqLogic_id($this->getId());
            $hotwater_Off->setType('action');
            $hotwater_Off->setSubType('other');
            $hotwater_Off->setLogicalId('hotwater_Off');
            $hotwater_Off->setIsVisible(1);
            $hotwater_Off->setValue($hotwateractive->getId());
            $hotwater_Off->save();

            // Action eau chaude marche.
            $hotwater_On = $this->getCmd(null, 'hotwater_On');
            if (!is_object($hotwater_On)) {
                $hotwater_On = new elmtouchCmd();
                $hotwater_On->setTemplate('dashboard', 'hotwater');
                $hotwater_On->setTemplate('mobile', 'hotwater');
                $hotwater_On->setName('hotwater_On');
            }
            $hotwater_On->setEqLogic_id($this->getId());
            $hotwater_On->setType('action');
            $hotwater_On->setSubType('other');
            $hotwater_On->setLogicalId('hotwater_On');
            $hotwater_On->setIsVisible(1);
            $hotwater_On->setValue($hotwateractive->getId());
            $hotwater_On->save();

            // Info verrouillage.
            $lockState = $this->getCmd(null, 'lock_state');
            if (!is_object($lockState)) {
                $lockState = new elmtouchCmd();
                $lockState->setTemplate('dashboard', 'lock');
                $lockState->setTemplate('mobile', 'lock');
                $lockState->setName(__('Verrouillage', __FILE__));
                $lockState->setIsVisible(0);
            }
            $lockState->setDisplay('generic_type', 'THERMOSTAT_LOCK');
            $lockState->setEqLogic_id($this->getId());
            $lockState->setType('info');
            $lockState->setSubType('binary');
            $lockState->setLogicalId('lock_state');
            $lockState->save();

            // Action verrouillage.
            $lock = $this->getCmd(null, 'lock');
            if (!is_object($lock)) {
                $lock = new elmtouchCmd();
                $lock->setTemplate('dashboard', 'lock');
                $lock->setTemplate('mobile', 'lock');
                $lock->setName('lock');
            }
            $lock->setDisplay('generic_type', 'THERMOSTAT_SET_LOCK');
            $lock->setEqLogic_id($this->getId());
            $lock->setType('action');
            $lock->setSubType('other');
            $lock->setLogicalId('lock');
            $lock->setIsVisible(1);
            $lock->setValue($lockState->getId());
            $lock->save();

            // Action déverrouillage.
            $unlock = $this->getCmd(null, 'unlock');
            if (!is_object($unlock)) {
                $unlock = new elmtouchCmd();
                $unlock->setTemplate('dashboard', 'lock');
                $unlock->setTemplate('mobile', 'lock');
                $unlock->setName('unlock');
            }
            $unlock->setDisplay('generic_type', 'THERMOSTAT_SET_UNLOCK');
            $unlock->setEqLogic_id($this->getId());
            $unlock->setType('action');
            $unlock->setSubType('other');
            $unlock->setLogicalId('unlock');
            $unlock->setIsVisible(1);
            $unlock->setValue($lockState->getId());
            $unlock->save();

            // Commande info associée aux deux modes
            // Prend pour valeur le Name du mode actif (Mode programme et Mode manuel)
            $mode = $this->getCmd(null, 'mode');
            if (!is_object($mode)) {
                $mode = new elmtouchCmd();
                $mode->setName(__('Mode', __FILE__));
                $mode->setIsVisible(0);
            }
            $mode->setDisplay('generic_type', 'THERMOSTAT_MODE');
            $mode->setEqLogic_id($this->getId());
            $mode->setType('info');
            $mode->setSubType('string');
            $mode->setLogicalId('mode');
            $mode->save();

            // Etat binaire du bruleur pour mobile et homebridge
            // 0 = éteint, 1 = allumé.
            $heatstatus = $this->getCmd(null, 'heatstatus');
            if (!is_object($heatstatus)) {
                $heatstatus = new elmtouchCmd();
                $heatstatus->setName(__('Etat bruleur', __FILE__));
                $heatstatus->setTemplate('dashboard', 'burner');
                $heatstatus->setTemplate('mobile', 'burner');
                $heatstatus->setIsVisible(1);
                $heatstatus->setIsHistorized(1);
            }
            $heatstatus->setEqLogic_id($this->getId());
            $heatstatus->setLogicalId('heatstatus');
            $heatstatus->setType('info');
            $heatstatus->setSubType('binary');
            $heatstatus->setDisplay('generic_type', 'THERMOSTAT_STATE');
            $heatstatus->save();

            // Etat du bruleur pour mobile et homebridge
            // Prend 2 valeurs 'Chauffage' et 'Arrêté'.
            $status = $this->getCmd(null, 'status');
            if (!is_object($status)) {
                $status = new elmtouchCmd();
                $status->setIsVisible(1);
                $status->setName(__('Nom etat bruleur', __FILE__));
            }
            $status->setDisplay('generic_type', 'THERMOSTAT_STATE_NAME');
            $status->setEqLogic_id($this->getId());
            $status->setType('info');
            $status->setSubType('string');
            $status->setLogicalId('status');
            $status->save();

            // Commande action mode programme
            $clock = $this->getCmd(null, 'clock');
            if (!is_object($clock)) {
                $clock = new elmtouchCmd();
                $clock->setLogicalId('clock');
                $clock->setIsVisible(1);
                $clock->setTemplate('dashboard', 'usermode');
                $clock->setTemplate('mobile', 'usermode');
                $clock->setName(__('Mode horloge', __FILE__));
            }
            $clock->setType('action');
            $clock->setSubType('other');
            $clock->setOrder(1);
            $clock->setDisplay('generic_type', 'THERMOSTAT_SET_MODE');
            $clock->setEqLogic_id($this->getId());
            $clock->setValue($mode->getId());
            $clock->save();

            // Commande action mode manuel.
            $manual = $this->getCmd(null, 'manual');
            if (!is_object($manual)) {
                $manual = new elmtouchCmd();
                $manual->setLogicalId('manual');
                $manual->setIsVisible(1);
                $manual->setTemplate('dashboard', 'usermode');
                $manual->setTemplate('mobile', 'usermode');
                $manual->setName(__('Mode manuel', __FILE__));
            }
            $manual->setType('action');
            $manual->setSubType('other');
            $manual->setOrder(1);
            $manual->setDisplay('generic_type', 'THERMOSTAT_SET_MODE');
            $manual->setEqLogic_id($this->getId());
            $manual->setValue($mode->getId());
            $manual->save();
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
    /*
     * Fonction pour permettre de relancer la récupération de tout l'historique.
     */
    public function resetHistory() {
        cache::set('elmtouch::lastgaspage::'.$this->getId(), 0, 0);
    }

    public function getThermostatStatus() {
        // log::add('elmtouch', 'debug', 'Running getThermostatStatus');
        $url = 'http://127.0.0.1:3000/api/status';
        $request_http = new com_http($url);
        $request_http->setNoReportError(true);
        $json_string = $request_http->exec(30);
        if ($json_string === false) {
            log::add('elmtouch', 'debug', 'Problème de lecture status');
            $request_http->setNoReportError(false);
            $json_string = $request_http->exec(30,1);
            return;
        }
        $parsed_json = json_decode($json_string, true);
        // log::add('elmtouch', 'debug', 'getThermostatStatus : ' . print_r($json_string, true));

        $inhousetemp = floatval($parsed_json['in house temp']);
        if ( $inhousetemp >= 5 && $inhousetemp <= 30) {
            log::add('elmtouch', 'info', 'Température intérieure : ' . $inhousetemp);
            $this->checkAndUpdateCmd('temperature', $inhousetemp);
        } else {
            log::add('elmtouch', 'debug', 'temp incorrecte ' . $inhousetemp);
        }

        $outdoortemp = floatval($parsed_json['outdoor temp']);
        if ( $outdoortemp >= -40 && $outdoortemp <= 50) {
            log::add('elmtouch', 'info', 'Température extérieure : ' . $outdoortemp);
            $this->checkAndUpdateCmd('temperature_outdoor', $outdoortemp);
        } else {
            log::add('elmtouch', 'debug', 'temp extérieure incorrecte ' . $outdoortemp);
        }

        $tempsetpoint = floatval($parsed_json['temp setpoint']);
        if ( $tempsetpoint >= 5 && $tempsetpoint <= 30) {
            log::add('elmtouch', 'info', 'Consigne : ' . $tempsetpoint);
            $this->checkAndUpdateCmd('order', $tempsetpoint);
        } else {
            log::add('elmtouch', 'debug', 'tempsetpoint incorrecte ' . $tempsetpoint);
        }
        $currentUserMode = $parsed_json['user mode'];
        log::add('elmtouch', 'info', 'user mode ' . $currentUserMode);

        // New string command mode.
        $existingModes = array('manual' => __('Mode manuel', __FILE__), 'clock' => __('Mode horloge', __FILE__));
        foreach ($existingModes as $modeId => $modeName) {
            if ($currentUserMode == $modeId) {
                //log::add('elmtouch', 'debug', 'evenement mode value = '.$modeName);
                $this->getCmd(null, 'mode')->event($modeName);
            }
        }

        /* Deprecated info binay command clockmode
        if ($currentUserMode =='clock') {
            $this->checkAndUpdateCmd('clockmode', true);
        } else {
            $this->checkAndUpdateCmd('clockmode', false);
        } */

        $boilerindicator = $parsed_json['boiler indicator'];
        log::add('elmtouch', 'info', 'boiler indicator ' . $boilerindicator);
        switch ($boilerindicator) {
            case 'central heating' :
                $this->checkAndUpdateCmd('boilerindicator', __('Chauffage', __FILE__));
                $this->getCmd(null, 'heatstatus')->event(1);
                $this->getCmd(null, 'status')->event(__('Chauffage', __FILE__));
                if (!$this->getCmd(null,'actif')->execCmd()) {
                    $this->getCmd(null, 'actif')->event(1);
                }
                break;
            case 'hot water' :
                $this->checkAndUpdateCmd('boilerindicator', __('Eau chaude', __FILE__));
                $this->getCmd(null, 'heatstatus')->event(1);
                $this->getCmd(null, 'status')->event(__('Chauffage', __FILE__));
                break;
            case 'off' :
                $this->checkAndUpdateCmd('boilerindicator', __('Arrêt', __FILE__));
                $this->getCmd(null, 'heatstatus')->event(0);
                $this->getCmd(null, 'status')->event(__('Arrêté', __FILE__));
                if ($this->getCmd(null,'actif')->execCmd()) {
                    $this->getCmd(null, 'actif')->event(0);
                }
                break;
            default :
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
            $request_http->setNoReportError(false);
            $json_string = $request_http->exec(30,1);
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
            $request_http->setNoReportError(false);
            $json_string = $request_http->exec(30,1);
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

    // Calcul de la pente par la méthode des moindres carrés
    // On ne peut pas utiliser history::getTendance qui ne tient pas compte des abscisses.
    public function slope($histories) {
        if (count($histories) == 0) {
			return 0;
		}
		foreach ($histories as $history) {
            $xvalues[] = strtotime($history->getDatetime());
			$yvalues[] = $history->getValue();
		}
		$x_mean = array_sum($xvalues) / count($xvalues);
        $y_mean = array_sum($yvalues) / count($yvalues);

		$base = 0.0;
		$divisor = 0.0;
		foreach ($yvalues as $key => $yvalue) {
			$base += ($xvalues[$key] - $x_mean) * ($yvalue - $y_mean);
			$divisor += ($xvalues[$key] - $x_mean) * ($xvalues[$key] - $x_mean);
		}
		if ($divisor == 0) {
			return 0;
		}
		return ($base / $divisor);
	}

    public function getYearlyTotalGas() {
        // log::add('elmtouch', 'debug', 'Running getYearlyTotalGas');
        $yearlyConsoCmd = $this->getCmd(null, 'totalyearkwh');

        $url = 'http://127.0.0.1:3000/bridge/ecus/rrc/recordings/yearTotal';
        $request_http = new com_http($url);
        $request_http->setNoReportError(true);
        $json_string = $request_http->exec(30);
        if ($json_string === false) {
            log::add('elmtouch', 'debug', 'Problème de lecture YearlyTotalGas');
            $request_http->setNoReportError(false);
            $json_string = $request_http->exec(30,1);
            return;
        }
        $parsed_json = json_decode($json_string, true);
        // log::add('elmtouch', 'debug', 'Réponse serveur getYearlyTotalGas : ' . print_r($json_string, true));
        $totalyearkwh = floatval($parsed_json['value']);
        // Filtrage des valeurs nulles qui sont certainement incorrectes.
        if ( $totalyearkwh > 0 && $totalyearkwh <= 429496729.5) {
            log::add('elmtouch', 'info', 'Consommation depuis le 1 janvier : ' . $totalyearkwh);
            $yearlyConsoCmd->event($totalyearkwh);
            $now = strtotime('now');
           //
        } else {
            log::add('elmtouch', 'debug', 'Conso annuelle incorrecte ' . $totalyearkwh);
            return;
        }


        // Calcul de la puissance
        // Si pas de consommation pendant 5 minutes on considère que la chaudière est arrétée.
        $startdate = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' - 5 minutes'));
        $enddate = date('Y-m-d H:i:s');
        // log::add('elmtouch', 'debug', "Dates pour calcul de puissance $startdate et $enddate");

        $histories = $yearlyConsoCmd->getHistory($startdate, $enddate);
        $counter = count($histories);
        log::add ('elmtouch', 'debug', 'Nb évenements conso en 5 minutes : ' . $counter);

        /* if ($counter) {
            foreach ($histories as $key => $history) {
                log::add ('elmtouch', 'debug', 'key = ' . $key);
                log::add ('elmtouch', 'debug', 'datetime = ' . $history->getDatetime());
                log::add ('elmtouch', 'debug', 'time = ' . strtotime($history->getDatetime()));
                log::add ('elmtouch', 'debug', 'value = ' . $history->getValue());
            }

            $oldConsommation = $histories[$counter - 1]->getValue();
            $oldDatetime = strtotime($histories[$counter - 1]->getDatetime());
            log::add ('elmtouch', 'debug', 'Ancienne consommation = ' . $oldConsommation);
            log::add ('elmtouch', 'debug', 'Ancienne date = ' . $histories[$counter - 1]->getDatetime());
            $duration = $now - $oldDatetime;
            log::add ('elmtouch', 'debug', 'Durée en s = ' . $duration);
            $power = round(3600 * 100 * ($totalyearkwh - $oldConsommation) / $duration) *10;
        } else {
             log::add ('elmtouch', 'debug', 'Pas de conso dans les 5 minutes');
            $power = 0;
        }
        log::add ('elmtouch', 'debug', 'power = ' . $power);
        $this->getCmd(null, 'boilerpower')->event($power); */


        // Puissance arrondie à 100 W
        $power = round($this->slope($histories) * 3600 * 10) * 100;
        log::add ('elmtouch', 'debug', 'Puissance = ' . $power);
        // Limites contre les résultats aberrants (à voir pour le max)
        if ($power >= 0 && $power <= 50000) {
            $this->getCmd(null, 'boilerpower')->event($power);
        }

         //   $this->toHtml('mobile');
         //   $this->toHtml('dashboard');
    }

    public function getSystemPressure() {
        // log::add('elmtouch', 'debug', 'Running getPressure');
        $url = 'http://127.0.0.1:3000/bridge/system/appliance/systemPressure';
        $request_http = new com_http($url);
        $request_http->setNoReportError(true);
        $json_string = $request_http->exec(30);
        if ($json_string === false) {
            log::add('elmtouch', 'debug', 'Problème de lecture Pressure');
            $request_http->setNoReportError(false);
            $json_string = $request_http->exec(30,1);
            return;
        }
        $parsed_json = json_decode($json_string, true);
        // log::add('elmtouch', 'debug', 'Réponse serveur getPressure : ' . print_r($json_string, true));
        $pressure = floatval($parsed_json['value']);
        if ( $pressure >= 0 && $pressure <= 25) {
            log::add('elmtouch', 'info', 'Pression : ' . $pressure);
            $this->checkAndUpdateCmd('systempressure', $pressure);
        } else {
            log::add('elmtouch', 'debug', 'Pression incorrecte ' . $pressure);
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

     public function getGasPointer() {
        $json_string = $this->readThermostatData('/ecus/rrc/recordings/gasusagePointer');
        $pointer = intval(json_decode($json_string, true)['value']);
        return $pointer;
    }

    public function getGasLastPage() {
        $pointer = $this->getGasPointer();
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
                        // Consommations chauffage.
                        $heatingday_kwh = floatval($dailyconso['ch']);
                        // On convertit en m3 avec le coefficient pris par Bosch.
                        $heatingday_m3 = $heatingday_kwh / 8.125;
                        // Et on reconvertit en kWh avec le coefficient de la configuration.
                        $heatingday_kwh = $heatingday_m3 * floatval($this->getConfiguration('convkwhm3', '8.125'));
                        // On calcule le prix en euros.
                        $heatingday_euro = $heatingday_kwh * floatval($this->getConfiguration('prixgazkwh', '0.05'));
                        // Et maintenant on stocke les valeurs arrondies.
                        $cmdheatingdaykwh->event(round($heatingday_kwh, 1), $jeedom_event_date);
                        $cmdheatingdayeuro->event(round($heatingday_euro, 2), $jeedom_event_date);
                        $cmdheatingdaym3->event(round($heatingday_m3, 1), $jeedom_event_date);
                        // Consommations eau chaude sanitaire.
                        $hotwaterday_kwh = floatval($dailyconso['hw']);
                        // On convertit en m3 avec le coefficient pris par Bosch.
                        $hotwaterday_m3 = $hotwaterday_kwh / 8.125;
                        // Et on reconvertit en kWh avec le coefficient de la configuration.
                        $hotwaterday_kwh = $hotwaterday_m3 * floatval($this->getConfiguration('convkwhm3', '8.125'));
                        // On calcule le prix en euros.
                        $hotwaterday_euro = $hotwaterday_kwh * floatval($this->getConfiguration('prixgazkwh', '0.05'));
                        $cmdhotwaterdaykwh->event(round($hotwaterday_kwh, 1), $jeedom_event_date);
                        $cmdhotwaterdaym3->event(round($hotwaterday_m3, 1), $jeedom_event_date);
                        $cmdhotwaterdayeuro->event(round($hotwaterday_euro, 2), $jeedom_event_date);
                        // Consommations totales.
                        $totalday_kwh = $heatingday_kwh + $hotwaterday_kwh;
                        $cmdtotaldaykwh->event($totalday_kwh, $jeedom_event_date);
                        $totalday_m3 = $heatingday_m3 + $hotwaterday_m3;
                        $cmdtotaldaym3->event($totalday_m3, $jeedom_event_date);
                        $totalday_euro = $heatingday_euro + $hotwaterday_euro;
                        $cmdtotaldayeuro->event($totalday_euro, $jeedom_event_date);
                        // Température extérieure moyenne.
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

    public function setHotWaterState($state) {
        // Actualisation du status au cas où il ait changé sur le thermostat depuis le dernier cron.
        // log::add('elmtouch', 'debug', 'debut de sethotwaterstate state = ' . $state);
        $this->getThermostatStatus();
        $currentStatus = $this->getCmd(null, 'mode')->execCmd();
        // log::add('elmtouch', 'debug', 'Currentstatus = ' . $currentStatus);
        $value = ($state) ? 'on' : 'off';
        if ($currentStatus == 'clock') {
            // log::add('elmtouch', 'debug', 'On met à jour /dhwCircuits/dhwA/dhwOperationClockMode avec ' . $value);
            $this->writeThermostatData('/dhwCircuits/dhwA/dhwOperationClockMode', '{ "value" : "' .$value . '" }');
        } else {
            // log::add('elmtouch', 'debug', 'On met à jour /dhwCircuits/dhwA/dhwOperationManualMode avec ' . $value);
            $this->writeThermostatData('/dhwCircuits/dhwA/dhwOperationManualMode', '{ "value" : "' .$value . '" }');
        }
        $this->getCmd(null, 'hotwateractive')->event($state);
    }

    public function executeMode($_name) {
        // log::add('elmtouch', 'debug', 'début de executeMode name = '. $_name);
        $existingModes = array('manual' => __('Mode manuel', __FILE__), 'clock' => __('Mode horloge', __FILE__));
        foreach ($existingModes as $modeId => $modeName) {
            if ($_name == $modeName) {
                // log::add('elmtouch', 'debug', 'ecriture dans le thermostat value = '.$modeId);
                $this->writeThermostatData('/heatingCircuits/hc1/usermode', '{ "value" : "' .$modeId . '" }');
            }
        }
        $this->getCmd(null, 'mode')->event($_name);
    }

    public function runtimeByDay($_startDate = null, $_endDate = null) {
        $actifCmd = $this->getCmd(null, 'actif');
        if (!is_object($actifCmd)) {
            return array();
        }
        $return = array();
        $prevValue = 0;
        $prevDatetime = 0;
        $day = null;
        $day = strtotime($_startDate . ' 00:00:00 UTC');
        $endDatetime = strtotime($_endDate . ' 00:00:00 UTC');
        while ($day <= $endDatetime) {
            $return[date('Y-m-d', $day)] = array($day * 1000, 0);
            $day = $day + 3600 * 24;
        }
        foreach ($actifCmd->getHistory($_startDate, $_endDate) as $history) {
            if (date('Y-m-d', strtotime($history->getDatetime())) != $day && $prevValue == 1 && $day != null) {
                if (strtotime($day . ' 23:59:59') > $prevDatetime) {
                    $return[$day][1] += (strtotime($day . ' 23:59:59') - $prevDatetime) / 60;
                }
                $prevDatetime = strtotime(date('Y-m-d 00:00:00', strtotime($history->getDatetime())));
            }
            $day = date('Y-m-d', strtotime($history->getDatetime()));
            if (!isset($return[$day])) {
                $return[$day] = array(strtotime($day . ' 00:00:00 UTC') * 1000, 0);
            }
            if ($history->getValue() == 1 && $prevValue == 0) {
                $prevDatetime = strtotime($history->getDatetime());
                $prevValue = 1;
            }
            if ($history->getValue() == 0 && $prevValue == 1) {
                if ($prevDatetime > 0 && strtotime($history->getDatetime()) > $prevDatetime) {
                    $return[$day][1] += (strtotime($history->getDatetime()) - $prevDatetime) / 60;
                }
                $prevValue = 0;
            }
        }
        return $return;
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
        return true;
    }

    public function execute($_options = array()) {
        if ($this->getType() == '') {
            return '';
        }
        $eqLogic = $this->getEqlogic();
        $action= $this->getLogicalId();
        $lockState = $eqLogic->getCmd(null, 'lock_state');
        if ($action == 'lock') {
            $lockState->event(1);
            return true;
        } else if ($action == 'unlock') {
            $lockState->event(0);
            return true;
        } else if ($action == 'clock' || $action =='manual') {
            log::add('elmtouch', 'debug', 'action set mode ' . $action);
            $eqLogic->executeMode($this->getName());
            return true;
        } else if ($action == 'thermostat') {
            // log::add('elmtouch', 'debug', 'action thermostat');
            // log::add('elmtouch', 'debug', print_r($_options, true));
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
        } else if ($action == 'hotwater_Off' || $action == 'hotwater_On') {
            log::add('elmtouch', 'debug', 'action set hotwater ' . $action);
            $value = $this->getName() == 'hotwater_On';
            $eqLogic->setHotWaterState($value);
        }
        if (!is_object($lockState) || $lockState->execCmd() == 1) {
            $eqLogic->refreshWidget();
            return;
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}