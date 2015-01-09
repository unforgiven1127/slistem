
function init_ptree(psFieldId, psContainerId, psDummyId)
{
  var oTreeContainer = jQuery("#"+psContainerId);

  jQuery(".TSelect_lvl_0 li", oTreeContainer).bind("click", function()
  {
    if(jQuery(this).hasClass("final"))
      return saveTreeValue(this, psDummyId, psFieldId, psContainerId);

    jQuery(".TSelect_lvl_0 li.selected", oTreeContainer).removeClass("selected");
    jQuery(this).addClass("selected");
    jQuery(".TSelect_lvl_2:visible", oTreeContainer).fadeOut("fast");
    var nVal = jQuery(this).val();

    if(jQuery(".TSelect_lvl_1 ul[parent="+nVal+"]", oTreeContainer).length)
    {
      jQuery(".TSelect_lvl_1:not(:visible)", oTreeContainer).fadeIn("fast");
      jQuery(".TSelect_lvl_1 ul:not([parent="+nVal+"])", oTreeContainer).hide(0);
      jQuery(".TSelect_lvl_1 ul[parent="+nVal+"]", oTreeContainer).show(0);
    }
    else
    {
      jQuery(".TSelect_lvl_2:visible", oTreeContainer).fadeOut("fast");
      jQuery(".TSelect_lvl_1:visible", oTreeContainer).fadeOut("fast");
      jQuery(".TSelect_lvl_1 ul", oTreeContainer).hide(0);
      jQuery(".TSelect_lvl_2 ul", oTreeContainer).hide(0);
    }
  });


  jQuery(".TSelect_lvl_1 li", oTreeContainer).bind("click", function()
  {
    if(jQuery(this).hasClass("final"))
      return saveTreeValue(this, psDummyId, psFieldId, psContainerId);

    jQuery(".TSelect_lvl_1 li.selected", oTreeContainer).removeClass("selected");
    jQuery(this).addClass("selected");
    var nVal = jQuery(this).val();

    if(jQuery(".TSelect_lvl_2 ul[parent="+nVal+"]", oTreeContainer).length)
    {
      jQuery(".TSelect_lvl_2:not(:visible)", oTreeContainer).fadeIn("fast");
      jQuery(".TSelect_lvl_2 ul:not([parent="+nVal+"])", oTreeContainer).hide(0);
      jQuery(".TSelect_lvl_2 ul[parent="+nVal+"]", oTreeContainer).show(0);

    }
    else
    {
      jQuery(".TSelect_lvl_2:visible", oTreeContainer).fadeOut("fast");
      jQuery(".TSelect_lvl_2 ul", oTreeContainer).hide(0);
    }
  });

  //manage default selected nodes
  jQuery(".TSelect_lvl_2 li.selected", oTreeContainer).each(function()
  {
    jQuery(".TSelect_lvl_2:not(:visible)", oTreeContainer).show(0);
    jQuery(this).parent().closest("ul").show(0);
    jQuery(".TSelect_lvl_1 li[value="+jQuery(this).attr("parent")+"]", oTreeContainer).addClass("selected");
    jQuery("input[name="+psFieldId+"_lvl_2]", oTreeContainer).val(jQuery(this).val());

    //console.log("li lvl 2 selected => save value");
    jQuery("input[name="+psFieldId+"]", oTreeContainer).val(jQuery(this).val());
  });

  jQuery(".TSelect_lvl_1 li.selected", oTreeContainer).each(function()
  {
    //console.log("li lvl 1 selected [val: "+jQuery(this).val()+"][parent: "+jQuery(this).attr("parent")+"]");

    jQuery(".TSelect_lvl_1:not(:visible)", oTreeContainer).show(0);
    jQuery(this).parent().closest("ul").show(0);
    jQuery(".TSelect_lvl_0 li[value="+jQuery(this).attr("parent")+"]", oTreeContainer).addClass("selected");
    jQuery("input[name="+psFieldId+"_lvl_1]", oTreeContainer).val(jQuery(this).val());

    if(!jQuery("input[name="+psFieldId+"]", oTreeContainer).val())
    {
      //console.log("li lvl 1 selected & field empty => save value");
      jQuery("input[name="+psFieldId+"]", oTreeContainer).val(jQuery(this).val());
    }
  });


  //$('.TSelect_mainBtn').bind('keydown', function(event){ ptreeNavigate(event); });
  $('#'+psDummyId).bind('keydown', function(event){ ptreeNavigate(event); });
 }


function saveTreeValue(poElement, psInputId, psFieldId, psContainerId)
{
  //select current element
  $(poElement).parent("ul").find("li.selected").removeClass("selected");
  $(poElement).addClass("selected");

  //hide lower levels if necessary
  var nLevel = $(poElement).closest(".TSelectLevel").attr("level");

  if(nLevel == 1)
  {
    $(".TSelect_lvl_2").hide(0);
    $(".TSelect_lvl_2 li.selected").removeClass("selected");
  }
  else
  {
    if(nLevel == 0)
    {
      $(".TSelect_lvl_1, .TSelect_lvl_2").hide(0);
      $(".TSelect_lvl_1 li.selected, .TSelect_lvl_2 li.selected").removeClass("selected");
    }
  }

  //update button
  $("#"+psInputId).val($(poElement).text());



  $("input#"+psFieldId, oTreeContainer).val( $(poElement).val() );
  var oTreeContainer = $("#"+psContainerId);

  $("input[name="+psFieldId+"_lvl_0]", oTreeContainer).val( $(".TSelect_lvl_0 li.selected", oTreeContainer).val() );
  $("input[name="+psFieldId+"_lvl_1]", oTreeContainer).val( $(".TSelect_lvl_1 li.selected", oTreeContainer).val() );
  $("input[name="+psFieldId+"_lvl_2]", oTreeContainer).val( $(".TSelect_lvl_2 li.selected", oTreeContainer).val() );

  $(oTreeContainer).fadeOut("fast");
  // $(poElement).closest('.formFieldContainer').next(':visible').find('input,textarea,select').focus();
}






function paneControl(psFieldSelector, psDummySelector)
{
  var oField = $(psFieldSelector);

  if( $(oField).is(':visible'))
  {
    $(oField).fadeOut('fast', function(){ jQuery(psFieldSelector +' div.TSelectLevel').hide(0); });
    /*console.log('unbind keydown');
    $('.TSelect_mainBtn').unbind('keydown');*/
  }
  else
  {
    $('.TSelect_mainContainer').fadeOut('fast');
    var oPosition = $(psDummySelector).position();

    $(oField).attr('style',  'display: none; top: '+(oPosition.top+20)+'px; left: '+(oPosition.left)+'px; ');
    $(oField).fadeIn('fast', function(){ jQuery(psFieldSelector +' div:first').fadeIn('fast'); });
  }
}



 function ptreeNavigate(poEvent)
{
  var nKey = poEvent.which;

  if(nKey == 9)
  {
    //console.log(nKey);
    $(poEvent.target).closest('.formField').find('.TSelect_close').click();
    return true;
  }

  if(nKey != 47 && nKey < 37 && nKey > 40)
    return false;

  //console.log(nKey);

  var oInput = $(poEvent.target);
  var oContainer = $(oInput).parent().find('.TSelect_mainContainer');

  if( $(oInput).hasClass('TSelect_mainBtn') )
  {
    var nLevel = $(oInput).attr('current_position');
    if(!nLevel)
      nLevel = 0;

    //console.log('level: '+nLevel);

    // --------------------------------------
    //move horizontally
    if(nKey == 37 || nKey == 39)
    {

      if(nLevel == 1 && nKey == 39)
      {
        $('.TSelect_lvl_1 li.highlight', oContainer).click();
        //console.log('select element lvl 1 + right ');
      }

      if(nLevel == 0 && nKey == 39)
      {
        $(oInput).attr('current_position', 1);
        nLevel = 1;
        $('.TSelect_lvl_0 li.highlight', oContainer).click();


        if($('.TSelect_lvl_1 ul:visible li.highlight', oContainer).length == 0)
        {
          $('.TSelect_lvl_1 li', oContainer).removeClass('highlight');
          $('.TSelect_lvl_1 ul:visible li:first', oContainer).addClass('highlight');
          //console.log(' select first li');
        }

        //console.log('open pane lvl 1');
      }

      if(nLevel == 1 && nKey == 37)
      {
        $(oInput).attr('current_position', 0);
        $('.TSelect_lvl_1', oContainer).fadeOut();
        nLevel = 0;

        //console.log('close pane lvl 1');
      }

      return true;
    }



    // --------------------------------------
    //move vertically

    if(nKey == 38 || nKey == 40)
    {
      //open the list if not already done
      if($('.TSelect_lvl_0:not(:visible)', oContainer).length)
      {
        $(oInput).click();
      }

      var oLvlPane = $('.TSelect_lvl_'+nLevel+' ul:visible', oContainer);


      if($('li.highlight', oLvlPane).length == 0)
      {
        $('li', oLvlPane).removeClass('highlight');
        $('li:first', oLvlPane).addClass('highlight');
        //console.log(' select first li');
      }
      else
      {
        //key press UP
        if(nKey == 38)
        {
          if($('li.highlight', oLvlPane).prev('li'))
          {
            $('li.highlight', oLvlPane).removeClass('highlight').prev().addClass('highlight');
            nScroll+= -20;
            //console.log(' select prev li');
          }
          else
          {
            $('li', oLvlPane).removeClass('highlight');
            $('li:last', oLvlPane).addClass('highlight');
            //console.log(' no prev li -> jump last');
          }
        }
        else //key press DOWN
        {
          if($('li.highlight', oLvlPane).next('li'))
          {
            $('li.highlight', oLvlPane).removeClass('highlight').next().addClass('highlight');
            //console.log(' select next li ');
          }
          else
          {
            $('li', oLvlPane).removeClass('highlight');
            $('li:first', oLvlPane).addClass('highlight');

            //console.log(' no next li -> jump first');
          }
        }

      }

      var nScroll = ($('li.highlight', oLvlPane).index()) * $('li.highlight', oLvlPane).outerHeight();
      $('.TSelect_lvl_'+nLevel).scrollTop(nScroll);

      return true;
    }

    if(nKey == 47)
    {
      $('.TSelect_lvl_2 li.highlight', oContainer).click();
      $('.TSelect_lvl_1 li.highlight', oContainer).click();
      $('.TSelect_lvl_0 li.highlight', oContainer).click();
    }

  }
}
