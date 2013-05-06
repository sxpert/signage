var cssParams = [
	'background', 'background-color', 'color',
	'font-size'
];

var zoneData = undefined;

function uriParams (){
	var s = window.location.search;
	var a = {};
	if ((s.length!=0)&&(s[0]=='?')) {
		s = s.substr(1);
		var e = s.split('&');
		for (var i in e) {
			var param=e[i];
			var p = s.indexOf('=');
			if (p>=0) {
				var key = s.substring(0,p);
				var value = s.substr(p+1);
				a[key]=value;
			}
		}
	}
	return a;
}

function getZoneData (callback) {
	/* grab screen id from current url */
	var p = uriParams();
	console.log(p);
	$.ajax ({
		dataType: 'json',
		type: 'POST',
		url: '/admin/ajax/screen-zones.php',
		data: p,
		success: function (data, textStatus, jqXHR) {
			callback(data);
		},
		error: function (jqXHR, textStatus, errorThrown) {
			console.log (textStatus);
			console.log (errorThrown);
		}
	});
}

function showZones () {
	console.log ('showZones');
	var $zv = getZoneViewer();
	var zones=zoneData['zones'];
	for (z in zones) {
		var zone = zones[z];
		console.log(zone);
		var name = zone['name'];
		var params = zone['params'];
		var id = 'zv.'+name;
		var $zone_el = $zv.children().find('#'+id);
		if ($zone_el.length>0) {
			$zone_el = $zone_el[0];
		} else {
			/* not found. add zone item */
			var ze = document.createElement('div');
			ze.id = 'zv.'+name;
			var ze_name = document.createElement('span');
			ze_name.style.backgroundColor='black';
			ze_name.style.color='white';
			ze_name.appendChild(document.createTextNode(name));
			ze.appendChild(ze_name);
			$zone_el=$(ze);
			$zone_el.appendTo($zv);
		}
		console.log($zone_el);
	}
}

function genSizeField(label, labelWidth, name, width, height, marginRight, unit, unitWidth) {
	var span = document.createElement('span');
	var span_label = document.createElement('span');
	var text_label = document.createTextNode(label);
	var input = document.createElement('input');
	var span_unit = document.createElement('span');
	var text_unit = document.createTextNode(unit);

	span_label.appendChild(text_label);
	span_label.style.display='inline-block';
	span_label.style.width=labelWidth; // tw
	
	span.appendChild(span_label);

	input.name=name;
	input.style.borderWidth='1px';
	input.style.width=width;
	input.style.height=height;
	input.style.textAlign='right';
	input.style.marginRight=marginRight;

	span.appendChild(input);

	span_unit.appendChild(text_unit);
	span_unit.style.display='inline-block';
	span_unit.style.width=unitWidth;

	span.appendChild(span_unit);
	
	return span;
}

function genCssRuleField(height) {
	var div = document.createElement('div');
	// un select avec des noms de rules css
	var select = document.createElement('select');
	select.style.width='150px';
	select.style.height=parseInt(height)+4+'px';
	select.style.borderWidth='1px';
	select.style.marginRight='3px';
	for(var i=0;i<cssParams.length;i++) {
		var option=document.createElement('option')
		option.value=cssParams[i];
		option.appendChild(document.createTextNode(cssParams[i]));
		select.appendChild(option);
	}
	div.appendChild(select);
	// un champ pour entrer les valeurs de la rule
	var value = document.createElement('input');
	value.type='text';
	value.style.width='230px';
	value.style.height=height;
	value.style.borderWidth='1px';
	value.style.marginRight='3px';
	div.appendChild(value);
	// un bouton pour supprimer la rule
	var del = document.createElement('button');
	del.appendChild(document.createTextNode('supprimer'));
	del.style.paddingLeft='1px';
	del.style.paddingRight='1px';
	del.style.width='70px';
	del.style.height=parseInt(height)+4+'px';
	$(del).click(function (event) {
		$app = $(event.target);
		event.stopImmediatePropagation();
		$app.parent().remove();
	});
	div.appendChild(del);
	var $div = $(div);
	$div.addClass('css-rule');
	return $div;
}

function getZoneViewer() {
	var zv = document.getElementById("zone-viewer");
	var $zv = undefined;
	console.log(zv);
	if (zv===null) {
		var div = document.createElement('div');
		div.id='zone-viewer';
		div.style.border='1px solid black';
		var $sim = $("#simulator");
		div.style.width=$sim.css('width');
		div.style.height=$sim.css('height');
		$zv = $(div);
		$zv.insertAfter($sim);
	} else {
		$zv = $(zv);
	}
	return $zv;
}

function genZoneForm($lastrow) {
	var newrow = document.createElement('tr');
	if (!$lastrow.hasClass('odd'))
		$(newrow).addClass('odd');
		
	/* cell with the input for the name */
	var cell1 = document.createElement('td');
	cell1.style.verticalAlign='top';
	newrow.appendChild(cell1);
	var input_name = document.createElement('input');
	input_name.style.position='relative';
	var $firstcell = $lastrow.children().first();
	input_name.style.width=($firstcell.width()-2)+'px';
	var h = ($firstcell.height()-4)+'px';
	input_name.style.height=h;
	input_name.style.borderWidth='1px';
	input_name.name='zone-name';
	cell1.appendChild(input_name);
	
	/* cell with parameters */
	var cell2 = document.createElement('td');
	newrow.appendChild(cell2);

	/* dimensions */
	var tw = '75px';
	var w = '100px';
	var mr = '3px';
	var uw = '49px';

	/* deux lignes pour 4 dimensions */
	var l1 = document.createElement('div'); 
  l1.appendChild( genSizeField('pos. gauche', tw, 'left', w, h, mr, 'px', uw));
  l1.appendChild( genSizeField('pos. haut', tw, 'top', w, h, mr, 'px', uw));
	cell2.appendChild(l1);
	
	var l2 = document.createElement('div');
  l2.appendChild( genSizeField('largeur', tw, 'width', w, h, mr, 'px', uw));
  l2.appendChild( genSizeField('hauteur', tw, 'heigth', w, h, mr, 'px', uw));
	cell2.appendChild(l2);

	/* deux lignes de boutons */

	/* ajout de regles css */
	var l3 = document.createElement('div');
	var appendcss = document.createElement('button');
	appendcss.id = 'append-zone-css';
	appendcss.appendChild(document.createTextNode('Ajouter une règle CSS'));
	$(appendcss).click(function (event) {
		var $app = $(event.target);
		event.stopImmediatePropagation();
		/* création de l'objet */
		genCssRuleField(h).insertBefore($app.parent());
	});
	l3.appendChild(appendcss);
	cell2.appendChild(l3);
	
	/* annuler / valider */
	var l4 = document.createElement('div');
	var cancel = document.createElement('button');
	cancel.id='cancel-zone';
	cancel.appendChild(document.createTextNode('Annuler la zone'));
	$(cancel).click(function (event) {
		$app = $(event.target);
		event.stopImmediatePropagation();
		$(newrow).remove();
		$("#add-zone").show();
	});
	l4.appendChild(cancel);
	var validate = document.createElement('button');
	validate.id='validate-zone';
	validate.appendChild(document.createTextNode('Créer la zone'));
	$(validate).click(function (event) {
		$app = $(event.target);
		event.stopImmediatePropagation();
		alert ('create zone');
	});
	l4.appendChild(validate);
	cell2.appendChild(l4);
	
	return newrow;
}

function findTableLastRow ($table) {
	var $tbody = $table.find('tbody');
	var $lastrow = $tbody.children().last();
	return $lastrow;
}

function appendRowToTable ($lastrow, newrow) {
	$lastrow.parent().append(newrow);
}

function showZoneForm ($addZoneButton) {
	/* hide button */
	$addZoneButton.hide();
	/* hide simulator */
	$("#simulator").hide();
	/* show zone viewer */
	getZoneViewer().show();
	var $table = $addZoneButton.parent().prev();
	var $lastrow = findTableLastRow($table);
	var newrow = genZoneForm($lastrow);
	appendRowToTable ($lastrow, newrow);
	return $(newrow);
}

$("#add-zone").click(function(event) {
  /*
   * get the value of the screen id
   */
  var $addZoneButton = $(event.target)
	event.stopImmediatePropagation();

	getZoneData(function (data) {
		zoneData = data;
		var $zone = showZoneForm ($addZoneButton);
		showZones();
	});
});

