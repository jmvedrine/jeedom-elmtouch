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

	foreach (eqLogic::byType('elmtouch') as $eqLogic) {
		// Pour permettre un affichage correct du nom dans les 2 commandes action
		$cmd = $eqLogic->getCmd(null, 'mode');
		if (is_object($cmd)) {
			$cmd->setName(__('Nom du mode', __FILE__));
			$cmd->setIsVisible(0);
			$cmd->save();
		}

		// Nouvelle commande info binary mode associée aux 2 commandes action.
		$clockState = $eqLogic->getCmd(null, 'clock_state');
		if (!is_object($clockState)) {
			$clockState = new elmtouchCmd();
			$clockState->setName(__('Mode', __FILE__));
			$clockState->setIsVisible(0);
			$clockState->setEqLogic_id($eqLogic->getId());
			$clockState->setType('info');
			$clockState->setSubType('binary');
			$clockState->setLogicalId('clock_state');
			$clockState->save();
		}

		// normalizeName ne marche pas sur les anciens noms ce qui empèchait d'utiliser un widget core.
		$cmd = $eqLogic->getCmd(null, 'clock');
		if (is_object($cmd)) {
			$cmd->setName(__('Activer programme', __FILE__));
			$cmd->setValue($clockState->getId());
			$cmd->setTemplate('dashboard', 'elmtouch::usermode');
			$cmd->setTemplate('mobile', 'elmtouch::usermode');
			$cmd->save();
		}
		$cmd = $eqLogic->getCmd(null, 'manual');
		if (is_object($cmd)) {
			$cmd->setName(__('Désactiver programme', __FILE__));
			$cmd->setValue($clockState->getId());
			$cmd->setTemplate('dashboard', 'elmtouch::usermode');
			$cmd->setTemplate('mobile', 'elmtouch::usermode');
			$cmd->save();
		}
		// Nouveaux templates
		$cmd = $eqLogic->getCmd(null, 'hotwateractive');
		if (is_object($cmd)) {
			$cmd->setTemplate('dashboard', 'elmtouch::hotwateractive');
			$cmd->setTemplate('mobile', 'elmtouch::hotwateractive');
			$cmd->save();
		}
		$cmd = $eqLogic->getCmd(null, 'hotwater_Off');
		if (is_object($cmd)) {
			$cmd->setTemplate('dashboard', 'elmtouch::hotwater');
			$cmd->setTemplate('mobile', 'elmtouch::hotwater');
			$cmd->save();
		}
		$cmd = $eqLogic->getCmd(null, 'hotwater_On');
		if (is_object($cmd)) {
			$cmd->setTemplate('dashboard', 'elmtouch::hotwater');
			$cmd->setTemplate('mobile', 'elmtouch::hotwater');
			$cmd->save();
		}
		$cmd = $eqLogic->getCmd(null, 'heatstatus');
		if (is_object($cmd)) {
			$cmd->setTemplate('dashboard', 'elmtouch::burner');
			$cmd->setTemplate('mobile', 'elmtouch::burner');
			$cmd->save();
		}
		$eqLogic->save();
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
