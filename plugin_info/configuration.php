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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
$plugin = plugin::byId('elmtouch');
?>
<form class="form-horizontal">
    <fieldset>
      <legend>
         <i class="fa fa-list-alt"></i> {{Informations}}
      </legend>
      <div class="form-group">
         <?php
            $update = $plugin->getUpdate();
			$nodeVer = shell_exec("node -v");
			$npmVer = shell_exec("npm -v");
            if (is_object($update)) {
				echo '<div class="col-md-6">';
				echo '<div>';
				echo '<label>{{Branche}} :</label> '. $update->getConfiguration('version', 'stable');
				echo '</div>';
				echo '<div>';
				echo '<label>{{Source}} :</label> ' . $update->getSource();
				echo '</div>';
				echo '<div>';
				echo '<label>{{Version plugin}} :</label> ' . $update->getLocalVersion();
				echo '</div>';
				echo '<div>';
				echo '<label>{{Version NodeJS}} :</label> ' . $nodeVer;
				echo '</div>';
				echo '<div>';
				echo '<label>{{Version NPM}} :</label> ' . $npmVer;
				echo '</div>';
				echo '<div>';
				echo '<label>{{Version OS}} :</label> ' . shell_exec('lsb_release -ds');
				echo '</div>';
				echo '</div>';
			}
			?>
	  </div>
	  </fieldset>
	  <fieldset>
		<legend>
			<i class="fa fa-list-alt"></i> {{Paramètres}}
		</legend>
		<div class="form-group">
			<label class="col-sm-4 control-label">{{Vous laisser personnaliser entierement les widgets}}</label>
			<div class="col-sm-2">
				<input type="checkbox" class="configKey tooltips" data-l1key="widgetCustomization">
			</div>
		</div>
    </fieldset>
	<fieldset>
	  	<legend>
		    <i class="fas fa-user-cog"></i> {{Authentification}}
		</legend>
    <fieldset>
    <div class="form-group">
        <label class="col-lg-2 control-label">{{Numéro de série}}</label>
        <div class="col-lg-2">
            <input id="elmtouchserial" class="configKey form-control" data-l1key="serialNumber" style="margin-top:-5px" placeholder="{{Voir notice ou au dos}}"/>
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-2 control-label">{{Clé d'accès}}</label>
        <div class="col-lg-2">
            <input id="elmtouchaccess" class="configKey form-control" data-l1key="accessKey" style="margin-top:-5px" placeholder="{{Voir notice ou au dos}}"/>
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-2 control-label">{{Mot de passe}}</label>
        <div class="col-lg-2">
            <input id="elmtouchpassword" type="password" class="configKey form-control" data-l1key="password" style="margin-top:-5px" placeholder="{{Mot de passe choisi sur le smartphone}}"/>
        </div>
        <div class="col-lg-1">
            <i class="fas fa-eye-slash" id="bt_showPassword"></i>
        </div>
    </div>
    </fieldset>
</form>

<script>
$('#bt_showPassword').on('click', function() {
        event.preventDefault();
        if($('input.configKey[data-l1key="password"]').attr('type') == 'text'){
            $('input.configKey[data-l1key="password"]').attr('type', 'password');
            $('#bt_showPassword').addClass('fa-eye-slash');
            $('#bt_showPassword').removeClass('fa-eye');
        }else if($('input.configKey[data-l1key="password"]').attr('type') == 'password'){
            $('input.configKey[data-l1key="password"]').attr('type', 'text');
            $('#bt_showPassword').removeClass('fa-eye-slash');
            $('#bt_showPassword').addClass('fa-eye');
        }
});
</script>
