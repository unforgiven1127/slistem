/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function()
{
  $(".subMenuOwner").each(function(nKey, oElem)
  {
    var sSubMenuId = $(oElem).attr("subMenu");

    $(oElem).bind("click", function()
    {
      $(".subMenu").hide(1, function(){  $("#"+sSubMenuId).show(1);  });
    });

  });
});


function toggleVerticalMenu(poTag, pbForceHide)
{
  if(!poTag)
    poTag = $('#toggleVertMenu');

  if(pbForceHide !== true && $(poTag).attr('current_width') == $(poTag).attr('min_width'))
  {
    var nWidth = $(poTag).attr('max_width');
    var oMenu = $(poTag).closest('.menu');

    $(oMenu).animate({width: nWidth+'px'}, 200);
    $('#componentContainerId').animate({marginLeft: 200+'px'}, 200);

    $(poTag).attr('current_width', nWidth);
    $(oMenu).addClass('menu_open');
  }
  else
  {
    var nWidth = $(poTag).attr('min_width');
    var oMenu = $(poTag).closest('.menu');

    $(oMenu).animate({width: nWidth+'px'}, 200);

    $(poTag).attr('current_width', nWidth);
    $('#componentContainerId').animate({marginLeft: '0'}, 200);
    $(oMenu).removeClass('menu_open');
    $('.subMenu:visible', oMenu).attr('style', '');
  }

}

function toggleSubmenu(poTag)
{
  var oMenu = $(poTag).closest('.menu');
  $('ul.subMenu:visible', oMenu).slideUp(400);
  $('.menu_item_label.expended', oMenu).removeClass('expended');

  $(poTag).parent().find('.subMenu').slideDown(400);
  $(poTag).addClass('expended');
}
