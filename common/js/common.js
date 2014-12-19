/* basic functions needed all the time in the page */

gbHasAjaxScreen = false; //can be overwritten


function in_array(pasArray, p_val, pbContain)
{
	for(var i = 0, l = pasArray.length; i < l; i++)
  {
    //console.log('--> Is '+p_val+ ' == '+ pasArray[i]);

		if(pasArray[i] == p_val)
    {
      //console.log('in_array equal  !! mitsuketta !!');
			return true;
    }

    if(pbContain == true && pasArray[i].indexOf(p_val) >= 0)
		{
      //console.log(pasArray[i].indexOf(p_val));
      //console.log('in_array indexOf  !! mitsuketta !! '+pasArray[i]+'.indexOf('+p_val+')');
			return true;
    }
	}
  //console.log(' ==> not in array ');
	return false;
}

function parseUri (str)
{
	var	o = parseUri.options,
		m   = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
		uri = {},
		i   = 14;

	while (i--) uri[o.key[i]] = m[i] || "";

	uri[o.q.name] = {};
	uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
		if ($1) uri[o.q.name][$1] = $2;
	});

	return uri;
};

parseUri.options = {
	strictMode: false,
	key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
	q:   {
		name:   "queryKey",
		parser: /(?:^|&)([^&=]*)=?([^&]*)/g
	},
	parser: {
		strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
		loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
	}
};


/* ****************************************************************************** */
/* ****************************************************************************** */
/* ****************************************************************************** */
/* Ajax engine  */

/* List all the files that have been loaded in ajax  */
var asIncludedFile = {};

/* detect when page is unloading; allow to manage errors in ajaxrequest */
var bUnloading = false;
$(window).bind('beforeunload', function(){ bUnloading = true; });

function AjaxRequest(psUrl, psLoadingScreen, psFormToSerialize, psZoneToRefresh, pbReloadPage,  pbSynch, psCallback, pbWithAnimation)
{

  if(!psZoneToRefresh)
    psZoneToRefresh = '';

  if(!psLoadingScreen)
    psLoadingScreen = false;

  if(!pbReloadPage)
    pbReloadPage = false;

  if(!psFormToSerialize)
    psFormToSerialize = '';

  if(!pbSynch)
    pbSynch = 'false';


  if(psLoadingScreen)
  {
    mngAjaxScreen(psLoadingScreen, true);
  }
  /*if(pbWithAnimation || psLoadingScreen)
  {
    $(document).ajaxSend(function(){ setCoverScreen(true); });
  }*/


  sExtraParams = '';
  bIframeTransport = false;
  oFile = null;
  bProcessData = true;
  if(psFormToSerialize != '')
  {
    if($('#'+psFormToSerialize+' input:file').length)
    {
      bIframeTransport = true;
      oFile = $('#'+psFormToSerialize+' :file');
      bProcessData= false;

      //ask page to return http header with json contentType (for iframe transport)
      psUrl = psUrl+ '&rqjson=1'

      if($('#'+psFormToSerialize+' input[type=file]').length)
        console.log('!! AjaxRequest: using  IFrmTranport --> look in the network tab !!');

      sExtraParams = $('#'+psFormToSerialize).serializeArray();
    }
    else
      sExtraParams = $('#'+psFormToSerialize).serialize();
    //alert('serialize form: '+sExtraParams);
  }

  if(psZoneToRefresh == '')
  {
    if(pbReloadPage)
    {
      //No refresh + reload after execution
      $.ajax({
        type: 'POST',
        data: sExtraParams,
        cache: false,
        files: oFile,
        iframe: bIframeTransport,
        processData: bProcessData,
        url: psUrl,
        scriptCharset: "utf-8" ,
        contentType: "application/x-www-form-urlencoded; charset=UTF-8",
        context: document.body,
        async: pbSynch,
        dataType: "JSON",
        success: function(sURL)
        {
          mngAjaxScreen(psLoadingScreen);

          if(oJsonData.error)
            goPopup.setErrorMessage(oJsonData.error, true);

          if(sURL)
            return $(document).load(sURL);

          if(oJsonData.url)
            return $(document).load(oJsonData.url);

          if(oJsonData.timedUrl)
            setTimeout("document.location.href = '"+oJsonData.timedUrl+"'; ", 1500);

          return window.location.reload();
        },
        error: function(){  mngAjaxScreen(psLoadingScreen); }
      });

    }
    else
    {
      //No refresh + callback or action from json
      $.ajax({
        type: 'POST',
        data: sExtraParams,
        cache: false,
        files: oFile,
        iframe: bIframeTransport,
        processData: bProcessData,
        url: psUrl,
        scriptCharset: "utf-8" ,
        contentType: "application/x-www-form-urlencoded; charset=UTF-8",
        context: document.body,
        async: pbSynch,
        dataType: "JSON",
        success: function(oJsonData)
        {
          mngAjaxScreen(psLoadingScreen);

          if(!oJsonData)
            oJsonData = {error:"An unknown error occured"};

          if(oJsonData.error)
          {
            goPopup.setErrorMessage(oJsonData.error, true);
            $(document).ajaxSuccess().unbind();

            //requested by server
            if(oJsonData.action)
              eval(oJsonData.action);

            //requested by browser
            if(psCallback)
              eval(psCallback);
          }
          else
          {
            //0- include extra css or js files
            if(oJsonData.cssfile)
            {
              asFile = checkCssToInclude(oJsonData.cssfile);
              for(var sKey in asFile)
                $('head').append('<link rel="stylesheet" href="'+asFile[sKey]+'" type="text/css" />');
            }

            if(oJsonData.js)
              $('head').append('<script type="text/javascript">'+oJsonData.js+'</script>');

            if(oJsonData.jsfile)
            {
              asFile = checkJsToInclude(oJsonData.jsfile);
              //console.log('have to load: ');
              //console.log(asFile);

              if(asFile.length == 0)
                jsonEngine_noRefresh(oJsonData, psUrl, psLoadingScreen, psFormToSerialize, psZoneToRefresh, pbReloadPage,  pbSynch, psCallback, pbWithAnimation);
              else
              {
                yepnope({load: asFile,
                complete: function ()
                {
                  //console.log('all files loaded');
                  jsonEngine_noRefresh(oJsonData, psUrl, psLoadingScreen, psFormToSerialize, psZoneToRefresh, pbReloadPage,  pbSynch, psCallback, pbWithAnimation);
                }});
              }
            }
            else
              jsonEngine_noRefresh(oJsonData, psUrl, psLoadingScreen, psFormToSerialize, psZoneToRefresh, pbReloadPage,  pbSynch, psCallback, pbWithAnimation);
          }

          //6- Remove the loading screen
          mngAjaxScreen('', false);
        },
        error: function(oJsonData, jqXhr, ajaxSettings, thrownError)
        {
          mngAjaxScreen(psLoadingScreen);

          if(!bUnloading && (!jqXhr || jqXhr.status != 0))
          {
            //send the result to the error report form, and display the error message
            $('#dumpId').val('['+ sExtraParams +'] ['+psUrl +'] ['+pbSynch +'] ['+ajaxSettings +'] ['+jqXhr +']');
            $('#ajaxErrorContainerId').show();
          }
        }
      });
    }
  }
  else
  {
    //Refresh a part of the page + callback or action from json
    $.ajax({
      type: 'POST',
      data: sExtraParams,
      cache: false,
      files: oFile,
      iframe: bIframeTransport,
      processData: bProcessData,
      url: psUrl,
      scriptCharset: "utf-8" ,
      contentType: "application/x-www-form-urlencoded; charset=UTF-8",
      context: document.body,
      async: pbSynch,
      dataType: "JSON",
      success: function(oJsonData)
      {
        //console.log(oJsonData);

        mngAjaxScreen(psLoadingScreen);

        // pager Issue here
        if(!oJsonData)
          oJsonData = {notice:"An  unknown error occured"};

        if(oJsonData.error)
        {
          $('#'+psZoneToRefresh).html('<div class="notice3">'+oJsonData.error+'</div>');

          //requested by server
          if(oJsonData.action)
            eval(oJsonData.action);

          //requested by browser
          if(psCallback)
            eval(psCallback);

          //6- Remove the loading screen
          mngAjaxScreen('', false);
        }
        else
        {
          if(oJsonData.popupError)
            goPopup.setErrorMessage(oJsonData.popupError, true);

          //0- include extra css or js files
          if(oJsonData.cssfile)
          {
            asFile = checkCssToInclude(oJsonData.cssfile);
            for(var sKey in asFile)
              $('head').append('<link rel="stylesheet" href="'+asFile[sKey]+'" type="text/css" />');
          }

          if(oJsonData.js)
            $('head').append('<script type="text/javascript">'+oJsonData.js+'</script>');


          if(oJsonData.jsfile)
          {
            //console.log('Js already loaded');
            //console.log(gasJsFile);
            asFile = checkJsToInclude(oJsonData.jsfile);
            //console.log('Files to add in the page: ');
            //console.log(asFile);

            if(asFile.length == 0)
            {
              jsonEngine_withRefresh(oJsonData, psUrl, psLoadingScreen, psFormToSerialize, psZoneToRefresh, pbReloadPage,  pbSynch, psCallback, pbWithAnimation);
            }
            else
            {
              yepnope({load: asFile,
              complete: function ()
              {
                //console.log('all files loaded');
                jsonEngine_withRefresh(oJsonData, psUrl, psLoadingScreen, psFormToSerialize, psZoneToRefresh, pbReloadPage,  pbSynch, psCallback, pbWithAnimation);
              }});
            }
          }
          else
          {
            jsonEngine_withRefresh(oJsonData, psUrl, psLoadingScreen, psFormToSerialize, psZoneToRefresh, pbReloadPage,  pbSynch, psCallback, pbWithAnimation);
          }
        }
      },
      error: function(oJsonData, jqXhr, oAjaxSetting)
      {
        mngAjaxScreen(psLoadingScreen);

        if(!bUnloading && jqXhr.status != 0)
        {
          //send the result to the error report form, and display the error message
          $('#dumpId').val('['+ sExtraParams +'] ['+psUrl +'] ['+pbSynch +'] ['+oAjaxSetting +'] ['+jqXhr +']');
          $('#ajaxErrorContainerId').show();
        }
      }
    });
  }
}

//
function jsonEngine_noRefresh(oJsonData, psUrl, psLoadingScreen, psFormToSerialize, psZoneToRefresh, pbReloadPage,  pbSynch, psCallback, pbWithAnimation)
{
  //console.log('no_refresh');

  //2- Alert the message
  if(oJsonData.alert)
    alert(oJsonData.alert);

  //--------------------------------------------------
  //Display

  //Message: display a popup in the middle of the screen
  if(oJsonData.message)
  {
    if(oJsonData.timedUrl)
    {
      goPopup.setPopupMessage(oJsonData.message);
      setTimeout('goPopup.removeActive();', oJsonData.timedUrl);

      oJsonData.timedUrl = null;
    }
    else
      goPopup.setPopupMessage(oJsonData.message);
  }

  //Notice management
  //display a message at the bottom of the screen : redirect, delay before hiding... are mmanaged in setNotice
  if(oJsonData.notice)
  {
    setNotice(oJsonData);
  }
  else
  {
    //--------------------------------------------------
    //Basic action: redirections

    if(oJsonData.url)
      document.location.href = oJsonData.url;

    if(oJsonData.reload)
      window.location.reload();

    if(oJsonData.timedUrl)
      setTimeout("document.location.href = '"+oJsonData.timedUrl+"'; ", 1500);

    if(oJsonData.timedReload)
      setTimeout("window.location.reload();", 1500);
  }


  //--------------------------------------------------
  //If no redirections: actions to be executed once the ajax request has been done

  //requested by server
  if(oJsonData.action)
    eval(oJsonData.action);

  //requested by browser
  if(psCallback)
     eval(psCallback);

   mngAjaxScreen('', false);
}

function jsonEngine_withRefresh(oJsonData, psUrl, psLoadingScreen, psFormToSerialize, psZoneToRefresh, pbReloadPage,  pbSynch, psCallback, pbWithAnimation)
{
  //console.log('with_refresh ['+psZoneToRefresh+']');

  //2- Alert the message
  if(oJsonData.alert)
    alert(oJsonData.alert);

  var sSelector = psZoneToRefresh.substring(0, 1);
  if(sSelector != '.' && sSelector != '#')
    psZoneToRefresh = '#'+psZoneToRefresh;

  //3- Display message from server (in the refresh zone)
  if(oJsonData.message)
  {
    $(psZoneToRefresh).html(oJsonData.message);
    $(psZoneToRefresh).attr('class', 'notice');
  }

  //4- Reload the page
  if(oJsonData.reload)
  {
    window.location.reload();
  }

  if(oJsonData.data)
    $(psZoneToRefresh).html(oJsonData.data);

  //Notice: display a message at the bottom left of the screen : redirect or hiding automatic
  if(oJsonData.notice)
    setNotice(oJsonData);
  else
  {
    //--------------------------------------------------
    //Redirections

    if(oJsonData.url)
      document.location.href = oJsonData.url;

    if(oJsonData.reload)
      window.location.reload();

    if(oJsonData.timedUrl)
      setTimeout("document.location.href = '"+oJsonData.timedUrl+"'; ", 3000);

    if(oJsonData.timedReload)
      setTimeout("window.location.reload();", 1500);
  }

  //- Execute action ask from server
  if(oJsonData.action)
    eval(oJsonData.action);


  //5- Execute callback requested after the ajax query
  if(psCallback)
     eval(psCallback);

   mngAjaxScreen('', false);
}

//replace in version 2.5.0. To remove as soon as we're sure it's stable
function AjaxPopup(psUrl, psLoadingScreen, pbSynch, psHeight, psWidth, pbNoFooter)
{
  alert('AjaxPopup deprecated: use goPopup.setLayerFromAjax');
}


function mngAjaxScreen(psTag, pbSet)
{
  if(gbHasAjaxScreen)
  {
    customAjaxScreen(psTag, pbSet);
  }
}

/**
 * check the list of js files requested to be included in the page,
 * Return an array with the ones that are not already included
 * Log the files in the global array
 */
function checkJsToInclude(pasFiles)
{
  //console.log(gasJsFile);
  var asFile = new Array();
  for(var sKey in pasFiles)
  {
    pasFiles[sKey] = pasFiles[sKey].split('//').join('/');
    asUrl = parseUri(pasFiles[sKey]);

    //console.log('check js to include:   IS '+ asUrl['path']+' in the array above ?');
    if(!in_array(gasJsFile, asUrl['path'], true))
    {
      asFile.push(pasFiles[sKey]);
      gasJsFile.push(asUrl['path']);

      //console.log('not found, including file now !!!');
    }
    /*else
      console.log('found, no include ....');*/
  }
  return asFile;
}

function checkCssToInclude(pasFiles)
{
  var asFile = new Array();
  for(var sKey in pasFiles)
  {
    asUrl = parseUri(pasFiles[sKey]);
    if(!in_array(gasCssFile, asUrl['path']))
    {
      asFile.push(pasFiles[sKey]);
      gasCssFile.push(asUrl['path']);
    }
  }

  return asFile;
}


function insertJs(psContent)
{
  //http://www.sencha.com/forum/showthread.php?100865-Execute-an-include-js-file-from-ajax

  var head = document.getElementsByTagName("head")[0];
  var script = document.createElement("script");
  script.type = 'text/javascript';
  script.text = psContent;
  head.appendChild(script);
}

/**
 *Display a notice at the bottom right of the page. Use setPopup adding extra class
 *updated to implement the new popup management
 **/
function setNotice(asData, pbAnimation, pnTime, pbModal)
{
  aAction = {};
  if(!asData.delay)
    asData.delay = 1500;

  if(pnTime)
    asData.delay = pnTime;

  if(pbModal !== false)
    pbModal = true;

  if(asData.url)
    return goPopup.setNotice(asData.notice, {url: asData.url, delay: 5}, true, pbModal);

  if(asData.timedUrl)
    return goPopup.setNotice(asData.notice, {url: asData.timedUrl, delay: asData.delay}, true, pbModal);

  if(asData.reload || asData.timedReload)
    return goPopup.setNotice(asData.notice, {callback: ' setTimeout( "location.reload(true);", '+asData.delay+'); '}, true, pbModal);

  return goPopup.setNotice(asData.notice, {delay: asData.delay}, true, pbModal);
}


nLockScreenOn = false;
nLockScreenOff = false;
function setLoadingScreen(psSelector, pbSetup, pbWithAnimation)
{
  oHtmlElement = $(psSelector);
  oHiddenDiv = $('#loadingScreen');

  if(pbSetup && !nLockScreenOff)
  {
    nHeight = oHtmlElement.height();
    nWidth = oHtmlElement.width();
    oPosition = oHtmlElement.offset();
    oHiddenDiv.attr('style', 'width:'+nWidth+'; height:'+nHeight+'px; top:'+oPosition.top+'px; left:'+oPosition.left);

    if(pbWithAnimation == false)
       $('#loadingScreenAnimation').hide();
    else
      $('#loadingScreenAnimation').attr('style', 'margin:150px auto 0;');

    oHiddenDiv.show();
    return true;
  }

  if(!nLockScreenOn)
  {
    oHiddenDiv.hide();
  }
}


/*
 * ============================================================================
 * ============================================================================
 * ============================================================================
 * ============================================================================
 */

var oEmbedTimer;

function getEmbedOption(psFieldId)
{
  oEmbedTimer = setTimeout('displayEmbedLink("'+psFieldId+'");', '1250');
}
function clearEmbedOption(psFieldId)
{
  clearTimeout(oEmbedTimer);
  displayEmbedLink(psFieldId, true)
}

function displayEmbedLink(psFieldId, pbRemove)
{
  $('#embedPopupId').clearQueue();

  if(pbRemove)
  {
    $('#embedPopupId').fadeOut().html('');
  }
  else
  {
    oLinkPosition = $('#'+psFieldId).position();
    $('#embedPopupId').attr('style', 'top:'+(oLinkPosition.top+20)+'; left:'+(oLinkPosition.left + 15)+';');
    $('#embedPopupId').html('2 links').fadeIn();
  }
}

/**
 * Hide all elements having the parameter class, and display the element with the parameter id
 *
 **/

function toggleBlocks(psClassToHide, psIdToDisplay)
{
  $('.' + psClassToHide).slideUp('fast', function()
  {
      $('#' + psIdToDisplay).slideDown();
  });
}



function SwitchFullSearch()
{

	   $JQ('#topPageId > *').toggle('fast', function(){
		     $JQ('#personnel-FRM').animate({height:'1000px'}, 1200);
	   });
}

function toggleDisplay(psElementId, pvSpeed)
{
  if(!pvSpeed)
    pvSpeed = 'slow';

  /*$JQ('#'+psElementId).toggle(pvSpeed);*/
  $JQ('#'+psElementId).fadeToggle(pvSpeed);
  /*$JQ('#'+psElementId).slideToggle(pvSpeed);*/
}


function toggleImage(psImageSelector, psMode)
{
  oImage = $(psImageSelector);

  if(!oImage || oImage == undefined)
    return false;

  if(!psMode)
  {
		//autodetect which one to display
		sNewSrc = $(oImage).attr('imgDisplay');
		sCurrentSrc = $(oImage).attr('src');

		if(sCurrentSrc == sNewSrc)
		  sNewSrc = $(oImage).attr('imgHidden');
	}
	else
	{
		if(psMode == 'view')
		  sNewSrc = $(oImage).attr('imgDisplay');
		else
		  sNewSrc = $(oImage).attr('imgHidden');
	}


  $(oImage).fadeOut(function()
  {
    $(oImage).attr('src', sNewSrc).delay(10).fadeIn();
  });

}


function zoomPicture(psPicSelector)
{
  oPic = $(psPicSelector);
  sPicPath = oPic.attr('src');

  mngAjaxScreen();
  goPopup.setLayer(null, '<img src="'+sPicPath+'" style="max-width:1024; max-height:900;" />');
}

function pagerGetPage(oCurrentElement,psUrl, pnIsAjax, psRefreshZone)
{
  var sElementValue = $(oCurrentElement).attr('pagervalue');
   var nPageOffset = 0;

  if(sElementValue)
    nPageOffset = parseInt(sElementValue);
  else
    nPageOffset = parseInt($(oCurrentElement).html());

  psUrl = psUrl+'&pageoffset='+nPageOffset;

  if(pnIsAjax)
  {
    if(!psRefreshZone)
      return alert('Ajax pager, but no bloc to refresh ');

    AjaxRequest(psUrl, 'body', '', psRefreshZone);
  }
  else
  {
    document.location.href = psUrl;
  }
}

function pagerSetPageNbResult(psUrl, pnIsAjax, pnNbResult, psRefreshZone)
{
  psUrl = psUrl+'&nbresult='+pnNbResult;

  if(pnIsAjax)
  {
    if(!psRefreshZone)
      return alert('Ajax pager (set results), but no bloc to refresh ');

    AjaxRequest(psUrl, 'body', '', psRefreshZone);
  }
  else
  {
    document.location.href = psUrl;
  }
}


var oPagerTimer;
var bPagerStop;
function slidePager(psCurrentPager, pbNext, pnTime, pbStop, pbRecursiveCall)
{
  oPager = jQuery(psCurrentPager);

  if(pbRecursiveCall && bPagerStop === true)
    return true;

  if(pbStop)
  {
    bPagerStop = true;
    clearTimeout(oPagerTimer);
    return true;
  }

  var nNbElement = 9;
  var nPages = parseInt(jQuery('.pagerNavigationNumbers', oPager).attr('nbpages'));
  var nCurrent = parseInt(jQuery('.pagerNavigationNumbers', oPager).attr('currentpage'));
  var nMaxDisplayed = parseInt(jQuery('.pagerNavigationNumbers', oPager).attr('maxdisplayed'));
  var nMinDisplayed = parseInt(jQuery('.pagerNavigationNumbers', oPager).attr('mindisplayed'));

  if(parseInt(nPages) == 1)
    return true;

  //going forward increasing page number
  if(pbNext && nPages && nCurrent)
  {
    nMin = (nMaxDisplayed+1);
    if(nMin >= nPages)
      return true;

    nMax = (nMaxDisplayed+1+nNbElement);
    nPagedUp = false;

    jQuery('.pagerNavigationBefore, .pagerNavigationBefore .pager_pageLinkPic', oPager).fadeIn();
    for(nCount = nMin; nCount <= nMax; nCount++)
    {
      if(nCount <= nPages)
      {
        nPagedUp = true;
        jQuery('.pagerNavigationNumbers div:first', oPager).remove();
        var oElem = jQuery('.pager_toClone', oPager).clone();
        jQuery(oElem).removeClass('pager_toClone');

        if(nCount == nCurrent)
          jQuery(oElem).addClass('pager_CurrentPage');

        if(nCount > 9999)
          jQuery(oElem).addClass('pagerSmaller');

        jQuery('a', oElem).html(nCount);
        oElem.appendTo(jQuery('.pagerNavigationNumbers', oPager));
      }
    }

    if(nPagedUp)
    {
      jQuery('.pagerNavigationNumbers', oPager).attr('maxdisplayed', nMax);
      jQuery('.pagerNavigationNumbers', oPager).attr('mindisplayed', nMin);
      //get to the last page
      if(nCount >= nPages)
        jQuery('.pagerNavigationAfter .pager_pageLinkPic', oPager).fadeOut();
    }
  }

  //Going backward, decreasing the page number
  if(!pbNext && nPages && nCurrent)
  {
    nPagedDown = false;
    nMax = (nMinDisplayed-1);
    if(nMax < 1)
      return true;

    nMin = (nMinDisplayed-1-nNbElement);
    //console.log('from '+nMax+' to '+nMin+' <br />');

    jQuery('.pagerNavigationAfter, .pagerNavigationAfter .pager_pageLinkPic', oPager).fadeIn();
    for(nCount = nMax; nCount >= nMin; nCount--)
    {
      if(nCount > 0)
      {
        nPagedDown = true;
        jQuery('.pagerNavigationNumbers div:last', oPager).remove();
        var oElem = jQuery('.pager_toClone', oPager).clone();

        jQuery(oElem).removeClass('pager_toClone');
        if(nCount == nCurrent)
          jQuery(oElem).addClass('pager_CurrentPage');

        if(nCount > 9999)
          jQuery(oElem).addClass('pagerSmaller');

        jQuery('a', oElem).html(nCount);
        oElem.prependTo(jQuery('.pagerNavigationNumbers', oPager));
      }
    }

    if(nPagedDown)
    {
      jQuery('.pagerNavigationNumbers', oPager).attr('maxdisplayed', nMax);
      jQuery('.pagerNavigationNumbers', oPager).attr('mindisplayed', nMin);

      //get to the first page
      if(nCount <= 1)
        jQuery('.pagerNavigationBefore .pager_pageLinkPic', oPager).fadeOut();
    }
  }

  if(!pbStop)
  {
    if(!pnTime || pnTime == 0)
      pnTime = 500;

    if(parseInt(pnTime) >= 100)
      pnTime = parseInt(pnTime, 10) - 50;

    bPagerStop = false;
    setTimeout("slidePager('"+psCurrentPager+"', "+pbNext+", "+pnTime+", false, true); ", pnTime);
  }

  return true;
}

function showActivityPopup(oElement)
{
  var oPosition = $(oElement).offset();
  if(!oPosition)
    return null;

   $('#body').append('<div class="activityPopup" "></div>');
   $('.activityPopup').attr('style', 'position:absolute; top:'+oPosition.top+'px; left:'+(parseInt(oPosition.left) -350)+'px;');
   $('.activityPopup').html($(oElement).parent().attr('title'));
   $('.activityPopup').css('display:block');

}

function hideActivityPopup()
{
    $('.activityPopup').delay(1000).fadeOut(1000, function(){$('.activityPopup').html('');$('.activityPopup').remove();});

}

function reloadPage(url)
{
    window.location=url;
}

function setCoverScreen(pbSetup, pbWithAnimation, pnSpeed)
{

  if(pbSetup)
    return mngAjaxScreen('', true);

  return mngAjaxScreen('', false);

  /*if(!pnSpeed)
    pnSpeed = 150;

  if(pbSetup)
  {
    nHeight = jQuery(document).height();
    if(pbWithAnimation)
    {
      jQuery('<div id="coverScreen" style=" width:100%; height:'+nHeight+'px;"></div>').appendTo('body');
      jQuery('<div id="coverScreenPic"><div><img src="'+$('#loadingScreenAnimation img').attr('src')+'" border=0 /></div></div>').appendTo('body');
    }
    else
      jQuery('<div id="coverScreen" style=" width:100%; height:'+nHeight+'px; "></div>').appendTo('body');

    jQuery('#coverScreen').fadeIn(100, function(){jQuery('#coverScreenPic ').fadeIn(pnSpeed);});
  }
  else
  {
    jQuery('#coverScreen').fadeOut(pnSpeed, function(){jQuery('#coverScreen, #coverScreenPic').remove();});
  }*/
}


function resetContactSearch()
{
  $("#queryFormId").find(':input').each(function() {

        switch(this.type) {
	            case 'text':
	            case 'textarea':
               	case 'select-one':
                case 'select-multiple':

                  $(this).val('');
	                break;
	         }
      });

    $("#queryFormId .autocompleteField").tokenInput("clear").blur();
    $('.bsmSelect option:disabled').removeClass('bsmOptionDisabled');
    $('.bsmSelect option:disabled').removeAttr('disabled');
    $('#contact_industryId option:selected').removeAttr('selected');
    $('.bsmListItem').remove();

 }

function resetCompanySearch()
{
  $("#queryFormId").find(':input').each(function() {
        switch(this.type) {
	            case 'text':
	            case 'textarea':
	                $(this).val('');
	                break;
	         }
      });
  $("#queryFormId .autocompleteField").tokenInput("clear").blur();

 }

function showHide(displaytext,hidetext,display,hide)
{
  $('#'+displaytext).show();
  $('#'+hidetext).hide();

  $('#'+display).show();
  $('#'+hide).hide();
}


function resetJobSearch()
{
  $("#advSearchFormId").find(':input').each(function()
  {
    switch(this.type)
    {
        case 'text':
        case 'textarea':
        case 'select-multiple':
        case 'select-one':
        case 'select':

        $(this).val('');
          break;
      }

      $("#industry_treeId").val('');

     mRange = $("#salary_monthId").attr("default");
     yRange = $("#salary_yearId").attr("default");
     hRange = $("#salary_hourId").attr("default");

     $("#salary_monthId").val(mRange);
     $("#salary_yearId").val(yRange);
     $("#salary_hourId").val(hRange);

    });

   $("form[name=advSearchForm] input[type=submit]").click();
   searchFormToggle(true);
}

/**
 * Comment
 */
function searchFormToggle(pbForceDisplay)
{
  //if the form is not anchored at the top, ignore the dummy form
  var bIgnoreDummy = jQuery('.jobLeftSectionInner').hasClass('menuFloating');

  if(bIgnoreDummy)
    jQuery('.jobDummySearchForm:visible').fadeOut(50);

  if(pbForceDisplay === false)
  {
    //form visible => we hide it
    if(bIgnoreDummy)
      jQuery('.jobSearchContainer:visible').fadeOut();
    else
      jQuery('.jobSearchContainer:visible, .jobDummySearchForm:visible').fadeOut();
    return true;
  }

  if(pbForceDisplay === false)
  {
    //form visible => we hide it
    if(bIgnoreDummy)
      jQuery('.jobSearchContainer:not(:visible)').fadeIn();
    else
      jQuery('.jobSearchContainer:not(:visible), .jobDummySearchForm:not(:visible)').fadeIn();
    return true;
  }

  if(bIgnoreDummy)
  {
    jQuery('.jobSearchContainer').fadeToggle();
  }
  else
  {
    jQuery('.jobSearchContainer, .jobDummySearchForm').fadeToggle();
  }
  return true;
}

function showHideSalary(value)
{
  if(value==1)
  {
    $("#salary").css('display','block');
    $("#salary_hourId").closest('div .formFieldContainer').hide();
    $("#salary_monthId").closest('div .formFieldContainer').show();

  }
  else
  {
    $("#salary").css('display','none');
    $("#salary_hourId").closest('div .formFieldContainer').show();
    $("#salary_monthId").closest('div .formFieldContainer').hide();
    $("#salary_yearId").closest('div .formFieldContainer').hide();
   }
}

function displaySalary(value)
{
  if(value==0)
   {
    $("#salary_monthId").closest('div .formFieldContainer').show();
    $("#salary_yearId").closest('div .formFieldContainer').hide();
   }
  else
   {
     $("#salary_yearId").closest('div .formFieldContainer').show();
     $("#salary_monthId").closest('div .formFieldContainer').hide();
    }
 }

 function submitForm(oElement)
 {
    var industrypk = $(oElement).attr("industrypk");
    var industryname = $(oElement).attr("industryname");
    var companypk = $(oElement).attr("companypk");
    var companyname = $(oElement).attr("companyname");

    $('input[name=industrypk]').val(industrypk);
    $('input[name=industryname]').val(industryname);

    $('input[name=companypk]').val(companypk);
    $('input[name=companyname]').val(companyname);

   $("form[name=hiddenForm] input[type=submit]").click();

 }

 var oFilterTime;

 function clearFilter()
 {
    clearTimeout(oFilterTime);

    jQuery('.filterRemovalLoader:not(:visible)').fadeIn();
    oFilterTime =  setTimeout(function(){$("form[name=advSearchForm]").submit();jQuery('.filterRemovalLoader:visible').fadeOut();},1500);
 }


  function addParameter(oElement)
  {
    var _href = $(oElement).attr("href");
    $(oElement).attr("href", _href + '&settime='+$.now());
  }

  function uniqueId()
  {
    var nTime = new Date().getTime();
    var sText = "";
    var sChar = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for( var i=0; i < 5; i++ )
        sText += sChar.charAt(Math.floor(Math.random() * sChar.length));

    return sText+'_'+nTime;
  }


  function select2PictureRender(oState)
  {
    var oOriginalOption = oState.element;

    if($(oOriginalOption).data('pic'))
      return "<img src='" + $(oOriginalOption).data('pic') + "' alt='" + oState.text + "' /><span>" + oState.text+'</span>';

    return '<span>'+ oState.text +'</span>';
  }

  //oElement is the hidden original select tag. Fct launch on change
  function select2OnChangeRedirect(oSelect2Element)
  {
    //console.log(oSelect2Element);
    //console.log(oSelect2Element.val);
    var oOption = $(' #'+oSelect2Element.val);
    var oData = $(oOption).data();
    //console.log(oData);

    if(oData.url)
    {
      if(!oData.ajaxlayer)
        document.location.href = oData.url;
      else
      {
        //console.log('ajaxLayer');
        goPopup.setLayerFromTag(oOption);
      }
    }

    if($(oOption).attr('data-onclick'))
      eval($(oOption).attr('data-onclick'));

    $(oSelect2Element.target).select2("val", "");
  }



  function sortList(poElement, psWay, psMode)
  {
    var oList = $('ul#'+$(poElement).attr('list-id'));
    var sColumn = $(poElement).parent().attr('column');
    //console.log('col:'+sColumn+' - sort list call here - mode: '+psMode);

    var listItems = oList.children('li:not(:first)').get();
    //console.log(listItems);

    $(oList).animate({opacity: '0.2'}, function()
    {
        switch(psMode)
        {
          case 'integer':
          {
            listItems.sort(function(a,b)
            {
              compA = parseInt($(a).find('.'+sColumn).text());
              compB = parseInt($(b).find('.'+sColumn).text());

              if(isNaN(compA))
                compA = 0;

              if(isNaN(compB))
                compB = 0;

              //console.log('mode 3 [.'+sColumn+'] ==> a: '+compA+' / b: '+compB);

               if(psWay == 'up')
                 return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;

              return (compA > compB) ? -1 : (compA < compB) ? 1 : 0;
            });
          }
          break;

          case 'value':
          {
            listItems.sort(function(a,b)
            {
              compA = $(a).find('.'+sColumn).attr('sort_value');
              compB = $(b).find('.'+sColumn).attr('sort_value');

              //console.log('mode 2 [.'+sColumn+' attr(sort_value)] ==> a: '+compA+' / b: '+compB);

               if(psWay == 'up')
                 return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;

              return (compA > compB) ? -1 : (compA < compB) ? 1 : 0;
            });
          }
          break;

          case 'value_integer':
          {
            listItems.sort(function(a,b)
            {
              compA = parseInt($(a).find('.'+sColumn).attr('sort_value'));
              compB = parseInt($(b).find('.'+sColumn).attr('sort_value'));

              if(isNaN(compA))
                compA = 0;

              if(isNaN(compB))
                compB = 0;

              //console.log('mode 2 [.'+sColumn+' attr(sort_value)] ==> a: '+compA+' / b: '+compB);

               if(psWay == 'up')
                 return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;

              return (compA > compB) ? -1 : (compA < compB) ? 1 : 0;
            });
          }
          break;

          case 'text':
          default:
          {
            listItems.sort(function(a,b)
            {
              compA = $(a).find('.'+sColumn).text().toUpperCase();
              compB = $(b).find('.'+sColumn).text().toUpperCase();

              //console.log('mode 1 [.'+sColumn+'] ==> a: '+compA+' / b: '+compB);

               if(psWay == 'up')
                 return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;

              return (compA > compB) ? -1 : (compA < compB) ? 1 : 0;
            });
          }
        }

      $(oList).append(listItems);
      $(oList).css('opacity', 1);
    });

  }


// lowercase all letters of a string if force=true, capitalize first letter

function ucfirst(str,force){
      str=force ? str.toLowerCase() : str;
      return str.replace(/(\b)([a-zA-Z])/,
               function(firstLetter){
                  return   firstLetter.toUpperCase();
               });
 }

 /**
 * add a zero to one digit numbers
 */
function addZero(number)
{
  return ("0" + number).slice(-2);
}


//======================================================================================================
//======================================================================================================
//======================================================================================================
//======================================================================================================
//jquery 1.9+ -> dropped browser used by BSM select
//light usage in BSM, we could remove the calls to $.broswer, resulting to little issues with ie6 - ie7

//extract from http://code.jquery.com/jquery-migrate-1.2.1.js
jQuery.uaMatch = function( ua ) {
	ua = ua.toLowerCase();

	var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
		/(webkit)[ \/]([\w.]+)/.exec( ua ) ||
		/(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
		/(msie) ([\w.]+)/.exec( ua ) ||
		ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
		[];

	return {
		browser: match[ 1 ] || "",
		version: match[ 2 ] || "0"
	};
};



if(!jQuery.browser)
{
	matched = jQuery.uaMatch( navigator.userAgent );
	browser = {};

	if ( matched.browser ) {
		browser[ matched.browser ] = true;
		browser.version = matched.version;
	}

	// Chrome is Webkit, but Webkit is also Safari.
	if ( browser.chrome ) {
		browser.webkit = true;
	} else if ( browser.webkit ) {
		browser.safari = true;
	}

	jQuery.browser = browser;
}

//======================================================================================================
//======================================================================================================
//======================================================================================================
//======================================================================================================

function initMce(psFieldName, pbAdvanced)
{
  if(!pbAdvanced)
  {
    tinyMCE.init(
    {
      mode : "exact",
      elements : psFieldName,
      theme : "modern",
      plugins : "save,layer,table,hr,textcolor,link,emoticons,media,searchreplace,print,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,template,code",

      gecko_spellcheck : true,

      // Theme options
      menubar:false,
      toolbar1 : "bold,italic,underline,strikethrough,|,forecolor,|,cut,copy,|,bullist,numlist,|,link,unlink,|,fullscreen,|,code",
      toolbar2 : "styleselect,formatselect,fontselect,fontsizeselect,|,outdent,indent,|,pastetext",

      save_enablewhendirty: true,
      paste_as_text: true,
      protect:
      [
        /\<\/?(if|endif)\>/g, // Protect <if> & </endif>
        /\<xsl\:[^>]+\>/g, // Protect <xsl:...>
        /<\?php.*?\?>/g // Protect php code
      ],
      cleanup: true
    });
  }
  else
  {
    tinyMCE.init(
    {
      mode : "exact",
      elements : psFieldName,
      theme : "modern",
      plugins : "save,autosave,layer,table,hr,textcolor,link,emoticons,media,searchreplace,print,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,template,code",

      gecko_spellcheck : true,
      paste_as_text: true,

      // Theme options
      toolbar1 : "bold,italic,underline,strikethrough,|,forecolor,|,justifyleft,justifycenter,justifyright,justifyfull,|,emoticons,blockquote,|,fullscreen,spellchecker,cut,copy,|,bullist,numlist,|,link,unlink,|,code",
      toolbar2 : "styleselect,formatselect,fontselect,fontsizeselect,|,outdent,indent,|,pastetext",

      cleanup: true
    });

  }
}

//function used in getuserLink()
//since it's used a lot, i shortened it to tp()
function tp(poTag, psSelector)
{

  if(psSelector)
  {
    var sHTML = ""+$(psSelector).html();
    sHTML = sHTML.split('"').join("'");

    $(poTag).attr('title', sHTML);
  }


  $(poTag).tooltip(
  {content: function()
    {
      if(!$(this).attr('title'))
        $(this).attr('title', $(this).text());

      return $(this).attr('title');
    }
  }).blur().mouseenter();

  return true;
}

function ajaxLayer(psUrl, pnWidth, pnHeight)
{
  if(!psUrl)
    return console.log('no url for ajaxPopup');

  if(!pnWidth)
    pnWidth = 950;

  if(!pnHeight)
    pnHeight = 725;

  var oConf = goPopup.getConfig();
  oConf.width = pnWidth;
  oConf.height = pnHeight;
  oConf.modal = true;
  goPopup.setLayerFromAjax(oConf,  psUrl, '', false);
}