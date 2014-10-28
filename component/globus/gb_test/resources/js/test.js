$(document).ready(function()
{
  $('#sendForm').click(function(){

   if(checkForm('testAnswer'))
    {
      var sURL = $('form[name=testAnswer]').attr('action');
      var sFormId = $('form[name=testAnswer]').attr('id');
      $('form[name=testAnswer] input[name=status]').val('sent');
      setCoverScreen(true, true);
      setTimeout(" AjaxRequest('"+sURL+"', 'body', '"+sFormId+"', '', '', '', 'setCoverScreen(false);'); ", 350);
    }

  return false;
  });

  $('.noCopyPaste').bind('cut copy paste', function(event) {
        event.preventDefault();
    });

});