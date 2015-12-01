<?php

require_once('component/folder/folder.class.php5');

class CFolderEx extends CFolder
{
  private $cnPreloadOption = 1;
  private $cnTreeStatus = 0;  //0 empty, 1 folders, 2 All folders + items
  private $_aRights;
  protected $_aTypes;
  private $_userPk;
  private $casAfterSavingAction = array();

  public function __construct($pnPreloadOption = null)
  {
    $oLogin = CDependency::getCpLogin();
    $this->_userPk = $oLogin->getUserPk();

    // Don't use "-" in right names. It is used as a separator when saving in database
    $this->_aRights = array('edit', 'delete', 'add_item', 'remove_item', 'read');
    $this->_aTypes = array(
        'Connexions' => array('cp_uid' => '777-249', 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_AB_TYPE_CONTACT),
        'Companies' => array('cp_uid' => '777-249', 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_AB_TYPE_COMPANY),
        'Documents' => array('cp_uid' => '999-111', 'cp_action' => CONST_ACTION_VIEW, 'cp_type' => CONST_SS_TYPE_DOCUMENT)
        );

    if($pnPreloadOption === null)
      $this->cnPreloadOption = CONST_FOLDER_LOADING_MODE;
    else
      $this->cnPreloadOption = $pnPreloadOption;


    if(isset($_SESSION['folder_save_action']))
      $this->casAfterSavingAction = $_SESSION['folder_save_action'];
  }

  //====================================================================
  // Component interface methods
  //====================================================================

  public function getHtml()
  {
    return '';
  }

  public function getAjax()
  {
    $this->_processUrl();

    if($this->csType == CONST_FOLDER_TYPE_FOLDER)
    {
      switch ($this->csAction)
      {
        case CONST_ACTION_LIST :
          $oPage = CDependency::getCpPage();
          return json_encode($oPage->getAjaxExtraContent(array('data' => $this->manageFolders('admin'))));
          break;

        case CONST_ACTION_EDIT:
        case CONST_ACTION_ADD:
          $oPage = CDependency::getCpPage();
          return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_formFolder())));
          break;

        case CONST_ACTION_SAVEADD:
        case CONST_ACTION_SAVEEDIT:
          return json_encode($this->_saveFolder($this->cnPk));
          break;

        case CONST_ACTION_DELETE:
          return json_encode($this->_removeFolder($this->cnPk));
          break;

        case CONST_ACTION_SEARCH:
          return json_encode($this->_folderSelector($this->cnPk));
          break;
      }
    }
    elseif($this->csType == CONST_FOLDER_TYPE_ITEM)
    {
      switch ($this->csAction)
      {
        case CONST_ACTION_SAVEADD:
          return json_encode($this->_addToFolder($this->cnPk));
          break;

        case CONST_ACTION_DELETE:
          return json_encode($this->_removeItem($this->cnPk));
          break;
      }
    }
  }

  //====================================================================
  // Public methods
  //====================================================================

  public function getSubFolders($pnParentFolderPk)
  {
    if(!assert('is_numeric($pnParentFolderPk)'))
      return new CDbResult();

    $oSubFolders = $this->_getModel()->getFoldersByParentFk($pnParentFolderPk, $this->_userPk);
    $bRead = $oSubFolders->readFirst();

    if(!$bRead)
      return new CDbResult();

    return $oSubFolders;
  }

  public function getRootFolderPk($paCpValues)
  {
    if(!is_cpValues($paCpValues))
      return 0;

    return $this->_getModel()->getRootFolderPk($paCpValues);
  }

  public function displayFolders($psType = '')
  {
    if(!assert('is_string($psType)'))
      return '';

    $aFolders = $this->getFolderTree();

    $sHTML = '';
    $sHTML.= $this->_displayFolder($aFolders[0], $psType);

    return $sHTML;
  }

  public function displayCustomMenuItem()
  {
    $oHTML = CDependency::getCpHtml();
    $sHTML = '';


    $folder_tree = self::_loadFolderTree();

    $sPic = $oHTML->getPicture($this->getResourcePath().'img/folder_32.png', 'Browse folder');

    if(!empty($folder_tree[0]['content']['subfolders']))
    {
      $sHTML = $oHTML->getListItemStart('folderslink');
        $sHTML.= $oHTML->getBlocStart();
          $sHTML.= $oHTML->getLink($sPic, 'javascript;', array('id' => 'showfolder-menu', 'onclick' => '$(\'#folderlist-menu\').toggle(); return false;'));
          $sHTML.= $oHTML->getListStart('folderlist-menu');
          $sHTML.= $this->_displayFolder($folder_tree[0]);
          $sHTML.= $oHTML->getListEnd();
        $sHTML.= $oHTML->getBlocEnd();
      $sHTML.= $oHTML->getListItemEnd();
    }

    return $sHTML;
  }

  public function getUserAccountTabData($pnLoginPk)
  {
    $oPage = CDependency::getCpPage();
    $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_LIST, '', $pnLoginPk);
    return array('title' => 'Folder management', 'label' => 'folders', 'url' => $sURL);
  }

  // Moves an item to a new folder
  // Checks if the item is saved somewhere. If yes, delete the row.
  // Then insert a new rows.

  public function moveItemToFolder($pnItemPk, $pnFolderFk, $psType, $psLabel)
  {
    if(!assert('is_key($pnItemPk) && is_key($pnFolderFk)'))
      return false;

    if(!assert('isset($this->_aTypes[$psType])'))
      return false;

    if(!assert('!empty($psLabel) && is_string($psLabel)'))
      return false;

    $oExisting = $this->_getModel()->getItemFromType($pnItemPk, $this->_aTypes[$psType]);
    $bRead = $oExisting->readFirst();
    if($bRead)
      $this->_getModel()->deleteByPk((int)$oExisting->getFieldValue('folder_itempk'), 'folder_item');

    $nRank = $this->_getModel()->getHighestRank($pnFolderFk, 'folder_item');
    $nRank++;

    $nPk = $this->_getModel()->add(array('itemfk' => $pnItemPk, 'label' => $psLabel, 'rank' => $nRank, 'parentfolderfk' => $pnFolderFk), 'folder_item');

    return is_key($nPk);
  }


  // Deletes an item
  public function deleteItem($pnItemFk, $paCpValues)
  {
    if(!assert('is_key($pnItemFk)'))
      return false;

    if(!assert('is_cpValues($paCpValues)'))
      return false;

    $oItem = $this->_getModel()->getItemFromType($pnItemFk, $paCpValues);
    $bRead = $oItem->readFirst();

    if(!$bRead)
      return false;
    else
    {
      $this->_getModel()->deleteByPk((int)$oItem->getFieldValue('folder_itempk'), 'folder_item');
      return true;
    }
  }

  // Returns the folderpk of an item
  public function getFolderFromItemFk($pnItemFk, $paCpValues)
  {
    if(!assert('is_key($pnItemFk)'))
      return 0;

    if(!assert('is_cpValues($paCpValues)'))
      return 0;

    $oItem = $this->_getModel()->getItemFromType($pnItemFk, $paCpValues);
    $bRead = $oItem->readFirst();
    if(!$bRead)
      return 0;

    return $oItem->getData();
  }

  public function displayAddToFolderLink()
  {

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $aCpValues = array(
            CONST_CP_UID => $oPage->getUid(),
            CONST_CP_ACTION => $oPage->getAction(),
            CONST_CP_TYPE => $oPage->getType(),
            CONST_CP_PK => (int)$oPage->getPk()
          );

    $sHTML = '';

    $oFolders = $this->_getModel()->getFoldersByLink($aCpValues, $this->_userPk);
    $bRead = $oFolders->readFirst();

    if($bRead)
    {
      $oPage->addCssFile($this->getResourcePath().'css/addtofolder.css');
      $oPage->addJsFile($this->getResourcePath().'js/addtofolder.js');

      $sPic = $oHTML->getPicture($this->getResourcePath().'img/addtofolder_128.png', 'Add to folder', '', array('width' => '32px'));
      $sHTML .= $oHTML->getLink($sPic, 'javascript;', array('id' => 'showfolders'));

      $sHTML .= $oHTML->getListStart('folderslist');

      $aFoldersItemAddto = $aFoldersItemIn = array();
      $sItemLabel = $oPage->getPageTitle();
      if(empty($sItemLabel))
        $sItemLabel = 'Item';

      while($bRead)
      {
        if(!$this->_getModel()->itemInFolder((int)$oFolders->getFieldValue('folderpk'), $aCpValues[CONST_CP_PK]))
        {
          $sPic = $oHTML->getPicture($this->getResourcePath().'img/folder_32.png', 'Add to folder '.$oFolders->getFieldValue('label'), '', array('width' => '16px', 'align' => 'left'));
          $sUrl = $oPage->getAjaxUrl('folder', CONST_ACTION_SAVEADD, CONST_FOLDER_TYPE_ITEM, $oFolders->getFieldValue('folderpk'), array('itemfk' => $aCpValues[CONST_CP_PK], 'itemlabel' => $sItemLabel));
          $sLink = $oHTML->getLink($sPic.' '.$oFolders->getFieldValue('label'), $sUrl);
          $aFoldersItemAddto[]=$sLink;
        }
        else
        {
          $sPic = $oHTML->getPicture($this->getResourcePath().'img/folder_32.png', 'In folder '.$oFolders->getFieldValue('label'), '', array('width' => '16px', 'align' => 'left'));
          $aFoldersItemIn[]=$sPic.' '.$oFolders->getFieldValue('label');
        }

        $bRead = $oFolders->readNext();
      }

      if(!empty($aFoldersItemIn))
      {
        $sHTML .= $oHTML->getListItemStart();
        $sHTML .= 'This item is saved in folder';
        $sHTML .= $oHTML->getListItemEnd();

        foreach($aFoldersItemIn as $sFolder)
        {
          $sHTML .= $oHTML->getListItemStart();
          $sHTML .= $sFolder;
          $sHTML .= $oHTML->getListItemEnd();
        }
      }

      if(!empty($aFoldersItemAddto))
      {
        $sHTML .= $oHTML->getListItemStart();
        $sHTML .= 'Add this item to folder';
        $sHTML .= $oHTML->getListItemEnd();

        foreach($aFoldersItemAddto as $sFolder)
        {
          $sHTML .= $oHTML->getListItemStart();
          $sHTML .= $sFolder;
          $sHTML .= $oHTML->getListItemEnd();
        }
      }

      $sHTML .= $oHTML->getListEnd();
    }

    return $sHTML;
  }

  protected function _formFolder($pasItems = array(), $pasDisplayParam = array())
  {
    if(!assert('is_array($pasItems)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oLogin = CDependency::getCpLogin();

    $oForm = $oHTML->initForm('folderForm');

    $asUserRights = array();

    if($this->cnPk==0)
    {
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD);
      $sTitle = "Add a new folder";
      $oResult = new CDbResult;
    }
    else
    {
      $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, '', $this->cnPk);
      $sTitle = "Edit folder";
      $oResult = $this->_getModel()->getFolder($this->cnPk);
      $asUserRights = $this->_getModel()->getUserRightsOnFolder($this->cnPk);
    }

    $oForm->setFormParams('folderForm', true, array('action' => $sURL, 'submitLabel'=>'Save folder', 'noCancelButton' => 'noCancelButton'));

    //if elements are passed in parameter, we need to add those after the creation
    if(!empty($pasItems))
      $oForm->addField('input', 'items', array('type' => 'hidden', 'value'=> base64_encode(serialize($pasItems))));

    //start building the form
    $oForm->addField('misc', 'title', array('type' => 'text', 'text'=> $oHTML->getTitle($sTitle)));

    $oForm->addField('input', 'label', array('type' => 'text', 'label'=>'Label', 'value' => $oResult->getFieldValue('label')));
    $oForm->setFieldControl('label', array('jsFieldNotEmpty' => '', 'jsFieldMinSize' => 5, 'jsFieldMaxSize' => 20));

    $oForm->addField('select', 'type', array('label' => 'Folder type'));

    $aSelectedValues = array (
           'cp_uid' => $oResult->getFieldValue('cp_uid'),
           'cp_action' => $oResult->getFieldValue('cp_action'),
           'cp_type' => $oResult->getFieldValue('cp_type'));

    foreach($this->_aTypes as $sLabel => $aValues)
    {
      $vVals = array_diff($aValues, $aSelectedValues);
      if(empty($vVals))
        $oForm->addOption('type', array('value'=> urlencode(serialize($aValues)), 'selected' => 'selected', 'label' => $sLabel));
      else
        $oForm->addOption('type', array('value'=> urlencode(serialize($aValues)), 'label' => $sLabel));
    }

    //allow folders without types
    if(!isset($pasDisplayParam['force_item_type']) || empty($pasDisplayParam['force_item_type']))
    {
      $oForm->addOption('type', array('value'=> '', 'label' => 'No type. Will contains subfolders.'));
    }

    $folder_tree = self::_loadFolderTree(1);

    $oForm->addField('select', 'parentfolderfk', array('label' => 'Parent folder'));
    $aSelectOptions = $this->_getSelectOptions($folder_tree[0], 0, $this->_userPk);

    foreach ($aSelectOptions as $aSelectOption)
    {
      if($aSelectOption['value'] == $oResult->getFieldValue('parentfolderfk'))
        $aSelectOption['selected'] = 'selected';

      $oForm->addOption('parentfolderfk', $aSelectOption);
    }

    $aRights = array(
        0 => array('value' => 1, 'label' => 'Private'),
        1 => array ('value' => 0, 'label' => 'Public'),
        2 => array('value' => 2, 'label' => 'Custom'));

    $oForm->addField('select', 'private', array('label' => 'Rights'));
    foreach ($aRights as $aRight)
    {
      if($aRight['value'] == $oResult->getFieldValue('private'))
        $aRight['selected'] = 'selected';

      $oForm->addOption('private', $aRight);
    }

    $asUser = $oLogin->getUserList(0,true,false);

    $aCRParams = array('style' => 'display:none;');
    if($oResult->getFieldValue('private')==2)
      $aCRParams['style'] = 'display:block;';

    $oForm->addSection('custom_rights',$aCRParams);

    $oForm->addField('select', 'users', array('label' => 'Add user'));

    foreach($asUser as $aUser)
    {
      if(isset($asUserRights[$aUser['loginpk']]))
      {
        $oForm->addOption('users', array('value' => $aUser['loginpk'], 'label' => $aUser['id'], 'style' => 'display:none;'));
        $sDisplay = 'display:inline-block;';
      }
      else
      {
        $oForm->addOption('users', array('value' => $aUser['loginpk'], 'label' => $aUser['id']));
        $sDisplay = 'display:none;';
      }

      $oForm->addSection('user_'.$aUser['loginpk'],array('style' => 'width:100%; '.$sDisplay));
      $oForm->addField('misc', 'name_'.$aUser['loginpk'], array('type' => 'text', 'text' => $aUser['id']));
      $oForm->setFieldDisplayParams('name_'.$aUser['loginpk'], array('style' => 'width: 150px; float: left;'));

      foreach ($this->_aRights as $sRight)
      {
        $aParams = array ('label' => $sRight, 'class' => $sRight.' user_'.$aUser['loginpk'], 'keepinline' => 1);

        if((isset($asUserRights[$aUser['loginpk']])) && (in_array($sRight, $asUserRights[$aUser['loginpk']])))
          $aParams['checked']='checked';

        $oForm->addField('checkbox', $aUser['loginpk'].'_'.$sRight, $aParams);
        $oForm->setFieldDisplayParams($aUser['loginpk'].'_'.$sRight, array('style' => 'width: 120px; float: left;'));
      }
      $oForm->addField('misc', 'removeuser_'.$aUser['loginpk'], array('type' => 'text', 'text' => '<a href=\''.$aUser['loginpk'].'\' class=\'remove_user\' >Remove</a>'));
      $oForm->setFieldDisplayParams('removeuser_'.$aUser['loginpk'], array('style' => 'width: 60px; float: left;'));

      $oForm->closeSection();
    }

    $oForm->closeSection();

    if(!empty($this->cnPk))
    {
      $oForm->addField('misc', '', array('type' => 'br'));
      $oForm->addField('misc', '', array('type' => 'br'));

      $sURL = CONST_CRM_DOMAIN . '/index.php5?uid=555-002&ppa=ppad&ppt=fol&ppk='.$this->cnPk.'&pg=ajx';

      $oForm->addField('misc', '', array('type' => 'text', 'text' => '<span style="font-size: 15px;">Click <a href="javascript:;" '
          . 'onclick="if(window.confirm(\'Are you sure you want to delete this folder ?\'))'
          . '{ AjaxRequest(\''.$sURL.'\', \'body\', \'\', \'\', \'\', \'\', \'$(\\\'#userFolderRow_'.$this->cnPk.'\\\').remove(); goPopup.removeLastByType(\\\'layer\\\');\');'
          . '};"><b style="font-size: 15px;">here</b></a> to delete the folder.</span>'));
    }

    $sJavascript = '<script>
      $(\'.remove_user\').click(function(){
        var userpk = $(this).attr(\'href\');
        $(\'.user_\'+userpk).removeAttr(\'checked\');
        $(\'#user_\'+userpk).hide();
        $(\'#usersId option[value=\'+userpk+\']\').show();
        return false;
      });

      $(\'#privateId\').change(function(){
        if ($(\'#privateId option:selected\').val()==\'2\')
          { $(\'#custom_rights\').show(); }
        else
          { $(\'#custom_rights\').hide(); }
      });

      $(\'#usersId\').change(function(){
        var userpk = $(\'#usersId option:selected\').val();
        $(\'#user_\'+userpk).show();
        $(\'#usersId option:selected\').hide();
      });
      </script>';

    $oForm->addField('misc', 'js_select',
            array('type' => 'text',
                'text' => $sJavascript
                )
            );
    return $oForm->getDisplay();
  }

  // Adds an option to a select field
  // Used to display folder tree and show children / parents folder
  // @param $poForm : form to update
  // @param $psFieldName : field name to add options to
  // @param $paFolder : array containing folder tree generated by _getFolderTree
  // @param $pnIndent : space showing inheritance
  protected function _getSelectOptions($paFolder, $pnIndent = 0, $pnOwner = 0)
  {
    if(!assert('is_array($paFolder) && !empty($paFolder)'))
      return array();

    if(!assert('is_integer($pnIndent)'))
      return array();

    $oHTML = CDependency::getCpHtml();
    $aOutpout = array();


    //dump($pnOwner); dump($paFolder['ownerloginfk']);
    if($pnIndent == 0 || empty($pnOwner) || $pnOwner == @$paFolder['ownerloginfk'])
    {
      $aParams = array('value'=> $paFolder['folderpk'], 'label' => $oHTML->getSpace($pnIndent) . $paFolder['label']);
      if(!in_array('add_item', $paFolder['rights']))
        $aParams['disabled'] = 'disabled';

      $aOutpout[$paFolder['folderpk']] = $aParams;
    }

    if(isset($paFolder['content']['subfolders']) && !empty($paFolder['content']['subfolders']))
    {
      foreach($paFolder['content']['subfolders'] as $aFolder)
        $aOutpout = array_merge($aOutpout, $this->_getSelectOptions($aFolder, $pnIndent+1, $pnOwner));
    }


    return $aOutpout;
  }

  public function manageFolders($psType = 'list')
  {
    if(!assert('!empty($psType)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(self::getResourcePath().'css/folder.css');
    $oPage->addCssFile(self::getResourcePath().'css/'.$psType.'.css');
//  $oPage->addJsFile($this->getResourcePath().'js/draganddrop.js');

    //link to create opportunity on connections
    $sUrl = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, '', 0);
    $sAjax = 'var oConf = goPopup.getConfig();
                oConf.height = 640;
                oConf.width = 880;
                oConf.modal = true;
                goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ';

    $sHTML = $oHTML->getActionButton('Add folder', '', CONST_PICTURE_ADD, array('onclick' => $sAjax));
    $sHTML .= $oHTML->getCR(2);
    $sHTML .= $this->_displayFoldersAdmin();

    return $sHTML;
  }

  protected function _displayFoldersAdmin($psType = '')
  {
    if(!assert('is_string($psType)'))
      return '';

    $oHTML = CDependency::getCpHtml();

    $folder_tree = self::_loadFolderTree(1);

    if(empty($folder_tree))
      return 'Impossible to load folders. Please contact your administrator.';

    $aParams = array('class' => 'folders');
    $sHTML = $oHTML->getBlocStart('', $aParams);
    $sHTML .= $this->_displayFolderAdmin($folder_tree[0], $psType);
    $sHTML .= $oHTML->getBlocEnd();

    return $sHTML;
  }

  // Display a folder with associated actions

  protected function _displayFolderAdmin($paFolders, $psType='')
  {
    if(!assert('is_array($paFolders) && !empty($paFolders)'))
      return '';

    if(!assert('is_string($psType)'))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $sHTML = $oHTML->getBlocStart('foldername_'.$paFolders['folderpk'], array('class' => 'foldername'));
      $sHTML .= $paFolders['label'];
      $sHTML .= $oHTML->getBlocStart('', array('class' => 'item-actions'));

      if(in_array('edit', $paFolders['rights']))
      {
        $sUrlEditFolder = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_FOLDER_TYPE_FOLDER, $paFolders['folderpk']);
        $sAjax = 'var oConf = goPopup.getConfig();
                    oConf.height = 640;
                    oConf.width = 880;
                    oConf.modal = true;
                    goPopup.setLayerFromAjax(oConf, \''.$sUrlEditFolder.'\'); ';

        $sHTML .= $oHTML->getActionButton('Edit', '', CONST_PICTURE_EDIT, array('onclick' => $sAjax));
      }

      if(in_array('delete', $paFolders['rights']))
      {
        $sUrlRemoveFolder = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_FOLDER_TYPE_FOLDER, $paFolders['folderpk']);
        $sLinkRemove = $oHTML->getLink(
                'Remove '.$oHTML->getPicture(CONST_PICTURE_DELETE, 'Remove this folder'),
                $sUrlRemoveFolder,
                array('ajaxCallback' => 'javascript:$(\'#foldername_'.$paFolders['folderpk'].'\').remove();$(\'#folder_'.$paFolders['folderpk'].'\').remove();')
                );
        $sHTML .= $oHTML->getActionButton('', '', CONST_PICTURE_DELETE, array(), $sLinkRemove);
      }

      $sHTML .= $oHTML->getBlocEnd();
    $sHTML .= $oHTML->getBlocEnd();



    if(isset($paFolders['content']) && !empty($paFolders['content']))
    {
      $sHTML .= $oHTML->getListStart('folder_'.$paFolders['folderpk'], array('class' => 'folder', 'ondrop' => 'drop(event)', 'ondragover' => 'allowDrop(event)'));

      if(isset($paFolders['content']['subfolders']) && !empty($paFolders['content']['subfolders']))
      {
        foreach ($paFolders['content']['subfolders'] as $aSubfolder)
        {
          if($aSubfolder['ownerloginfk'] == $this->_userPk && ($psType == '' || $this->isType($aSubfolder, $psType)))
          {
            $sHTML .= $oHTML->getListItemStart('subfolder_'.$aSubfolder['folderpk'], array('class' => 'subfolder', 'draggable' => 'true'));
            $sHTML .= $this->_displayFolderAdmin($aSubfolder, $psType);
            $sHTML .= $oHTML->getListItemEnd();
          }
        }
      }

      if(isset($paFolders['content']['subpages']) && !empty($paFolders['content']['subpages']))
      {
        foreach ($paFolders['content']['subpages'] as $aSubitem)
        {
          if(in_array('read', $paFolders['rights']))
          {
            $sHTML .= $oHTML->getListItemStart('subitem_'.$aSubitem['folder_itempk'], array('class' => 'subitem', 'draggable' => 'true', 'ondragstart' => 'drag(event)'));
            $sHTML .= $oHTML->getBloc('', $aSubitem['label'], array('class' => 'filename'));

            $sHTML .= $oHTML->getBlocStart('', array('class' => 'item-actions'));

              if(!empty($paFolders['cp_uid']))
              {
                $sUrl = $oPage->getUrl($paFolders['cp_uid'], $paFolders['cp_action'], $paFolders['cp_type'], $aSubitem['itemfk']);
                $sHTML .= $oHTML->getLink(
                        $oHTML->getPicture(self::getResourcePath().'img/external_32.png', 'Open this item in new window'),
                        $sUrl,
                        array('target' => '_blank')
                        );

                if(in_array('remove_item', $paFolders['rights']))
                {
                  $sUrlRemoveItem = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_FOLDER_TYPE_ITEM, $aSubitem['folder_itempk']);
                  $sHTML .= $oHTML->getLink(
                        $oHTML->getPicture(CONST_PICTURE_DELETE, 'Remove this item'),
                        $sUrlRemoveItem,
                        array('ajaxCallback' => 'javascript:$(\'#subitem_'.$aSubitem['folder_itempk'].'\').remove();')
                        );
                 }
              }

            $sHTML .= $oHTML->getBlocEnd();
            $sHTML .= $oHTML->getListItemEnd();
          }
        }
      }

      $sHTML .= $oHTML->getListEnd();
    }

    return $sHTML;
  }

  public function getFolder($pnFolderPk)
  {
    if(!assert('is_key($pnFolderPk)'))
      return new CDbResult();

    return $this->_getModel()->getByPk($pnFolderPk, 'folder');
  }

  //Checks if a folder is of a given type

  protected function isType($paFolder, $psType)
  {
    if(!assert('is_array($paFolder) && !empty($paFolder)'))
      return false;

    if(!assert('is_string($psType) && !empty($psType)'))
      return false;

    if((isset($paFolder['shortname'])) && ($paFolder['shortname']=='root'))
      return true;

    $bIsType = ($this->_aTypes[$psType]['cp_uid']==$paFolder['cp_uid']) && ($this->_aTypes[$psType]['cp_action']==$paFolder['cp_action']) && ($this->_aTypes[$psType]['cp_type']==$paFolder['cp_type']);

    return $bIsType;
  }

  // Displays a folder with a link to the related items

  protected function _displayFolder($paFolders, $psType = '')
  {
    if(!assert('is_array($paFolders) && !empty($paFolders)'))
      return '';

    if(!assert('is_string($psType)'))
      return '';

    if ($psType!='')
    {
      if(!$this->isType($paFolders, $psType))
        return '';
    }

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $sHTML = '';
    $sHTML .= $oHTML->getListItemStart('foldername_'.$paFolders['folderpk'], array('class' => 'foldername'));

    if(!isset($paFolders['shortname']) || $paFolders['shortname']!='root')
    {
    $sLink = $oHTML->getLink($paFolders['label'],'',array('onclick' => '$(this).parent().parent().children(\'.folder_'.$paFolders['folderpk'].'\').toggle();'));
    $sHTML .= $oHTML->getBloc('', $sLink, array('style' => 'width:100%;'));
    }

    if ((isset($paFolders['content']['subfolders']) && !empty($paFolders['content']['subfolders']))
        || (isset($paFolders['content']['subpages']) && !empty($paFolders['content']['subpages'])))
      {
        $sHTML .= $oHTML->getListStart('', array('class' => 'subfolder folder_'.$paFolders['folderpk']));

          if(isset($paFolders['content']['subfolders']) && !empty($paFolders['content']['subfolders']))
          {
            foreach ($paFolders['content']['subfolders'] as $aSubfolder)
              $sHTML .= $this->_displayFolder($aSubfolder, $psType);
          }

          if(isset($paFolders['content']['subpages']) && !empty($paFolders['content']['subpages']))
          {
            foreach ($paFolders['content']['subpages'] as $aSubitem)
            {
              if(is_cpValues($paFolders))
              {
                $sHTML .= $oHTML->getListItemStart('subitem_'.$aSubitem['folder_itempk']);
                $sUrl = $oPage->getUrl($paFolders['cp_uid'], $paFolders['cp_action'], $paFolders['cp_type'], $aSubitem['itemfk']);
                $sHTML .= '- '.$oHTML->getLink($aSubitem['label'],$sUrl);
                $sHTML .= $oHTML->getListItemEnd();
              }
            }
          }

        $sHTML .= $oHTML->getListEnd();
      }

    $sHTML .= $oHTML->getListItemEnd();

    return $sHTML;
  }


  // Loads the Folder tree and store it in session

  protected function _loadFolderTree($preload_option = null)
  {
    if(!assert('is_integer($preload_option)'))
      return false;

    if($preload_option != null)
      $this->cnPreloadOption = $preload_option;

    $folder_tree = $this->_getFolderTree();

    if (!empty($folder_tree))
      return $folder_tree;
    else
     return false;
  }

  // Returns a folder tree of a specific type asked
  public function getFolders($psType)
  {
    if(!assert('isset($this->_aTypes[$psType])'))
      return array();

    $oFolders = $this->_getModel()->getFolders($this->_userPk, '', $this->_aTypes[$psType]);
    $asFolders = $oFolders->getAll();

    $aUserRights = $this->_getModel()->getUserRights($this->_userPk);

    $oFolderItems = $this->_getModel()->getFolderItems($this->_userPk, '', $this->_aTypes[$psType]);
    $asFolderItems = $oFolderItems->getAll();



    return $this->_getFolder($asFolders, $asFolderItems, 0, $aUserRights);
  }

  // Sends folder tree to some other component

  public function getFolderTree()
  {
    return self::_loadFolderTree();
  }

  protected function _getFolderTree()
  {
    $oFolders = $this->_getModel()->getFolders($this->_userPk);
    $asFolders = $oFolders->getAll();



    $aUserRights = $this->_getModel()->getUserRights($this->_userPk);

    //simple version without all items
    if($this->cnPreloadOption === 1)
    {
      $asFolderItems = array();
    }
    else
    {
      $oFolderItems = $this->_getModel()->getFolderItems($this->_userPk);
      $asFolderItems = $oFolderItems->getAll();
    }
    //dump('fetched items from db ['.date('H:i:s').']'); flush(); ob_flush();


    $aFolderTree = array();
    $aFolderTree[0]['label'] = 'Root';
    $aFolderTree[0]['shortname'] = 'root';
    $aFolderTree[0]['rank'] = 0;
    $aFolderTree[0]['folderpk'] = 0;
    $aFolderTree[0]['folder_link'] = array();
    $aFolderTree[0]['content'] = $this->_getFolder($asFolders, $asFolderItems, 0, $aUserRights);
    $aFolderTree[0]['rights'] = array('add_item', 'remove_item', 'read');

    //dump('done building the tree with _getFolder ['.date('H:i:s').']'); flush(); ob_flush();
    $this->cnTreeStatus = $this->cnPreloadOption;

    return $aFolderTree;
  }

  // Builds an array with the folders and items listed in

  protected function _getFolder(&$pasFolders, $pasFolderItems, $pnParentFk, $paUserRights)
  {
    if(!assert('is_array($pasFolders)'))
      return array();

    if(!assert('is_array($pasFolderItems)'))
      return array();

    if(!assert('is_integer($pnParentFk)'))
      return array();

    if(!assert('is_array($paUserRights)'))
      return array();

    //dump('recursive call  _getFolder() ['.date('H:i:s').'] '); flush(); ob_flush();
    //dump('nb folders: '.count($pasFolders));


    $aSubFolders = array();
    $aSubPages = array();

    foreach($pasFolders as $nKey => $asFolder)
    {
      $nParentFk = (int)$asFolder['parentfolderfk'];

      if($nParentFk == $pnParentFk)
      {
        $asFolder['folderpk'] = (int)$asFolder['folderpk'];
        $asFolder['ownerloginfk'] = (int)$asFolder['ownerloginfk'];

        if(($asFolder['ownerloginfk'] == $this->_userPk) || ($asFolder['private'] == 0))
          $asFolder['rights'] = $this->_aRights;
        else
          $asFolder['rights'] = $paUserRights[$asFolder['folderpk']];

        $aSubFolders[(int)$asFolder['rank']] = $asFolder;

        //treated this folder, remove it so it's not checked again when treating sub folders
        unset($pasFolders[$nKey]);
      }
    }


    if(!empty($pasFolders))
    {
      //dump('are there subfolders (parent: '.$pnParentFk.') ? ');
      //dump(count($aSubFolders));

      foreach($aSubFolders as $nKey => $aSubFolder)
      {
        /* if(!empty($pasFolders))   // moved above */
        if(!empty($aSubFolder))
        {
          //dump('subfolder found, call _getFolder() on ');
          //dump($aSubFolder['folderpk'].' -- '.$aSubFolder['label']);


          $aSubFolderContent = $this->_getFolder($pasFolders, $pasFolderItems, $aSubFolder['folderpk'], $paUserRights);
          if(!empty($aSubFolderContent))
            $aSubFolders[$nKey]['content'] = $aSubFolderContent;
        }
      }
    }

    //-----------------------------------
    //only if I need to load the full tree
    if($this->cnPreloadOption > 1)
    {
      //dump('load all tree data ['.$this->cnPreloadOption.']');

      foreach($pasFolderItems as $nKey => $asItems)
      {
        $nParentFk = (int)$asItems['parentfolderfk'];

        if($nParentFk == $pnParentFk)
        {
          $nRank = (int)$asItems['rank'];
          $aSubPages[$nRank] = $asItems;
        }
      }
    }
    /*else
    {
      dump('DO NOT load all tree data ');
    }*/


    asort($aSubFolders);
    asort($aSubPages);

    $aOutput = array();
    if(!empty($aSubFolders))
      $aOutput['subfolders'] = $aSubFolders;

    if(!empty($aSubPages))
      $aOutput['subpages'] = $aSubPages;

    return $aOutput;
  }

  // Add an item to a folder. Rank is calculated automatically

  protected function _addToFolder($pnFolderFk, $pasItem = array(), $psCallback = '')
  {
    if(!assert('is_key($pnFolderFk) && is_array($pasItem)'))
      return array('error' => 'Item could not be added. Wrong folder given.');

    if(!empty($pasItem))
    {
      $anItemFk = array_keys($pasItem);
      $asItemLabel = array_values($pasItem);
    }
    else
    {
      $anItemFk = explode(',', getValue('itemfk'));
      $asItemLabel = explode(',', getValue('itemlabel'));
    }

    if(empty($anItemFk) || !is_arrayOfInt($anItemFk))
      return array('error' => 'Wrong item(s) given. Could not be added.');

    if(count($anItemFk) != count($asItemLabel))
      return array('error' => 'Ids and label don\'t match.');


    $nHRank = $this->_getModel()->getHighestRank($pnFolderFk, 'folder_item');

    //check what item is already in the folder to not add it twice
    //this query MUST always retun 1 result to prove the folder exists
    $oDbResult = $this->_getModel()->checkItemExist($pnFolderFk, $anItemFk);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array('error' => 'Looks like the folder doesn\'t exist anymore.');

    $anExist = array();
    while($bRead)
    {
      $nDbItemfk = $oDbResult->getFieldValue('itemfk');
      if(!empty($nDbItemfk))
        $anExist[] = $nDbItemfk;

      $bRead = $oDbResult->readnext();
    }

    //remove existing from items to add
    $anItemFk = array_diff($anItemFk, $anExist);
    if(empty($anItemFk))
      return array('notice' => 'Item(s) already in the folder', 'nb_added' => 0, 'delay' => 0);


    $asAdd = array();
    $nCount = 0;
    foreach($anItemFk as $nKey => $nItemfk)
    {
      $asAdd['itemfk'][] = $nItemfk;
      $asAdd['rank'][] = ($nHRank + $nCount +1);
      $asAdd['label'][] = $asItemLabel[$nKey];
      $asAdd['parentfolderfk'][] = $pnFolderFk;
      $nCount++;
    }

    $nFolderItemPk = $this->_getModel()->add($asAdd, 'folder_item');

    if($nFolderItemPk == 0)
      return array('error' => 'Could not add item to the folder. Please contact the administrator.');

    //dump('folder --> added item -> need refresh tree'); flush(); ob_flush();

    // self::_loadFolderTree(true);
    if(empty($psCallback))
      return array('notice' => 'Item saved in folder successfully', 'nb_added' => $nCount, 'reload' => 1);

    return array('notice' => 'Item saved in folder successfully', 'nb_added' => $nCount, 'action' => $psCallback);
  }

  // Saves a folder. Rank is calculated automatically if not given

  protected function _saveFolder($pnPk = 0)
  {
    if(!assert('is_integer($pnPk)'))
      return false;

    // Saving folder main table
    $aData['parentfolderfk'] = (int)$_POST['parentfolderfk'];
    $aData['label'] = addslashes($_POST['label']);
    $aData['private'] = $_POST['private'];

    if($pnPk == 0)
    {
      $nHRank = $this->_getModel()->getHighestRank($aData['parentfolderfk']);
      $aData['rank'] = $nHRank+1;
      $aData['ownerloginfk'] = $this->_userPk;
      $nPk = $this->_getModel()->add($aData, 'folder');

      if($nPk == 0)
        return array('error' => 'Could not save folder. Please contact the administrator.');
    }
    else
    {
      $aData['folderpk'] = $pnPk;
      $bUpdated = $this->_getModel()->update($aData, 'folder');

      if(!$bUpdated)
        return array('error' => 'Could not update folder. Please contact the administrator.');

      $nPk = $pnPk;
    }

    // Saving folder type (link table)
    if(!empty($_POST['type']))
    {
      $aLinkData = unserialize(urldecode($_POST['type']));
      $aLinkData['folderfk']=$nPk;

      if(is_key($pnPk))
      {
        $bUpdatedLink = $this->_getModel()->update($aLinkData, 'folder_link', 'folderfk='.$nPk);
        if(!$bUpdatedLink)
          return array('error' => 'Could not update folder type. Please contact the administrator.');
      }
      else
      {
        $nLinkPk = $this->_getModel()->add($aLinkData, 'folder_link');
        if($nLinkPk == 0)
          return array('error' => 'Could not save folder type. Please contact the administrator.');
      }
    }

    // Saving folder rights
    $this->_getModel()->deleteByFk($nPk, 'folder_rights', 'folder');

    if($aData['private']==2)
    {
      $oLogin = CDependency::getCpLogin();
      $asUser = $oLogin->getUserList(0,true,false);

      $aDataRights = array();
      foreach ($asUser as $aUser)
      {
        foreach ($this->_aRights as $sRight)
        {
          $sField = $aUser['loginpk'].'_'.$sRight;
          if ((isset($_POST[$sField])) && ($_POST[$sField]=='on'))
          {
            $aDataRights['folderfk'][]= $nPk;
            $aDataRights['loginfk'][]= $aUser['loginpk'];
            $aDataRights['rights'][]= $sRight;
          }
        }
      }

      if(!empty($aDataRights))
        $this->_getModel()->add($aDataRights, 'folder_rights');
    }


    $sItems = getValue('items');
    if(!empty($sItems))
    {
      $asItem = unserialize(base64_decode($sItems));
      if(!empty($asItem))
      {
        $this->_addToFolder($nPk, $asItem);
      }
    }

    // self::_loadFolderTree(true);


    if(!empty($this->casAfterSavingAction))
    {
      unset($_SESSION['folder_save_action']);
      return $this->casAfterSavingAction;
    }

    return array('notice' => 'Folder saved successfully', 'reload' => 1);
  }

  protected function _removeItem($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => 'Wrong Pk given');

    $this->_getModel()->deleteByPk($pnPk, 'folder_item');
    return array('notice' => 'Item removed.');
  }

  protected function _removeFolder($pnPk)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => 'Wrong Pk given');

    $this->_getModel()->deleteItemFromParentFk($pnPk);
    $this->_getModel()->deleteByFk($pnPk, 'folder_rights', 'folder');
    $this->_getModel()->deleteByPk($pnPk, 'folder');
    // self::_loadFolderTree(true);
    return array('notice' => 'Folder removed.');
  }


  private function _folderSelector()
  {
    $sSearch = trim(getValue('q'));
    if(empty($sSearch))
      return json_encode(array());

    $bMine = (bool)getValue('exclude_mine', 1);
    $bShared = (bool)getValue('exclude_shared', 1);
    $asJsonData = array();
    $sSelect = '';

    $oDB = CDependency::getComponentByName('database');

    if($sSearch == 'all' || $sSearch == 'more' || $sSearch == '--')
    {
      //restrict to personal folders
      $bShared = false;

      $asEntry = array();
      $asEntry['id'] = -1;
      $asEntry['name'] = 'Displays only your folders';
      $asJsonData[] = json_encode($asEntry);
    }

    $asUserHash = array();
    $vResult = preg_match('/#([a-z]{1,}[^a-z]{0,1})/i', $sSearch, $asUserHash);
    //dump($asUserHash);

    if($vResult !== false && !empty($asUserHash[0]))
    {
      //restrict to personal folders
      $sName = trim($asUserHash[0]);
      $bMine = $bShared = true;
      $oLogin = CDependency::getCpLogin();

      $anUserPk = $oLogin->getUserPkFromName(substr($sName, 1), true, true);
      if(empty($anUserPk))
      {
        $asEntry = array();
        $asEntry['id'] = -1;
        $asEntry['name'] = 'User can not be found.';
        $asJsonData[] = json_encode($asEntry);
        exit('['.implode(',', $asJsonData).']');
      }

      $asEntry = array();
      $asEntry['id'] = 'token_clear';
      $asEntry['name'] = 'Displays '.substr($sSearch, 1).' folders ('.implode(',', $anUserPk).')';
      $asJsonData[] = json_encode($asEntry);

      //remove user name from search string
      $sSearch = trim(str_replace($sName, '', $sSearch));
      $sSelect = ', IF(slog.loginpk = '.$this->_userPk.', 1, 0) as customOrder ';
    }

    //dump($sSearch);
    if(empty($sSearch))
    {
      $sQuery = 'SELECT fold.*, count(fite.folder_itempk) as nb_item, slog.pseudo, "" as str_equal, "" as str_start '.$sSelect.'
      FROM folder as fold';
    }
    else
    {
      $sQuery = 'SELECT fold.*, count(fite.folder_itempk) as nb_item, slog.pseudo,
      IF(lower(fold.label) = '.$oDB->dbEscapeString(strtolower($sSearch)).', 1, 0) as str_equal,
      IF(lower(fold.label) LIKE '.$oDB->dbEscapeString(strtolower($sSearch).'%').', 1, 0) as str_start '.$sSelect.'
      FROM folder as fold';
    }

    $sQuery.= '
      INNER JOIN shared_login as slog ON (slog.loginpk = fold.ownerloginfk)
      LEFT JOIN folder_item as fite ON (fite.parentfolderfk = fold.folderpk)
      WHERE 1 ';

    if(!empty($sSearch))
      $sQuery.= ' AND  lower(fold.label) LIKE '.$oDB->dbEscapeString('%'.strtolower($sSearch).'%');


    if(!$bMine)
      $sQuery.= ' AND fold.ownerloginfk <> '.$this->_userPk.' ';

    if(!$bShared)
      $sQuery.= ' AND fold.ownerloginfk = '.$this->_userPk.' ';

    if(!empty($anUserPk))
    {
      $sQuery.= ' AND fold.ownerloginfk IN ('.implode(',', $anUserPk).')

        GROUP BY fold.folderpk
        ORDER BY  customOrder DESC, str_equal DESC, str_start DESC, fold.label, folderpk ';
    }
    else
    {
      $sQuery.= ' GROUP BY fold.folderpk
                  ORDER BY str_equal DESC, str_start DESC, fold.label, folderpk';
    }


    //dump($sQuery);
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if($bRead)
    {
      while($bRead)
      {
        $asData['id'] = $oDbResult->getFieldValue('folderpk');

        if($bShared)
        {
          if($this->_userPk == $oDbResult->getFieldValue('ownerloginfk'))
            $sUser = 'me';
          else
            $sUser = $oDbResult->getFieldValue('pseudo');

          $asData['name'] = $oDbResult->getFieldValue('label').' ['.$sUser.'] ('.$oDbResult->getFieldValue('nb_item').')' ;
        }
        else
          $asData['name'] = $oDbResult->getFieldValue('label').' ('.$oDbResult->getFieldValue('nb_item').')' ;

        $asJsonData[] = json_encode($asData);
        $bRead = $oDbResult->readNext();
      }
    }


    exit('['.implode(',', $asJsonData).']');
  }
}
