<?php

require_once('component/menu/menu.class.php5');

class CMenuEx extends CMenu
{

  protected $_oSettings;
  protected $_oPage;
  protected $_oDisplay;
  protected $_oLogin;

  public function __construct()
  {
    $this->_oDisplay = CDependency::getCpHtml();
    $this->_oLogin = CDependency::getCpLogin();

    $this->_oSettings = CDependency::getComponentByName('settings');
    $this->_oPage = CDependency::getCpPage();

    return true;
  }

  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csAction)
    {
      case CONST_ACTION_EDIT:
        switch($this->csType)
        {
          case 'menunav' :
            return json_encode($this->_displayFormMenuNav($this->cnPk));
          break;
        }
       break;
    }
  }

  private function _displayFormMenuNav($pnMenuNav)
  {
    $oForm = $this->_oDisplay->initForm('menuNavEditForm');
    $sValue = $this->_oSettings->getSettingValue('menunav'.$pnMenuNav);

    $sURL = $this->_oPage->getUrl('settings', CONST_ACTION_SAVEEDIT, CONST_TYPE_SETTINGS, 0);
    $oForm->setFormParams('menuNavEditForm', false, array('action' => $sURL, 'class' => 'fullPageForm', 'submitLabel'=>'Save changes'));
    $oForm->setFormDisplayParams(array('noCancelButton' => 'noCancelButton'));
    $oForm->addField('textarea', 'psFieldValue', array('label'=>'Menu nav '.$pnMenuNav, 'value' => addslashes(var_export($sValue))));
    $oForm->addField('input', 'psFieldName', array('type' => 'hidden', 'value' => 'menunav'.$pnMenuNav));
    $oForm->addField('input', 'psFieldType', array('type' => 'hidden', 'value' => 'serialized64'));

    $sHTML = $oForm->getDisplay();
    return $this->_oPage->getAjaxExtraContent(array('data'=>$sHTML));
  }

  private function _canAccessMenu($pasMenuItem)
  {
    if(!assert('is_array($pasMenuItem) && !empty($pasMenuItem)'))
      return false;

    if(!isset($pasMenuItem['right']) || !is_array($pasMenuItem['right']))
    {
      assert('false; // no right data for the menu item ');
      return false;
    }

    //check if component is available
    if(!empty($pasMenuItem['uid']) )
    {
      if(!CDependency::getComponentNameByUid($pasMenuItem['uid']))
        return false;
    }

    //public link
    if(in_array('*', $pasMenuItem['right'], true))
    {
      return true;
    }

    if(in_array('logged', $pasMenuItem['right'], true) && $this->_oLogin->isLogged())
    {
      return true;
    }

    $oRight = CDependency::getComponentByName('right');

    //check if there are details uid/act/type/pk in the right array
    foreach($pasMenuItem['right'] as $asRight)
    {
      if(count($asRight) >= 3 && $oRight->canAccess(@$asRight['uid'], @$asRight['action'], @$asRight['type'], (int)@$asRight['pk']))
      {
        return true;
      }
    }

    //finally check if the link url is accessible
    if($oRight->canAccess($pasMenuItem['uid'], $pasMenuItem['action'], $pasMenuItem['type'], (int)$pasMenuItem['pk']))
    {
      return true;
    }

    return false;
  }

  // Displays menus depending on the location
  // @param $psPosition string
  // @param $pnMenuNumber int

  public function getMenuNav($psPosition = '', $pnMenuNumber = 0)
  {
    if(!assert('is_string($psPosition) && !empty($psPosition)'))
      return '';

    if(!assert('is_integer($pnMenuNumber)'))
      return '';

    $bVertical = ($psPosition == 'left' || $psPosition == 'right');
    $this->_oPage->addCssFile(self::getResourcePath().'/css/menu.css');

    $sHTML = $this->_oDisplay->getBlocStart('menunav'.$psPosition, array('class' => 'menu'));

      $sMenus = '';
      if($pnMenuNumber == 0)
      {
        for($nCount = 1; $nCount <= 3; $nCount++)
        {
          if($this->_oSettings->getSettingValue('menunav'.$nCount.'pos') == $psPosition)
            $sMenus .= $this->_displayMenuNav($nCount, $psPosition, $bVertical);
        }
      }
      else
      {
        $sMenus = $this->_displayMenuNav($nCount, $psPosition, $bVertical);
      }

      if(empty($sMenus))
        return '';

      $sHTML.= $sMenus;

    $sHTML.= $this->_oDisplay->getBlocEnd();

    $sEmbedUrl = $this->_oPage->getEmbedUrl();
    if(!empty($sEmbedUrl))
    {
      $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'embedMenuLink'));
      $sHTML.= $this->_oDisplay->getLink('+ Open in new tab', $sEmbedUrl, array('target' => '_blank'));
      $sHTML.= $this->_oDisplay->getBlocEnd();
    }

    return $sHTML;
  }

  public function getMenuAction($psPosition)
  {
    if(!assert('is_string($psPosition) && !empty($psPosition)'))
      return '';

    if(($this->_oSettings->getSettingValue('menuactionpos') != $psPosition))
      return '';

    $oHTML = $this->_oDisplay;
    $sHTML = '';

    //TODO : Need to make this condition generic later
    if(CONST_WEBSITE == 'talentAtlas')
    {
      $sLanguage = $this->_oPage->getLanguage();

      $sJavascript = ' var value = $(this).val();
       if(value == \'en\')
       {
          url = \''.CONST_CRM_DOMAIN.$_SERVER['REQUEST_URI'].'&setLang=en\'
       }
       else
       {
         url = \''.CONST_CRM_DOMAIN.$_SERVER['REQUEST_URI'].'&setLang=jp\'
       }
       window.location.href = url;';

      $sHTML.= $oHTML->getBlocStart('',array('class'=>'languageSelect'));
       $sHTML.= '<select name="language_change" onchange="'.$sJavascript.'" class="selectLang">';
        $sHTML.= '<option value= "en" '.(($sLanguage == 'en') ? 'selected="selected"':'').'> English </option>';
        $sHTML.= '<option value= "jp" '.(($sLanguage == 'jp') ? 'selected="selected"':'').'> Japanese </option>';
       $sHTML.= '</select>';
      $sHTML.= $oHTML->getBlocEnd();
    }

    $asUid = CDependency::getComponentUidByInterface('has_menuAction');

    foreach($asUid as $sUid)
    {
      $oComponent = CDependency::getComponentByUid($sUid);
      $asComponentAction = $oComponent->getPageActions($this->_oPage->getAction(), $this->_oPage->getType(), $this->_oPage->getPk());

      if(!empty($asComponentAction))
      {
        $sHTML .= $oHTML->getListStart('menuaction', array('class' => 'menuActionList'));

        foreach ($asComponentAction as $aasAction)
        {
          $nAction= count($aasAction);
          $nCount = 0;
          foreach($aasAction as $sType => $asAction)
          {
            if($nCount == 1)
            {
              //More than 1 action: close the Menu container + add arrow + open submenu container
              $sHTML.= '</div>';
              $sHTML.= '<div class="menuActionMenuExtend"><div><a href="javascript:;"><img src="'.CONST_PICTURE_MENU_MULTIPLE.'" /></a></div></div>';
              $sHTML.= '<div class="menuActionSubMenuContainer"><ul class="menuActionSubMenu">';
            }

            if(!isset($asAction['title']))
              $asAction['title'] = '';

            if(!isset($asAction['option']))
              $asAction['option'] = array();

            $sHTML.= '<li><div class="menuActionMenuContainer">';

            if(isset($asAction['picture']) && !empty($asAction['picture']))
              $sHTML.= $oHTML->getPicture($asAction['picture'], $asAction['title'], $asAction['url'], $asAction['option']);
            else
              $sHTML.= $oHTML->getLink($asAction['title'], $asAction['url'], $asAction['option']);

            if($nAction <= 1)
              $sHTML.= '</div><div class="floatHack"></div></li>';

            $nCount++;
          }

          if($nAction > 1)
          {
            $sHTML.= ' </ul></div><div class="floatHack"></div></li>';
          }
        }

        // Adding the add to folder button to the action menu if an interface exist
        if(CDependency::hasInterfaceByUid($sUid, 'has_folders'))
        {
          $oFolder = CDependency::getComponentByName('folder');
          if($oFolder)
          {
            $sHTML .= $oHTML->getListItemStart();
            $sHTML .= $oHTML->getBlocStart('addtofolder');
            $sHTML .= $oFolder->displayAddToFolderLink();
            $sHTML .= $oHTML->getBlocEnd();
            $sHTML .= $oHTML->getListItemEnd();
          }
        }

        $sHTML.= $oHTML->getListEnd();

      }
    }

    if(empty($sHTML))
      return '';

    return $this->_oDisplay->getBloc('menuact'.$psPosition, $sHTML, array('class' => 'menu'));
  }


  private function _addComponentActions(&$pasMenuItem)
  {
    $asAction = $asActionToAdd = array();


    $asUid = CDependency::getComponentUidByInterface('has_menuAction');
    foreach($asUid as $sUid)
    {
      $sType = $this->_oPage->getType();
      $oComponent = CDependency::getComponentByUid($sUid);
      $asAction = $oComponent->getPageActions($this->_oPage->getAction(), $sType, $this->_oPage->getPk());

      if(!empty($asAction))
        $asActionToAdd[$sUid][$sType] = $asAction;
    }

    //dump($asActionToAdd);

    foreach($asActionToAdd as $sUid => $asComponentAction)
    {
      foreach($asComponentAction as $sType => $asCpAction)
      {
        //dump(count($asCpAction).' actions provided by component'.$sUid);
        if(!empty($asCpAction))
        {
          foreach($pasMenuItem as $vKey => $asMenuItem)
          {
            //dump('is there a menu item related to this component ['.$sUid.' = '.$asMenuItem['uid'].']');
            //dump($asMenuItem);
            /*dump($sUid);
            dump($sType);
            dump($asCpAction);
            dump(' - - - -  - - - - ');*/

            if(isset($asMenuItem['uid']) && $asMenuItem['uid'] == $sUid
               && isset($asMenuItem['type']) && $asMenuItem['type'] == $sType )
            {
              //found a menu item that fits these component actions
              //need to format and insert it
              $pasMenuItem[$vKey]['menu_action'] = array();
              $nCount = 0;

              foreach($asCpAction as $sCPAction => $asActions)
              {
                foreach($asActions as $asAction)
                {
                  //dump('--> adding '.$asAction['title'].' actions in the menu');
                  $pasMenuItem[$vKey]['menu_action'][$nCount]['uid'] = $sUid;
                  $pasMenuItem[$vKey]['menu_action'][$nCount]['action'] = $sCPAction;
                  $pasMenuItem[$vKey]['menu_action'][$nCount]['type'] = '';
                  $pasMenuItem[$vKey]['menu_action'][$nCount]['pk'] = 0;
                  $pasMenuItem[$vKey]['menu_action'][$nCount]['icon'] = $asAction['picture'];
                  $pasMenuItem[$vKey]['menu_action'][$nCount]['link'] = $asAction['url'];
                  $pasMenuItem[$vKey]['menu_action'][$nCount]['name'] = $asAction['title'];
                  $pasMenuItem[$vKey]['menu_action'][$nCount]['right'] = array('logged');

                  if(isset($asAction['option']))
                    $pasMenuItem[$vKey]['menu_action'][$nCount]['option'] = $asAction['option'];

                  $nCount++;
                }
              }

              //action has been added in the menu. we remove it so it's not added again
              unset($asActionToAdd[$sUid][$sType]);
            }
          }
        }
      }
    }


    //add the action that couldn't be added in the menu at the end
    if(!empty($asActionToAdd))
    {
      $nCount = 0;
      $asLastAction = array();
      foreach($asActionToAdd as $sUid => $asComponentAction)
      {
        if(!empty($asComponentAction))
        {
          foreach($asComponentAction as $sType => $asCpAction)
          {
            foreach($asCpAction as $sCPAction => $asActions)
            {
              foreach($asActions as $asAction)
              {
                //dump('--> adding '.$asAction['title'].' actions in the menu');
                $asLastAction[$nCount]['uid'] = $sUid;
                $asLastAction[$nCount]['action'] = $sCPAction;
                $asLastAction[$nCount]['type'] = '';
                $asLastAction[$nCount]['pk'] = 0;
                $asLastAction[$nCount]['icon'] = $asAction['picture'];
                $asLastAction[$nCount]['link'] = $asAction['url'];
                $asLastAction[$nCount]['name'] = $asAction['title'];
                $asLastAction[$nCount]['right'] = array('logged');

                if(isset($asAction['option']))
                  $asLastAction[$nCount]['option'] = $asAction['option'];

                $nCount++;
              }
            }
          }
        }
      }

      if($nCount > 0)
      {
        $sKey = uniqid();
        $pasMenuItem[$sKey] = array('name' => 'Contacts', 'link' => '', 'icon' => '/component/menu/resources/pictures/action_48.png',
          'target' => '', 'uid' => '', 'type' => '', 'action' => '', 'pk' => 0, 'right' => array('logged'),
          'menu_action' => $asLastAction);
      }

    }

    return $pasMenuItem;
  }



  private function _displayMenuNav($pnMenuNumber, $psPosition, $pbVertical = false)
  {
    $sUid = $this->_oPage->getUid();
    $bIsLogged = $this->_oLogin->isLogged();

    if(!assert('is_key($pnMenuNumber)'))
      return '';

    $sMenuName = 'menunav'.$pnMenuNumber;
    $asMenu = $this->_oSettings->getSettingValue($sMenuName);


    if(!is_array($asMenu) || empty($asMenu))
      return '';

    if(!assert('is_key($pnMenuNumber)'))
      return '';

    if(empty($sUid) || !CDependency::hasInterfaceByUid($sUid, 'has_publicContent'))
      $bPublic = false;
    else
      $bPublic = true;

    if(!$bIsLogged && !$bPublic)
      return '';



    //get an array fromn the conf file, and display the menu based on the values
    //array[component_ui/name][action][type][pk][] = array('label' => '', 'link option' => array() );

    $sLanguage = $this->_oPage->getLanguage();

    if(isset($asMenu[$sLanguage]))
      $asMenuArray = $asMenu[$sLanguage];
    else
      $asMenuArray = $asMenu[CONST_DEFAULT_LANGUAGE];

    if($pbVertical)
    {
      $asSetting = $this->_oSettings->getSettings('menuactionpos');
      if($asSetting['menuactionpos'] == 'merged')
      {
        //load actions and merge it with current menu
        $this->_addComponentActions($asMenuArray, $sLanguage);
      }
    }

    $sHTML = $this->_oDisplay->getListStart('',array('class' => 'menuNavList'));

      //add a first link to expand/reduce the menu
      if($pbVertical)
      {
        $this->_oPage->addJsFile($this->getResourcePath().'js/menu.js');

        $sPic = $this->_oDisplay->getPicture($this->getResourcePath().'pictures/toggle_menu_48.png');
        $sHTML.= $this->_oDisplay->getListItemStart();
        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'menuNavIcon menu_open_label'));
        $sHTML.= $this->_oDisplay->getLink($sPic, 'javascript:;', array('id' => 'toggleVertMenu', 'style' => 'width: 100%;', 'class' => 'mainMenuPic',
            'current_width' => '55', 'max_width' => '250', 'min_width' => '55', 'onclick' => 'toggleVerticalMenu(this);'));
        $sHTML.= $this->_oDisplay->getBlocEnd();
        $sHTML.= $this->_oDisplay->getListItemEnd();
      }

      if(!empty($asMenuArray))
      {
        foreach($asMenuArray as $asMenuItems)
        {
          if($this->_canAccessMenu($asMenuItems))
          {
            $sExtraClass = '';
            set_array($asMenuItems['onclick'], '');
            set_array($asMenuItems['target'], '');
            set_array($asMenuItems['child'], '');
            if(!isset($asMenuItems['menu_action']))
              $asMenuItems['menu_action'] = array();


            if(!empty($asMenuItems['link']))
            {
              if(isset($asMenuItems['embedLink']) && !empty($asMenuItems['embedLink']))
                $sLink = $this->_oPage->getUrlEmbed($asMenuItems['link']);
              else
                $sLink = $asMenuItems['link'];
            }
            else
            {
              //2013-07-31: changed by stephane (was displaying "javascript:;" if not uid
              $sLink = $this->_oPage->getUrl($asMenuItems['uid'].'', ''.$asMenuItems['action'], ''.$asMenuItems['type'], (int)$asMenuItems['pk']);
              if(isset($asMenuItems['loginpk']) && $asMenuItems['loginpk'])
                $sLink.= '&loginpk='.$this->_oLogin->getUserPk();
            }

            if(isset($asMenuItems['ajaxpopup']) && !empty($asMenuItems['ajaxpopup']))
            {
              $asPopupParam = $this->_getAjaxPopupParams($asMenuItems);
              set_array($asPopupParam['width'], 900);
              set_array($asPopupParam['height'], 700);

              $sURL = $this->_oPage->getAjaxUrl($asMenuItems['uid'], $asMenuItems['action'], $asMenuItems['type'], (int)$asMenuItems['pk']);
              if(isset($asMenuItems['loginpk']) && $asMenuItems['loginpk'])
                $sURL.= '&loginpk='.$this->_oLogin->getUserPk();

              $sAjax = $this->_oDisplay->getAjaxPopupJS($sURL, 'body', '', 0, 0, $asPopupParam);
              if(!empty($asMenuItems['icon']))
              {
                $sPicture = $this->_oDisplay->getPicture($asMenuItems['icon'],  $asMenuItems['name'], '', array('class' => 'mainMenuPic'));
                //$sItem = $this->_oDisplay->getLink($sPicture, 'javascript:;', array('onclick' => $sAjax.' '.$asMenuItems['onclick'], 'class' => 'mainMenuPic', 'target' => $asMenuItems['target']));
                //$sTextItem = $this->_oDisplay->getLink($asMenuItems['name'], 'javascript:;', array('onclick' => $sAjax.' '.$asMenuItems['onclick'], 'class' => 'mainMenuPic', 'target' => $asMenuItems['target']));
                $sExtraClass.= ' menuNavIcon ';
              }
              else
                $sPicture = '';

              if(!empty($asMenuItems['name']))
                $sExtraClass.= ' menuNavText ';

              $sItem = $this->_oDisplay->getLink($sPicture.$asMenuItems['name'], 'javascript:;', array('onclick' => $sAjax.' '.$asMenuItems['onclick'], 'class' => 'mainMenuPic', 'target' => $asMenuItems['target']));
              $sTextItem = $sItem;

            }
            else
            {
              if(!empty($asMenuItems['icon']))
              {
                $sExtraClass.= ' menuNavIcon ';

                if(substr($asMenuItems['icon'], 0, 1) == '/' || substr($asMenuItems['icon'], 0, 4) == 'http')
                  $sPic = $this->_oDisplay->getPicture($asMenuItems['icon'], $asMenuItems['name']);
                else
                  $sPic = $this->_oDisplay->getPicture($this->_oDisplay->getResourcePath().$asMenuItems['icon'], $asMenuItems['name']);

                if(!empty($asMenuItems['name']))
                {
                  $sPic.= ' '.$asMenuItems['name'];
                  $sExtraClass.= ' menuNavText ';
                }

                $sItem = $this->_oDisplay->getLink($sPic, $sLink, array('class' => 'mainMenuPic', 'onclick' => $asMenuItems['onclick'], 'target' => $asMenuItems['target'])).' ';
                $sTextItem = $this->_oDisplay->getLink($asMenuItems['name'].'&nbsp;', $sLink, array('class' => 'mainMenuPic', 'onclick' => $asMenuItems['onclick'], 'target' => $asMenuItems['target'])).' ';
              }
              else
              {
                $sExtraClass.= ' menuNavText ';
                $sItem = $this->_oDisplay->getLink($asMenuItems['name'], $sLink, array('class' => 'mainMenuPic', 'onclick' => $asMenuItems['onclick'], 'target' => $asMenuItems['target']));
                $sTextItem = $sItem;
              }

              if(isset($asMenuItems['legend']) && !empty($asMenuItems['legend']))
              {
                $sItem.= $this->_oDisplay->getCR();
                $sItem.= $this->_oDisplay->getSpanStart('', array('class' => 'menuNavLegend'));
                $sItem.= $this->_oDisplay->getLink($asMenuItems['legend'], $sLink, array('class' => 'mainMenuPic'));
                $sItem.= $this->_oDisplay->getSpanEnd();
              }
            }

            $asOption = array('class' => $sExtraClass);
            $bHasChilds = (is_array($asMenuItems['child']) && !empty($asMenuItems['child'])) || !empty($asMenuItems['menu_action']);

            if($pbVertical)
            {
              $asOption['class'].= ' menu_open_label';

              if($bHasChilds)
                $sItem.= '<div class="menu_item_label expendable" onclick="toggleSubmenu(this);">'.$asMenuItems['name'].'</div> ';
              else
                $sItem.= '<div class="menu_item_label">'.$sTextItem.'</div> ';
            }

            $sHTML.= $this->_oDisplay->getListItemStart();
            $sHTML.= $this->_oDisplay->getBlocStart('', $asOption);
            $sHTML.= $sItem;

            //Display submenu if the child is set
            if($bHasChilds)
            {
              if(empty($asMenuItems['child']))
                $asMenuItems['child'] = array();

              $sHTML.= $this->_displayMenuNavChild($asMenuItems['child'], (array)$asMenuItems['menu_action'], $pbVertical);
            }


            $sHTML.= $this->_oDisplay->getFloatHack();
            $sHTML.= $this->_oDisplay->getBlocEnd();
            $sHTML.= $this->_oDisplay->getListItemEnd();
          }
        }
      }

      if($pnMenuNumber==1)
      {
        $aoComponent = CDependency::getComponentsByInterface('custom_menu_item');
        foreach($aoComponent as $oComponent)
          $sHTML.= $oComponent->displayCustomMenuItem();
      }

      $sHTML.= $this->_oDisplay->getListItem('&nbsp;', '', array('class' => 'floathack'));
      $sHTML.= $this->_oDisplay->getListEnd();

    return $sHTML;
  }

  private function _displayMenuNavChild($pasChildMenu, $pasAction = array(), $pbVertical = false, $pasOption = array())
  {
    if(!assert('is_array($pasChildMenu) && is_array($pasAction)'))
      return '';

    if(empty($pasChildMenu) && empty($pasAction))
      return '';

    //dump($pasChildMenu);
    set_array($pasOption['class'], 'subMenu', ' subMenu');

    $sHTML = $this->_oDisplay->getListStart('', $pasOption);

    foreach($pasChildMenu as $asChildren)
    {
      $sHTML.= $this->_getChildItem($asChildren, $pbVertical);
    }

    if(!empty($pasAction))
    {
      if(!empty($pasChildMenu))
        $sHTML.= $this->_oDisplay->getListItem('<div>&nbsp;</div>', '', array('class' => 'menu_action_separator'));

      foreach($pasAction as $asChildren)
      {
        $sHTML.= $this->_getChildItem($asChildren, $pbVertical);
      }
    }

    $sHTML.= $this->_oDisplay->getListEnd();

    return $sHTML;
  }

  private function _getChildItem($asChildren, $pbVertical)
  {
    if(!$this->_canAccessMenu($asChildren))
      return '';

    if(!isset($asChildren['onclick']))
        $asChildren['onclick'] = '';

    if(!isset($asChildren['target']))
        $asChildren['target'] = '';

    if(!isset($asChildren['icon']) || empty($asChildren['icon']))
        $asChildren['icon'] = '';
    else
    {
      if(substr($asChildren['icon'], 0, 1) == '/' || substr($asChildren['icon'], 0, 4) == 'http')
        $asChildren['icon'] = $this->_oDisplay->getPicture($asChildren['icon']).' ';
      else
        $asChildren['icon'] = $this->_oDisplay->getPicture($this->_oDisplay->getResourcePath().$asChildren['icon']).' ';
    }

    if(!empty($asChildren['link']))
    {
      if(isset($asChildren['embedLink']) && !empty($asChildren['embedLink']))
        $sURL = $this->_oPage->getUrlEmbed($asChildren['link']);
      else
        $sURL = $asChildren['link'];
    }
    else
    {
      if(!empty($asChildren['uid']))
      {
        if(isset($asChildren['loginpk']) && !empty($asChildren['loginpk']))
          $sURL = $this->_oPage->getUrl($asChildren['uid'], $asChildren['action'], $asChildren['type'], (int)$asChildren['pk'], array('loginpk'=>(int)$this->_oLogin->getUserPk()));
        else
          $sURL = $this->_oPage->getUrl($asChildren['uid'], $asChildren['action'], $asChildren['type'], (int)$asChildren['pk']);
      }
      else
        $sURL = 'javascript:;';
     }


     $asOption = array('target'=>$asChildren['target'], 'onclick' => $asChildren['onclick']);

     if(isset($asChildren['option']) && !empty($asChildren['option']))
       $asOption = array_merge($asOption, $asChildren['option']);

      if(isset($asChildren['ajaxpopup']) && !empty($asChildren['ajaxpopup']))
      {
        $asPopupParam = $this->_getAjaxPopupParams($asChildren);
        set_array($asPopupParam['width'], 900);
        set_array($asPopupParam['height'], 700);

        $sURL = $this->_oPage->getAjaxUrl($asChildren['uid'], $asChildren['action'], $asChildren['type'], (int)$asChildren['pk']);
        if(isset($asChildren['loginpk']) && $asChildren['loginpk'])
          $sURL.= '&loginpk='.$this->_oLogin->getUserPk();

        $asOption['onclick'] = $this->_oDisplay->getAjaxPopupJS($sURL, 'body', '', 0, 0, $asPopupParam). ' '.$asOption['onclick'];
        $sURL = 'javascript:;';
      }

    if($pbVertical)
      $asOption['onclick'].= ' toggleVerticalMenu(null, true); ';

     return $this->_oDisplay->getListItem($this->_oDisplay->getLink($asChildren['icon'].$asChildren['name'], $sURL, $asOption));
  }

  private function _getAjaxPopupParams($pasParams)
  {
    $asPopupParams = array();
    foreach($pasParams as $sParam => $vValue)
    {
      if(substr($sParam, 0, 7) == 'popup__')
        $asPopupParams[substr($sParam,7)] = $vValue;
    }

    return $asPopupParams;
  }

  public function declareSettings()
  {
    $aOptions = array (
        'none' => 'Disable menu',
        'top' => 'Top menu',
        'left' => 'Left side menu',
        'right' => 'Right side menu',
        'bottom' => 'Bottom menu'
    );
    $aSettings[] = array(
        'fieldname' => 'menunav1',
        'fieldtype' => 'serialized64',
        'label' => 'Navigation menu 1',
        'description' => 'First navigation menu',
        'value' => '',
        'customformurl' => $this->_oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_EDIT, 'menunav', 1)
    );
    $aSettings[] = array(
        'fieldname' => 'menunav1pos',
        'fieldtype' => 'select',
        'options' => $aOptions,
        'label' => 'Navigation menu 1 position',
        'description' => 'Navigation menu 1 position',
        'value' => ''
    );
    $aSettings[] = array(
        'fieldname' => 'menunav2',
        'fieldtype' => 'serialized64',
        'label' => 'Navigation menu 2',
        'description' => 'Second navigation menu',
        'value' => ''
    );
    $aSettings[] = array(
        'fieldname' => 'menunav2pos',
        'fieldtype' => 'select',
        'options' => $aOptions,
        'label' => 'Navigation menu 2 position',
        'description' => 'Navigation menu 2 position',
        'value' => ''
    );
    $aSettings[] = array(
        'fieldname' => 'menunav3',
        'fieldtype' => 'serialized64',
        'label' => 'Navigation menu 3',
        'description' => 'Third navigation menu',
        'value' => ''
    );
    $aSettings[] = array(
        'fieldname' => 'menunav3pos',
        'fieldtype' => 'select',
        'options' => $aOptions,
        'label' => 'Navigation menu 3 position',
        'description' => 'Navigation menu 3 position',
        'value' => ''
    );

    $aOptions['merged'] = 'Merged into navigation menu';
    $aSettings[] = array(
        'fieldname' => 'menuactionpos',
        'fieldtype' => 'select',
        'options' => $aOptions,
        'label' => 'Action menu position',
        'description' => 'Action menu position',
        'value' => ''
    );

    return $aSettings;
  }

}