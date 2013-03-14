


$(".feed-item-active").click(function(event) {
  /*
   * get the value of the screen id
   */
  $box = $(event.target)
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
