

function loadPositionMce(poTag)
{
  var oForm = $(poTag).closest('form');
  var sId = $('.content_html', oForm).attr('id');

  //$.tinyMCE.get('content_htmlId').setContent('asdasdasdassd');
   //$('.content_html', oForm).tinyMCE.get(sId).setContent('asdasdasdassd');

   return true;
}

function expandField(poTag)
{
  $(poTag).removeClass('compact');
  return true;
}