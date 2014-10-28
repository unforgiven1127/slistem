
/**
 * A simple tab managemnet
 * not gettabs because we'll need to add duplicates tab and other stuff in this one
 */
function toggleFormTabs(poTab, psBlocId)
{
  $('#addcandidateId .formSection:visible:not(#'+psBlocId+',.candidate_inner_section)').fadeOut(75, function()
  {
    $(poTab).siblings('.selected').removeClass('selected');
    $(poTab).addClass('selected');

    $('#'+psBlocId).fadeIn(75);
  });

}


function toggleGenderPic(poTag, pnValue)
{
  if(poTag)
    sValue = parseInt($(poTag).val());

  if(pnValue)
  {
    sValue = pnValue;
    $("#addcandidateId #sexId").val(pnValue);
  }

  if(sValue == 2)
  {
    $("#addcandidateId .genderPic .man").hide(0);
    $("#addcandidateId .genderPic .woman").fadeIn();
  }
  else
  {
    $("#addcandidateId .genderPic .woman").hide(0);
    $("#addcandidateId .genderPic .man").fadeIn();
  }
}


function manageFormStatus(poTag, pnCandidatePk)
{
  var nValue = parseInt($(poTag).val());
  if(!nValue)
    return true;

  if( $('option.unavailable', poTag).length == 0)
    return true;

  if(nValue == 3)
  {
    if(!pnCandidatePk)
    {
      goPopup.setErrorMessage("To set up an interview, please use the dedicated meeting feature on the candidate page.\n\nThe candidate status will then be updated automatically when the meeting status will change.")
      $(poTag).val(2);
    }
    else
    {
      goPopup.setErrorMessage("To set up an interview, please use the dedicated meeting form <a href='javascript:;' style='font-size: inherit; color: red;' onclick='$(this).closest(\".ui-dialog\").find(\".ui-button\").click();  var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550; goPopup.setLayerFromAjax(oConf, \"/index.php5?uid=555-001&ppa=ppaa&ppt=meet&ppk="+pnCandidatePk+"&pg=ajx&pclose=1\");'>here</a>.\n\nThe candidate status will then be updated automatically when the meeting status will change.\nIf the meeting is not set through the form it <u>won't be accounted for the KPIs</u>.")
    }

    return true;
  }

  /*if(nValue >= 4 && nValue < 7)
  {
    alert('Gaaa .');
    return true;
  }*/
}

function toggleApproxAge(poTag, psType)
{
  var oForm = $(poTag).closest('form');
  var oDiv = $(poTag).closest('.formFieldContainer').find('.formField');

  if(psType == "age")
  {
    if($('input[name=age]:visible', oDiv).length == 0)
    {
      $('> *', oDiv).hide(0);
      $(oDiv).append('<input type="text" name="age" class="ageField" jsControl="jsFieldTypeIntegerPositive|jsFieldMaxValue@100" />');
    }
  }
  else
  {
    $(oDiv).removeClass('age_field_container');
    $('input[name=age]', oDiv).remove();
    $('> *', oDiv).fadeIn(0);
  }
  return true;
}

