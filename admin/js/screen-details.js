function getScreenId () {
	var $hid=$('#screen [name="id"]');
	return {"id": $hid.val()};
}

/******************************************************************************
 * screen parameters
 */

var screenParams = {
	'width' : [ 'Largeur', 'px', 1920],
	'height' : [ 'Hauteur', 'px', 1080]
};

var zoneParams = {
	'top' : [ 'Pos. Haut', 'px' ],
	'left' : [ 'Pos. Gauche', 'px' ],
	'width' : [ 'Largeur', 'px' ],
	'height' : [ 'Hauteur', 'px' ]
};

var cssParams = [
	'background', 'background-color', 'color',
	'font-size'
];

var screenData = undefined;

function getScreenData (callback) {
	/* grab screen id from current url */
	var p = getScreenId();
	$.ajax ({
		dataType: 'json',
		type: 'POST',
		url: '/admin/ajax/screen-zones.php',
		data: p,
		success: function (data, textStatus, jqXHR) {
			screenData = data;
			callback(data);
		},
		error: function (jqXHR, textStatus, errorThrown) {
			console.log (jqXHR);
			console.log (textStatus);
			console.log (errorThrown);
		}
	});
}

function getScreenParamLabel(param) {
	return screenParams[param][0];
}

function getScreenParamUnit(param) {
	return screenParams[param][1];
}

function getScreenParamValue(param) {
	var p = screenData['params'];
	var v = undefined;
	if (p!==null)
		v = p[param];
	if ((p===null)||(v===undefined)) {
		/* find default value */
		v = screenParams[param][2];
	}
	return v;
}

function setScreenParam(param, value) {
	var p = screenData['params']
	if (p===null) {
		p = {};
		screenData['params'] = p;
	} 
	p[param] = value;
}

function getZoneCount () {
	var zs = screenData['zones'];
	if (zs===null) return 0;
	return zs.length;
}

function addZone () {
	console.log(screenData);
	var zs = screenData['zones'];
	if (zs===null) {
		zs = [];
		screenData['zones'] = zs;
	}
	zs.push({});
	console.log(screenData);
}

function getZoneName (zoneid) {
	if (typeof zoneid == 'string')
		zoneid = parseInt(zoneid);
	var zs = screenData['zones'];
	if (zs===null) return null;
	if ((zoneid<0)&&(zoneid>=zs.length)) return null;
	var z = zs[zoneid];
	if (z===undefined) return '';
	return z['name'];
}

function setZoneName (zoneid, zone_name) {
	var zs = screenData['zones'];
	if (zs===null) return false;
	if ((zoneid<0)&&(zoneid>=zs.length)) return false;
	var z = zs[zoneid];
	if (z===undefined) return false;
	z['name'] = zone_name;
	return true;
}

function getZoneParamLabel (param) {
	return zoneParams[param][0];
}

function getZoneParamUnit (param) {
	return zoneParams[param][1];
}

function findZone (zone) {
	var zs = screenData['zones'];
	switch (typeof zone) {
	case 'number' :
		return zs[zone];
	case 'string' :
		for (i in zs) {
			var z = zs[i];
			console.log(z);
			if (z.hasOwnProperty('name')) {
				if (z.name==zone) {
					console.log('found zone \''+zone+'\'');
					return z;
				}
			}
		}
		break;
	default :
		return null;
	}
}

function getZoneParam (zone, param) {
	var z = findZone (zone);
	if (z===undefined) {
		console.log('unable to find zone '+(typeof zone)+'\''+zone+'\'');
		return null;
	}
	if (zone===null) return null;
	if (!z.hasOwnProperty('params')) return null;
	var params = z.params;
	if (params===null) return null;
	if (params.hasOwnProperty(param)) return params[param];
	return null;
}

function setZoneParams (zone, params) {
	console.log(zone);
	var z = findZone (zone);
	if (z===null) return false;
	z['params'] = params;
	return true;
}

/******************************************************************************
 *
 */

function getZoneViewer() {
	var zv = document.getElementById("zone-viewer");
	var $zv = undefined;
	if (zv===null) {
		var div = document.createElement('div');
		div.id='zone-viewer';
		div.style.border='1px solid black';
		var $sim = $("#simulator");
		div.style.width=$sim.css('width');
		div.style.height=$sim.css('height');
		div.style.position='relative';
		$zv = $(div);
		$zv.insertAfter($sim);
	} else {
		$zv = $(zv);
	}
	return $zv;
}

/******************************************************************************
 * edition de la liste des zones
 */

function addEmptyZone () {
	var zones = screenData['zones'];
	var nz = { 'name': '', 'params': null, 'new': true };
	zones.push(nz);
	return (zones.length-1)
}

function removeZone (zoneid) {
	/* la zone doit etre la derniere */
	var z = screenData['zones'];
	if ((z.length-1)!=zoneid) {
		console.log ('attempt to remove zone that is not last in list');
		return;
	}
	screenData['zones'] = z.slice(0, zoneid-2);
}

/******************************************************************************
 * affichage des zones
 */

function showZones () {
	var $zv = getZoneViewer();
	var zones=screenData['zones'];
	for (z in zones) {
		var zone = zones[z];
		var name = zone['name'];
		var params = zone['params'];
		var id = 'zv_'+z;
		var $zone_el = $('#'+id);
		if ($zone_el.length>0) {
			$zone_el = $zone_el[0];
		} else {
			/* not found. add zone item */
			var ze = document.createElement('div');
			ze.style.position='absolute';
			ze.id = id;
			var ze_name = document.createElement('span');
			ze_name.style.backgroundColor='black';
			ze_name.style.color='white';
			ze_name.appendChild(document.createTextNode(name));
			ze.appendChild(ze_name);
			$zone_el=$(ze);
			$zone_el.appendTo($zv);
		}
	}
}

/******************************************************************************
 * événements liés au formulaire de zone
 */

function zoneNameUpdated(event) {
	var zn_input = event.target;
	var name = zn_input.value;
	var zoneid = zn_input.getAttribute('zoneid');

	var zones = screenData['zones'];
	/* find if we already have a zone by that name 
	 * or name is empty
	 */
	var conflict = false;
	for (i=0; i<zones.length; i++) {
		if ((i!=zoneid)&&(zones[i]['name']==name)) {
			conflict = true;
		}
	}
	var c = '#99ff99';
	if ((conflict)||(name.length==0)) {
		c = '#ff9999';
	}
	zn_input.style.backgroundColor=c;

	z = zones[zoneid];
	z['name'] = name;

	var id = 'zv_'+zoneid;
	var $zd = $('#'+id);
	var $sp = $zd.children('span:first-child')
	$sp.empty();
	$(document.createTextNode(name)).appendTo($sp);
}

function findRow($item) {
	var $p = $item.parents().filter('tr')[0];
	return $($p);
}

function getZoneId($row) {
	var $cells = $row.children();
	var $cell1 = $($cells[0]);
	var input = $cell1.find('input')[0];
	var zoneid = input.getAttribute('zoneid');
	return zoneid;
}

function cancelZoneEdit (event) {
	var $button = $(event.target);
	event.stopImmediatePropagation();
	var $row = findRow($button)
	var zoneid = getZoneId($row);
	/* suppression de la zone si elle est nouvelle */
	var z = screenData['zones'][zoneid];
	if (z['new']===true) {
		removeZone(zoneid);
		$row.remove();
		$('#zv_'+zoneid).remove();
	}
	$("#add-zone").show();
}

/******************************************************************************
 *
 * Display screen data 
 * 
 */

function getTable (id, headers) {
	var $t = $('#'+id);
	/* checks if we have thead */
	var $th = $t.children('thead');
	if ($th.length==0) {
		$th = $('<thead/>').appendTo($t);
		$tr = $('<tr/>').appendTo($th);
		for (var i=0; i<headers.length; i++) {
			$h = $('<th/>').append(headers[i]).appendTo($tr);
			$h.addClass('col'+i);
		}
	}
	return $t;
}

function getTBody ($table) {
	var $tb = $table.children('tbody');
	if ($tb.length==0) {
		$tb = $('<tbody/>').appendTo($table);
	} else
	/* only one tbody per table */
		$tb = $tb[0];
	return $tb;
}

function getTFoot ($table) {
	var $th = $table.children('thead');
	var $tf = $table.children('tfoot');
	if ($tf.length==0) {
		$tf = $('<tfoot/>')
		$th.after($tf);
	} else 
		$tf = $tf[0];
	return $tf;
}

function iconButton(file, alt, func) {
	return $('<img/>').attr('src',file).addClass('icon-button')
		.attr('alt',alt).attr('title',alt).click(func);
}

/****
 * screen parameters
 */

function screenParamDisplay(name) {
	return [ 
           iconButton('images/edit.png','Modifier le paramètre',editScreenParam), 
					 $('<span/>').addClass('screen-param').attr('name',name).append(getScreenParamValue(name)),
           $('<span/>').addClass('screen-param-unit').append(getScreenParamUnit(name)),
				 ];
}

function screenParamForm(name) {
	return [
           iconButton('images/cancel.png','Annuler la modification du paramètre',editScreenParamCancel),
           iconButton('images/checkmark.png','Valider la modification du paramètre',editScreenParamValidate), 
					 $('<input/>').addClass('screen-param').attr('name', name).attr('type','text').attr('value',getScreenParamValue(name)),
					 $('<span/>').addClass('screen-param-unit').append(getScreenParamUnit(name)),
				 ];
}

function editScreenParam (event) {
	var $button = $(event.target);
	var $cell = $button.parents('td');
	var name = $cell.children('span').attr('name');
	$cell.empty().append(screenParamForm(name));
}

function editScreenParamCancel (event) {
	var $button = $(event.target);	
	var $cell = $button.parents('td');
	var name = $cell.children('input').attr('name');
	$cell.empty().append(screenParamDisplay(name));
}

function editScreenParamValidate (event) {
	var $button = $(event.target);	
	var $cell = $button.parents('td');
	var $input = $cell.children('input');
	var name = $input.attr('name');
	var value = $input.attr('value');
	setScreenParam (name, value);
	$cell.empty().append(screenParamDisplay(name));
	showSaveZones();
}

function displayScreenData() {
	var $tbody = getTBody(getTable ('screen-info', ['','valeurs']));
	var i=0;
	for (k in screenParams) {
		var $tr = $('<tr/>').append($('<td/>').append($('<span/>').append(getScreenParamLabel(k))))
			.append($('<td/>').append(screenParamDisplay(k)))
			.appendTo($tbody);
	}
}

/****
 * zone parameters
 */

function evCancelZoneEdit (event) {
	var $button = $(event.target);
	var $line = $button.parents('tr');
	var zn = parseInt($line.attr('zoneid'));
	$line.empty().append(zoneParamDisplayRow(zn));

}

//-----------------------------------------------------------------------------
// validate zone modifications
//

function evValidateZoneEdit (event) {
	var $line = $(event.target).parents('tr');
	var zoneid = parseInt($line.attr('zoneid'));
	var zone_name=$line.find('input.zone-param-name').val();
	var $zpv = $line.find('input.zone-param-value');
	var values = { };	
	var v;

	// get values from form
	v=parseInt($zpv[0].value);
	if (!isNaN(v)) values['top']=v;
	v=parseInt($zpv[1].value);
	if (!isNaN(v)) values['left']=v;
	v=parseInt($zpv[2].value);
	if (!isNaN(v)) values['width']=v;
	v=parseInt($zpv[3].value);
	if (!isNaN(v)) values['height']=v;

	// css rules values
	var $csslabels = $line.find('select.css-label');
	var $cssvalues = $line.find('input.css-value');
	$csslabels.each(function (index, elem) {
		values[$(elem).find(':selected').val()]=$($cssvalues[index]).val();
	});

	// save to storage
	console.log ('evValidateZoneEdit');
	console.log (values);
	setZoneName(zoneid, zone_name);
	setZoneParams(zoneid, values);
	$line.empty().append(zoneParamDisplayRow(zoneid));

	// display the 'save' button
	showSaveZones ();
}

//-----------------------------------------------------------------------------
// adds one css rule to a zone
//

function evAddCssRule (event) {
	var $e = $(event.target);
	var $p = $e.parents('div :first');
	var $z = $e.parents('tr');
	var zoneid = parseInt($z.attr('zoneid'));	
	$p.before(zoneParamFormCssRow(zoneParamFormCssRule(zoneid, null)));
}

//-----------------------------------------------------------------------------
// remove one css rule from a zone
//

function evDelCssRule (event) {
	var $e = $(event.target);
	var $p = $e.parents('div :first');
	$p.remove();
}

//-----------------------------------------------------------------------------
// Creates a new zone form when adding a brand new zone
//

function evAppendNewZone (event) {
	var $button = $(event.target);
	var $table = $button.parents('table');
	var zn = getZoneCount();
	addZone();
	// zn is the number of the new zone
	var $tbody = getTBody($table);
	var $tr = createZone(zn).append(zoneParamForm(zn))
		.appendTo($tbody);
}

//-----------------------------------------------------------------------------
// starts editing a zone
//

function evEditZone (event) {
	var $button = $(event.target);
	var $line = $button.parents('tr');
	var zoneid = parseInt($line.attr('zoneid'));
	console.log('evEditZone : '+zoneid.toString());
	$line.empty().append(zoneParamForm(zoneid));
}

//-----------------------------------------------------------------------------
// removes a zone completely 
//

function evDeleteZone (event) {
	alert('remove zone');
}

//-----------------------------------------------------------------------------
// saves screen definition to the server
//

function evSaveScreen (event) {
	alert('saving screen definitions');
}

//-----------------------------------------------------------------------------
// generate zones form
//

function zoneParamFormZoneName (zoneid) {
	var zn = getZoneName(zoneid);
	console.log ('zoneParamFormZoneName : \''+zn+'\'');
	return $('<input/>').addClass('zone-param-name').val(getZoneName(zoneid));
}

function zoneParamFormDimension(zoneid, dimension) {
	var v = getZoneParam(zoneid, dimension);
	if (typeof v === 'number')
		if (isNaN(v)) v='';
		else v = v.toString();
	if (v === null)
		v = '';
	console.log ('zoneParamFormDimension : '+zoneid.toString()+' \''+dimension+'\' -> \''+v+'\'');

	return $('<span/>').addClass('zone-param').append([
					 $('<span/>').addClass('zone-param-label').append(getZoneParamLabel(dimension)),
					 $('<input/>').addClass('zone-param-value').val(v),
					 $('<span/>').addClass('zone-param-unit').append(getZoneParamUnit(dimension)),
				 ]);
}

function zoneParamFormDimensions (zoneid, dimensions) {
	var dims = [];
	for (i in dimensions)
		dims.push(zoneParamFormDimension(zoneid, dimensions[i]));
	return dims;
}

function zoneParamFormCssRule (zoneid, rule) {
	var $select = $('<select/>').addClass('css-label');
	for (var i in cssParams) {
		var css = cssParams[i];
		var $option = $('<option/>').val(css).append(css);
		if (css==rule) 
			$option.attr('selected',true);
		$select.append($option);
	}
	var v = getZoneParam (zoneid, rule);
	if (v === null) 
		v = '';
	var $input = $('<input/>').addClass('css-value').val(v);
	return [
					 $select,
					 $input,
					 iconButton('images/delete.png','Supprimer la règle CSS', evDelCssRule)
				 ];
}

function zoneParamFormCssRow (contents) {
		return $('<div/>').addClass('zone-param-row').addClass('zone-css-row').append(contents);
}

function zoneParamFormCssRules (zoneid) {
	var rules = [];
	for (var i in cssParams) {
		var css = cssParams[i];
		var p = getZoneParam(zoneid, css);
		console.log(css,p);
		if (p !== null)
			rules.push(zoneParamFormCssRow(zoneParamFormCssRule (zoneid, css)));
	}
	rules.push(zoneParamFormCssRow([iconButton('images/add.png','Ajouter une règle CSS', evAddCssRule),'&nbsp;']));
	return rules;
}

function zoneParamFormParams (zoneid) {
	return [
           iconButton('images/cancel.png','Annuler la modification de la zone',evCancelZoneEdit),
           iconButton('images/checkmark.png','Valider la modification de la zone',evValidateZoneEdit), 
					 $('<div/>').addClass('zone-param-row').append(zoneParamFormDimensions(zoneid,['top', 'left'])),
					 $('<div/>').addClass('zone-param-row').append(zoneParamFormDimensions(zoneid,['width', 'height'])),
				 ].concat(zoneParamFormCssRules(zoneid));
}

function zoneParamForm (zoneid) {
	return [
					 $('<td/>').append(zoneParamFormZoneName(zoneid)),
					 $('<td/>').append(zoneParamFormParams(zoneid)),
				 ];
}

//-----------------------------------------------------------------------------
// Display zone information
//

function zoneParamDisplayDimension (zoneid, dimension) {
	var v = getZoneParam(zoneid, dimension);
	console.log ([zoneid, v]);
	var $dim = $('<span/>').addClass('zone-param-value')
	if ((v===undefined)||(v===null)||(v.length==0)||(isNaN(v))) $dim.addClass('zone-param-error').append('manquant');
	else $dim.append(v);
	return $('<span/>').addClass('zone-param').append([
					 $('<span/>').addClass('zone-param-label').append(getZoneParamLabel(dimension)),
					 $dim,
					 $('<span/>').addClass('zone-param-unit').append(getZoneParamUnit(dimension)),
				 ]);
}

function zoneParamDisplayDimensions (zoneid, dimensions) {
	var dims = [];
	for (i in dimensions)
		dims.push(zoneParamDisplayDimension(zoneid, dimensions[i]));
	return dims;
}

function zoneParamDisplayCssRule (zoneid, css) {
	var p = getZoneParam(zoneid, css);
	console.log('zoneParamDisplayCssRule : '+zoneid.toString()+' \''+css+'\' \''+p+'\'');
	if (p===null) return null;
	return $('<div/>')
					 .append($('<span/>').append(css).addClass('css-name'))
					 .append($('<span/>').append(p));
}

function zoneParamDisplayCssRules (zoneid) {
	var rules = [];
	for (i in cssParams) {
		var r = zoneParamDisplayCssRule (zoneid, cssParams[i]);
		if (r !== null)
			rules.push (r);
	}
	return rules;
}

function zoneParamDisplayZoneName (zoneid) {
	var name = getZoneName(zoneid);
	if ((name===undefined)||(name===null)||(name.length==0)) name = '&nbsp;';
	return $('<span/>').addClass('zone-param-name').append(name);
}

function zoneParamDisplay(zoneid) {
	return [
					 $('<div/>').addClass('zone-buttons').append([
						 iconButton('images/edit.png','Modifier les paramètres de la zone',evEditZone),
						 iconButton('images/delete.png','Supprimer la zone',evDeleteZone),
					 ]),
					 $('<div/>').addClass('zone-param-row').append(zoneParamDisplayDimensions(zoneid,['top', 'left'])),
					 $('<div/>').addClass('zone-param-row').append(zoneParamDisplayDimensions(zoneid,['width', 'height'])),
				 ].concat(zoneParamDisplayCssRules(zoneid));
}

function zoneParamDisplayRow (zoneid) {
	return [
					 $('<td/>').append(zoneParamDisplayZoneName(zoneid)),
					 $('<td/>').append(zoneParamDisplay(zoneid)),
				 ];
}

//-----------------------------------------------------------------------------
// creates a row container for the new zone
//

function createZone (zoneid) {
		var $tr = $('<tr/>').attr('zoneid',zoneid);
		return $tr;
}

//-----------------------------------------------------------------------------
// displays the zone information array
//
 
function displayZonesData() {
	var $table = getTable ('zones-info', ['nom','paramètres']);
	var $tbody = getTBody($table);
	var zones = screenData['zones'];
	for (zoneid in zones) {
		zoneid = parseInt(zoneid, 10);
		console.log('displayZonesData '+(typeof zoneid));
		createZone(zoneid).append(zoneParamDisplayRow(zoneid))
			.appendTo($tbody);
	}
	addAppendZoneButton(getTFoot($table));
}

//-----------------------------------------------------------------------------
// displays the save zones button (called when things have changed)
//

function showSaveZones () {
	$('#save-zones').css('display','');
}

function addAppendZoneButton ($tfoot) {
	$tfoot.empty().append($('<tr/>').addClass('zone-footer').append([
			$('<td/>'),
			$('<td/>').append([
				iconButton('images/add.png','Ajouter une zone',evAppendNewZone),
				iconButton('images/checkmark.png','Sauver les modifications',evSaveScreen).attr('id','save-zones').css('display','none')
			])
		]));
}

//-----------------------------------------------------------------------------
// displays informations about the screen
//

function displayData () {
	displayScreenData();
	displayZonesData();
}

$(function () {
	getScreenData(displayData);
});

