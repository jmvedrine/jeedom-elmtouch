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
    // Cron de récupération des consommations de gaz.
    $cron = cron::byClassAndFunction('elmtouch', 'getGasDaily');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('elmtouch');
        $cron->setFunction('getGasDaily');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule(rand(10, 59) . ' 0' . rand(1, 5) . ' * * *');
        $cron->save();
    }
}

function elmtouch_update() {
    $pluginId = 'elmtouch';
    $cron = cron::byClassAndFunction($pluginId, 'getGasDaily');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass($pluginId);
        $cron->setFunction('getGasDaily');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule(rand(10, 59) . ' 0' . rand(1, 5) . ' * * *');
        $cron->save();
    }

    $dependencyInfo = elmtouch::dependancy_info();
    if (!isset($dependencyInfo['state'])) {
        message::add($pluginId, __('Veuilez vérifier les dépendances', __FILE__));
    } elseif ($dependencyInfo['state'] == 'nok') {
        try {
            $plugin = plugin::byId($pluginId);
            $plugin->dependancy_install();
        } catch (\Throwable $th) {
            message::add($pluginId, __('Cette mise à jour nécessite de réinstaller les dépendances même si elles sont marquées comme OK', __FILE__));
        }
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
