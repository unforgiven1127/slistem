
var gbHasAjaxScreen = true;
function customAjaxScreen(psTag, pbSet)
{
  /*$( document ).ajaxError(function()
  {
    $('#slLoadingScreen').remove();
    $('body').removeClass('noScroll');
  });*/

  if(pbSet)
  {
    $('body').addClass('noScroll').append('<div id="slLoadingScreen" class="'+psTag+'" style="width: '+ ($(document).innerWidth() + 100) +'px; height: '+ ($(document).innerHeight() + 100) +'px; position: absolute; top: 0; left: 0; ">\n\
<div class="bg"></div><div class="ani"></div></div>');
  }
  else
  {
    $('#slLoadingScreen').remove();
    $('body').removeClass('noScroll');
  }
}






// // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -(- - - - - - - - - - - - - -
/**
* Manage thetoggling betwen the 4 sections of the menu
*/
var oPrevPageSize = {};
var oPageSize = {};

$(document).ready(function()
{
  var nHeight = $(window).height();
  var nTopHeight = 375;       //top section when opened (350) + space between top and bottom container (15) + 10px from I don't know where
  var nTopOffset = 57;        //top menu 42px Chrome / 32px for firefox  => 37 + margin between menu and main page 20px
  var nFloatingHeaderHeight = 40;


  oPageSize = {width: $(window).width(), height: nHeight,
    bottomHeight: (nHeight - nTopHeight - nTopOffset),
    bottomWithHeader: (nHeight - nTopHeight - nTopOffset),
    bottomHeadless: (nHeight - nTopHeight - nTopOffset - nFloatingHeaderHeight),
    fullWithHeader: (nHeight - nTopOffset),
    fullHeadless: (nHeight - nTopOffset - nFloatingHeaderHeight)};

  /*console.log('offset =>  topOffset: '+nTopHeight+'px, topOffset with floatingHead: '+nFloatingHeaderHeight+' ');
  console.log('width: '+oPageSize.width);
  console.log('height: '+oPageSize.height);
  console.log('bottomHeight: '+oPageSize.bottomHeight);
  console.log('bottomWithHeader: '+oPageSize.bottomWithHeader);
  console.log('bottomHeadless: '+oPageSize.bottomHeadless);
  console.log('fullWithHeader: '+oPageSize.fullWithHeader);
  console.log('fullHeadless: '+oPageSize.fullHeadless);*/


  $(window).bind("resizeEnd", function()
  {
    oPrevPageSize = oPageSize;

    //var nHeight = nHeight = $(window).height();
    oPageSize = {width: $(window).width(), height: nHeight, bottomHeight: nHeight - nTopHeight, bottomWithHeader: nHeight-nTopHeight, bottomHeadless: nHeight - nFloatingHeaderHeight -50, fullWithHeader: nHeight - 50, fullHeadless: nHeight - 92};
  });

});


/**
* Compare current and previous page size array
*/
function hasSizeChanged()
{
  if(oPrevPageSize.width != oPageSize.width || oPrevPageSize.height != oPageSize.height)
    return true;

  return false;
}

function resetSizeChanged()
{
  oPrevPageSize = oPageSize;
  return false;
}


/*
 * Update the bloc size depending on .containerSplit
 * To keep the load low, we apply changes only when page size is changed or when asked (with pbChangeDisplay)
 * @param {string} psSelector
 * @returns {nothing}
 */
function updateBottomSize(pbChangeDisplay)
{
  if(pbChangeDisplay || hasSizeChanged())
  {
    //remove existing
    $('head #customResize').remove();

    $('head').append(
    '<style id="customResize">\n\
    #tab_content_container > li .scrollingContainer { height: '+oPageSize.fullWithHeader+'px; } \n\
    #tab_content_container > li .scrollingContainer.scroll_binded { height: '+oPageSize.fullHeadless+'px; } \n\
    \n\
     /*when page is splitted */\n\
    .componentMainContainer.containerSplit #tab_content_container > li .scrollingContainer { height: '+oPageSize.bottomWithHeader+'px; }\n\
    .componentMainContainer.containerSplit #tab_content_container > li .scrollingContainer.scroll_binded { height: '+oPageSize.bottomWithHeader+'px;}\n\
    </style>');

    resetSizeChanged();
  }
}



function reloadFolders()
{
  var sUrl = $('#userFolders').attr('url');
  AjaxRequest(sUrl, '', '', '#userFolders');
}

function clearSelection()
{
  listSelectBox( $('.tplListContainer:visible').attr('id'), false);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

function toggleMenu(poElement, psUrl)
{
  var oLi = $(poElement).closest('li');

  if(psUrl)
  {
    //log last menu item clicked
    $.ajax(psUrl);
  }


  if($('.menuActionBloc:visible', oLi).length > 0)
  {
    $('.menuActionBloc', oLi).slideToggle(400);
    return true;
  }

  var oUl = $(poElement).closest('ul');
  var nVisible = $('.menuActionBloc:visible', oUl).length;

  if(nVisible)
  {
    $('.menuActionBloc:visible', oUl).slideUp(200, function()
    {
      $('.menuActionBloc', oLi).slideDown(575, function(){ $(this).mCustomScrollbar("update"); });
    });
  }
  else
  {
    $('.menuActionBloc', oLi).slideDown(575, function(){ $(this).mCustomScrollbar("update"); });
  }

  return true;
}


// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -



/**
 * Split the page and resize the bottom of the page accordingly
 */
function splitPage(pbForce)
{
  $('#topCandidateSection').show(0);
  $('.componentMainContainer').addClass('containerSplit');
}

function showFullPage()
{
  $('#topCandidateSection').hide();
  $('.componentMainContainer').removeClass('containerSplit');
}

function toggleFullWidthPage()
{
  var nMenuWidth = $('#menuactleft').attr('data-width');
  var nPadding = $('#componentContainerId').attr('data-padding');

  if(!nMenuWidth)
  {
    nMenuWidth = $('#menuactleft').css('width');
    nPadding = $('#componentContainerId').css('padding-left');
    $('#menuactleft').attr('data-width', nMenuWidth);
    $('#componentContainerId').attr('data-padding', nPadding);
  }

  var nContainerWidth = $('#componentContainerId').attr('data-width');
  if(!nContainerWidth)
  {
    nContainerWidth = $('#componentContainerId').css('width');
    $('#componentContainerId').attr('data-width', nContainerWidth);
  }

  if($('#menuactleft').is(':visible'))
  {
    $('#menuactleft').animate({width: 0}, function()
    {
      $('#menuactleft').hide(0, function()
      {
        $('#componentContainerId, #componentContainerId > div').css('width', '100%');
        $('#componentContainerId').animate({marginLeft: 0});
      });
    });
  }
  else
  {
    $('#componentContainerId, #componentContainerId > div').attr('style', '');
    $('#menuactleft').show(0, function()
    {
      $('#componentContainerId').animate({paddingLeft: nPadding});
      $('#menuactleft').animate({width: nMenuWidth});
    });
  }

}



// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//Drag and drop
var bDragScrolLocked = false;

//separate drag and drop because we need reload only drop when refreshing the menu
function initDragAndDrop(psUrl)
{
  initDrag();
  initDrop(psUrl);
}

function initDrag(psSelector)
{
  if(!psSelector)
    psSelector = ".list_item_draggable";

  $(psSelector).draggable(
  {
    /*opacity: 0.7,*/
    helper: "clone",
    /*cursor: "move",*/
    /*cursorAt: {top:0, left: 0},*/
    /*stack: ".list_item_draggable li, .list_item_draggable li div",*/

    helper: function() { return $( "<div class='row_dragging'>"+$(this).attr("data-title")+"</div>" );  },
    zIndex: 5000000,
    appendTo: "body",

    containment: "window",

    //containment: ".menu_workspace",
    //scroll: true,

    start: function()
    {
      var nFolderList = $(".menuFolderContainer:visible").length;
      if(!nFolderList)
        $(".menu_workspace:not(:visible)").parent().find(".menuActionMenuContainer").click();

      $(this).closest("li").addClass("rowActive");

      $(".row_dragging").mousewheel(function(event, delta)
      {
        if(bDragScrolLocked)
          return null;

        bDragScrolLocked = true;
        setTimeout('bDragScrolLocked = false;', 150);


        /*if(delta > 0)
          $(".menuFolderContainer:visible").mCustomScrollbar("scrollTo", "top");
          //$(".menuFolderContainer:visible").mCustomScrollbar("scrollTo", "Up");
          //$(".menuFolderContainer:visible .mCSB_buttonUp").css('border', '1px solid red').trigger("click");
        else
          $(".menuFolderContainer:visible").mCustomScrollbar("scrollTo", "bottom");
          //$(".menuFolderContainer:visible").mCustomScrollbar("scrollTo", "down");
          //$(".menuFolderContainer:visible .mCSB_buttonDown").css('border', '1px solid red').trigger("click");*/

        sTop = $(".menuFolderContainer:visible .mCSB_container").css("top");
        sHeight = parseInt($(".menuFolderContainer:visible .mCSB_container").css("height"), 10);
        nNewPos = (parseInt(sTop, 10) + (delta*100));

        if(nNewPos < (200-sHeight))
          nNewPos = (200-sHeight);

        if(nNewPos > 0)
          nNewPos = 0

        /*$(".menuFolderContainer:visible .mCSB_scrollTools > div").addClass('mCS-mouse-over');
        $(".menuFolderContainer:visible .mCSB_scrollTools *").css('border', '1px solid red').show(0);*/

        $(".menuFolderContainer:visible .mCSB_container").css("top", nNewPos);
      });

    },

    stop: function()
    {
      $(this).closest("li").removeClass("rowActive");
    }
  });
}


function initDrop(psUrl)
{
  $(".menu_workspace .userFolderRow, .menu_folder .userFolderRow").droppable(
  {
    accept: ".list_item_draggable, .multi_drag",
    activeClass: "list_item_dropable",
    hoverClass: "list_item_hover",
    drop: function(event, ui )
    {
      //alert("ui dropped on .userFolderRow +> "+ui.draggable.text());
      //console.log(ui.draggable);

      sFolderPk = $(this).attr("data-folderpk");

      sType = ui.draggable.attr("data-type");
      sValue = ui.draggable.attr("data-ids");
      asIds = sValue.split(",");
      nItems = asIds.length;

      //alert(sType+" // "+sValue+" // "+nItems);

      if(!sType || sType == "undefined" || !sValue || sValue == "undefined" || !nItems)
      {
        alert("Missing some data");
        return false;
      }

      AjaxRequest(psUrl+"&folderpk="+sFolderPk+"&item_type="+sType+"&item_ids="+sValue, 'body');

      if(ui.draggable.hasClass("multi_drag"))
      {
        $(".listBox:checked").addClass("itemDroped").prop("checked", "");
        $(".multi_drag").remove();
      }

    },
    tolerance: "pointer"
  });

  $( "ul.tplListFullSize, ul.tplListFullSize li div" ).disableSelection();
}


/**
 * Comment
 */
function loadAjaxInNewTab(psUrl, psType, psTabLabel)
{
  var asContainer = goTabs.create(psType, '', '', psTabLabel);
  AjaxRequest(psUrl, 'body', '',  asContainer['id'], '', '', 'initHeaderManager(); ' );
  goTabs.select(asContainer['number']);
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -







/**
 * Moved from sl_candidate JS, but is required if accessiing candi list from weird page such
 * as settings, contact sheet...
 */
/*function initHeaderManager()
{
  $('#tab_content_container .scrollingContainer:not(.scroll_binded)').each(function()
  {
    $(this).addClass('scroll_binded');
    $(this).scroll(function()
    {
      //using ids is safer than scrollingcontainer:visible
      listHeaderManager($(this).attr('id'));
    });

    listHeaderManager($(this).attr('id'), true);
  });
}*/
function initHeaderManager()
{
  $('#tab_content_container .scrollingContainer:not(.floatingHeader)').each(function()
  {
    $(this).addClass('floatingHeader');
    $(this).scroll(function()
    {
      //using ids is safer than scrollingcontainer:visible
      listHeaderManager($(this).attr('id'));
    });

    listHeaderManager($(this).attr('id'), true);
  });
}


var oScrollTimer = null;
var bScrollTimer = false;
function listHeaderManager(poScrollingElement, pbForceResize)
{
  var sId = '';
  if(typeof poScrollingElement == 'string')
  {
    sId = poScrollingElement;
    poScrollingElement = $('#'+poScrollingElement);
  }

  if(bScrollTimer)
  {
    clearTimeout(oScrollTimer);
    oScrollTimer = setTimeout("bScrollTimer = false; listHeaderManager('"+sId+"'); ", 150);
    return true;
  }

  if(!poScrollingElement)
  {
    //alert('no poScrolling, I take the visible one');
    poScrollingElement = $('.scrollingContainer:visible');
  }

  var oLi = $(poScrollingElement).parent('li');
  //console.log('li id: '+oLi.attr('id'));

  bScrollTimer = true;
  oScrollTimer = setTimeout("bScrollTimer = false;", 150);

  var nPosition = $(poScrollingElement).scrollTop();
  if(nPosition === null || isNaN(nPosition))
    return true;

  if(nPosition > 120)
  {
    //console.log('Copy header ? size: '+ $('div.tplListContainer > ul > li.tplListHeaderContainer', oLi).length);
    var oRealHeader = $('div.tplListContainer > ul > li.tplListHeaderContainer', oLi);

    //if($(oHeader).is(':visible') || pbForceResize)
    if($('.fixedListheader > ul > li', oLi).length == 0)
    {
      //console.log('set fixed header in li '+ oLi.attr('id'));
      oRealHeader.hide(0);
      var oFixedHeader = oRealHeader.clone(true);
      //console.log(oFixedHeader);

      $('<div class="fixedListheader"><ul></ul></div>').prependTo(oLi).show(0);
      $('.fixedListheader ul', oLi).append(oFixedHeader);
      $('.fixedListheader ul li', oLi).fadeIn();
      $(poScrollingElement).addClass('scroll_binded');
    }
  }
  else
  {
    //console.log('remove fixed');
    if($('.fixedListheader', oLi).length > 0)
    {
      $('.fixedListheader', oLi).remove();
      $('li.tplListHeaderContainer', poScrollingElement).fadeIn(200);
      $(poScrollingElement).removeClass('scroll_binded');
    }
  }

  return true;
}






// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -







(function($)
{
  $(window).load(function()
  {
    $(".menuActionBloc").mCustomScrollbar(
    {
      autoHideScrollbar: true,
      mouseWheel: true,
      scrollButtons:{enable: true, scrollSpeed: 80}
    });
  });

})(jQuery);




function pipeCall(poTag, psUrl)
{
  var sUser = '&pipe_user='+ $(poTag).closest("li.menu_section").find('#pipe_user').val();
  var asContainer = goTabs.create('pipe', '', '', $(poTag).text());

  AjaxRequest(psUrl+ sUser, 'body', '',  asContainer['id'], '', '', 'initHeaderManager();');
  goTabs.select(asContainer['number']);

  return true;
}

//slistem specific tooltip function
function stp(poTag)
{
  $(poTag).tooltip().blur().mouseenter();
}

function sMsg(poTag)
{
  if($(poTag).attr('active'))
  {
    window.open('mailto:'+$(poTag).text(), 'zm_mail');
  }

}

function toggleSubfolder(pnParentPk, pbDisplay)
{
  if(pbDisplay === true)
  {
    if(pnParentPk)
      $('#userFolders .subfolder_list[data-folder-parent='+pnParentPk+']').fadeIn();
    else
      $('#userFolders .subfolder_list').fadeIn();

    return true;
  }

  if(pbDisplay === false)
  {
    if(pnParentPk)
      $('#userFolders .subfolder_list[data-folder-parent='+pnParentPk+']').fadeOut();
    else
      $('#userFolders .subfolder_list').fadeOut();

    return true;
  }


  if(pnParentPk)
    $('#userFolders .subfolder_list[data-folder-parent='+pnParentPk+']').fadeToggle();
  else
    $('#userFolders .subfolder_list').fadeToggle();

    return true;
}