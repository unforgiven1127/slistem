$(document).ready(function()
{
  $('#sendForm').click(function(){

   if(checkForm('esaForm'))
    {
      var sURL = $('form[name=esaForm]').attr('action');
      var sFormId = $('form[name=esaForm]').attr('id');
      $('form[name=esaForm] input[name=status]').val('sent');
      setCoverScreen(true, true);
      setTimeout(" AjaxRequest('"+sURL+"', 'body', '"+sFormId+"', '', '', '', 'setCoverScreen(false);'); ", 350);
    }

  return false;
  });
});