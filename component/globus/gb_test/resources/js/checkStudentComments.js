/**
 * Checks if comments has been red and checkbox checked
 */
function checkComments()
{

  var checked = $('#commentList .actions input[checked=\'checked\']').length;
  var total = $('#commentList .actions input').length;
  
  if (total==checked)
    $('#confirm').show();
  else
    $('#confirm').hide();

  return true;
}


$(document).ready(function() {
  $('#commentList .actions input').click(function(){

    var isChecked = $(this).attr('checked');

    if (isChecked == undefined)
      $(this).attr('checked', 'checked');
    else
      $(this).removeAttr('checked');

    checkComments();

    return true;
  });
});