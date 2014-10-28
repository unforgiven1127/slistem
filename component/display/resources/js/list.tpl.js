function rowClic(poTag)
{
  $(poTag).siblings('.listRowSelected').removeClass('listRowSelected');
  $(poTag).addClass('listRowSelected');
}

function displayFilter(poTag)
{
  var nWidth = $(poTag).width();
  var oPosition = $(poTag).offset();
  if(!oPosition)
  {
    return false;
  }

  nLeft = oPosition.left - (nWidth/2);

  $(body).append("<div id='listFilterContainer' style='top:"+(oPosition.top+40)+"px; left:"+nLeft+"px;' ></div>");
  $('#listFilterContainer').append($('.filter_bloc', poTag).clone());
  $('#listFilterContainer .filter_bloc').show(0);

  return true;
}