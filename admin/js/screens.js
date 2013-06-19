
/*
* traitement "Adopter un écran"
*/
function askAdopt($button) {
  var screenid = $button.val();
	var div = buildDialog ('Adopter cet écran ?',
    'Cet écran sera adopté par le système, pourra être configuré, '+
    'et recevra des informations à afficher.',
		'Êtes vous sûr de vouloir adopter l\'écran ?');
	var data = { id : screenid };
  var url = 'ajax/adopt-screen.php';
  var success = function (data) {
    if (data.adopted) {
      $adopted_cell = $button.parent();
      $active_cell = $adopted_cell.prev();
      $adopted_cell.html('oui');
      $active_cell.html(data.enabled?'oui':'non');
      $adopted_cell.parent().removeClass('notadopted');
    } else {
      alert ('Une erreur est survenue lors de l\'adoption de l\'écran');
    }
  };
	var error = function () {
    alert ('Une erreur est survenue lors de l\'adoption de l\'écran');
  };
	askConfirm(div, data, url, success, error);
} 

/*
* traitement "ignorer un écran"
*/
function askIgnore($button) {
  var screenid = $button.val();
	var div = buildDialog ('Ignorer cet écran ?',
    'Cet écran sera ignoré par le système',
		'Êtes vous sûr de vouloir ignorer l\'écran ?');
	var data = { id : screenid };
  var url = 'ajax/ignore-screen.php';
  var success = function (data) {
    if (data.ignored) {
			$cell = $button.parent();
			$line = $cell.parent();
			$line.remove();
    } else {
      alert ('Une erreur est survenue lors de l\'action');
    }
  };
	var error = function () {
    alert ('Une erreur est survenue lors de l\'action');
  };
	askConfirm(div, data, url, success, error);
}

$("button").click(function(event) {
  /*
   * get the value of the screen id
   */
  $button = $(event.target)
  /*
   * ask if user is really sure
   */
	if ($button.hasClass('adopt')) {
		askAdopt ($button);
	} 
	if ($button.hasClass('ignore')) {
		askIgnore ($button);
	}
  return false;
});
