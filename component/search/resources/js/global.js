var isCheckable = true;

$(document).ready(function() {

  $('#global').change(function(){
    var choice = $(this).children('option:selected').val();

    if (choice==0)
    {
      $('#field-types').show();
    }
    else
    {
      $('#field-types').hide();
    }
  });

  $('.fieldtype').click(function(){
    isCheckable = false;
    return true;
  });

});

/**
 * Checks the content that a user just typed in
 */
function checkInput(input)
{
  if (isCheckable==true)
  {
    if (isEmail(input))
    {
      $('input.fieldtype').removeAttr('checked');
      $('input.fieldtype[value=email]').prop({'checked' : true});
      return true;
    }

    if (isPhoneNumber(input))
    {
      $('input.fieldtype').removeAttr('checked');
      $('input.fieldtype[value=phone]').prop({'checked' : true});
      return true;
    }

    if (isDate(input))
    {
      $('input.fieldtype').removeAttr('checked');
      $('input.fieldtype[value=date]').prop({'checked' : true});
      return true;
    }

    if (isAddress(input))
    {
      $('input.fieldtype').removeAttr('checked');
      $('input.fieldtype[value=address]').prop({'checked' : true});
      return true;
    }
  }
  return false;
}