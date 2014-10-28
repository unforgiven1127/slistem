/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function()
{
  $('.seeMoreLink').click(function()
  {
    var sTarget = $(this).attr('rel');
    if(!sTarget)
      return false;

    var sSelectorToToggle = $(this).attr('data-to-toggle');

    if($(sSelectorToToggle).length)
    {
      $(sSelectorToToggle).fadeToggle(300, function()
      {
        $('.seeMoreContent[rel='+sTarget+']').fadeToggle(300);
      });
    }
    else
      $('.seeMoreContent[rel='+sTarget+']').fadeToggle(300);

    return false;
  });
});


/**
 * Manage toggling betwenn a short and loing texts created by displa->getTogglingText()
 */
function toggleText(poElement, psAction)
{
  var oContainer = $(poElement).closest('.toggleTextContainer');

  if(psAction == 'open')
  {
    $('.togg_short_text', oContainer).hide(100, function()
    {
       $('.togg_link_open', oContainer).hide(0);
       $('.togg_link_close', oContainer).show(0, function()
       {
         $('.togg_long_text', oContainer).slideDown(500);
       });
    });
  }
  else
  {
    $('.togg_long_text', oContainer).slideUp(150, function()
    {
       $('.togg_link_close', oContainer).hide(0);
       $('.togg_link_open', oContainer).show(0);
       $('.togg_short_text', oContainer).slideDown(200);
    });
  }
}