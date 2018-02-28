
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

$('#typeEq').change(function(){
});

$('#bt_cronGenerator').on('click',function(){
   jeedom.getCronSelectModal({},function (result) {
       $('.eqLogicAttr[data-l1key=configuration][data-l2key=autorefresh]').value(result.value);
   });
});

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" ></span>';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" >';
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="type"></span>';
    tr += '<br/>';
    tr += '<span class="cmdAttr" data-l1key="subType"></span>';
    tr += '</td>';
    tr += '<td>';
    if (init(_cmd.subType) == 'numeric' || init(_cmd.subType) == 'binary') {
        tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    }
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
}

function printEqLogic(_eqLogic) {
    printScheduling(_eqLogic);
}

function printScheduling(_eqLogic){
   $.ajax({
    type: 'POST',
    url: 'plugins/elmtouch/core/ajax/elmtouch.ajax.php',
    data: {
        action: 'getLinkCalendar',
        id: _eqLogic.id,
    },
    dataType: 'json',
    error: function (request, status, error) {
        handleAjaxError(request, status, error);
    },
    success: function (data) {
        if (data.state != 'ok') {
            $('#div_alert').showAlert({message: data.result, level: 'danger'});
            return;
        }
        $('#div_schedule').empty();
        if(data.result.length == 0){
            $('#div_schedule').append("<center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez encore aucune programmation. Veuillez cliquer <a href='index.php?v=d&m=calendar&p=calendar'>ici</a> pour programmer votre thermostat Elm Touch à l'aide du plugin agenda}}</span></center>");
        }else{
            var html = '<legend>{{Liste des programmations du thermostat Elm Touch dans le plugin Agenda}}</legend>';
            for (var i in data.result) {
                var color = init(data.result[i].cmd_param.color, '#2980b9');
                if(data.result[i].cmd_param.transparent == 1){
                 color = 'transparent';
             }
             html += '<span class="label label-info cursor" style="font-size:1.2em;background-color : ' + color + ';color : ' + init(data.result[i].cmd_param.text_color, 'black') + '">';
             html += '<a href="index.php?v=d&m=calendar&p=calendar&id='+data.result[i].eqLogic_id+'&event_id='+data.result[i].id+'" style="color : ' + init(data.result[i].cmd_param.text_color, 'black') + '">'

             if (data.result[i].cmd_param.eventName != '') {
                html += data.result[i].cmd_param.icon + ' ' + data.result[i].cmd_param.eventName;
            } else {
                html += data.result[i].cmd_param.icon + ' ' + data.result[i].cmd_param.name;
            }
            html += '</a></span><br\><br\>';
        }
        $('#div_schedule').empty().append(html);
    }
}
});

}
