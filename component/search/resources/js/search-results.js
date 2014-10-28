/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

var aSearchIn = new Array();

function startLoadResults(){

  var oSearchIn = $('.link-results');

  var nCount = 0;
  oSearchIn.each(function(){
    aSearchIn[nCount] = new Array();
    aSearchIn[nCount]['url']= $(this).attr('id');
    aSearchIn[nCount]['target']= $(this).attr('rel');
    nCount++;
  });

  loadResults(0);

};

function loadResults(i)
{
  if (i == aSearchIn.length)
    return true;

  var contenturl = aSearchIn[i]['url'];
  var target = aSearchIn[i]['target'];

  i++;
  AjaxRequest(contenturl, '', '', target, '', '', 'loadResults('+i+')');
}

/**
 * Prepends html result to a result list
 */
function prependResults(psUrl, psToPrepend)
{
  console.log(psUrl);
  $.ajax({
    type: 'POST',
    url: psUrl,
    scriptCharset: "utf-8" ,
    contentType: "application/x-www-form-urlencoded; charset=UTF-8",
    success: function(oJsonData)
    {
      if(oJsonData.error)
        console.log(oJsonData.error);

      //$(psToPrepend).append(oJsonData.data);
    },
    async: false,
    dataType: "JSON"
  });

  return false;
}
