<?php
if (!isConnect()) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$date = array(
	'start' => init('startDate', date('Y-m-d', strtotime('-1 month ' . date('Y-m-d')))),
	'end' => init('endDate', date('Y-m-d', strtotime('+1 days ' . date('Y-m-d')))),
);

if (init('object_id') == '') {
	$object = object::byId($_SESSION['user']->getOptions('defaultDashboardObject'));
} else {
	$object = object::byId(init('object_id'));
}
if (!is_object($object)) {
	$object = object::rootObject();
}
if (is_object($object)) {
	$_GET['object_id'] = $object->getId();
}

sendVarToJs('object_id', init('object_id'));
?>

<div class="row row-overflow" id="div_thermostat">
    <div class="col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_object" class="nav nav-list bs-sidenav">
                <li class="nav-header">{{Liste objets}}</li>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
$allObject = object::buildTree();
foreach ($allObject as $object_li) {
	if ($object_li->getIsVisible() == 1 && count($object_li->getEqLogic(true, false, 'elmtouch')) > 0) {
		$margin = 15 * $object_li->parentNumber();
		if ($object_li->getId() == init('object_id')) {
			echo '<li class="cursor li_object active" ><a href="index.php?v=d&m=elmtouch&p=panel&object_id=' . $object_li->getId() . '" style="position:relative;left:' . $margin . 'px;">' . $object_li->getHumanName(true) . '</a></li>';
		} else {
			echo '<li class="cursor li_object" ><a href="index.php?v=d&m=elmtouch&p=panel&object_id=' . $object_li->getId() . '" style="position:relative;left:' . $margin . 'px;">' . $object_li->getHumanName(true) . '</a></li>';

		}
	}
}
?>
         </ul>
     </div>
 </div>
 <div class="col-lg-10">
    <div id="div_object">
        <legend style="height: 35px;">
            <span class="objectName"></span>
            <span class="pull-right">
                {{Du}} <input class="form-control input-sm in_datepicker" id='in_startDate' style="display : inline-block; width: 150px;" value='<?php echo $date['start'] ?>'/> {{au}}
                <input class="form-control input-sm in_datepicker" id='in_endDate' style="display : inline-block; width: 150px;" value='<?php echo $date['end'] ?>'/>
                <a class="btn btn-success btn-sm tooltips" id='bt_validChangeDate' title="{{Attention une trop grande plage de date peut mettre très longtemps a etre calculer ou même ne pas s'afficher}}">{{Ok}}</a>
            </span>
        </legend>
    </div>
    <div class="row">
        <div class="col-lg-4" id="div_displayEquipement"></div>
        <div class="col-lg-8" id="div_chartRuntime"></div>
    </div>
    <div id="div_charts"></div>
</div>
</div>
</div>
<?php include_file('desktop', 'panel', 'js', 'elmtouch');?>