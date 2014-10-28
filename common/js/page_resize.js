/**
 * sizeManagement
 */

function sizeManagement(pasPageParam, pbUpdatePhpSize)
{
  var oPageSize = {};
  var oPageResizeTimer = null;

  if(pasPageParam && pasPageParam.length == 3)
  {
    nMinWidth = parseInt(pasPageParam[0]);
    nMinHeight = parseInt(pasPageParam[1]);
    nSwitchWidth = parseInt(pasPageParam[2]);
  }
  else
  {
    nMinWidth = 1050;
    nMinHeight = 730;
    nSwitchWidth = 1500;
  }


  $(window).resize(function(event)
  {
    //resize event is triggered by jquery-ui dialog, we need to ignore those
    if($(event.target).hasClass('ui-resizable'))
      return true;

    if(oPageResizeTimer)
      clearTimeout(oPageResizeTimer);
    //else
      //setCoverScreen(true, false, 0);

    oPageResizeTimer = setTimeout(function(){ $(this).trigger("resizeEnd"); }, 350);
  });


  $(window).bind("resizeEnd", function()
  {
    oPageResizeTimer = null;
    oPageSize = {width: $(window).width(), height: $(window).height()};

    if(pbUpdatePhpSize)
      updatePhpWindowSize();

    if(oPageSize.width < nMinWidth || oPageSize.height < nMinHeight)
    {
      removeWideCss();
      //alert("The window size is too small to display the page correctly. (w:"+oPageSize.width+"|h:"+oPageSize.height+" need w:"+nMinWidth+"|h:"+nMinHeight+")");
      goPopup.setPopupMessage('The window size is too small to display the page correctly.<br/>Currently [width:'+oPageSize.width+' | height:'+oPageSize.height+'] need [width:'+nMinWidth+' | height:'+nMinHeight+']', true, 'Browser/window size problem...', 515, 125);
      return true;
    }

    if($("link#widecss").length && $('body').attr('wide-css'))
      bCssLoaded = true;
    else
      bCssLoaded = false;

    /*alert($("link#widecss").length);
    alert($('body').attr('wide-css'));
    alert(bCssLoaded);*/

    /*console.log('wide css on ?'+ bCssLoaded);
    console.log($("link#widecss"));
    console.log($('body').attr('wide-css'));*/
    if(bCssLoaded && oPageSize.width < nSwitchWidth)
    {
      var sNotice = "Window is too small, changing display size ";
      setNotice({notice: sNotice}, false, 2000);
      setTimeout("removeWideCss();", 750);
    }

    if(!bCssLoaded && oPageSize.width > nSwitchWidth)
    {
      var sNotice = "Shift to wide screen display ?  <a href='javascript:;' onclick=\"addWideCss(); \" >yes</a> ";
      sNotice+= "/ <a href='javascript:;' onclick=\"removeWideCss(); \" >no</a> ";
      setNotice({notice: sNotice}, false, 8500, false);
    }

    //setCoverScreen(false);
  });
}


  function removeWideCss()
  {
    $('head #widecss').attr('href', '').remove();
    $('body').attr('wide-css', 0);

    AjaxRequest('/index.php5?uid=665-544&ppt=stgsys&setting=wide_css_on&value=0&pg=ajx');
    goPopup.removeActive();
  }

  function addWideCss()
  {
    var asCss = new Array(sWideCssFile);
    checkCssToInclude(asCss);
    $("head").append("<link id='widecss' rel='stylesheet' href='"+sWideCssFile+"' type='text/css' />");
    $('body').attr('wide-css', 1);

    AjaxRequest('/index.php5?uid=665-544&ppt=stgsys&setting=wide_css_on&value=1&pg=ajx');
    goPopup.removeByType('notice');
  }


  function toggleCss()
  {
    if($('head #widecss').length)
       return removeWideCss();

    return addWideCss();
 }
