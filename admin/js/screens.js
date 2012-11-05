function buildDialog() {
  var div = document.createElement('div');
  div.id = 'dialog-confirm';
  div.setAttribute('title', 'Adopter cet écran ?');
  var p = document.createElement('p');
  var span = document.createElement('span');
  span.className = 'ui-icon ui-icon-alert';
  span.style.float = 'left';
  span.style.margin = '0 7px 7px 0';
  p.appendChild(span);
  var text1 = document.createTextNode(
    'Cet écran sera adopté par le système, pourra être configuré, '+
    'et recevra des informations à afficher.');
  var br = document.createElement('br');
  var text2 = document.createTextNode('Êtes vous sûr de vouloir adopter l\'écran ?');
  p.appendChild(text1);
  p.appendChild(br);
  p.appendChild(text2);
  div.appendChild(p);
  document.body.appendChild(div);
  return div;
}

$("button").click(function(event) {
  /*
   * get the value of the screen id
   */
  $button = $(event.target)
  var screenid = $button.val();
  /*
   * ask if user is really sure
   */
  var div = buildDialog();
  $(div).dialog({
    resizable: false,
    modal: true,
    buttons: {
      "Oui": function() {
        $(this).dialog("close");
        /*
         * call the ajax on the server to adopt the screen
         */
        $.ajax({
          type:    'POST',
          url:     'ajax/adopt-screen.php', 
          data:    { 'id': screenid },
          success: function (data) {
                     if (data.adopted) {
                       $adopted_cell = $button.parent();
                       $active_cell = $adopted_cell.prev();
                       $adopted_cell.html('oui');
                       $active_cell.html(data.enabled?'oui':'non');
                       $adopted_cell.parent().removeClass('notadopted');
                     } else {
                       alert ('Une erreur est survenue lors de l\'adoption de l\'écran');
                     }
                   },
          error:   function () {
                     alert ('Une erreur est survenue lors de l\'adoption de l\'écran');
                   },
          datatype: 'json'  
        });
      },
      "Non !": function () {
        $(this).dialog("close");
      }
    }
  }); 
  return false;
}); 
