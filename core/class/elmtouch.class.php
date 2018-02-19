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
    public static function cron() {
        foreach (self::byType('elmtouch') as $elmtouch) {
            $cron_isEnable = $elmtouch->getConfiguration('cron_isEnable', 0);
            $autorefresh = $elmtouch->getConfiguration('autorefresh', '');
            $serial = $elmtouch->getConfiguration('serialNumber', '');
            $access = $elmtouch->getConfiguration('accessKey', '');
            $password = $elmtouch->getConfiguration('password', '');
            if ($elmtouch->getIsEnable() == 1 && $cron_isEnable == 1 && $serial != '' && $access != '' && $password != '' && $autorefresh != '') {
                try {
                    $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                    if ($c->isDue()) {
                        try {
                            $elmtouch->getStatus();
                            $elmtouch->getOutdoorTemp();
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

    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'elmtouch_update';
        $return['progress_file'] = jeedom::getTmpFolder('elmtouch') . '/dependance';
        $return['state'] = 'ok';
        // TODO détecter si nefit easy server est installé.
        return $return;
    }

    public static function dependancy_install() {
        log::remove(__CLASS__ . '_update');
        return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('elmtouch') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
    }

/*    public static function start() {
        self::cron15();
    }   */

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

      }
     */



    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {

    }

    public function postInsert() {

    }

    public function preSave() {
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
    public function getStatus() {
        // log::add('elmtouch', 'debug', 'Running getStatus');
        $url = 'http://127.0.0.1:3000/api/status';
        $request_http = new com_http($url);
        $json_string = $request_http->exec(30);
        if ($json_string === false) {
            log::add('elmtouch', 'debug', 'Problème de lecture status');
            return;
        }
        $parsed_json = json_decode($json_string, true);
        // log::add('elmtouch', 'debug', print_r($json_string, true));
        // log::add('elmtouch', 'debug', print_r($parsed_json, true));
        $inhousetemp = floatval($parsed_json['in house temp']);
        if ( $inhousetemp >= 5 && $inhousetemp <= 30) {
            log::add('elmtouch', 'debug', 'Température intérieure : ' . $inhousetemp);
            $this->checkAndUpdateCmd('temperature', $inhousetemp);
        } else {
            log::add('elmtouch', 'debug', 'temp incorrecte ' . $inhousetemp);
        }
        // log::add('elmtouch', 'debug', 'Result6 : ' . $parsed_json['temp setpoint']);
        $tempsetpoint = floatval($parsed_json['temp setpoint']);
        if ( $tempsetpoint >= 5 && $tempsetpoint <= 30) {
            log::add('elmtouch', 'debug', 'Consigne : ' . $tempsetpoint);
            $this->checkAndUpdateCmd('order', $tempsetpoint);
        } else {
            log::add('elmtouch', 'debug', 'tempsetpoint incorrecte ' . $tempsetpoint);
        }
        $clockmode = $parsed_json['user mode'];
        log::add('elmtouch', 'debug', 'user mode ' . $clockmode);
        if ($clockmode =='clock') {
            $this->checkAndUpdateCmd('clockmode', true);
        } else {
            $this->checkAndUpdateCmd('clockmode', false);
        }
         //   $this->toHtml('mobile');
         //   $this->toHtml('dashboard');
    }

    public function getOutdoorTemp() {
        // log::add('elmtouch', 'debug', 'Running getOutdoorTemp');
        $url = 'http://127.0.0.1:3000/bridge/system/sensors/temperatures/outdoor_t1';
        $request_http = new com_http($url);
        $json_string = $request_http->exec(30);
        if ($json_string === false) {
            log::add('elmtouch', 'debug', 'Problème de lecture outdoortemp');
            return;
        }
        $parsed_json = json_decode($json_string, true);
        // log::add('elmtouch', 'debug', print_r($json_string, true));
        // log::add('elmtouch', 'debug', 'Result7 : ' . print_r($parsed_json, true));
        $outdoortemp = floatval($parsed_json['value']);
        if ( $outdoortemp >= 5 && $outdoortemp <= 30) {
            log::add('elmtouch', 'debug', 'Température extérieure : ' . $outdoortemp);
            $this->checkAndUpdateCmd('temperature_outdoor', $outdoortemp);
        } else {
            log::add('elmtouch', 'debug', 'outdoortemp incorrecte ' . $outdoortemp);
        }
         //   $this->toHtml('mobile');
         //   $this->toHtml('dashboard');
    }

    public function writeData($endpoint, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:3000/bridge' . $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);
        curl_close ($ch);
        log::add('elmtouch', 'debug', 'writedata '. $endpoint . ' ' . $data . ' > ' . $server_output);
    }

    public function setTemperature($value) {
        $this->writeData('/heatingCircuits/hc1/temperatureRoomManual', '{ "value" : ' .$value . ' }');
        $this->writeData('/heatingCircuits/hc1/manualTempOverride/status', '{ "value" : "on" }');
        $this->writeData('/heatingCircuits/hc1/manualTempOverride/temperature', '{ "value" : ' . $value . ' }');
    }

}

class elmtouchCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

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
            $eqLogic->refresh_place();
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}


