function getSelectionHtml() {
    var html = "";
    if (typeof window.getSelection != "undefined") {
        var sel = window.getSelection();
        if (sel.rangeCount) {
            var container = document.createElement("div");
            for (var i = 0, len = sel.rangeCount; i < len; ++i) {
                container.appendChild(sel.getRangeAt(i).cloneContents());
            }
            html = container.innerHTML;
        }
        sel.removeAllRanges();
    } else if (typeof document.selection != "undefined") {
        if (document.selection.type == "Text") {
            html = document.selection.createRange().htmlText;
            document.selection.empty();
        }
    }
    if (html == "")
    {
      $('#noSelection').show();
      $('#noSelection').delay(5000).fadeOut();
    }
    else
    {
      var haystack = '<div>'+html+'</div>';
      idStart = +$(haystack).find('z').first().attr('id');
      idEnd = +$(haystack).find('z').last().attr('id');

      if ((isNaN(idStart) || isNaN(idEnd)) == false)
      {
        selectText(idStart, idEnd);
        showCommentForm(idStart, idEnd);
      }
    }
}

/**
 * Shows the Add Comment Form
 */
function showCommentForm(pnIdStart, pnIdEnd, psUrl)
{
  if (isNaN(pnIdStart) || isNaN(pnIdEnd))
    return false;

  sUrlAddComment = $('#addCommentLink').attr('formUrl');
  if ((typeof psUrl !== "undefined") && (psUrl.length > 0))
    sUrl = psUrl;
  else
    sUrl = sUrlAddComment+'&start='+pnIdStart+'&end='+pnIdEnd;

  AjaxRequest(sUrl, '', '', 'commentForm');

  return true;
}

/**
 * Hides Add COmment Form
 */
function hideCommentForm()
{
  $('#commentForm').hide();
  $('#commentForm').empty();
  $('#commentForm').removeClass();
  return true;
}

/**
 * Moves the Add Comment Form close to the selection
 */
function moveAddForm(psIdStart)
{
  $('#commentForm').appendTo('#'+psIdStart);
  $('#commentForm').show();
  var offset = $('#commentForm').offset();
  if (offset.left > 425)
  {
    if (offset.left < 680)
      $('#commentForm').addClass('center');
    else
      $('#commentForm').addClass('alignToLeft');
  }

  return true;
}

/**
 * Selects a slice of text
 */
function selectText(pnIdStart, pnIdEnd)
{
 /* TODO: wrap selection with tag instead of adding a class to everyelement.
  *
  * var sSelectedText = $('#seltext');

  if (sSelectedText.length==0)
  {
    $('#'+pnIdStart).before('<h id=\'seltext\'>');
    $('#'+pnIdEnd).after('</p>');
  }
*/
  if (!$('#'+pnIdStart).hasClass('highlighted'))
  {
    for (i = pnIdStart; i <= pnIdEnd; i++)
      $('#'+i).addClass('highlighted');
  }

  return true;
}

/**
 * Unselects text
 */
function unselectText()
{
 // var area = $('#seltext');
 // area.replaceWith(area.html());

  $('.highlighted').removeClass('highlighted');
  return true;
}

/**
 * Checks if the trainer has added comments
 */
function checkCorrectionForm()
{
  var nbComments = $('#listComments li').length;
  var good = $('form input[name=good]').val();

  if (good == '0')
    return (nbComments>0);
  else
    return true;
}

/**
 * Take Action button behavior
 * nGood = 2 -> Click fired by user, the function is called by onclick(), with its a link as a second parameter
 * nGood = -1 -> The user hasnt chosen any of the two options yet, no action
 * nGood = 0 or 1 -> The user has already clicked by the past and it has been saved. We restore the right state.
 */
function clickAction(nGood, oLink)
{
  if (nGood==-1)
    return false;
  else
  {
    if (nGood==2)
     var thisButton = oLink;
    else
    {
      var thisButton = (nGood==0) ? $('.bad') : $('.good');
    }
  }

  var isSelected = thisButton.hasClass('selected');

  if (!isSelected)
  {
    var isBad = thisButton.hasClass('bad');
    var brotherButton = thisButton.siblings('.teacherAction');

    brotherButton.removeClass('selected');
    thisButton.addClass('selected');

    var goodVal = (isBad)?0:1;
    $('input[name=good]').val(goodVal);
    var overallComment = thisButton.html();
    $('#overallComment').html(overallComment);
    $('.secondAction').show();

    if (!isBad)
    {
      $('#addCommentLink').hide();
      $('#sendForm').show();
    }
    else
    {
      $('#addCommentLink').show();
      toggleSendButton();
    }
  }
  return true;
}

/**
 * Display or hide the 'Send' button
 */
function toggleSendButton()
{
  var formIsFilled = checkCorrectionForm();

  console.log('Toggle: '+formIsFilled);

  if (formIsFilled)
    $('#sendForm').show();
  else
    $('#sendForm').hide();

  return true;
}

/**
 * Refreshes the comment list
 */
function refreshCommentList()
{
  var sUrl = $('#commentList').attr('refreshWith');

  AjaxRequest(sUrl, '', '', 'commentList');

  $('.highlighted').addClass('text_commented');
  $('#sendForm').fadeIn();

  unselectText();
  hideCommentForm();
}

/**
 * Unhighlight comments
 */
function unHighlightComments()
{
  $('#commentList li.selected').removeClass('selected');
  $('.pin.selected').removeClass('selected');
  $('.pin').show();
  unselectText();
}

/**
 * Highlights an existing comment
 */
function highlightComment(pnPk)
{
  var listItem = $('#commentList li[pk=\''+pnPk+'\']');
  var pin = $('.pin[pk=\''+pnPk+'\']');
  var nStart = listItem.attr('start');
  var nEnd = listItem.attr('end');

  console.log(nStart+' '+nEnd);

  unHighlightComments();
  listItem.addClass('selected');
  pin.addClass('selected');
  hideCommentForm();
  selectText(nStart, nEnd);
  $('.pin[pk!=\''+pnPk+'\']').hide();
  return false;
}

/**
 * Places a Pin to show where is the comment location
 */
function placePin(pnStart, pnPinNumber, pnPk)
{
  sPin = '<div class=\'pin\' pk=\''+pnPk+'\' onClick=\'highlightComment('+pnPk+');\'>'+pnPinNumber+'<span class=\'darrow\'></span></div>';
  $('#'+pnStart).append(sPin);
}

/**
 * Removes pins
 */
function removePins()
{
  $('.pin').remove();
}

$(document).ready(function() {

  $('body').click(function(e){
    var uiAutocomplete = $('.ui-autocomplete');
    var formContainer = $('#commentForm');
    var listItem = $('#listComments li a');
    var pin = $('.pin');

    if  ((!formContainer.is(e.target) && formContainer.has(e.target).length === 0)
      && (!uiAutocomplete.is(e.target) && uiAutocomplete.has(e.target).length === 0))
    {
      if (((!listItem.is(e.target)) && (listItem.has(e.target))) && ((!pin.is(e.target)) && (pin.has(e.target))))
        unHighlightComments();
      hideCommentForm();
    }
  });

  $('.teacherAction').click(function(){
    clickAction(2, $(this));
  });

  $('#sendForm').click(function(){

   if(checkCorrectionForm())
    {
      var formCorrection = $('form[name=correctionForm]');
      var sURL = formCorrection.attr('action');
      var sFormId = formCorrection.attr('id');
      $('#'+sFormId+' input[name="status"]').val('sent');
      setCoverScreen(true, true);
      setTimeout(" AjaxRequest('"+sURL+"', 'body', '"+sFormId+"', '', '', '', 'setCoverScreen(false);'); ", 350);
    }

  return false;
  });

});