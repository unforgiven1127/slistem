
function checkForm(psFormName)
{
  var oForm = $('form[name='+psFormName+']');
  var bError = false;

  //clear previous error messages
  clearErrorMessage();

  //checking form fields
  $(oForm[0].elements).each(function()
  {
     //no js control on hiddem fields and ones with no control defined

     if($(this).attr('type') != 'hidden' && $(this).attr('jsControl') !== undefined && !$(this).hasClass('field_inactive'))
     {
        var asControls = $(this).attr('jsControl').split('|');

        if($(this).is('select') && $(this).attr('multiple'))
        {
          asFieldValue = $(this).val() || [];
          var sFieldValue = asFieldValue.join(',');
        }
        else
          var sFieldValue = $(this).val();


        for( nCount=0; nCount < asControls.length; nCount++)
        {
          var asControlDetail = asControls[nCount].split('@');

          switch(asControlDetail[0])
          {
            case 'jsFieldNotEmpty':
            case 'jsFieldRequired':
              if(sFieldValue.split(' ').join('').length == 0)
              {
                bError = true;
                bindErrorMessage($(this), 'Can\'t be empty');
              }
            break;

            case 'jsFieldMinSize':
              if(sFieldValue)
              {
                nLength = sFieldValue.split(' ').join('').length;
                if(nLength < parseInt(asControlDetail[1]))
                {
                  bError = true;
                  bindErrorMessage($(this), asControlDetail[1]+' characters min (currently '+nLength+')');
                }
              }
            break;

            case 'jsFieldMaxSize':
              if(sFieldValue)
              {
                nLength = sFieldValue.split(' ').join('').length;
                if(nLength > parseInt(asControlDetail[1]))
                {
                  bError = true;
                  bindErrorMessage($(this), asControlDetail[1]+' characters max (currently '+nLength+')');
                }
              }
            break;

            case 'jsFieldMinValue':
              if(sFieldValue)
              {
                if(parseInt(sFieldValue) < parseInt(asControlDetail[1]))
                {
                  bError = true;
                  bindErrorMessage($(this), 'Value must not be lower than '+asControlDetail[1]);
                }
              }
            break;

            case 'jsFieldMaxValue':
              if(sFieldValue)
              {
                if(parseInt(sFieldValue) > parseInt(asControlDetail[1]))
                {
                  bError = true;
                  bindErrorMessage($(this), 'Value must not exceed '+asControlDetail[1]);
                }
              }
            break;

            case 'jsFieldTypeInteger':
              if(sFieldValue)
              {
                if(sFieldValue.length == 0 || parseInt(sFieldValue) == Number.NaN)
                {
                  bError = true;
                  bindErrorMessage($(this), 'Should be a integer number');
                }
              }
            break;

            case 'jsFieldTypeIntegerNegative':
              if(sFieldValue)
              {
                if(sFieldValue.length == 0 || parseInt(sFieldValue) == Number.NaN || parseInt(sFieldValue) > 0)
                {
                  bError = true;
                  bindErrorMessage($(this), 'Should be a negative integer number');
                }
              }
            break;

            case 'jsFieldTypeIntegerPositive':

              if(sFieldValue)
              {
                var intRegex = /^\d+$/;
                if(sFieldValue.length == 0 || parseInt(sFieldValue) == Number.NaN || parseInt(sFieldValue) < 0 || !(intRegex.test(sFieldValue)))
                {
                  bError = true;
                  bindErrorMessage($(this), 'Should be a positive integer number');
                }
              }
            break;

            case 'jsFieldTypeFloat':
              var value = parseFloat(sFieldValue);
              if(sFieldValue)
              {
                if(sFieldValue.length == 0 || isNaN(value) || sFieldValue.indexOf('.')<0)
                {
                  bError = true;
                  bindErrorMessage($(this), 'Should be a decimal number');
                }
              }
            break;

            case 'jsFieldTypeCurrencyJpy':

              if(sFieldValue)
              {
                sFieldValue = sFieldValue.split(',').join('');
                sFieldValue = sFieldValue.split(' ').join('');
                var value = parseFloat(sFieldValue);

                if(isNaN(value))
                {
                  bError = true;
                  bindErrorMessage($(this), 'Bad currency format, no letters or symbols allowed. (1500000 | 1,500,500 | 1 500 500 | 1.50)');
                }
                else
                {
                  var asNumber = sFieldValue.split('.');
                  //check number with more than 1 dots and the ones that are different than their float conversion
                  if(asNumber.length > 2 || (asNumber.length == 1 && value != sFieldValue))
                  {
                    bError = true;
                    bindErrorMessage($(this), 'Bad currency format, no letters or symbols allowed. (1500000 | 1,500,500 | 1 500 500 | 1.50)');
                  }
                  else
                  {
                    if(asNumber.length == 2 && asNumber[1].length > 2)
                    {
                      bError = true;
                      bindErrorMessage($(this), 'Currency can\'t have more than 2 decimals.');
                    }
                  }
                }
              }
            break;

            case 'jsFieldTypeCurrencyEu':

              if(sFieldValue)
              {
                sFieldValue = sFieldValue.split(' ').join('');
                var value = parseFloat(sFieldValue);

                if(isNaN(value) || value != sFieldValue || sFieldValue.indexOf('.') > 0)
                {
                  bError = true;
                  bindErrorMessage($(this), 'Allowed currency format: 1500000 | 1 500 500 | 1,50');
                }
              }
            break;

            case 'jsFieldGreaterThan':
              if(sFieldValue)
              {
                var sRef = parseInt(asControlDetail[1]);
                if(sRef && sFieldValue <= sRef)
                {
                  bError = true;
                  bindErrorMessage($(this), 'Should be greater than '+sRef);
                }
              }
            break;

            case 'jsFieldSmallerThan':
              if(sFieldValue)
              {
                var sRef = parseInt(asControlDetail[1]);
                if(sFieldValue >= sRef)
                {
                  bError = true;
                  bindErrorMessage($(this), 'Should be smaller than '+sRef);
                }
              }
            break;

            case 'jsFieldTypeEmail':
              if(sFieldValue)
              {
                 sFieldValue =$.trim(sFieldValue);
               //   var regExp = new RegExp("^[0-9a-zA-Z]+@[0-9a-zA-Z]+[\.]{1}[0-9a-zA-Z]+[\.]?[0-9a-zA-Z]+$","");
               var regExp = new RegExp("[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])","");

                if(!regExp.test(sFieldValue))
                {
                  bError = true;
                  bindErrorMessage($(this), 'Mail format not valid');
                }
              }
            break;

            case 'jsFieldTypeUrl':
              if(sFieldValue)
              {
                var regExp = new RegExp(/[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi);
                if(!regExp.test(sFieldValue))
                {
                  bError = true;
                  bindErrorMessage($(this), 'Url format not valid');
                }
              }
            break;

            case 'jsFieldJpPostCode':
              if(sFieldValue)
              {
                var regExp = new RegExp("^[0-9]{3}-[0-9]{4}|[0-9]{7}$");
                if(!regExp.test(sFieldValue))
                {
                  bError = true;
                  bindErrorMessage($(this), 'PostCode format not valid');
                }
              }

              break;
          }
        }
     }
  });

  if(!bError)
   return true;

  setTimeout('clearErrorMessage(true);', 6500);
  goPopup.setNotice('The form contains incorrect data.', {delay: 3500}, false, '', '', '', 'formNoticeError');

  return false;
}

function bindErrorMessage(poField, psMsg)
{
  var fieldName = $(poField).attr('name');

  if(fieldName.indexOf('[]'))
    fieldName = fieldName.split('[').join('').split(']').join('') +'_'+ Math.random();

  //check if there's already an error; add the msg and thats it
  if($("#formError_"+fieldName).is('div'))
  {
    $("#formError_"+fieldName).append(", "+psMsg);
    return true;
  }


  //check if i have to "attach" the error to the field container or to the body
  var oContainer = $(poField).closest('div.formFieldContainer');
  if(oContainer && $(oContainer).css('position') != 'relative')
  {
    //we need to add a error bloc, get the position
    var nFieldWidth = 0;
    var oPosition = $(poField).offset();
    if(!oPosition || !oPosition.top)
    {
      var oParentDiv = $(poField).closest('div.formField');
      oPosition = oParentDiv.offset();
      nFieldWidth = parseInt(oParentDiv.css('width'));
    }
    else
      nFieldWidth = parseInt($(poField).css('width'));


    $('#body').append("<div id='formError_"+fieldName+"' class='formErrorMsg' style='top:"+oPosition.top+"px; left:"+(parseInt(oPosition.left)+nFieldWidth +20)+"px;'><div class='formErrorArrow'></div>"+psMsg+"</div>");
  }
  else
  {
    //add the error bloc into the formFieldContainer
    $(oContainer).append("<div id='formError_"+fieldName+"' class='formErrorMsg shadow_dark' style='top:0; right:-50px;'><div class='formErrorArrow'></div>"+psMsg+"</div>");
  }


}

function clearErrorMessage(pbFade)
{
  if(pbFade)
  {
    $('.formErrorMsg').fadeOut(function(){  $(this).remove(); });
  }
  else
    $('.formErrorMsg').remove();
}

