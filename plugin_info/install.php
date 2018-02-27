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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function elmtouch_install() {
    // Cron de récupération des consommations de gaz (obsolète).
    /* $cron = cron::byClassAndFunction('elmtouch', 'getGasDaily');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('elmtouch');
        $cron->setFunction('getGasDaily');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule(rand(10, 59) . ' 0' . rand(1, 5) . ' * * *');
        $cron->save();
    } */
}

function elmtouch_update() {
    foreach (eqLogic::byType('elmtouch') as $elmtouch) {
        $elmtouch->save();

        // Correction du bug des unités.
        $averageoutdoortemp = $this->getCmd(null, 'averageoutdoortemp');
        if (is_object($averageoutdoortemp)) {
            $averageoutdoortemp->setUnite('°C');
        }
    }

    // Cron de récupération des consommations de gaz (obsolète).
    /* $cron = cron::byClassAndFunction('elmtouch', 'getGasDaily');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('elmtouch');
        $cron->setFunction('getGasDaily');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule(rand(10, 59) . ' 0' . rand(1, 5) . ' * * *');
        $cron->save();
    }
    $cron->setSchedule(rand(10, 59) . ' 0' . rand(1, 5) . ' * * *');
    $cron->save(); */
     // On supprime l'ancien cron qui n'est plus utilisé.
    $cron = cron::byClassAndFunction('elmtouch', 'getGasDaily');
    if (is_object($cron)) {
        $cron->stop();
        $cron->remove();
    }
}


function elmtouch_remove() {
    $cron = cron::byClassAndFunction('elmtouch', 'getGasDaily');
    if (is_object($cron)) {
        $cron->stop();
        $cron->remove();
    }
}
?>
