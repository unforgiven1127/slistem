var CTabs = function()
{
  //contain the list of all the popups:  popupId => poPopupConf
  this.caoTab = {};
  this.cnUid = 0;
  this.cnTab = 0;
  this.cnTabSelected = 0;


  this.preload = function(psType, psId, pbSelected)
  {
    if(!psId)
    {
      //console.log('no id passed to preload tabs');
      return true;
    }

    if(!psType)
      psType = 'unknow';

    //initialize the internal array
    this.cnTab++;
    this.cnUid++;

    this.caoTab[this.cnUid] = {type: psType, id: psId, number: this.cnUid};

    //add the tab
    $('#tab_list').append(this._getTabHtml(this.cnTab, psType));

    //make sure the right class is settup on the container
    $('#'+psId).addClass('tab_nb_'+this.cnUid);

    if(pbSelected)
    {
      this.select(this.cnUid);
    }

    return {id: psId, number: this.cnUid};
  }


  this.remove = function(pnTab, pbRemovingAll)
  {
    if(!this.caoTab[pnTab])
    {
      alert('tab '+pnTab+' does not exist');
      return false;
    }

    $('#tab_list > li.tab_nb_'+pnTab).remove();
    $('#tab_content_container > li.tab_nb_'+pnTab).remove();
    delete this.caoTab[pnTab];
    this.cnTab--;

    if(this.cnTab > 0 && !pbRemovingAll && pnTab == this.cnTabSelected)
    {
      for (nFirst in this.caoTab) break;
      this.select(this.caoTab[nFirst].number);
    }

    if(this.cnTab < 2)
      $('#tab_list').removeClass('multiple_tabs');

    return true;
  };

  this.removeAll = function(pbForce)
  {
    $(this.caoTab).each(function(nIndex, oPopup)
    {
      this.remove(nIndex, true);
    });

    this.caoTab = {};
    this.cnTab = 0;
    this.cnTabSelected = 0;
  };


  this.removeByType = function(psType)
  {
    if(!psType)
      return false;

    //$(this.caoPopup).each(function(nIndex, oPopup)
    $.map(this.caoTab, function(oTab, sTagId)
    {
      //console.log('correct type:  popup - ['+sTagId+'] '+oPopup.type+' ? param -'+psType);
      if(oTab.type == psType)
      {
        goTab.remove(oTab.number);
      }
    });

    return true;
  };



  this.create = function(psType, psId, pbSelect, psTabLabel)
  {
    if(!psType)
      psType = 'candi';

    if(!psTabLabel)
      psTabLabel = '';

    var sId = '';
    if(psId)
      sId = psId;
    else
      sId = uniqueId();

    this.cnTab++;
    this.cnUid++;
    this.caoTab[this.cnUid] = {type: psType, id: sId, number: this.cnUid, label: psTabLabel};

    //add the tab: if this is thre first one, we display it straight away
    if(this.cnTab === 1)
    {
      $('#tab_content_container').append('<li id="'+sId+'" class="tab_nb_'+this.cnUid+' tab_type_'+psType+'"></li>');
    }
    else
    {
      $('#tab_content_container').append('<li id="'+sId+'" class="tab_nb_'+this.cnUid+' hidden"></li>');
      $('#tab_list').addClass('multiple_tabs');
    }

    //$('#tab_list').append(this._getTabHtml(this.cnUid, psType));
    $('#tab_list').prepend(this._getTabHtml(this.cnUid, psType, psTabLabel));

    if(pbSelect)
      this.select(this.cnUid);

    //return the new container id
    return {id: sId, number: this.cnUid};
  };

  this._getTabHtml = function(pnTab, psType, psTabLabel)
  {
    if(psTabLabel)
      return '<li class="tab_nb_'+pnTab+' tab_type_'+psType+'">\n\
            <div onclick="goTabs.select('+pnTab+');"> #'+pnTab+' | '+ psTabLabel +'</div>\n\
            <a onclick="goTabs.remove('+pnTab+');" class="close_tab">&nbsp;</a>\n\
            </li>';


    return '<li class="tab_nb_'+pnTab+' tab_type_'+psType+'">\n\
            <div onclick="goTabs.select('+pnTab+');">tab #'+pnTab+'</div>\n\
            <a onclick="goTabs.remove('+pnTab+');" class="close_tab">&nbsp;</a>\n\
            </li>';
  }


  this.select = function(pnTab)
  {
    if(this.cnTabSelected == pnTab)
    {
      //console.log('tab already selected: '+pnTab);
      return true;
    }

    this.cnTabSelected = pnTab;
    $('#tab_list > li.selected').removeClass('selected');
    $('#tab_list > li.tab_nb_'+pnTab).addClass('selected');

    if($('#tab_content_container > li:visible').length)
    {
      $('#tab_content_container > li:visible').fadeOut(50, function()
      {
        $('#tab_content_container > li.tab_nb_'+pnTab).fadeIn(50);
      });
    }
    else
      $('#tab_content_container > li.tab_nb_'+pnTab).fadeIn(50);

    return true;
  };


  this.getActive = function()
  {
    var oActive = this.caoTab[this.cnTabSelected];
    return oActive.id;
  };

}


var goTabs = new CTabs();
//console.log(goTabs);