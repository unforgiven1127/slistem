var typingTimer;
var doneTypingInterval = 1000;

function checkDuplicates() {

  var sFirstname = $('input[name=\'firstname\']').val();
  var sLastname = $('input[name=\'lastname\']').val();
  var eDup = $('#duplicates');

  if (sFirstname.length>0 && sLastname.length>0)
  {
    $('#duplicates').css('display', 'inline-block');
    var urlCheckDuplicate = eDup.attr('url');

    console.log('ajaxrequest '+urlCheckDuplicate);
    AjaxRequest(urlCheckDuplicate, false, 'formContact', 'duplicates');
  }
}

$(document).ready(
  function()
  {
    var eElems = $('input[name=\'firstname\'], input[name=\'lastname\'], input[name=\'phone\'], input[name=\'email\'], input[name=\'cellphone\']');

    eElems.keydown(function(){
        clearTimeout(typingTimer);
        typingTimer = setTimeout(checkDuplicates, doneTypingInterval);
    });

    eElems.change(function(){ checkDuplicates(); });
  }
);