
function format_currency(pvValue, psThousand, psDecimal, pnDecimal, psSymbole, pbSymbolebefore)
{
  pvValue = pvValue.trim();

  if(pvValue.length == 0 || pvValue == 0)
    return '';

  if(psThousand != " ")
    psThousand = ",";

  if(psDecimal != ",")
    psDecimal = ".";

  if(isNaN(pnDecimal) || pnDecimal < 0)
    pnDecimal = 2;

  //console.log('- - - -- - - - - - - - - - - - - - - - ');
  //console.log('original value: '+pvValue);

  //clean the string
  if(psDecimal == ".")
  {
    sValue = pvValue.split(",").join("").split(" ").join("");
  }
  else
  {
    sValue = pvValue.split(".").join("").split(" ").join("");
  }

  if(isNaN(sValue))
  {
    alert('Not a proper number.' );
    return '';
  }

  var asNumber = sValue.split(psDecimal);
  if(asNumber.length > 2)
  {
    alert('Too many decimal separator ['+psDecimal+'].' );
    return '';
  }
  var sInteger = sDecimal = '';

  //console.log('cleaned value: '+sValue);
  //console.log(asNumber);

  while(asNumber[0].length > 0)
  {
    //extract the 3 last digits, and update the string
    nLength = asNumber[0].length;
    sChunk = asNumber[0].substring((nLength -3), nLength);

    asNumber[0] = asNumber[0].substring(0, (nLength -3));

    //console.log('length: '+nLength+' // chunk: '+sChunk+' // rest: '+asNumber[0]);

    if(sChunk.length == 3 && asNumber[0].length > 0)
      sInteger= ','+sChunk+sInteger;
    else
      sInteger= sChunk+sInteger;

   // console.log(sInteger);
  }

  if(asNumber[1] && asNumber[1].length > 0 && !isNaN(asNumber[1]))
  {
    sDecimal = psDecimal + asNumber[1].substring(0, pnDecimal);
  }

  return sInteger+sDecimal;
}




function linkCurrencyFields(psFieldId, psFieldname, psParentName)
{

  var oForm = $("#"+psFieldId).closest("form");

  //bind change event on the parent unit field
  $(oForm).find("#"+psParentName+"_unit").change(function()
  {
    var sValue = $(this).val();
    $(oForm).find("#"+psFieldname+"_unit").val(sValue);
  });

  //bind change event on the parent currency field
  $(oForm).find("#"+psParentName+"_currency").change(function()
  {
    var sValue = $(this).val();
    $(oForm).find("#"+psFieldname+"_currency").val(sValue);
  });

  //init the child fields with the parent values
  $(oForm).find("#"+psParentName+"_unit").change();
  $(oForm).find("#"+psParentName+"_currency").change();

}