
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
      $('.fieldNamepla_loginfk_retainer').hide();
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
      $('#pla_candidatefkId').append('<option value="retainer">Retainer</option>');
    }
  });


}


function mirrorSelection(origin, target)
{
  var selected_index = $(origin).prop("selectedIndex");
  var selected_value = $(origin).val();

  if (selected_value === 'retainer')
  {
    $('.fieldNamepla_loginfk_retainer').show();
  }
  else
  {
    $('.fieldNamepla_loginfk_retainer').hide();
  }

  return true;
}

function updatePaymentAmount(tag)
{
  var full_salary = $('#full_salary').val();
  var salary_rate = $('#salary_rate').val();
  var refund_amount = $('#refund_amount').val();
  var check = false;
  var skip_invoice_amount = false;
  if(!full_salary || isNaN(full_salary) || !salary_rate || isNaN(salary_rate))
    check = true;

  var invoice_amount = (parseInt(full_salary) * (salary_rate / 100));

  if(refund_amount && refund_amount > 0)
  {
    if (refund_amount > invoice_amount && invoice_amount > 0)
    {
      $('#refund_amount').css('border', '1px solid red');
    }
    else if (!invoice_amount)
    {
      invoice_amount = refund_amount;
      skip_invoice_amount = true;
      $('#refund_amount').css('border', '');
    }
    else
    {
      invoice_amount -= refund_amount;
      $('#refund_amount').css('border', '');
    }
  }
  else if (check)
    return false;

  if (!skip_invoice_amount)
    $('#pla_amountId').val(Math.round(invoice_amount));

  var payment_section = $(tag).closest('form').find('.payment_section');

  $('.formFieldContainer .split', payment_section).each(function(field_index)
  {
    var split_percentage = $(this).val();
    if(!isNaN(split_percentage))
    {
      $('#pay_amount'+field_index, payment_section).val( Math.round(parseInt(invoice_amount) * (split_percentage / 100)) );
    }
  });
}

function update_payment_percentage(tag)
{
  var invoice_amount = $('#pla_amountId').val();
  var full_salary = $('#full_salary').val();
  var salary_rate = $('#salary_rate').val();
  var refund_amount = $('#refund_amount').val();
  var calculated_percentage;
  var check = false;

  if (!invoice_amount)
  {
    if (!full_salary && !salary_rate)
    {
      check = true;
    }
    else
    {
      invoice_amount = parseInt(full_salary) * (salary_rate / 100);
    }
  }

  if(refund_amount && refund_amount > 0)
  {
    if (refund_amount > invoice_amount && invoice_amount > 0)
    {
      $('#refund_amount').css('border', '1px solid red');
    }
    else if (invoice_amount === '')
    {
      invoice_amount = refund_amount;
      $('#refund_amount').css('border', '');
    }
    else
    {
      invoice_amount -= refund_amount;
      $('#refund_amount').css('border', '');
    }
  }
  else if (check)
    return false;

  var payment_section = $(tag).closest('form').find('.payment_section');

  $('.formFieldContainer .pay_amount', payment_section).each(function(field_index)
  {
    var split_amount = $(this).val();

    if(!isNaN(split_amount) || !split_amount || split_amount > 0)
    {
      calculated_percentage = parseFloat(split_amount / invoice_amount * 100).toFixed(5);

      if (calculated_percentage > 0)
        $('#split'+field_index, payment_section).val(calculated_percentage);
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

$(document).ready(function () {
    $(document).on('change', '#pla_loginfk_retainerId', function () {
        var user_id = $(this).val();
        var user_title = $('.pla_loginfk_retainer_item p').text();

        $('#pay_loginfk0Id').tokenInput("clear").tokenInput("add", {id: user_id, name: user_title});
        $('#pla_loginfkId').html('');
        $('#pla_loginfkId').append('<option value="'+user_id+'">'+user_title+'</option>');
    });
});