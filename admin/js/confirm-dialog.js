
/*
* Construction de la boite de dialogue
*/
function buildDialog(action, message, msgconfirm) {
  var div = document.createElement('div');
  div.id = 'dialog-confirm';
  div.setAttribute('title', action);
  var p = document.createElement('p');
  var span = document.createElement('span');
  span.className = 'ui-icon ui-icon-alert';
  span.style.float = 'left';
  span.style.margin = '0 7px 7px 0';
  p.appendChild(span);
  var text1 = document.createTextNode(message);
  var br = document.createElement('br');
  var text2 = document.createTextNode(msgconfirm);
  p.appendChild(text1);
  p.appendChild(br);
  p.appendChild(text2);
  div.appendChild(p);
  document.body.appendChild(div);
  return div;
}

/*
* Demande de confirmation
*/
function askConfirm (div, userdata, url, successfunc, errorfunc) {
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
          url:     url, 
          data:    userdata,
          success: successfunc,
          error:   errorfunc,
          datatype: 'json'  
        });
      },
      "Non !": function () {
        $(this).dialog("close");
      }
    }
  }); 
}
