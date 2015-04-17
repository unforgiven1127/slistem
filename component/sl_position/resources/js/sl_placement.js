
function refreshPlacementForm(poItem, psUrl)
{
  if(!psUrl || !poItem || !poItem.id)
    return false;

  //console.log(poItem);

  var asCpJd = poItem.id.split('_');
  if(asCpJd.length !== 2)
    return false;

  psUrl = psUrl + '&positionfk=' + asCpJd[1];
  //alert('Added ' + poItem.name + ' gonna go fill the next select '+psUrl);

  $.ajax(
  {
    url: psUrl,
    type: 'POST',
    dataType: 'json',
    success: function(oJson)
    {
      //console.log(oJson);
      if(!oJson.data)
      {
        alert('No playing or placed candidate for this position');
        return false;
      }

      $('#pla_candidatefkId').html('');
      $('#pla_loginfkId').html('');
      var bFirst = true;
      $.each(oJson.data, function(nIndex, oValue)
      {
        $('#pla_candidatefkId').append('<option value="'+oValue.candidatepk+'">'+oValue.candidate+'</option>');
        $('#pla_loginfkId').append('<option value="'+oValue.consultantpk+'">'+oValue.consultant+'</option>');


        if(bFirst)
        {
          bFirst= false;

          //first one found... update first payment raw
          $('#pay_loginfk0Id').tokenInput("clear").tokenInput("add", {id: oValue.consultantpk, name: oValue.consultant});

          if(oValue.contributor)
          {
            $(oValue.contributor).each(function(nIndex, sValue)
            {
              $('#pay_loginfk'+(nIndex+1)+'Id').tokenInput("clear").tokenInput("add", {id: sValue, name: oValue.contributor_name[nIndex]});
            });
          }
        }
      });
    }
  });


}


function mirrorSelection(poTag, psTargetId)
{
  var nIndexSelected = $(poTag).prop("selectedIndex");
  //alert('selected index: '+ nIndexSelected+' set #'+psTargetId);

  $('#'+psTargetId).prop("selectedIndex", nIndexSelected);
  return true;
}



function updatePaymentAmount(poTag)
{
  var sInvoiceAmount = $(poTag).val();
  if(!sInvoiceAmount || isNaN(sInvoiceAmount))
    return false;

  var oSection = $(poTag).closest('form').find('.payment_section');

  $('.formFieldContainer .split', oSection).each(function(nIndex)
  {
    var nSplit = $(this).val();
    if(!isNaN(nSplit))
    {
      $('#pay_amount'+nIndex, oSection).val( Math.round(parseInt(sInvoiceAmount) * (nSplit / 100)) );
    }
  });
}


function editPop(psUrl)
{
  var oConf = goPopup.getConfig();
  oConf.height = 750;
  oConf.width = 950;
  goPopup.setLayerFromAjax(oConf, psUrl);
}