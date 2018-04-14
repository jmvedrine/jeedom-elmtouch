<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('elmtouch');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un ELM Touch}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
<?php
foreach ($eqLogics as $eqLogic) {
    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity .'"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
            ?>
           </ul>
       </div>
   </div>

   <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend>{{Mes ELM Touchs}}</legend>
  <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
  <div class="eqLogicThumbnailContainer">
<?php
if (count($eqLogics) == 0) {
      echo '<div class="cursor eqLogicAction" data-action="add" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
          <i class="fa fa-plus-circle" style="font-size : 6em;color:#94ca02;"></i>
        <br>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02">{{Ajouter}}</span>
    </div>';
}
?>
      <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
      <i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
    <br>
    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{Configuration}}</span>
  </div>
  </div>
  <legend><i class="fa fa-table"></i> {{Mon ELM Touch}}</legend>
<div class="eqLogicThumbnailContainer">
    <?php
foreach ($eqLogics as $eqLogic) {
    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
    echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
    echo "<br>";
    echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
    echo '</div>';
}
?>
</div>
</div>

<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
  <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
  <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
    <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
    <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
<?php
try {
	$plugin = plugin::byId('calendar');
	if (is_object($plugin)) {
?>
    <li  role="presentation"><a href="#configureSchedule" aria-controls="profile" data-toggle="tab"><i class="fa fa-clock-o"></i> {{Programmation}}</a></li>
<?php
}
} catch (Exception $e) {

}
?>
  </ul>
  <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
<div role="tabpanel" class="tab-pane active" id="eqlogictab">
      <br/>
    <form class="form-horizontal">
        <fieldset>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Nom de l'équipement ELM Touch}}</label>
                <div class="col-sm-3">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement ELM Touch}}"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                <div class="col-sm-3">
                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                        <option value="">{{Aucun}}</option>
                        <?php
foreach (object::all() as $object) {
    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
                   </select>
               </div>
           </div>
       <div class="form-group">
        <label class="col-sm-3 control-label">{{Catégorie}}</label>
        <div class="col-sm-9">
         <?php
            foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
            echo '<label class="checkbox-inline">';
            echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
            echo '</label>';
            }
          ?>
        </div>
        </div>
    <div class="form-group">
        <label class="col-sm-3 control-label"></label>
        <div class="col-sm-9">
            <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
            <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
        </div>
    </div>
        <div class="form-group">
        <label class="col-sm-3 control-label">{{Coefficient de conversion}}
            <sup>
                <i class="fa fa-question-circle tooltips" title="{{Un m3 de gaz possède un pouvoir calorifique variable. GRDF calcule un coefficient de conversion m3 -> kWh suivant la ville et la période. L'application Bosch utilise 8.125.}}"></i>
            </sup>
        </label>
        <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="convkwhm3" placeholder="{{Facteur de conversion kWh / m3}}"/>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label">{{Prix du gaz par kWh}}
            <sup>
                <i class="fa fa-question-circle tooltips" title="{{Le gaz en France est facturé par kWh même si le compeur affiche des m3, consultez votre facture pour connaître ce prix.}}"></i>
            </sup>
        </label>
        <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="prixgazkwh" placeholder="{{Prix en € par kWh}}"/>
        </div>
        <div class="col-lg-2">
              <a class="btn btn-info bt_reImport" id="bt_reImport"><i class='fa fa-qrcode'></i> {{Ré-importer les consommations}}</a>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label">{{Auto-actualisation (cron)}}</label>
        <div class="col-sm-8">
            <input type="checkbox" class="eqLogicAttr" data-label-text="{{Activer}}" data-l1key="configuration" data-l2key="cron_isEnable" checked/>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-3 control-label"></label>
        <div class="col-sm-2">
            <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="autorefresh" placeholder="{{Auto-actualisation (cron)}}"/>
        </div>
        <div class="col-sm-1">
            <i class="fa fa-question-circle cursor floatright" id="bt_cronGenerator"></i>
        </div>
    </div>
</fieldset>
</form>
</div>

<div role="tabpanel" class="tab-pane" id="commandtab">
<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
<table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th style="width: 50px;">#</th>
            <th style="width: 200px;">{{Nom}}</th>
            <th style="width: 200px;">{{Type}}</th>
            <th style="width: 100px;">{{Paramètres}}</th>
            <th style="width: 150px;"></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>

<div role="tabpanel" class="tab-pane" id="configureSchedule">
    <form class="form-horizontal">
        <fieldset>
            <br/>
            <div id="div_schedule"></div>
        </fieldset>
    </form>
</div>

</div>

</div>
</div>
<div id="md_modal_elmtouch" title="{{Ré-importer les consommations}}">
  <p>
    <span class="glyphicon glyphicon-warning-sign" style="float:left; margin:12px 12px 20px 0;"></span>
    {{Ce bouton va relancer l'importation des consommations.}}<br />
    {{A raison de 32 jours toutes les 15 minutes.}}<br />
    {{Les anciennes valeurs seront remplacées par les nouvelles.}}<br />
    {{Les consommations en m3 et en kWh seront recalculées.}}<br />
    {{Assurez vous que vos facteurs de conversion sont bien corrects.}}
  </p>
</div>
<script>
  $('#md_modal_elmtouch').dialog({
    autoOpen: false,
    buttons: {
      "{{Continuer}}": function() {
        $( this ).dialog( "close" );
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/elmtouch/core/ajax/elmtouch.ajax.php", // url du fichier php
            data: {
                action: "resetConso",

            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                $('#div_alert').showAlert({message: '{{Ré-importation lancée, soyez patient}}', level: 'success'});
            }
        });
      },
      "{{Annuler}}": function() {
        $( this ).dialog( "close" );
      }
    }
  });
  $('#bt_reImport').on('click', function () {
    $('#md_modal_elmtouch').dialog('open');
    return false;
  });
</script>
<?php include_file('desktop', 'elmtouch', 'js', 'elmtouch');?>
<?php include_file('core', 'plugin.template', 'js');?>
