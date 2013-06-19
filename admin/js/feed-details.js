
/*
* traitement "Adopter un écran"
*/
function askDelete($button) {
  var itemid = $button.attr('value');
	var div = buildDialog ('Supprimer cet item ('+itemid+') ?',
    'Cet item sera supprimé',
		'Êtes vous sûr de vouloir supprimer l\'item ?');
	var data = { id : itemid };
  var url = 'ajax/delete-feed-item.php';
  var success = function (data) {
    if (data.ok) {
			// remove item from the table
			$button.parents('tr').remove();
			// should fetch an item to replace, and fix the numbers
			// but this is good enough for now
    } else {
      alert ('Une erreur est survenue lors de la suppression de l\'item');
    }
  };
	var error = function () {
    alert ('Une erreur est survenue lors de la suppression de l\'item');
  };
	askConfirm(div, data, url, success, error);
} 

$(function () {
	$(".feed-item-active").click(function(event) {
	  /*
	   * get the value of the screen id
	   */
	  $box = $(event.target);
	  /*
	   * ask if user is really sure
	   */
		event.stopImmediatePropagation();
		url = 'ajax/toggle-content.php';
		userdata = { id : $box.val(), checked : ($box.attr('checked')!==undefined)};
		successfunc = function (data) {
		};
		errorfunc = function () {
			console.log ('unable to contact server');
		};
	  $.ajax({
	    type:    'POST',
	    url:     url, 
	    data:    userdata,
	    success: successfunc,
	    error:   errorfunc,
	    datatype: 'json'  
	  });
	});
	
	$(".feed-item-delete").click(function (event) {
		$box = $(event.target);
		event.stopImmediatePropagation();
		askDelete($box);
	});
});
