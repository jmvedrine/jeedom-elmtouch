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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect()) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    if (init('action') == 'resetConso') {
        $eqLogics = eqLogic::byType('elmtouch');
        foreach ($eqLogics as $eqLogic) {
            $eqLogic->resetHistory();
            ajax::success($password);
        }
    }

    if (init('action') == 'getThermostat') {
        if (init('object_id') == '') {
            $_GET['object_id'] = $_SESSION['user']->getOptions('defaultDashboardObject');
        }
        $object = object::byId(init('object_id'));
        if (!is_object($object)) {
            $object = object::rootObject();
        }
        if (!is_object($object)) {
            throw new Exception(__('Aucun objet racine trouvé', __FILE__));
        }
        $return = array('object' => utils::o2a($object));

        $date = array(
            'start' => init('dateStart'),
            'end' => init('dateEnd'),
        );

        if ($date['start'] == '') {
            $date['start'] = date('Y-m-d', strtotime('-1 months ' . date('Y-m-d')));
        }
        if ($date['end'] == '') {
            $date['end'] = date('Y-m-d', strtotime('+1 days ' . date('Y-m-d')));
        }
        $return['date'] = $date;
        foreach ($object->getEqLogic(true, false, 'elmtouch') as $eqLogic) {
            $return['eqLogics'][] = array('eqLogic' => utils::o2a($eqLogic), 'html' => $eqLogic->toHtml(init('version')), 'runtimeByDay' => array_values($eqLogic->runtimeByDay($date['start'], $date['end'])));
        }
        ajax::success($return);
    }

    if (init('action') == 'getLinkCalendar') {
        if (!isConnect('admin')) {
            throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }
        $elmtouch = elmtouch::byId(init('id'));
        if (!is_object($elmtouch)) {
            throw new Exception(__('Equipement Elm touch non trouvé : ', __FILE__) . init('id'));
        }
        try {
            $plugin = plugin::byId('elmtouch');
            if (!is_object($plugin) || $plugin->isActive() != 1) {
                ajax::success(array());
            }
        } catch (Exception $e) {
            ajax::success(array());
        }
        if (!class_exists('calendar_event')) {
            ajax::success(array());
        }
        $return = array();
		$programmablecmds = array('hotwater_On', 'hotwater_Off');
		foreach ($programmablecmds as $name) {
            $thermostat_cmd = $elmtouch->getCmd(null, $name);
            if (is_object($thermostat_cmd)) {
			    foreach (calendar_event::searchByCmd($thermostat_cmd->getId()) as $event) {
                    $return[$event->getId()] = $event;
                }
			}
		}
        $thermostat_cmd = $elmtouch->getCmd(null, 'thermostat');
        if (is_object($thermostat_cmd)) {
            foreach (calendar_event::searchByCmd($thermostat_cmd->getId()) as $event) {
                $return[$event->getId()] = $event;
            }
        }
        ajax::success(utils::o2a($return));
    }

    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode()); 
}
