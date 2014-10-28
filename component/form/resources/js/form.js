/**
 * Comment
 */
function toggleSection(poTitle, psSectionId)
{
  if($(poTitle).hasClass('sectionClosed'))
  {
    $('#'+psSectionId).fadeIn(250, function()
    {
      $(poTitle).addClass('sectionOpened');
      $(poTitle).removeClass('sectionClosed');
    });

    $('#'+psSectionId+' .form_field_inactive').removeClass('field_inactive');
    $('#'+psSectionId+' .form_field_inactive').removeAttr('disabled');
  }
  else
  {
    $('#'+psSectionId).fadeOut(250, function()
    {
      $(poTitle).addClass('sectionClosed');
      $(poTitle).removeClass('sectionOpened');
    });

    $('#'+psSectionId+' .form_field_inactive').addClass('field_inactive');
    $('#'+psSectionId+' .form_field_inactive').attr('disabled', 'disabled');
  }

  return true;
}

$(document).ready(function() {

  $('input[onfinishinput]').keypress(function(e)
	{
		startTimer($(e.target));
	});


});

var inputTimeout;
function startTimer(input_field)
{
  var timeout = input_field.attr("inputtimeout");

  if (timeout.lenght==0)
    timeout = 1000;

  if (inputTimeout != undefined)
		clearTimeout(inputTimeout);

	inputTimeout = setTimeout( function()
  {
    eval(input_field.attr("onfinishinput"));
  }
	, timeout);
}

/**
 * Checks if the input is an email adress
 */
function isEmail(input)
{
  input= $.trim(input);

  var regExp = new RegExp("[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])","");

  return regExp.test(input);
}


/**
 * Checks if the input is a phone number
 */
function isPhoneNumber(input)
{
  input= $.trim(input);
  var regExp = new RegExp("^[0-9]{3}-[0-9]{3}-[0-9]{4}|[0-9]{3}-[0-9]{4}-[0-9]{4}|[0-9]{10}|[0-9]{11}|[0-9]{2}-[0-9]{4}-[0-9]{4}$");

  console.log('phone number:'+regExp.test(input));
  return regExp.test(input);
}

/**
 * Checks if it is a date
 */
function isDate(input)
{
  input= $.trim(input);

  var regExp1 = new RegExp("^([0-2][0-9]|3[0-1])-(0[0-9]|1[0-2])-[0-9]{4}$"); // 24-01-2001
  var regExp2 = new RegExp("^([0-2][0-9]|3[0-1])/(0[0-9]|1[0-2])/[0-9]{4}$"); // 24/01/2001
  var regExp3 = new RegExp("^[0-9]{4}-(0[0-9]|1[0-2])-([0-2][0-9]|3[0-1])$"); // 2001/01/24
  var regExp4 = new RegExp("^[0-9]{4}/(0[0-9]|1[0-2])/([0-2][0-9]|3[0-1])$"); // 2001-01-24

  var output = (regExp4.test(input) | regExp3.test(input) | regExp2.test(input) | regExp1.test(input));

  console.log('date:'+output);
  return output;
}

/**
 * Checks if it is an address Japanese format
 */
function isAddress(input)
{
  input= $.trim(input);

  var regExp = new RegExp(".+to.+ku.+[0-9]{1,2}-[0-9]{1,2}");
  console.log('address:'+regExp.test(input));
  return regExp.test(input);
}