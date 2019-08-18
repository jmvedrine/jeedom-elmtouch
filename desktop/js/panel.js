
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

 $('#div_pageContainer').on( 'click','.eqLogic-widget .history', function () {
    $('#md_modal2').dialog({title: "Historique"});
    $("#md_modal2").load('index.php?v=d&modal=cmd.history&id=' + $(this).data('cmd_id')).dialog('open');
});


 $(".in_datepicker").datepicker();

 $('#bt_validChangeDate').on('click', function () {
    jeedom.history.chart = [];
    $('#div_displayEquipement').packery('destroy');
    displayThermostat(object_id, $('#in_startDate').value(), $('#in_endDate').value());
});

 displayThermostat(object_id,'','');

 function displayThermostat(object_id,_dateStart,_dateEnd) {
    $.ajax({
        type: 'POST',
        url: 'plugins/elmtouch/core/ajax/elmtouch.ajax.php',
        data: {
            action: 'getThermostat',
            object_id: object_id,
            version: 'dashboard',
            dateStart : _dateStart,
            dateEnd : _dateEnd,
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
            var icon = '';
            if (isset(data.result.object.display) && isset(data.result.object.display.icon)) {
                icon = data.result.object.display.icon;
            }
            $('.objectName').empty().append(icon + ' ' + data.result.object.name);
            $('#div_displayEquipement').empty();
            $('#div_charts').empty();
            $('#div_chartRuntime').empty();
            var series = []
            for (var i in data.result.eqLogics) {
                $('#div_displayEquipement').append(data.result.eqLogics[i].html);
                $('#div_charts').append( '<div class="chartContainer" id="div_graph' + data.result.eqLogics[i].eqLogic.id + '"></div>');
                series.push({
                    name: data.result.eqLogics[i].eqLogic.name,
                    data: data.result.eqLogics[i].runtimeByDay,
                    type: 'column',
                    tooltip: {
                        valueDecimals: 1
                    },
                });
                graphThermostat(data.result.eqLogics[i].eqLogic.id);
            }
            drawSimpleGraph('div_chartRuntime', series, 'column');
            positionEqLogic();
            $('#div_displayEquipement').packery({
                itemSelector: ".eqLogic-widget",
            gutter : 0,
            });
        }
    });
}

function graphThermostat(_eqLogic_id) {
    jeedom.eqLogic.getCmd({
        id: _eqLogic_id,
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (cmds) {
            jeedom.history.chart['div_graph' + _eqLogic_id] = null;
            var foundPower = false;
            for (var i  in cmds) {
             if (cmds[i].logicalId == 'heatingsupplytemp') {
                jeedom.history.drawChart({
                    cmd_id: cmds[i].id,
                    el: 'div_graph' + _eqLogic_id,
                    dateStart: $('#in_startDate').value(),
                    dateEnd: $('#in_endDate').value(),
                    option: {
                        graphColor: '#BDBDBD',
                        derive : 0,
                        graphStep: 1,
                        graphScale : 1,
                  /*      graphType : 'area',  */
                        graphZindex :1
                    }
                });
                foundPower = true;
            }
        }
        for (var i  in cmds) {
            if (cmds[i].logicalId == 'order') {
                jeedom.history.drawChart({
                    cmd_id: cmds[i].id,
                    el: 'div_graph' + _eqLogic_id,
                    dateStart: $('#in_startDate').value(),
                    dateEnd: $('#in_endDate').value(),
                    option: {
                        graphStep: 1,
                        graphColor: '#27ae60',
                        derive : 0,
                        graphZindex : 2
                    }
                });
            }
            if (!foundPower && cmds[i].logicalId == 'actif') {
                jeedom.history.drawChart({
                    cmd_id: cmds[i].id,
                    el: 'div_graph' + _eqLogic_id,
                    dateStart: $('#in_startDate').value(),
                    dateEnd: $('#in_endDate').value(),
                    option: {
                        graphStep: 1,
                        graphColor: '#2c3e50',
                        graphScale : 1,
                        graphType : 'area',
                        derive : 0,
                        graphZindex : 1
                    }
                });
            }
            if (cmds[i].logicalId == 'temperature') {
                jeedom.history.drawChart({
                    cmd_id: cmds[i].id,
                    el: 'div_graph' + _eqLogic_id,
                    dateStart: $('#in_startDate').value(),
                    dateEnd: $('#in_endDate').value(),
                    option: {
                        graphColor: '#f39c12',
                        derive : 0,
                        graphZindex : 4
                    }
                });
            }
            if (cmds[i].logicalId == 'temperature_outdoor') {
                jeedom.history.drawChart({
                    cmd_id: cmds[i].id,
                    el: 'div_graph' + _eqLogic_id,
                    dateStart: $('#in_startDate').value(),
                    dateEnd: $('#in_endDate').value(),
                    option: {
                        graphColor: '#2E9AFE',
                        derive : 0,
                        graphZindex : 3
                    }
                });
            }
        }
        setTimeout(function(){
            jeedom.history.chart['div_graph' + _eqLogic_id].chart.xAxis[0].setExtremes(jeedom.history.chart['div_graph' + _eqLogic_id].chart.navigator.xAxis.min,jeedom.history.chart['div_graph' + _eqLogic_id].chart.navigator.xAxis.max)
        }, 1000);
    }
});
}

function drawSimpleGraph(_el, _serie) {
    new Highcharts.StockChart({
        chart: {
            zoomType: 'x',
            renderTo: _el,
            height: 180,
            spacingTop: 0,
            spacingLeft: 0,
            spacingRight: 0,
            spacingBottom: 0
        },
        credits: {
          text: '',
          href: '',
        },
        navigator: {
            enabled: false
        },
        rangeSelector: {
            buttons: [{
                type: 'minute',
                count: 30,
                text: '30m'
            }, {
                type: 'hour',
                count: 1,
                text: 'H'
            }, {
                type: 'day',
                count: 1,
                text: 'J'
            }, {
                type: 'week',
                count: 1,
                text: 'S'
            }, {
                type: 'month',
                count: 1,
                text: 'M'
            }, {
                type: 'year',
                count: 1,
                text: 'A'
            }, {
                type: 'all',
                count: 1,
                text: 'Tous'
            }],
            selected: 6,
            inputEnabled: false
        },
        legend: {
            enabled: false
        },
        tooltip: {
            pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y} {{minute(s)}}</b><br/>',
            valueDecimals: 2,
        },
        yAxis: {
            format: '{value}',
            showEmpty: false,
            showLastLabel: true,
            min: 0,
            labels: {
                align: 'right',
                x: -5
            }
        },
        scrollbar: {
            barBackgroundColor: 'gray',
            barBorderRadius: 7,
            barBorderWidth: 0,
            buttonBackgroundColor: 'gray',
            buttonBorderWidth: 0,
            buttonBorderRadius: 7,
            trackBackgroundColor: 'none', trackBorderWidth: 1,
            trackBorderRadius: 8,
            trackBorderColor: '#CCC'
        },
        series: _serie
    });
}