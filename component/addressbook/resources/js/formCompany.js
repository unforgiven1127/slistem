var typingTimer;
var doneTypingInterval = 1000;

function checkDuplicates() {

  var sCpName = $('input[name=\'name\']').val();
  var eDup = $('#duplicates');

  if (sCpName.length>0)
  {
    $('#duplicates').css('display', 'inline-block');
    var urlCheckDuplicate = eDup.attr('url');

    console.log('ajaxrequest '+urlCheckDuplicate);
    AjaxRequest(urlCheckDuplicate, false, 'formCompany', 'duplicates');
  }
}

$(document).ready(
  function()
  {
    var eElems = $('input[name=\'name\'], input[name=\'corporate\'], input[name=\'phone\'], input[name=\'email\'], input[name=\'address_1\']');

    eElems.keydown(function(){
        clearTimeout(typingTimer);
        typingTimer = setTimeout(checkDuplicates, doneTypingInterval);
    });

    eElems.change(function(){ checkDuplicates(); });
  }
);