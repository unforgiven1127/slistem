<?php

require_once('component/sl_menu/sl_menu.class.php5');

class CSl_menuEx extends CSl_menu
{
  public function __construct()
  {
    $oPage = CDependency::getCpPage();
    $asPageSize = $oPage->getPageSize();

    //has to match what is is sl_menu.js
    if(!empty($asPageSize['height']))
    {
      //==================================================
      //see sl_menu.js for details of those offset numbers
      $oPage->addCustomCss(
      '/* in full page mode */
        #tab_content_container > li .scrollingContainer { height: '.($asPageSize['height']-47).'px; }
        #tab_content_container > li .scrollingContainer.scroll_binded { height: '.($asPageSize['height']-87).'px;}

        /*when page is splitted */
       .componentMainContainer.containerSplit #tab_content_container > li .scrollingContainer { height: '.($asPageSize['height']-419).'px; }
       .componentMainContainer.containerSplit #tab_content_container > li .scrollingContainer.scroll_binded { height: '.($asPageSize['height']-459).'px;}
      ');
      }

    parent::__construct();
    return true;
  }

  //****************************************************************************
  //****************************************************************************
  // Interfaces and component settings
  //****************************************************************************
  //****************************************************************************


  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $asActions = array();
    $asActions['ppaa'][] = array('picture' => '','title'=>'quick search', 'url' => '');
    $asActions['ppab'][] = array('picture' => '','title'=>'My workspace', 'url' => '');
    $asActions['ppac'][] = array('picture' => '','title'=>'Shared folders', 'url' => '');
    $asActions['ppad'][] = array('picture' => '','title'=>'Pipeline', 'url' => '');

    return $asActions;
  }

  public function declareUserPreferences()
  {
    $asPrefs[] = array(
        'fieldname' => 'qs_position',
        'fieldtype' => 'select',
        'options' => array('0' => 'In a popup, like slistem 2', '1' => 'Always displayed (window height > 1020px)'),
        'label' => 'Quick search display option',
        'description' => 'Define if the quick search form is always displayed or placed in a popup.',
        'value' => '0'
    );

    return $asPrefs;
  }


  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case 'menu':
        return json_encode($this->_saveClicked());
        break;

      default:
        return json_encode($this->_getZimbraMailForm());
    }
  }


  //****************************************************************************
  //****************************************************************************
  // Component core
  //****************************************************************************
  //****************************************************************************

  public function getMenuAction($psPosition)
  {
    if(!assert('is_string($psPosition) && !empty($psPosition)'))
      return '';

    $oLogin = CDependency::getCpLogin();
    if(!$oLogin->isLogged())
      return '';

    $oSettings = CDependency::getComponentByName('settings');
    $asSettings = $oSettings->getSettings(array('menuactionpos', 'qs_position', 'qs_wide_search', 'qs_name_order'), false);

    if($asSettings['menuactionpos'] != $psPosition)
      return '';

    $bInlineQs = (int)$asSettings['qs_position'];

    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'/css/sl_menu.css');
    $oPage->addJsFile($this->getResourcePath().'/js/sl_menu.js');
    $oPage->addJsFile($this->getResourcePath().'/js/tabs.js');

    $oPage->addJsFile($this->getResourcePath().'/js/jquery.mousewheel.min.js');
    $oPage->addJsFile($this->getResourcePath().'/js/jquery.mCustomScrollbar.min.js');
    $oPage->addCssFile($this->getResourcePath().'/css/jquery.mCustomScrollbar.css');

    if(!isset($_SESSION['last_menu_clicked']))
      $_SESSION['last_menu_clicked'] = '';

    $sURL = $oPage->getAjaxUrl('search', CONST_ACTION_SEARCH, '', 0, array('formType' => 'advanced', 'CpUid' => '555-001', 'CpType' => 'candi'));
    $sHTML = '<ul class="menuActionList menuActionShort" id="menuaction">
      <li id="menuqs" class="menuqs">
        <div class="menuActionMenuContainer">
          <ul>';

    if(!$bInlineQs)
    {
      $sHTML.= '
            <li><a href="javascript:;" onclick="goPopup.setLayerFromTag(\'quickSearchContainerCp\');" title="Quick Search - companies"><img src="/common/pictures/slistem/qs_cp_inactive_24.png" /></a></li>
            <li><a href="javascript:;" onclick="goPopup.setLayerFromTag(\'quickSearchContainer\');" title="Quick Search - candidates"><img src="/common/pictures/slistem/qs_ct_inactive_24.png"/></a></li>
            <li><a href="javascript:;" onclick="goPopup.setLayerFromTag(\'quickSearchContainerJd\');" title="Quick Search - positions"><img src="/common/pictures/slistem/qs_jd_inactive_24.png"/></a></li>';
    }
    else
    {
      $sHTML.= '
            <li><a href="javascript:;" onclick="$(\'.qs_inline\').hide(); $(\'#quickSearchContainerCp\').fadeIn(); $(\'.menuqs ul li a.selected\').removeClass(\'selected\'); $(this).addClass(\'selected\'); " title="Quick Search - companies"><img src="/common/pictures/slistem/qs_cp_inactive_24.png" /></a></li>
            <li><a href="javascript:;" onclick="$(\'.qs_inline\').hide(); $(\'#quickSearchContainer\').fadeIn(); $(\'.menuqs ul li a.selected\').removeClass(\'selected\'); $(this).addClass(\'selected\');" title="Quick Search - candidates"><img src="/common/pictures/slistem/qs_ct_inactive_24.png"/></a></li>
            <li><a href="javascript:;" onclick="$(\'.qs_inline\').hide(); $(\'#quickSearchContainerJd\').fadeIn(); $(\'.menuqs ul li a.selected\').removeClass(\'selected\'); $(this).addClass(\'selected\');" title="Quick Search - positions"><img src="/common/pictures/slistem/qs_jd_inactive_24.png"/></a></li>';
    }

    $sHTML.= '
            <li><a href="javascript:;" title="Complex search form" onclick="
              var oConf = goPopup.getConfig(\'complex_layer\');
              oConf.width = 1150;
              oConf.height = 775;
              oConf.persistent = true;
              goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');"><img src="/common/pictures/slistem/qs_complex_search_24.png"/></a>
            </li>
          </ul>

        </div><div class="floatHack"></div>';

     if($bInlineQs)
     {
       $sHTML.= $this->_getQuickSearchForm(true, $asSettings);
       $sHTML.= $this->_getQuickSearchCpForm(true, $asSettings);
       $sHTML.= $this->_getQuickSearchJdForm(true, $asSettings);
     }


    $sHTML.= '</li>';


    $oFolder = CDependency::getComponentByName('sl_folder');
    if(!empty($oFolder))
    {
      $sHTML.= '<li class="section_multidrag" style="border: 0;"></li>';
      $sHTML.= $oFolder->getHtml(true, $_SESSION['last_menu_clicked']);
    }
    else
      $sHTML.= '<li class="menu_section"><br />Folders unavailable<br /><br /></li>';

    //Pipeline section
    $oPage = CDependency::getCpPage();
    $sURL = $oPage->getAjaxUrl('sl_menu', CONST_ACTION_UPDATE, 'menu', 0, array('last_menu_clicked' => 'pipeline'));

    if(empty($_SESSION['last_menu_clicked']) || $_SESSION['last_menu_clicked'] == 'pipeline')
      $sClass = '';
    else
      $sClass = ' hidden';

    $sHTML.= '<li class="menu_section">
        <div class="menuActionMenuContainer" onclick="toggleMenu(this, \''.$sURL.'\');"><a href="javascript:;">Pipeline</a></div>
        <div class="menuActionBloc menu_pipeline '.$sClass.'">
          <select id="pipe_user" name="pipe_user">
          <option value="'.$oLogin->getUserPk().'" selected="selected"> - mine - </option>';

     $asUser = $oLogin->getUserList(0, true, true);
     foreach($asUser as $asUser)
     {
       $sHTML.= '<option value="'.$asUser['loginpk'].'">'.$oLogin->getUserNameFromData($asUser).'</option>';
     }


    //Pipeline section
    $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_CANDI, 0);

    //&nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=in_play\');" title="Candidates in play, independently of their pitched status">In play</a><br />

     $sHTML.= '
          </select>
          <div class="menu_pipe_section">
          <strong>Active candidates</strong><br />

          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=met\');" title="Candidates met during the last 3 months">Recently met</a>&nbsp;&nbsp;&nbsp;
          <span class="optional">(
          <a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=met6\');">6m</a> |
          <a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=met12\');">1y</a> )
          </span><br />

          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=pitched\');">Pitched</a><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=resume_sent\');">Resume sent</a><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=ccm\');">In play / CCMs</a><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=offer\');">Offer</a><br />

          <br /><strong>Inactive candidates</strong><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=placed\');">Placed</a><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=fallen_off\');">Fallen Off</a><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=expired\');" style="color: #7F1414;">Stalled & expired</a><br />

          <br /><strong>Other</strong><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=rm\');">Following (RM)</a><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=meeting\');">Meeting scheduled</a><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=all_active\');" title="Created by me and picted to positions" >My active candidates</a><br />
          &nbsp;&nbsp;&nbsp;&nbsp;<a class="pipeLink" href="javascript:;" onclick="pipeCall(this, \''.$sURL.'&pipe_filter=all\');" title="Created by me" >All my candidates</a><br />
          </div>';

      if(isDevelopment())
      {
        $sURL = $oPage->getUrl('sl_candidate', CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_CANDI, 0);
        if(getValue('tpl'))
          $sHTML.= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$sURL.'&tpl=" >back to std view</a> <span class="alpha">(test/slow)</span><br />';
        else
          $sHTML.= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$sURL.'&tpl=name_collect" target="_blank">NC template</a> <span class="alpha">(test/slow)</span><br />';
      }

      $sHTML.= '
        </div>
        <div class="floatHack"></div>
      </li>

      <li class="menu_section section_tab" style="border-top: 1px solid #ddd;">
        <div class="menuActionMenuContainer" style="border-bottom: 1px solid #ddd; height: 20px;  margin-left: 0;  margin-top: 3px; padding-left: 5px;"><a href="javascript:;">Search tabs</a></div>
         <ul id="tab_list">
        </ul>
         <div class="floatHack"></div>
      </li>

    </ul>';

    if(!$bInlineQs)
    {
      $sHTML.= $this->_getQuickSearchForm(false, $asSettings);
      $sHTML.= $this->_getQuickSearchCpForm();
      $sHTML.= $this->_getQuickSearchJdForm();
    }


    return $this->_oDisplay->getBloc('menuact'.$psPosition, $sHTML, array('class' => 'menu'));
  }



  private function _getQuickSearchForm($pbInline = false, $pasSettings = array(), $pbHidden = false)
  {
    $oPage = CDependency::getComponentByName('page');
    $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_CANDI);

    $sNameOrder = 'lastname';
    if(isset($pasSettings['qs_name_order']) && !empty($pasSettings['qs_name_order']))
      $sNameOrder = $pasSettings['qs_name_order'];

    //Inline => always displayed, need to be compacted
    if($pbInline)
    {
      $sHTML = $this->_oDisplay->getBlocStart('quickSearchContainer', array( 'class' => 'qs_inline'));
      $sFieldJs = ' onfocus="if($(this).val() == $(this).attr(\'data-default\')){ $(this).val(\'\'); $(this).removeClass(\'defaultText\'); }"
      onblur="if($(this).val().trim().length == 0){ $(this).val($(this).attr(\'data-default\')); $(this).addClass(\'defaultText\');}" ';

      $sContactField = 'class="defaultText" data-default="Contact" value="Contact" '.$sFieldJs;
      $sCompanyField = 'class="defaultText" data-default="Company" value="Company" '.$sFieldJs;
      $sDepartmentField = 'class="defaultText" data-default="Department" value="Department" '.$sFieldJs;
      $sPositionField = 'class="defaultText" data-default="Position ID or title" value="Position ID or title" '.$sFieldJs;
      $sLabelClass = ' hidden';
    }
    else
    {
      $sHTML = $this->_oDisplay->getBlocStart('quickSearchContainer', array('class' => 'hidden', 'data-height' => 185, 'data-width' => 340, 'data-persistent' => 1, 'data-position' => '[15,85]', 'data-class' => 'noTitle','data-title' => 'Quick search...', 'data-draggable' => 0, 'data-resizable' => 0));
      $sFieldJs = $sContactField = $sCompanyField = $sDepartmentField = $sPositionField = $sLabelClass = '';
    }

    $sHTML.= '<form id="quickSearchForm" class="quickSearchForm" onsubmit="$(\'#alt_submit\', this).click(); return false;">';
    $sHTML.= '<input type="hidden" name="data_type" value="candi"/>';

    //$sHTML.= '<div><div class="label">ref ID</div><div class="field"><input type="text" name="ref_id"/></div></div>';
    $sHTML.= '<div><div class="label '.$sLabelClass.'">candidate</div><div class="field">
      <input type="text" name="candidate" class="defaultText" data-default="ID  or  lastname, firstname" value="ID  or  lastname, firstname"
      onfocus="if($(this).val() == $(this).attr(\'data-default\')){ $(this).val(\'\'); $(this).removeClass(\'defaultText\'); }"
      onblur="if($(this).val().trim().length == 0)
      { $(this).val($(this).attr(\'data-default\')); $(this).addClass(\'defaultText\');}
      else
      {
        asValue = $(this).val().trim().split(\',\');
        if(asValue.length > 2)
          return alert(\'There should be only 1 comma to separate the lastname and the firstname.\');

        if(asValue.length == 2)
        return true;

        asWords = asValue[0].split(\' \');
        if(asWords.length > 1)
        {
          sValue = asWords[0]+\', \';
          delete(asWords[0]);

          sValue+= asWords.join(\' \');
          $(this).val(sValue);
        }
      }"
      />
      </div></div>

     <div>

      <div class="label '.$sLabelClass.'">contacts</div><div class="field">
      <input type="text" name="contact" '.$sContactField.'/></div></div>

      <div><div class="label '.$sLabelClass.'">company</div><div class="field">
      <input type="text" name="company" '.$sCompanyField.'/></div></div>

      <div><div class="label '.$sLabelClass.'">department</div><div class="field">
      <input type="text" name="department" '.$sDepartmentField.'/></div></div>

      <div><div class="label '.$sLabelClass.'">position</div><div class="field">
      <input type="text" name="position" '.$sPositionField.' onchange="$(\'#qs_pos_status\').val(\'\'); "/>
      <input type="hidden" id="qs_pos_status" name="position_status" value=""/>
      </div></div>


      <div class="hidden option">Search options</div>

      <div class="hidden option"><div class="label '.$sLabelClass.'">wide search</div><div class="field">
      <input type="checkbox" name="qs_wide" ';

     if(isset($pasSettings['qs_wide_search']) && !empty($pasSettings['qs_wide_search']))
       $sHTML.= ' checked="checked" ';

     $sHTML.= ' /> (contains the string)</div></div>

      <div class="hidden option"><div class="label '.$sLabelClass.'">Get lucky</div><div class="field">
      <input type="checkbox" name="qs_super_wide" /> (lastname OR firstname)</div></div>

      <div class="hidden option"><div class="label '.$sLabelClass.'">name format</div><div class="field">
      <select name="qs_name_format">
        <option value="lastname" '.(($sNameOrder == 'lastname')? 'selected="selected"':'').'>Lastname, Firstname</option>
        <option value="firstname" '.(($sNameOrder == 'firstname')? 'selected="selected"':'').'>Firstname, Lastname</option>
        <option value="none" '.(($sNameOrder == 'none')? 'selected="selected"':'').'>Indifferent (slower/wider search)</option>
      </select>
      </div></div>

      <div class="hidden option" style="margin-top: 15px;"><a class="floatLeft" href="javascript:;" onclick="$(this).closest(\'form\').find(\'> div:not(.option_link)\').toggle(0);">&nbsp;apply&nbsp;</a></div>


    <div class="qs_action_row">
    <a class="floatLeft" href="javascript:;" onclick="$(this).closest(\'form\').find(\'> div:not(.option_link)\').toggle(0);">&nbsp;<img src="'.self::getResourcePath().'/pictures/qs_option.png"/>&nbsp;</a>
    <a class="floatLeft" href="javascript:;" onclick="$(this).closest(\'form\').find(\'input:visible\').val(\'\').blur();">&nbsp;<img src="/component/form/resources/pictures/tree_clear.png" title="Clear quick search form" onclick="tp(this);"/>&nbsp;</a>';

    if(!$pbInline)
      $sHTML.= '<a class="floatLeft" href="javascript:;" onclick="goPopup.remove(\'quickSearchContainer\');">&nbsp;<img src="/component/search/resources//pictures/delete_row_16.png" /></a>';

    $sHTML.= '<a id="alt_submit" href="javascript:;" class="floatRight" onclick="
          var asContainer = goTabs.create(\'candi\', \'\', \'\', \'Candidate QS\');
          AjaxRequest(\''.$sURL.'\', \'body\', \'quickSearchForm\',  asContainer[\'id\'], \'\', \'\', \'initHeaderManager(); \');
          goTabs.select(asContainer[\'number\']);">&nbsp;<img src="/component/search/resources/pictures/search_24.png" /></a>
          <input type="submit" style="opacity:0; width: 0px; height: 0px;" />
    </div>';

    $sHTML.= '<p class="floatHack" /></form>';
    $sHTML.= $this->_oDisplay->getBlocEnd();
    return $sHTML;
  }


  private function _getQuickSearchCpForm($pbInline = false)
  {
    $oPage = CDependency::getComponentByName('page');
    $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_LIST, CONST_CANDIDATE_TYPE_COMP);


    //Inline => always displayed, need to be compacted
    if($pbInline)
    {
      $sHTML = $this->_oDisplay->getBlocStart('quickSearchContainerCp', array( 'class' => 'qs_inline hidden'));
      $sLabelClass = ' hidden';

      $sFieldJs = ' onfocus="if($(this).val() == $(this).attr(\'data-default\')){ $(this).val(\'\'); $(this).removeClass(\'defaultText\'); }"
      onblur="if($(this).val().trim().length == 0){ $(this).val($(this).attr(\'data-default\')); $(this).addClass(\'defaultText\');}" ';

      $sNameField = 'class="defaultText" data-default="Company name or ID" value="Company name or ID" '.$sFieldJs;
      $sIndustryField = 'class="defaultText" data-default="Industry" value="Industry" '.$sFieldJs;
      $sContactField = 'class="defaultText" data-default="Contact" value="Contact" '.$sFieldJs;
      $sOwnerField = 'class="defaultText" data-default="Owner" value="Owner" '.$sFieldJs;
      $sLabelClass = ' hidden';
    }
    else
    {
      $sHTML = $this->_oDisplay->getBlocStart('quickSearchContainerCp', array('class' => 'hidden', 'data-height' => 160, 'data-width' => 340, 'data-persistent' => 1, 'data-position' => '[15,85]', 'data-class' => 'noTitle','data-title' => 'Quick search...', 'data-draggable' => 0, 'data-resizable' => 0));
      $sLabelClass = $sNameField = $sIndustryField = $sContactField = $sOwnerField = $sLabelClass = '';
    }

    $sHTML.= '<form id="quickSearchFormCp" class="quickSearchForm" onsubmit="$(\'#alt_submit\', this).click(); return false;">';
    $sHTML.= '<input type="hidden" name="data_type" value="comp"/>';


    $sHTML.= '<div><div class="label '.$sLabelClass.'">company</div><div class="field">
      <input type="text" name="company" class="defaultText" '.$sNameField.'/>
      </div></div>

     <div>

      <div class="label '.$sLabelClass.'">industry</div><div class="field">
      <input type="text" name="industry" '.$sIndustryField.'/></div></div>

      <div><div class="label '.$sLabelClass.'">contact</div><div class="field">
      <input type="text" name="contact" '.$sContactField.'/></div></div>

      <div><div class="label '.$sLabelClass.'">creator/owner</div><div class="field">
      <input type="text" name="owner"'.$sOwnerField.' /></div></div>

    <div><br />
    <a class="floatLeft" href="javascript:;" onclick="$(this).closest(\'form\').find(\'input:visible\').val(\'\').blur();">&nbsp;<img src="/component/form/resources/pictures/tree_clear.png" title="Clear quick search form" onclick="tp(this);"/>&nbsp;</a>';

    if(!$pbInline)
      $sHTML.= '<a class="floatLeft" href="javascript:;" onclick="goPopup.remove(\'quickSearchContainerCp\');">&nbsp;<img src="/component/search/resources//pictures/delete_row_16.png" /></a>';

    $sHTML.= '<a id="alt_submit" href="javascript:;" class="floatRight" onclick="
          var asContainer = goTabs.create(\'comp\', \'\', \'\', \'Company QS\');
          AjaxRequest(\''.$sURL.'\', \'body\', \'quickSearchFormCp\',  asContainer[\'id\'], \'\', \'\', \'initHeaderManager(); \');
          goTabs.select(asContainer[\'number\']);">&nbsp;<img src="/component/search/resources/pictures/search_24.png" /></a>
          <input type="submit" style="display: none;" />
    </div>';

    $sHTML.= '<p class="floatHack" /></form>';
    $sHTML.= $this->_oDisplay->getBlocEnd();
    return $sHTML;
  }

  private function _getQuickSearchJdForm($pbInline = false)
  {
    $oPage = CDependency::getComponentByName('page');
    $sURL = $oPage->getAjaxUrl('sl_position', CONST_ACTION_SEARCH, CONST_POSITION_TYPE_JD, 0, array('qs' => 1));


    //Inline => always displayed, need to be compacted
    if($pbInline)
    {
      $sHTML = $this->_oDisplay->getBlocStart('quickSearchContainerJd', array( 'class' => 'qs_inline hidden'));
      $sLabelClass = ' hidden';

      $sFieldJs = ' onfocus="if($(this).val() == $(this).attr(\'data-default\')){ $(this).val(\'\'); $(this).removeClass(\'defaultText\'); }"
      onblur="if($(this).val().trim().length == 0){ $(this).val($(this).attr(\'data-default\')); $(this).addClass(\'defaultText\');}" ';

      $sTitleField = 'class="defaultText" data-default="Position title" value="Position title" '.$sFieldJs;
      $sCompanyField = 'class="defaultText" data-default="Company name" value="Company name" '.$sFieldJs;
      $sIndustryField = 'class="defaultText" data-default="Industry" value="Industry" '.$sFieldJs;
      $sContentField = 'class="defaultText" data-default="Content" value="Content" '.$sFieldJs;
      $sLabelClass = ' hidden';
    }
    else
    {
      $sHTML = $this->_oDisplay->getBlocStart('quickSearchContainerJd', array('class' => 'hidden', 'data-height' => 160, 'data-width' => 340, 'data-persistent' => 1, 'data-position' => '[15,85]', 'data-class' => 'noTitle','data-title' => 'Quick search...', 'data-draggable' => 0, 'data-resizable' => 0));
      $sLabelClass = $sTitleField = $sCompanyField = $sIndustryField = $sContentField = '';
    }

    $sHTML.= '<form id="quickSearchFormJd" class="quickSearchForm" onsubmit="$(\'#alt_submit\', this).click(); return false;">';
    $sHTML.= '<input type="hidden" name="data_type" value="comp"/>
      <input type="hidden" name="qs" value="1"/>

      <div><div class="label '.$sLabelClass.'">id / title</div><div class="field">
      <input type="text" name="title" '.$sTitleField.'/></div></div>

      <div><div class="label '.$sLabelClass.'">company</div><div class="field">
      <input type="text" name="company" class="defaultText" '.$sCompanyField.'/>
      </div></div>

     <div>

      <div class="label '.$sLabelClass.'">industry</div><div class="field">
      <input type="text" name="industry" '.$sIndustryField.'/></div></div>

      <div><div class="label '.$sLabelClass.'">all content</div><div class="field">
      <input type="text" name="content" '.$sContentField.' /></div></div>

    <div>
    <br />
    <a class="floatLeft" href="javascript:;" onclick="$(this).closest(\'form\').find(\'input:visible\').val(\'\').blur();">&nbsp;<img src="/component/form/resources/pictures/tree_clear.png" title="Clear quick search form" onclick="tp(this);"/>&nbsp;</a>';

    if(!$pbInline)
      $sHTML.= '<a class="floatLeft" href="javascript:;" onclick="goPopup.remove(\'quickSearchContainerJd\');">&nbsp;<img src="/component/search/resources//pictures/delete_row_16.png" /></a>';

    $sHTML.= '<a id="alt_submit" href="javascript:;" class="floatRight" onclick="
          var asContainer = goTabs.create(\'pos\', \'\', \'\', \'Position QS\');
          AjaxRequest(\''.$sURL.'\', \'body\', \'quickSearchFormJd\',  asContainer[\'id\'], \'\', \'\', \'initHeaderManager();\');
          goTabs.select(asContainer[\'number\']);">&nbsp;<img src="/component/search/resources/pictures/search_24.png" /></a>
          <input type="submit" style="opacity: 0; " />
    </div>';

    $sHTML.= '<p class="floatHack" /></form>';
    $sHTML.= $this->_oDisplay->getBlocEnd();
    return $sHTML;
  }


  /**
   * Check item params, then load an ifram with zmbra mail for\m in it.
   * Based on item params, include item descriptions
   */
  private function _getZimbraMailForm()
  {

    //=======================================================================
    //We receive a string to describe the item to load in the cp_item_selector
    //check it and check item
    $sCpItem = getValue('cp_item_selector');
    $sDescription = '';
    if(!empty($sCpItem))
    {
      $asItem = explode('|@|', $sCpItem);
      if(count($asItem) == 4)
      {
        //check the item existe and fetch the label
        $oComponent = CDependency::getComponentByUid($asItem[0]);
        if(!empty($oComponent))
        {
          $asItemData = $oComponent->getItemDescription((int)$asItem[3], $asItem[1], $asItem[2]);
          if(!empty($asItemData))
          {
            $sDescription = '- - - '."\n";
            $sDescription.= 'This emails concerns:'."\n\n";
            $sDescription.= $asItemData[$asItem[3]]['label']."\n".$asItemData[$asItem[3]]['description']."\n".$asItemData[$asItem[3]]['url']."\n";
            $sDescription = str_ireplace(array('<br/>', '<br />', '<br>'), "\n", $sDescription);
            $sDescription = strip_tags($sDescription);
            $sDescription.= "\n".'- - - ';
          }
        }
      }
    }

    $sJs = ' window.open(\'mailto:?body='.  urlencode($sDescription).'\', \'zm_mail\'); ';
    return array('data' => 'ok', 'action' => $sJs);
  }


  private function _saveClicked()
  {
    $_SESSION['last_menu_clicked'] = getValue('last_menu_clicked');
    return array('data' => $_SESSION['last_menu_clicked']);
  }
}
