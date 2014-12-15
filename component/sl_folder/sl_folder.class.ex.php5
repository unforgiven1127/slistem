<?php

require_once('component/sl_folder/sl_folder.class.php5');

class CSl_FolderEx extends CSl_Folder
{
  private $_aRights;
  //protected $_aTypes;
  private $_userPk;

  public function __construct()
  {
    //before because we redefine attributes
    parent::__construct();

    $oLogin = CDependency::getCpLogin();
    if($oLogin->isLogged())
    {
      $this->_userPk = $oLogin->getUserPk();

      //WIll redefine to match Slistem data types
      $this->_aRights = array('edit', 'delete', 'add_item', 'remove_item', 'read');
      $this->_aTypes = array(
          'Candidates' => array('cp_uid' => '555-001', 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_CANDIDATE_TYPE_CANDI),
          'Companies' => array('cp_uid' => '555-001', 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_CANDIDATE_TYPE_COMP)
          );

      //dump('sl_folder ['.date('H:i:s').']--> __construct '); flush(); ob_flush();
      self::_loadFolderTree((bool)getValue('refresh_folder', 0), 1);
    }
  }


  public function getCustomContainerStart($psTabType = '', $psExtraClass = '')
  {
    return customComponentBlockStart($psTabType, $psExtraClass);
  }

  public function getCustomContainerEnd()
  {
    return customComponentBlockEnd();
  }

  //====================================================================
  //  public methods
  //====================================================================

  public function getHtml($pbCustomContent = false, $psSelected = '')
  {
    $this->_processUrl();

    if($pbCustomContent)
      return $this->_getMenuDisplay($psSelected);

    return parent::getHtml();
  }

  public function getAjax()
  {
    $this->_processUrl();
    $oPage = CDependency::getCpPage();

    switch ($this->csType)
    {
      case CONST_FOLDER_TYPE_FOLDER:

        switch ($this->csAction)
        {
          case CONST_ACTION_SEARCH:

            if(getValue('selector'))
              return parent::getAjax();

            return json_encode($this->_getFilteredFolderList());
            break;

          case CONST_ACTION_ADD:
            return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_getFolderForm())));
            break;

          case CONST_ACTION_REFRESH:
            return json_encode($oPage->getAjaxExtraContent($this->_refreshUserFolder()));
            break;

          case CONST_ACTION_DELETE:
            return json_encode($oPage->getAjaxExtraContent($this->_deleteUserFolder($this->cnPk)));
            break;


          case CONST_ACTION_SAVEEDIT:

            $asResult = $this->_saveFolder($this->cnPk);

            if(!empty($asResult['error']))
              return json_encode($asResult);

            //refresh tree
             self::_loadFolderTree(true, 1);

            return json_encode(array(
            'notice' => 'Folder updated.',
            'action' => ' goPopup.removeLastByType(\'layer\');
            var sTitle = $(\'#userFolderRow_'.$this->cnPk.' > a\').text();
            asTitle = sTitle.split(\'(\');
            sTitle = \''.getValue('label').' (\' + asTitle[1];

            $(\'#userFolderRow_'.$this->cnPk.' > a\').addClass(\'folder_edited\').text(sTitle);
            '));

            break;
        }
        break;

      case CONST_FOLDER_TYPE_ITEM:

        switch ($this->csAction)
        {
          case CONST_ACTION_ADD:
            return json_encode($oPage->getAjaxExtraContent($this->_addItemForm($this->cnPk)));
            break;

          case CONST_ACTION_SAVEADD:
            return json_encode($this->_addItemToFolder($this->cnPk));
            break;

          case CONST_ACTION_DELETE:
            return json_encode($this->_removeItemFromFolder($this->cnPk));
            break;
        }
        break;
    }
    return parent::getAjax();
  }



  //====================================================================
  //  Will contain only methods overloading generic features
  //====================================================================


  /**
   * Redifine !! Loads the Folder tree and store it in session
  */

  protected function _loadFolderTree($pbRefresh = false, $pnPreloadOption = 2)
  {
    if(!assert('is_integer($pnPreloadOption)'))
      return false;

    //dump('sl_folder ['.date('H:i:s').']--> _loadFolderTree '); flush(); ob_flush();

    //load the tree in session
    parent::_loadFolderTree($pbRefresh, $pnPreloadOption);

    //then load the list (based on the tree)
    // if(!isset($_SESSION['folder_list']) || $pbRefresh)
    {
      //dump('sl_folder ['.date('H:i:s').']--> no tree in session or forced refresh('.(int)$pbRefresh.')'); flush(); ob_flush();

       //dump('covert tree to list....');
       //dump($_SESSION['folder_tree']);

       $oLogin = CDependency::getCpLogin();
       $_SESSION['folder_list'] = $this->_getUserFolderList($oLogin->getuserPk());

       //dump('--! --! --!');
       //dump($_SESSION['folder_list']);
    }
    /*else
    {
      dump('sl_folder --> tree in session  nothing to do'); flush(); ob_flush();
    }*/

    return true;
  }




  private function _getMenuDisplay($psSelected = '')
  {
    /*
    $asFolder = $this-> _getFolderTree();
    dump($asFolder);
    dump($_SESSION['folder_list']);*/

    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/sl_folder.css');

    $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, '', $oLogin->getUserPk());
    $sHTML = '';

    /*$sHTML.= '
      <li class="section_manage">
          <a href="javascript:;" onclick="
          oConf = goPopup.getConfig();
          oConf.width = 1150;
          oConf.height = 650;
          goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');">
            <img src="/common/pictures/slistem/folder_manage_24.png" title="Create a new folder"/></a>

          <!-- <a href="" class="dba"><img src="/common/pictures/slistem/folder_add_24.png" title="New DBA request"/></a> -->
         <div class="floatHack"></div>
      </li>';*/


     $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_REFRESH, CONST_FOLDER_TYPE_FOLDER, 0);
     $sJsURL = $oPage->getAjaxUrl('sl_menu', CONST_ACTION_UPDATE, 'menu', 0, array('last_menu_clicked' => 'my_workspace'));

     if($psSelected == 'my_workspace')
       $sClass = '';
     else
       $sClass = ' hidden';


     $sHTML.= '
      <li class="menu_section section_workspace">
        <div class="menuActionMenuContainer" onclick="toggleMenu(this, \''.$sJsURL.'\');"><a href="javascript:;" >My workspace</a></div>
        <div id="userFolders" class="menuActionBloc menu_workspace menuFolderContainer'.$sClass.'" url="'.$sURL.'">';

      $sURL = $oPage->getAjaxUrl('sl_folder', CONST_ACTION_ADD, CONST_FOLDER_TYPE_FOLDER, 0);
      $sHTML.= '<div id="userFolderRow_0" class="userFolderRow userFolderNew" data-folderpk="-1" data-folder-type="candi" >
              <a href="javascript:;" onclick="goPopup.setLayerFromAjax(\'\', \''.$sURL.'\');">Add a new folder</a>
          </div>';

     $sHTML.= implode('', $_SESSION['folder_list']) .'
        </div>
        <div class="floatHack"></div>
      </li>';


     if($psSelected == 'shared_folder')
       $sClass = '';
     else
       $sClass = ' hidden';

     $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_FOLDER_TYPE_FOLDER, 0);
     $sJsURL = $oPage->getAjaxUrl('sl_menu', CONST_ACTION_UPDATE, 'menu', 0, array('last_menu_clicked' => 'shared_folder'));

     $sHTML.= '
      <li class="menu_section">
         <div class="menuActionMenuContainer" onclick="toggleMenu(this, \''.$sJsURL.'\');"><a href="javascript:;">Shared folders</a></div>
         <div class="menuActionBloc menu_folder menuFolderContainer'.$sClass.'">

          <div class="shared_folder_search">
          <form id="searchFolder" action="javascript:;" style="padding: 0; margin: 0 auto;">

            <div class="shared_folder_search_type">

              <input id="searchFolderCons" checked="checked" name="folder_search_type" type="radio" value="consultant" onchange="$(\'.shared_folder_search_input select, .shared_folder_search_input input\').toggle(); " />&nbsp;<label for="searchFolderCons">Cons</label>
              &nbsp;&nbsp;
              <input id="searchFolderName" name="folder_search_type" type="radio" value="folder" onchange="$(\'.shared_folder_search_input select, .shared_folder_search_input input\').toggle(); " />&nbsp;<label for="searchFolderName">Folder name</label>
            </div>

            <div class="shared_folder_search_input">
              <select name="loginfk">
                <option value=""> - consultant - </option>';

            $asSharedFolder = $this->_getSharedFolderOwners();
            foreach($asSharedFolder as $nLoginPk => $asData)
            {
              $sHTML.= '<option value="'.$nLoginPk.'"> '.$oLogin->getUserNameFromData($asData).' </option>';
            }

            $sHTML.= '</select>
              <input type="text" name="folder_name" class="hidden"/>

              <div style="float: right;">
                <img src="'.self::getResourcePath().'pictures/shared_folder_search.png" onclick="AjaxRequest(\''.$sURL.'\', \'\', \'searchFolder\', \'sharedFolderList\', \'\', \'\', \'$(\\\'.menu_folder\\\').mCustomScrollbar(\\\'update\\\'); \');"  />
              </div>

            </div>
          </form>

          <div id="sharedFolderList">';

     $asLastFolderSearch = $this->_getFilteredFolderList(true);

     if(!empty($asLastFolderSearch))
       $sHTML.= $asLastFolderSearch['data'];

        $sHTML.= '
          </div>

          </div>

        </div>
         <div class="floatHack"></div>
      </li>';

    return $sHTML;
  }


  /*
   * TO BE CHANGED:
   * need a different function for public folders because we need to order the list,
   *   and need to do it in SQL alphabetically, by users, by size (nb element)...
   */

  private function _getUserFolderList($pnUserPk, $pasTree = null, $pnLevel = 0, $psParentUid = '')
  {
    //load the full folder tree to start making the list
    // + add a first new folder row
    if($pasTree === null)
      $pasTree = $_SESSION['folder_tree'];

    if(empty($pasTree))
      return '';

    //dump('_getUserFolderList()');
    //dump($pasTree);
    //dump($pnUserPk);
    //dump($pnLevel);


    $asList = array();
    $oPage = CDependency::getCpPage();
    $sURL1 = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_LIST, '');

    if($pnLevel > 1)
      $sClass = 'subfolder_list';
    else
      $sClass = '';

    if(empty($psParentUid))
      $psParentUid = '0-0-0';

    foreach($pasTree as $asFolder)
    {

      $bSubfolders = !empty($asFolder['content']['subfolders']);
      $sUid = $psParentUid;

      if($pnUserPk == @$asFolder['ownerloginfk'])
      {
        $sURL = $oPage->getAjaxUrl('sl_folder', CONST_ACTION_EDIT, CONST_FOLDER_TYPE_FOLDER, (int)$asFolder['folderpk']);
        if(empty($asFolder['cp_type']))
          $asFolder['cp_type'] = 'candi';


        if(!$bSubfolders)
        {
          $sRow = '';
        }
        else
        {
          $sRow = '<a href="javascript:;" class="expand_subfolder" onclick="toggleSubfolder('.$asFolder['folderpk'].');" >&nbsp;</a>';
        }


        if($asFolder['parentfolderfk'] == 0)
          $pnLevel = 1;

        $sUid.= '_'.$pnLevel.'-'.$asFolder['label'].'-'.$asFolder['folderpk'];

        //data-uid="'.$sUid.'"
        $asList[$sUid] = '<div id="userFolderRow_'.$asFolder['folderpk'].'" class="userFolderRow fol_lvl_'.$pnLevel.' '.$sClass.'"
          data-folderpk="'.$asFolder['folderpk'].'" data-folder-type="'.$asFolder['cp_type'].'" data-folder-parent="'.$asFolder['parentfolderfk'].'" >
            '.$sRow.'<a href="javascript:;" onclick="'.getAjaxCall($sURL1.'&ppt='.$asFolder['cp_type'].'&data_type='.$asFolder['cp_type'].'&folderpk='.$asFolder['folderpk'], '', 'fold', $asFolder['label']).'">
              '.$asFolder['label'].' (<span class="folderNumber">'.$asFolder['nb_item'].'</span>)</a>

        <div class="userFolderAction" onclick="goPopup.setLayerFromAjax(\'\', \''.$sURL.'\');">&nbsp;</div>
        </div>';
      }

      if($bSubfolders)
      {
        //dump('got subfolders');
        $asList = array_merge($asList, $this->_getUserFolderList($pnUserPk, $asFolder['content']['subfolders'], ++$pnLevel, $sUid));
      }
    }

    ksort($asList);
    return $asList;
  }


  private function _getFilteredFolderList($pbReloadLast = false)
  {
    if(!assert('is_bool($pbReloadLast)'))
      return array('error' => 'Wrong parameters.');


    $oLogin = CDependency::getCpLogin();
    $nUserPk = $oLogin->getuserPk();

    //reload last search done and save in teh user activity table
    if($pbReloadLast)
    {
      $asActivity = $oLogin->getUserActivity($nUserPk, $this->csUid, CONST_ACTION_SEARCH, CONST_FOLDER_TYPE_FOLDER, 0, 1);

      if(!empty($asActivity) && !empty($asActivity[0]['data']))
        return array('data' => $asActivity[0]['data']);

      return array();
    }

    $sSearchType = getValue('folder_search_type');

    if(empty($sSearchType) || !in_array($sSearchType, array('folder', 'consultant')))
      return array('error' => 'Unknown folder type.');

    $nLoginFk = (int)getValue('loginfk', 0);
    $sSearchString = trim(getValue('folder_name'));

    if($sSearchType == 'consultant')
    {
      if($sSearchType == 'consultant' && empty($nLoginFk))
        return array('error' => 'You have to choose a consultant in the list.');

      $sSearchString = '';
      $sSearchLabel = 'Search for '.$oLogin->getUserLink($nLoginFk).'\'s folders';
    }

    if($sSearchType == 'folder')
    {
      if(strlen($sSearchString) < 2)
        return array('error' => 'Folder name should contain at least 2 characters.');

      $sSearchLabel = 'Search folders named "'.$sSearchString.'"';
      $nLoginFk = 0;
    }

    $oLogin = CDependency::getCpLogin();
    $asFolders = $this->_getModel()->searchFolders($oLogin->getUserPk(), $nLoginFk, $sSearchString, true, false, true);

    if(empty($asFolders))
      return array('data' => 'No folder found.<br /><br /><br />');


    $oPage = CDependency::getCpPage();

    $sURL = $oPage->getAjaxUrl('sl_candidate', CONST_ACTION_LIST, '');

    $asList = array();
    foreach($asFolders as $asFolder)
    {
      $sVisibility = $asFolder['right_list'];
      if(empty($sVisibility))
        $sVisibility = 'public folder';

      if(empty($asFolder['cp_type']))
        $asFolder['cp_type'] = 'candi';

      $asList[$asFolder['firstname'].' '.$asFolder['lastname']][] = '
        <li data-folder-type="'.$asFolder['cp_type'].'" data-folderpk="'.$asFolder['folderpk'].'" class="userFolderRow" id="userFolderRow_'.$asFolder['folderpk'].'">
          <a href="javascript:;" onclick="
          '. getAjaxCall($sURL.'&ppt='.$asFolder['cp_type'].'&data_type='.$asFolder['cp_type'].'&folderpk='.$asFolder['folderpk'], '', 'fold', $asFolder['label']) .'">'.$asFolder['label'].'</a>
          (<span class="folderItem">'.$asFolder['nb_items'].'</span>)
        </li>';
    }

    $sList = '<ul>';

    if($sSearchType == 'folder')
    {
      foreach($asList as $sLoginId => $asFolder)
      {
        $sList.= '<li class="consultant">'.$sLoginId.'</li>'.implode('', $asFolder);
      }
    }
    else
    {
      //get first (and only) key of the array
      $asList = current($asList);
      $sList.= implode('', $asList);
    }

    $sList.= '</ul>';
    $sURL = $oPage->getAjaxUrl('sl_folder', CONST_ACTION_SAVEADD, CONST_FOLDER_TYPE_ITEM, 0);
    $sList.='<script> initDrop(\''.$sURL.'\'); </script>';
    $oLogin->logUserAction($nUserPk, $this->csUid, CONST_ACTION_SEARCH, CONST_FOLDER_TYPE_FOLDER, 0, array('text' => $sSearchLabel, 'data' => $sList));

    return array('data' => $sList);
  }


  private function _refreshUserFolder()
  {
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();

    $sURL = $oPage->getAjaxUrl('sl_folder', CONST_ACTION_ADD, CONST_FOLDER_TYPE_FOLDER, 0);
    $sHTML = '<div id="userFolderRow_0" class="userFolderRow userFolderNew" data-folderpk="-1" data-folder-type="candi" >
              <a href="javascript:;" onclick="goPopup.setLayerFromAjax(\'\', \''.$sURL.'\');">Add a new folder</a>
          </div>';
    $sHTML.= implode('', $this->_getUserFolderList($oLogin->getuserPk()));

    $sURL = $oPage->getAjaxUrl('sl_folder', CONST_ACTION_SAVEADD, CONST_FOLDER_TYPE_ITEM, 0);
    $sHTML.='<script> initDrop(\''.$sURL.'\'); </script>';

    return array('data' => $sHTML, 'action' => '$(\'.menu_workspace\').mCustomScrollbar(\'destroy\'); $(\'.menu_workspace\').mCustomScrollbar();');
  }


  private function _getFolderForm()
  {
    $sIds = getValue('ids');
    $sSearchId = getValue('searchId');
    //$sType = getValue('item_type');
    $asItem = array();
    $sHTML = '';


    if(!empty($sSearchId))
    {
      //$sHTML.= 'replay the search and fetch item ids';
      $sQuery = $_SESSION['555-001']['query'][$sSearchId];
      if(empty($sQuery))
        return 'Can not find the candidates.';

      $sQuery = preg_replace('/LIMIT [0-9]{1,6}[0-9, ]{0,6}/i', '', $sQuery);

      $oDbResult = $this->_getModel()->executeQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return 'No candidates found.';

      $asItem = array();
      while($bRead)
      {
        $asItem[(int)$oDbResult->getFieldValue('sl_candidatepk')] = $oDbResult->getFieldValue('firstname').' '.$oDbResult->getFieldValue('lastname');
        $bRead = $oDbResult->readNext();
      }
    }
    else
    {
      $asItems = explode(',', $sIds);
      //$sHTML.= 'create folder with [ '.$sType.' / '.$sIds.']';
      $asItem = array();
      foreach($asItems as $nId)
      {
        $asItem[$nId] = 'candidate #'.$nId;
      }
    }

    $_SESSION['folder_save_action'] = array('notice' => 'Folder saved successfully', 'action' => ' goPopup.removeLastByType(\'layer\'); reloadFolders(); clearSelection();');
    //$sHTML.= $this->_formFolder($asItem, array('force_item_type' => true));
    $sHTML.= $this->_formFolder($asItem);

    return $sHTML;
  }

  private function _addItemForm($pnFolderPk)
  {
    $sIds = getValue('ids');
    $sSearchId = getValue('searchId');
    $sType = getValue('item_type');

    $oResult = $this->_getModel()->getFolders($this->_userPk, 'write');
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return 'No folder available to add candidates to.';

    $asOption = array();
    while($bRead)
    {
      if($oResult->getFieldValue('ownerloginfk') == $this->_userPk)
        $asOption['mine'][]= '<option value="'.$oResult->getFieldValue('folderpk').'">'.$oResult->getFieldValue('label').'</option>';
      else
        $asOption['others'][]= '<option value="'.$oResult->getFieldValue('folderpk').'">'.$oResult->getFieldValue('label').'</option>';

      $bRead = $oResult->readNext();
    }

    $sHTML = '';
    if(!empty($sSearchId))
    {
      $sHTML.= 'replay the search and fetch item ids';
      $sQuery = $_SESSION['555-001']['query'][$sSearchId];
      if(empty($sQuery))
        return 'Can not find the candidates.';

      $sQuery = preg_replace('/LIMIT [0-9]{1,6}[0-9, ]{0,6}/i', '', $sQuery);

      $oDbResult = $this->_getModel()->executeQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return 'No candidates found.';

      $asItems = array();
      while($bRead)
      {
        $asItems[(int)$oDbResult->getFieldValue('sl_candidatepk')] = (int)$oDbResult->getFieldValue('sl_candidatepk');
        $bRead = $oDbResult->readNext();
      }
    }
    else
    {
      $asItems = explode(',', $sIds);
      //$sHTML.= 'add to a folder with [ '.$sType.' / '.$sIds.']';

    }

    $oHTML = CDependency::getCpHtml();
    $sURL = CDependency::getCpPage()->getAjaxUrl('sl_folder', CONST_ACTION_SAVEADD, CONST_FOLDER_TYPE_ITEM);
    $sHTML.= $oHTML->getTitle('ADD ITEM', 'h3', true);

    $sHTML.= '<br /><br />Add those '.count($asItems).' candidates to the folder: ';


    $oForm = $oHTML->initForm('slfolderItemForm');
    $oForm->setFormParams('slfolderItemForm', true, array('action' => $sURL, 'submitLabel'=>'Save into folder', 'noCancelButton' => 'noCancelButton'));

    $oForm->addField('input', 'item_ids', array('type' => 'hidden', 'value' => implode(',', $asItems)));
    $oForm->addField('input', 'item_type', array('type' => 'hidden', 'value' => $sType));
    $oForm->addField('input', 'remove_layer', array('type' => 'hidden', 'value' => 1));


    $oForm->addField('select', 'folderpk', array('label' => '&nbsp;'));

    $sOption = '<optgroup label="My folders">'.implode('', $asOption['mine']).'</optgroup >';
    if(isset($asOption['others']))
      $sOption.= '<optgroup label="Other\'s shared folders">'.implode('', $asOption['others']).'</optgroup >';

    $oForm->addOptionHtml('folderpk', $sOption);
    return array('data' => $sHTML . $oForm->getDisplay());
  }


  private function _addItemToFolder()
  {
    $nFolderPk = (int)getValue('folderpk', 0);
    if(!assert('is_key($nFolderPk) || $nFolderPk == -1'))
      return array('error' => 'Item could not be added. Wrong folder given.');

    $asType = explode(',', getValue('item_type'));
    $anItemFk = explode(',', getValue('item_ids'));
    if(empty($asType) || empty($anItemFk))
      return array('error' => 'Missign parameter.');

    $bAddFolder = false;
    if($nFolderPk == -1)
    {
      $bAddFolder = true;

      $nHRank = $this->_getModel()->getHighestRank(0, 'folder');
      $asFolder = array('parentfolderpk' => 0, 'label' => '[new folder]  '.date('M-d H:i'), 'private' => 0, 'rank' => ($nHRank+1), 'ownerloginfk' => $this->_userPk, 'cp_type' => $asType[0]);
      $nFolderPk = $this->_getModel()->add($asFolder, 'folder');

      if(empty($nFolderPk))
        return array('error' => 'Could not create the new folder.');

      $this->_getModel()->add(array('folderfk' => $nFolderPk, 'cp_uid' => '555-001', 'cp_action' => 'ppav', 'cp_type' => 'candi'), 'folder_link');
    }
    else
    {
      $oDbResult = $this->_getModel()->getFolder($nFolderPk);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
         return array('error' => 'Could not find the folder. It may have been deleted.');

      $asFolder = $oDbResult->getData();
    }

    $asItem = array();
    $bGlobalItemType = true;
    if(count($asType) == 1)
    {
      if($asFolder['cp_type'] != $asType[0])
        return array('error' => 'Item type doesn\'t match the folder one. [folder: '.$asFolder['cp_type'].' / selected items: '.$asType[0].']');
    }
    else
      $bGlobalItemType = false;


    foreach($anItemFk as $nKey => $nItemPk)
    {
      if(!$bGlobalItemType && $asFolder['cp_type'] != $asType[$nKey])
        return array('error' => 'Item type doesn\'t match the folder one. ['.$asFolder['cp_type'].' / '.$asType[$nKey].']');

      $asItem[(int)$nItemPk] = 'candi #'.$nItemPk;
    }

    $asResult = $this->_addToFolder($nFolderPk, $asItem, 'true;');
    if(isset($asResult['error']) || $asResult['nb_added'] == 0)
      return $asResult;

    if($bAddFolder)
    {
      $oPage = CDependency::getCpPage();
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_REFRESH, CONST_FOLDER_TYPE_FOLDER);

      $asResult['action'].= 'AjaxRequest(\''.$sURL.'\', \'\', \'\', \'.menu_workspace\');';
      return $asResult;
    }

    // Update item number and display a notice
    $sSelector = '#menuaction #userFolderRow_'.$nFolderPk;
    $sAction = ' var oSpan = $("'.$sSelector.' span");
      var nValue = parseInt(oSpan.text(), 10);
      oSpan.text(nValue+'.$asResult['nb_added'].').addClass("folderEdited");  ';

    $asResult['action'].= $sAction;

    if(getValue('remove_layer'))
      $asResult['action'].= ';  goPopup.removeLastByType(\'layer\');  ';


    self::_loadFolderTree(true, 1);

    $asResult['delay'] = 1250;
    return $asResult;
  }


  private function _getSharedFolderOwners($pbIncludeUser = false)
  {

    $oDbResult = $this->_getModel()->getFolderOwners($this->_userPk, 'read', array(), $pbIncludeUser);

    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asOwner = array();
    while($bRead)
    {
      $asOwner[(int)$oDbResult->getFieldValue('loginpk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asOwner;
  }

  public function getFolderItem($pnFolderPk, $pbOnlyPk = false)
  {
    if(!assert('is_key($pnFolderPk) && is_bool($pbOnlyPk)'))
      return array();

    if($pbOnlyPk)
      $oDbResult = $this->_getModel()->getByWhere('folder_item', 'parentfolderfk = '.$pnFolderPk, '*', 'itemfk DESC');
    else
      $oDbResult = $this->_getModel()->getByWhere('folder_item', 'parentfolderfk = '.$pnFolderPk, 'itemfk', 'itemfk DESC');


    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asItem = array();
    while($bRead)
    {
      if($pbOnlyPk)
        $asItem[(int)$oDbResult->getFieldValue('itemfk')] = (int)$oDbResult->getFieldValue('itemfk');
      else
        $asItem[(int)$oDbResult->getFieldValue('itemfk')] = $oDbResult->getData();

      $bRead = $oDbResult->readNext();
    }

    return $asItem;
  }


  private function _removeItemFromFolder()
  {
    $nFolderPk = (int)getValue('folderpk', 0);
    if(!assert('is_key($nFolderPk) || $nFolderPk == -1'))
      return array('error' => 'Could not find the folder.');

    //$sType = explode(',', getValue('item_type'));
    $sSearchId = getValue('searchId');
    $sIds = getValue('ids');

    $oDbResult = $this->_getModel()->getByPk($nFolderPk, 'folder');
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
     return array('error' => 'Could not find the folder.');


    if(!empty($sSearchId))
    {
      $sQuery = $_SESSION['555-001']['query'][$sSearchId];
      if(empty($sQuery))
        return array('error' => 'Can not find the candidates.');

      $sQuery = preg_replace('/LIMIT [0-9]{1,6}[0-9, ]{0,6}/i', '', $sQuery);

      $oDbResult = $this->_getModel()->executeQuery($sQuery);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array('error' => 'No candidate found.');

      $asItem = array();
      while($bRead)
      {
        $asItem[] = (int)$oDbResult->getFieldValue('sl_candidatepk');
        $bRead = $oDbResult->readNext();
      }

      $sIds = implode(',', $asItem);
    }
    else
    {
      if(!assert('is_listOfInt($sIds)'))
        return array('error' => 'Invalid candidate selection.');
    }

    $oDeleted = $this->_getModel()->deleteByWhere('folder_item', 'parentfolderfk = '.$nFolderPk.' AND itemfk IN ('.$sIds.')');
    if(!$oDeleted)
        return array('error' => 'Invalid candidate selection.');

    $nRemoved = $oDeleted->getFieldValue('affected_rows');
    $asResult = array('notice' => $nRemoved.' candidates removed from folder', 'action' => '
      $(\'.tplListContainer:visible ul li > div input[type=checkbox]:checked\').each(function()
      {
        $(this).closest(\'li\').hide(function(){ $(this).remove(); });
      });
      clearSelection();');


    // Update item number and display a notice
    $sSelector = '#menuaction #userFolderRow_'.$nFolderPk;
    $sAction = ' var oSpan = $("'.$sSelector.' span");
      var nValue = parseInt(oSpan.text(), 10) - '.$nRemoved.';
      if(nValue < 0)
        nValue = 0;
      oSpan.text(nValue).addClass("folderEdited");  ';


    $asResult['action'].= $sAction;
    $asResult['delay'] = 1250;
    return $asResult;
  }

  private function _deleteUserFolder($pnFolderPk)
  {
    if(!assert('is_key($pnFolderPk)'))
      return array('array' => 'An error occured.');

    $asDeleted = $this->_removeFolder($pnFolderPk);

    if(isset($asDeleted['error']))
      return $asDeleted['error'];

    return array('notice' => 'folder deleted', 'action' => ' $(\'li#subfolder_'.$pnFolderPk.'\').remove(); ');
  }
}
