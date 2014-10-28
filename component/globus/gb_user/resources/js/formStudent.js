var sToRefresh = '';

/**
 * Shows the Select groups element
 */
function showGroupSelect ()
{
  console.log(sToRefresh);
  $('#'+sToRefresh).parent().parent('.fieldNamegroupfk').css('display', 'inline-block');
  $('#'+sToRefresh).parent().parent('.fieldNameemptygroup').hide();
  return true;
}

/**
 * Hides the Select groups element and hide the select
 */
function hideGroupSelect()
{
  $('#'+sToRefresh).empty();
  $('#'+sToRefresh).parent().parent('.fieldNamegroupfk').hide();
  $('#'+sToRefresh).parent().parent('.fieldNameemptygroup').css('display', 'inline-block');
  return true;
}

/**
 * Refreshs select content
 */
function refreshGroups(nCompanyFk, sToRefresh, sUrl)
{
  if (nCompanyFk!='0')
  {
    sUrl = sUrl.replace('ppk=0','ppk='+nCompanyFk);
  }

   AjaxRequest(sUrl, false, '', sToRefresh);

   return true;
}

$(document).ready(function() {

  $('#companyfkId').change(function(){

    var nCompanyFk = $(this).children('option:selected').attr('value');
    var sToRefresh = $(this).attr('refreshDiv')+'Id';
    var sUrl = $(this).attr('refreshWithUrl');

    refreshGroups(nCompanyFk, sToRefresh, sUrl);

    return true;
  });

  $('input[name=firstname], input[name=lastname]').change(function(){
    var newStr = ucfirst($(this).val(),true);
    $(this).val(newStr);
  });

  var nCompanyFk = $('#companyfkId > option:selected').attr('value');
  var sToRefresh = $('#companyfkId').attr('refreshDiv')+'Id';
  var sUrl = $('#companyfkId').attr('refreshWithUrl');

  refreshGroups(nCompanyFk, sToRefresh, sUrl);

});