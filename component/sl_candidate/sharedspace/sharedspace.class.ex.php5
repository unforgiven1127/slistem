<?php

require_once('./component/sharedspace/sharedspace.class.php5');

class CSharedspaceEx extends CSharedspace
{
  private $_rightManager;
  private $_rightAdmin;
  private $_aRights;
  private $_aFolders;
  private $_nUserPk;
  private $_aUserList;
  private $casMsZipExtension = array('docx' => 'application/vnd.ms-word', 'xlsx' => 'application/vnd.ms-excel', 'pptx' => 'application/vnd.ms-powerpoint');

  public function __construct()
  {
    $oRight = CDependency::getComponentByName('right');
    $this->_rightManager = $oRight->canAccess($this->getComponentUid(), 'right_manager', '', 0);
    $this->_rightAdmin = $oRight->canAccess($this->getComponentUid(), 'right_admin', '', 0);
    $this->_aRights = array('edit', 'read', 'notify');

    $oLogin = CDependency::getCpLogin();
    $this->_nUserPk = $oLogin->getUserPk();

    return true;
  }

  //====================================================================
  //  accessors
  //====================================================================


  //====================================================================
  //  interface
  //====================================================================

  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $asActions = array();
    switch($this->csType)
    {
      case CONST_SS_TYPE_DOCUMENT:

        $oPage = CDependency::getCpPage();
        $sPictureMenuPath = $this->getResourcePath().'/pictures/menu/';

        //always displayed: list, add
        $asActions['ppal'][] = array('picture' => $sPictureMenuPath.'doc_list_32.png','title'=>'List Documents', 'url' => $oPage->getUrl($this->_getUid(), CONST_ACTION_LIST, CONST_SS_TYPE_DOCUMENT));

        $sUrlAdd = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_ADD, CONST_SS_TYPE_DOCUMENT);
        $sAjaxAdd = 'var oConf = goPopup.getConfig();
                oConf.height = 660;
                oConf.width = 980;
                oConf.modal = true;
                goPopup.setLayerFromAjax(oConf, \''.$sUrlAdd.'\'); ';

        $sUrl = $oPage->getAjaxUrl('search', CONST_ACTION_SEARCH, '', 0, array('formType'=>'advanced', 'CpUid' => $this->csUid, 'CpType' => CONST_SS_TYPE_DOCUMENT));
        $sAjax = 'var oConf = goPopup.getConfig();
                    oConf.height = 660;
                    oConf.width = 980;
                    oConf.modal = true;
                    goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ';

        $asActions['ppaa'][] = array('picture' => $sPictureMenuPath.'doc_add_32.png','title'=>'Add Document', 'url' =>  'javascript:;', 'option' => array('onclick' => $sAjaxAdd));
//      $asActions['ppas'][] = array('picture' => CONST_PICTURE_MENU_SEARCH, 'title' => 'Search documents', 'url' => 'javascript:;', 'option' => array('onclick' => $sAjax));

      break;

      default:
        break;
    }

    return $asActions;
  }


  public function getAjax()
  {
    $this->_processUrl();

    switch ($this->csAction)
    {
      case CONST_ACTION_DELETE:
        $aContent = $this->_removeDocument($this->cnPk);
        return json_encode($aContent);
        break;

      case CONST_ACTION_EDIT:
      case CONST_ACTION_ADD:
        $oPage = CDependency::getCpPage();
        return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_displayForm())));
        break;

      case CONST_ACTION_VIEW:
        switch ($this->csType)
        {
          case CONST_SS_TYPE_DOCUMENT:
            $oPage = CDependency::getCpPage();
            $asJson = $oPage->getAjaxExtraContent($this->_displayDocument($this->cnPk));
            $sJson = json_encode($asJson);

            if(substr($sJson, 0 , 12) == '{"data":null')
               return json_encode($oPage->getAjaxExtraContent($this->_displayDocument($this->cnPk, true)));

            return $sJson;
          break;

          case CONST_FOLDER_TYPE_FOLDER:
            $oPage = CDependency::getCpPage();
            $oLogin = CDependency::getCpLogin();

            $this->_aUserList = $oLogin->getUserList(0, false, true);
            return json_encode($oPage->getAjaxExtraContent(array('data' => $this->_displayFolderContent($this->cnPk))));
          break;
        }
        break;

      case CONST_ACTION_SAVEEDIT:
        return json_encode($this->_saveDocument($this->cnPk));
        break;

      case CONST_ACTION_SAVEADD:
        return json_encode($this->_saveDocument(0));
        break;

      case CONST_ACTION_LIST:
        $aCpValues = getCpValuesFromPost();
        $oPage = CDependency::getCpPage();
        if(!empty($aCpValues))
        {
          $nPageOffset = (int)getValue('pageoffset',1);
          $nNbTotal = (int)getValue('nbTotal');
          $sContent = $this->_displayTabList($aCpValues, $nPageOffset, array(), $nNbTotal);
          return json_encode($oPage->getAjaxExtraContent(array ('data' => $sContent)));
        }
        else
        {
          $sContent = $this->_displayLastDocuments(array());
          return json_encode($oPage->getAjaxExtraContent(array ('data' => $sContent)));
        }
      break;
    }
  }

  public function getHtml()
  {
    $this->_processUrl();

    switch($this->csAction)
    {
      case CONST_ACTION_SEND:
       return $this->_sendDocument($this->cnPk);
        break;


      case CONST_ACTION_VIEW:
        $asData = $this->_displayDocument($this->cnPk);
        if(isset($asData['data']))
          return $asData['data'];

        return '';
        break;

      case CONST_ACTION_LIST:
      default:
        return $this->_displayList();
          break;
    }
  }

  public function displayAddLink($paCpValues = array(), $bDisplayText = true, $bOnlyAjax = false, $psZoneToRefresh = '', $psRefreshWithUrl = '')
  {
    if(!$this->_rightManager && !$this->_rightAdmin)
      return '';

    if(!assert('is_string($psZoneToRefresh)'))
      return '';

    if(!assert('is_string($psRefreshWithUrl)'))
      return '';

    if(!assert('(!empty($psZoneToRefresh) && !empty($psRefreshWithUrl)) || (empty($psZoneToRefresh) && empty($psRefreshWithUrl))'))
      return '';

    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();

    $aUrlParams = array();
    if(!empty($psZoneToRefresh) && !empty($psRefreshWithUrl))
    {
      $aUrlParams['psZoneToRefresh']=$psZoneToRefresh;
      $aUrlParams['psRefreshWithUrl']=$psRefreshWithUrl;
    }

    if(!empty($paCpValues))
      $aUrlParams=array_merge($aUrlParams, $paCpValues);

    if(!empty($aUrlParams))
      $sUrl = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_ADD, $this->getDefaultType(), 0, $aUrlParams);
    else
      $sUrl = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_ADD, $this->getDefaultType(), 0);

    $sAjax = 'var oConf = goPopup.getConfig();
                oConf.height = 660;
                oConf.width = 980;
                oConf.modal = true;
                goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\');';

    $sText ='';
    if($bDisplayText)
      $sText = ' Upload new file';

    $aParams = array('onclick' => $sAjax);
    $sLink = $oHTML->getLink($oHTML->getPicture(CONST_PICTURE_UPLOAD, 'Upload new file').$sText, 'javascript:;', $aParams);
    $sHTML = $oHTML->getActionButton('Upload new file', '', CONST_PICTURE_UPLOAD, array(), $sLink);

    if($bOnlyAjax)
      return $sAjax;
    else
      return $sHTML;
  }

  public function getCronJob()
  {
    $this->_processUrl();
    echo 'Sharedspace cron <br />';

    //notify users a document has been shared with them
    $day = date('l');
    $time = (int)date('H');

    if((($day=='Monday' || $day=='Thursday' ) && $time == 6) || (getValue('custom_uid') == '999-111' && getValue('forcecron')))
    {
      echo 'Notify users<br />';
      $this->_getCronDocument();
    }
  }

  // Tab content
  public function getTabContent($pasValues)
  {
    if(!assert('is_cpValues($pasValues)'))
      return array();

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $sHTML = '';

    $sAjaxUrl = $oPage->getAjaxUrl($this->getComponentUid(), CONST_ACTION_LIST, CONST_SS_TYPE_DOCUMENT, 0, $pasValues);
    $sHTML .= $this->displayAddLink($pasValues, true, false, 'documents-list', $sAjaxUrl);
    $sHTML.= $oHTML->getCR(2);

    $aLastDoc = array();
    $nTotal = $this->getCount($pasValues);

    $sHTML.= $oHTML->getBlocStart('documents-list', array('class' => 'document-list div-to-refresh', 'psZoneToRefresh' => 'document-list', 'psRefreshWithUrl' => $sAjaxUrl));
    $sHTML .= $this->_displayTabList($pasValues, 1, $aLastDoc, $nTotal);
    $sHTML .= $oHTML->getBlocEnd();

    $aOutput = array('count' => $nTotal, 'last' => $aLastDoc, 'html' => $sHTML);

    return $aOutput;
  }

  private function _displayTabList($pasValues, $pnPage = 1, $paLastDoc = array(), $pnTotal = 0)
  {
    if(!assert('is_cpValues($pasValues)'))
      return array();

    if(!assert('is_integer($pnTotal)'))
      return array();

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/sharedspace.css');
    $sHTML = '';
    $nNbResults = 10;

    $aDocuments = $this->_getModel()->getDocuments($this->_nUserPk, '', $pasValues, array('nPage' => $pnPage, 'nNbItems' => $nNbResults));

    if(empty($aDocuments))
      return $oHTML->getBlocMessage('No document was found.');

    $aLastDoc = current($aDocuments);

    $aRowData = array();
    foreach ($aDocuments as $nPk => $aDocument)
      $aRowData[]= $this->_getRowData($aDocument, array());

    $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => array('class' => 'CDocument_row', 'path' => $_SERVER['DOCUMENT_ROOT'].self::getResourcePath().'template/document_row.tpl.class.php5')))));
    $sAjaxUrl = $oPage->getAjaxUrl($this->getComponentUid(), CONST_ACTION_LIST, CONST_SS_TYPE_DOCUMENT, 0, $pasValues);
    $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam, array('sRefreshWithUrl' => $sAjaxUrl, 'sZoneToRefresh' => 'documents-list'));

    $oConf = $oTemplate->getTemplateConfig('CTemplateList');
    $oConf->setRenderingOption('full', 'full', 'full');

    $oConf->addColumn('Title', 'title', array('id' => 'title', 'sortable' => array('javascript' => 1), 'width' => 525));
    $oConf->addColumn('Size', 'file_size', array('id' => 'file_size', 'sortable' => array('javascript' => 1), 'width' => 70));
    $oConf->addColumn('Details', 'creator', array('id' => 'created_by', 'width' => 175));
    $oConf->addColumn('Actions', 'actions', array('id' => 'actions', 'width' => 300));

    $oConf->setPagerTop(false);
    $oConf->setPagerBottom(true, 'center', $pnTotal, $oPage->getAjaxUrl($this->getComponentUid(), CONST_ACTION_LIST, CONST_SS_TYPE_DOCUMENT, 0, array_merge(array('nbTotal' => $pnTotal),$pasValues)), array('ajaxTarget' => 'documents-list', 'nb_result' => $nNbResults), 10, array(10,20,30));

    $sHTML .= $oTemplate->getDisplay($aRowData);

    $paLastDoc = $aLastDoc;

    return $sHTML;
  }

  public function getLastDocuments()
  {
    return $this->_getModel()->getLastDocuments();
  }

  public function getCount($paValues)
  {
    return $this->_getModel()->getCountFromCpValues($paValues);
  }

  /**
   * Return an array with doc details. An array not a CDBresult because we need to buuild DL links here
   * @param type $pnLoginfk
   * @param type $pasCpValues
   * @return array
   */
  public function getDocuments($pnLoginfk, $pasCpValues)
  {
    if(!assert('is_integer($pnLoginfk) && is_array($pasCpValues)'))
      return array();

    if(empty($pnLoginfk))
      $pnLoginfk = $this->_nUserPk;

    $asResult = $this->_getModel()->getDocuments($pnLoginfk, '', $pasCpValues);
    if(empty($asResult))
      return array();

    $oPage = CDependency::getCpPage();
    foreach($asResult as $nDocumentPk => $asData)
    {
      $asResult[$nDocumentPk]['dl_url'] = $oPage->getUrl($this->csUid, CONST_ACTION_SEND, CONST_SS_TYPE_DOCUMENT, $nDocumentPk, array('target' => '_blank'));
      $asResult[$nDocumentPk]['view_url'] = $oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_SS_TYPE_DOCUMENT, $nDocumentPk);
      $asResult[$nDocumentPk]['view_popup_js'] = 'var oConf = goPopup.getConfig();
        oConf.height = 640;
        oConf.width = 880;
        oConf.modal = true;
        goPopup.setLayerFromAjax(oConf, \''.$oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_SS_TYPE_DOCUMENT, $nDocumentPk).'\');';
      $asResult[$nDocumentPk]['icon'] = $this->_getDocumentIcon($asData['mime_type'], $asData['file_name']);
    }

    return $asResult;
  }


  private function _getDocumentIcon($psMimeType, $psFile = '')
  {

    $asTemp = explode('/', $psMimeType);
    $sFileType = (count($asTemp)==2) ? $asTemp[1] : $asTemp[0];

    switch($sFileType)
    {
      //we need to put here the most common/used types
      case 'gif':
      case 'jpeg':
      case 'pdf':
      case 'png':
      case 'vnd.ms-excel':
      case 'msword':
      case 'vnd.ms-powerpoint':
      case 'vnd.oasis.opendocument.text':
      case 'zip':
        return $this->getResourcePath().'pictures/mime/'.$sFileType.'.png'; break;

      case 'vnd.ms-word':
        return $this->getResourcePath().'pictures/mime/msword.png'; break;
    }

    //try to match extension //
    if(!empty($psFile))
    {
      $asFile = pathinfo($psFile);
      if(!isset($asFile['extension']))
        $asFile['extension']='';

      switch($asFile['extension'])
      {
        case '':
          return $this->getResourcePath().'pictures/mime/unknown.png'; break;

        case 'jpg':
          return $this->getResourcePath().'pictures/mime/jpeg.png'; break;

        case 'ods':
          return $this->getResourcePath().'pictures/mime/ooo_spread.png'; break;

        case 'odt':
          return $this->getResourcePath().'pictures/mime/ooo_writer.png'; break;

        case 'odg':
          return $this->getResourcePath().'pictures/mime/ooo_presentation.png'; break;

        case 'docx':
        case 'doc':
          return $this->getResourcePath().'pictures/mime/msword.png'; break;

        case 'xlsx':
        case 'xls':
          return $this->getResourcePath().'pictures/mime/vnd.ms-excel.png'; break;

        case 'csv':
          return $this->getResourcePath().'pictures/mime/csv.png'; break;

        case 'pptx':
        case 'ppt':
          return $this->getResourcePath().'pictures/mime/vnd.ms-powerpoint.png'; break;

        case 'rar':
        case 'tar':
        case 'bz':
        case 'bz2':
        case 'gz':
        case 'ace':
          return $this->getResourcePath().'pictures/mime/rar.png'; break;
      }
    }

    //if still nothing, let's go for standard categories
    switch($psMimeType)
    {
      //to keep first
      case (stripos($psMimeType, '/x-') !== false):
        return $this->getResourcePath().'pictures/mime/zip.png'; break;

      case (stripos($psMimeType, 'vnd.oasis') !== false):
        return $this->getResourcePath().'pictures/mime/openoffice.png'; break;

      case (stripos($psMimeType, 'vnd.ms-') !== false):
      case (stripos($psMimeType, 'vnd.openxmlformats-') !== false):
        return $this->getResourcePath().'pictures/mime/msoffice.png'; break;

      case (stripos($psMimeType, 'audio/') !== false):
        return $this->getResourcePath().'pictures/mime/audio.png'; break;

      case (stripos($psMimeType, 'video/') !== false):
        return $this->getResourcePath().'pictures/mime/video.png'; break;

      case (stripos($psMimeType, 'text/') !== false):
        return $this->getResourcePath().'pictures/mime/text.png'; break;

    }

    return $this->getResourcePath().'pictures/mime/unknown.png';
  }

  /**
   * Function to send the notification email twice in a week about the shared document to them
   * @return boolean value
   */

  private function _getCronDocument()
  {
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oMail = CDependency::getComponentByName('mail');
    $sMailComponent = CDependency::getComponentUidByName('mail');

    $asActiveUsers = $oLogin->getUserList(0, true);
    $asAllUsers = $oLogin->getUserList(0, false, true);
    $asToNotify = array();
    $asDocuments = array();

    $oDocuments = $this->_getModel()->getLastDocuments(14,0,2);

    $bRead = $oDocuments->readFirst();
    if(!$bRead)
      return false;

    while($bRead)
    {
      $asDocData = $oDocuments->getData();

      if((int)$asDocData['private'] == 0)
      {
        $asRecipients = array_keys($asActiveUsers);
        echo "<BR>public<BR>";
      }
      elseif((int)$asDocData['private'] == 2)
      {
        echo "<BR>custom<BR>";
        $asRecipients = array_keys($this->_getModel()->getUsersRightsOnDocument((int)$asDocData['documentpk']));

        //the doc may be shared with users that are now inactive, we need to check and remove those
        foreach($asRecipients as $nKey => $nLoginfk)
        {
          if(!isset($asActiveUsers[$nLoginfk]))
            unset($asRecipients[$nKey]);
        }
      }

      //Check recipients
      //the doc may be shared with users that are now inactive, we need to remove those
      foreach($asRecipients as $nKey => $nLoginfk)
      {
        if(!isset($asActiveUsers[$nLoginfk]))
          unset($asRecipients[$nKey]);
      }

      // Removing users who have already been notified
      $aNotifiedUsers = $this->_getModel()->getNotifiedUsers((int)$oDocuments->getFieldValue('documentpk'));
      $asRecipients = array_diff($asRecipients, $aNotifiedUsers);


      // Set the document list for each user
      foreach($asRecipients as $nLoginfk)
        $asToNotify[$nLoginfk][] = $asDocData['documentpk'];

      if(isset($asAllUsers[$asDocData['creatorfk']]))
        $sCreatorName = $oLogin->getUserNameFromData($asAllUsers[$asDocData['creatorfk']]);
      else
        $sCreatorName = 'unavailable';

      $sContent = '<strong>Created on: </strong> '.$asDocData['date_creation'].' by '.$sCreatorName.'<br />';
      $sContent.= '<strong>Title:</strong> '.$asDocData['title'].'<br />';

      if(!empty($asDocData['description']))
        $sContent.= '<strong>Description:</strong> '.$asDocData['description'].'<br />';

      $sContent.= '<strong>File name:</strong> '.$asDocData['initial_name'].'<br /><br />';
      $asDocuments[$asDocData['documentpk']] = $sContent;

      $bRead = $oDocuments->ReadNext();
    }

    if(!empty($sMailComponent))
    {
      $asLogValues = array();
      $sSharespaceURL = $oPage->getUrl('sharedspace', CONST_ACTION_LIST, CONST_SS_TYPE_DOCUMENT);
      $sLink = '<a href="'.$sSharespaceURL.'">Shared space</a>';
      $nSent = 0;
      $nCount = 0;

      foreach($asToNotify as $nLoginfk => $anDocumentToNotify)
      {
        if(!empty($nLoginfk) && !empty($asActiveUsers[$nLoginfk]))
        {
          $sContent = '<h3>Dear '.$oLogin->getUserNameFromData($asActiveUsers[$nLoginfk]).',</h3><br />';

          if(count($anDocumentToNotify) > 1)
            $sContent.= count($anDocumentToNotify).' documents have been shared with you on '.CONST_APP_NAME.'. You can access your sharedspace by clicking on the followinfg link : '.$sLink.'<br /><br />';
          else
            $sContent.= 'A document has been shared with you on '.CONST_APP_NAME.'. You can access your sharedspace by clicking on the followinfg link: '.$sLink.'<br /><br />';

          $sContent.= " + Document details follow + <br /><br />";

          foreach($anDocumentToNotify as $nDocumentPk)
          {
            $sContent.= '<br />'.$asDocuments[$nDocumentPk].'<hr />';
            $asLogValues['loginfk'][$nCount] = (int)$nLoginfk;
            $asLogValues['documentfk'][$nCount] = (int)$nDocumentPk;
            $nCount++;
          }

          $sContent.= "Enjoy BCM.";
          $oMail->sendRawEmail('BCM Reminder', $asActiveUsers[$nLoginfk]['email'], 'BCM - Notifier: a file has been shared with you.', $sContent);

          $nSent++;
        }
      }

      if(!empty($asLogValues))
      {
        $nNotificationPk = $this->_getModel()->add($asLogValues, 'document_notification');

        if(!is_key($nNotificationPk))
          echo 'Notification log could not be saved in document_notification database<br />';
        else
          echo 'Notification log has been saved<br />';
      }

      echo $nSent.' email(s) have been sent.<br />';
    }

    return true;
  }

  // Returns folders in an array
  private function _getFolders()
  {
    if(empty($this->_aFolders))
    {
      $oFolder = CDependency::getComponentByInterface('manage_folder');
      $aFolders = $oFolder->getFolders('Documents');

      if(isset($aFolders['subfolders']) && !empty($aFolders['subfolders']))
        $this->_aFolders = current($aFolders['subfolders']);
      else
         $this->_aFolders = array();
    }

    return $this->_aFolders;
  }

 /**
  * Function for listing of the documents
  * @return string
  */
  private function _displayList()
  {
    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();

    $oPage->addCssFile($this->getResourcePath().'css/sharedspace.css');
    $oPage->addJsFile($this->getResourcePath().'js/sharedspace.js');

    $this->_aUserList = $oLogin->getUserList(0, false, true);
    $aUserRights = $this->_getModel()->getUserRights($this->_nUserPk);

    $sHTML = $oHTML->getTitleLine('Shared documents', $this->getResourcePath().'/pictures/component.png');

    //Add a link for standard form and fast drag and drop
    $sHTML.= $oHTML->getBlocStart();

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'add_document_block'));
        $sHTML.= $oHTML->getBlocStart();
        $sHTML.= '<br /><br />Add a document using advanced options <br /><br />';
        $sHTML.= $this->displayAddLink();
        $sHTML.= $oHTML->getBlocEnd();
      $sHTML.= $oHTML->getBlocEnd();

      $psAjaxRefreshUrl = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_LIST, CONST_SS_TYPE_DOCUMENT, 0);
      $sHTML.= $oHTML->getBlocStart('', array('class' => 'quickadd_document_block'));
      $sHTML.= $this->_fastUpload('last_documents', $psAjaxRefreshUrl);
      $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getFloatHack();

    $sHTML.= $oHTML->getBlocEnd();


    $sHTML .= $oHTML->getBlocStart('shared-space');

    $sHTML .= $oHTML->getBlocStart('last_documents', array('class' => 'document-list', 'divToRefresh' => 'last_documents', 'ajaxRefreshUrl' => $psAjaxRefreshUrl));
    $sHTML .= $this->_displayLastDocuments($aUserRights);
    $sHTML .= $oHTML->getBlocEnd();
    $sHTML.= $oHTML->getCR(2);

    $sRootFolderContent = $this->_displayFolderContent(0, $aUserRights);
    if(!empty($sRootFolderContent))
    {
      $sHTML .= $oHTML->getBlocStart('doc-folders', array('class' => 'document-list', 'folderpk' => 0));
        $sHTML .= $sRootFolderContent;
      $sHTML .= $oHTML->getBlocEnd();
    }

  //  $sHTML.= $this->_displayFolders($aFolders, $aDocuments, $aUserRights);

    $sHTML .= $oHTML->getBlocEnd();
    return $sHTML;
  }

  private function _displayLastDocuments($paUserRights)
  {
    if(!assert('is_array($paUserRights)'))
      return '';

    $aUserRights = $paUserRights;

    if(empty($aUserRights))
      $aUserRights = $this->_getModel()->getUserRights($this->_nUserPk);

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $sHTML = '';
    $aLastDocuments = $this->_getModel()->getLastDocumentsNotLinked($this->_nUserPk, 5);


    if(!empty($aLastDocuments))
    {
      $sHTML .= $oHTML->getBloc('', 'Last uploads', array('class' => 'list-name'));

      $aDataShortList = array();

      foreach ($aLastDocuments as $aDoc)
        $aDataShortList[]= $this->_getRowData($aDoc, array() , $aUserRights);

      $asData = array('sZoneToRefresh' => 'last_documents', 'sRefreshWithUrl' => $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_LIST, CONST_SS_TYPE_DOCUMENT, 0));
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => array('class' => 'CDocument_row', 'path' => $_SERVER['DOCUMENT_ROOT'].self::getResourcePath().'template/document_row.tpl.class.php5')))));
      $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam, $asData);
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->setRenderingOption('full', 'full', 'full');

      $oConf->addColumn('Title', 'title', array('id' => 'title', 'sortable' => array('javascript' => 1), 'width' => 580));
      $oConf->addColumn('Size', 'file_size', array('id' => 'file_size', 'width' => 60));
      $oConf->addColumn('Details', 'creator', array('id' => 'created_by', 'width' => 175));
      $oConf->addColumn('Actions', 'actions', array('id' => 'actions', 'width' => 300));

      $oConf->setPagerTop(false);
      $oConf->setPagerBottom(false);

      $sHTML .= $oTemplate->getDisplay($aDataShortList);
    }

    return $sHTML;
  }

  private function _displayFolderContent($pnFolderPk = 0, $paUserRights = array())
  {
    if(!assert('is_numeric($pnFolderPk)'))
      return '';

    if(!assert('is_array($paUserRights)'))
      return '';

    $aUserRights = $paUserRights;

    if(empty($aUserRights))
      $aUserRights = $this->_getModel()->getUserRights($this->_nUserPk);

    $oFolder = CDependency::getComponentByInterface('manage_folder');
    if(empty($oFolder))
      return '';

    $nFolderPk = $pnFolderPk;

    if(!is_key($nFolderPk))
      $nFolderPk = $oFolder->getRootFolderPk(array(CONST_CP_UID => $this->getComponentUid(), CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_SS_TYPE_DOCUMENT, CONST_CP_PK => 0));

    if(!is_key($nFolderPk))
      return '';

    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $sHTML = '';
    $aData = array();

    $oThisFolder = $oFolder->getFolder($nFolderPk);
    $aDocuments = $this->_getModel()->getDocumentsFromFolder($nFolderPk, $this->_nUserPk);

    $sHTML .= $oHTML->getBlocStart('folder_'.$nFolderPk, array('class' => 'folder', 'folderpk' => $nFolderPk));
    $sHTML .= $oHTML->getBloc('title_'.$nFolderPk, $oThisFolder->getFieldValue('label'), array('class' => 'foldername'));

    if($oThisFolder->getFieldValue('parentfolderfk') != 0)
    {
      $sAjaxUrl = $oPage->getAjaxUrl('sharedspace', CONST_ACTION_VIEW, CONST_FOLDER_TYPE_FOLDER, $oThisFolder->getFieldValue('parentfolderpk'));
      $sHTML .= $oHTML->getBlocStart();
      $sHTML .= $oHTML->getLink('< Back to parent', $sAjaxUrl, array('class' => 'subfolder', 'ajaxTarget' => 'doc-folders', 'onclick' => '$(\'#fastUploadId input[name=folderfk]\').val(\''.$oThisFolder->getFieldValue('parentfolderpk').'\');'));
      $sHTML .= $oHTML->getBlocEnd();
    }

    //Fetch and prepare the list of subfolders
    $oSubfolders = $oFolder->getSubfolders($nFolderPk);
    $bRead = $oSubfolders->readFirst();
    while($bRead)
    {
      $aData[] = array(
                'nFolderPk' => $oSubfolders->getFieldValue('folderpk'),
                'sSize' => '',  //not used in the template whend idpslaying folder row
                'sType' => 'folder',
                'sDesc' => '',
                'sTitle' => $oSubfolders->getFieldValue('label'),
                'nNbItems' => $this->_getModel()->getCountFromFolder((int)$oSubfolders->getFieldValue('folderpk'), $this->_nUserPk)
                );

      $bRead = $oSubfolders->readNext();
    }


    if(!empty($aDocuments))
    {
      //format the data for every documents
      reset($aDocuments);
      $aDoc = current($aDocuments);
      $nFirstYear = $nCurrentYear = $nYear = date('Y', strtotime($aDoc['date_last_revision']));

      //create the first title row
      $aData[-1] = array('sType' => 'separator', 'year' => $nCurrentYear, 'sTitle' => $nCurrentYear.'\'s documents ');

      foreach($aDocuments as $aDoc)
      {
        $nCurrentYear = date('Y', strtotime($aDoc['date_last_revision']));
        if($nCurrentYear != $nYear)
        {
          $aData[] = array('sType' => 'separator', 'year' => $nCurrentYear, 'sTitle' => $nCurrentYear.'\'s documents ');
          $nYear = $nCurrentYear;
        }

        $aData[] = $this->_getRowData($aDoc, array() , $paUserRights);
      }

      //if only one year is displayed, we remove the firsttitle line
      if($nFirstYear == $nCurrentYear)
        unset($aData[-1]);

      if(!empty($aData))
      {
        $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => array('class' => 'CDocument_row', 'path' => $_SERVER['DOCUMENT_ROOT'].self::getResourcePath().'template/document_row.tpl.class.php5')))));
        $asData = array('sZoneToRefresh' => 'doc-folders', 'sRefreshWithUrl' => $oPage->getAjaxUrl('sharedspace', CONST_ACTION_VIEW, CONST_FOLDER_TYPE_FOLDER, $nFolderPk));
        $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam, $asData);
        $oConf = $oTemplate->getTemplateConfig('CTemplateList');
        $oConf->setRenderingOption('full', 'full', 'full');

        $oConf->addColumn('Title', 'title', array('id' => 'title', 'sortable' => array('javascript' => 1), 'width' => 580));
        $oConf->addColumn('Size', 'file_size', array('id' => 'file_size', 'width' => 70));
        $oConf->addColumn('Details', 'creator', array('id' => 'created_by', 'width' => 175));
        $oConf->addColumn('Actions', 'actions', array('id' => 'actions', 'width' => 300));

        $oConf->setPagerTop(false);
        $oConf->setPagerBottom(false);
        $sHTML .= $oTemplate->getDisplay($aData);
      }
    }
    else
    {
      $sHTML .= 'This folder is empty';
    }

    $sHTML .= $oHTML->getBlocEnd();

    return  $sHTML;
  }

  // Get the data for a document row
  // Returns array that will be given to the row template
  private function _getRowData($paDocs, $paItem = array(), $paUserRights=array(), &$pnItems=0)
  {
    if(!assert('is_array($paDocs)'))
      return array();

    if(!assert('is_array($paItem)'))
      return array();

    if(!assert('is_array($paUserRights)'))
      return array();

    $oHTML = CDependency::getCpHtml();
    $aRow = array();

    if(!empty($paItem)) // Function has been call with item data > it is a folder display
    {
      if(!isset($paDocs[$paItem['itemfk']]))
        return array();

      $aDocument = $paDocs[$paItem['itemfk']];
      $nDocumentPk = $paItem['itemfk'];
    }
    else
    {
      $aDocument = $paDocs;
      $nDocumentPk = $paDocs['documentpk'];
    }

    // Rights managment
    $bIsCreator = ($aDocument['creatorfk'] == $this->_nUserPk);
    $bCanEdit = (isset($paUserRights[$nDocumentPk]) && in_array('edit', $paUserRights[$nDocumentPk]));
    $bIsPublic = ($aDocument['private'] == 0);
    $bIsCustom = ($aDocument['private'] == 2);


    // *************************************************************** //
    // *************************************************************** //
    // *************************************************************** //
    // *************************************************************** //
    // *************************************************************** //
    // *************************************************************** //
    //
    // MAY I HAVE YOUR ATTENTION PLEASE
    //
    // TODO : The following lines are meant to update document_file
    // table with mime_type of document when it is missing in database.
    // It should be deleted someday when the process is over.
    //

    if(empty($aDocument['mime_type']))
    {
      if(is_file($aDocument['file_path']))
      {
        $aDocument['mime_type'] = mime_content_type($aDocument['file_path']);
        $this->_getModel()->update(array('document_filepk' => (int)$aDocument['document_filepk'], 'mime_type' => $aDocument['mime_type']), 'document_file');
      }
    }

    // *************************************************************** //
    // *************************************************************** //

    $sPictureUrl = $this->_getDocumentIcon($aDocument['mime_type'], $aDocument['file_name']);
    if(!file_exists($_SERVER['DOCUMENT_ROOT'].$sPictureUrl))
      $sPictureUrl = CONST_PICTURE_FILE;

    $aRow['nDocumentPk'] = $nDocumentPk;
    $aRow['sPictureUrl'] = $sPictureUrl;
    $aRow['sTitle'] = $aDocument['title'];
    if(empty($aRow['sTitle']))
      $aRow['sTitle'] = $aDocument['initial_name'];

    $aRow['sMime'] = $aDocument['mime_type'];

    $aRow['sDesc'] = '';
    if(!empty($aDocument['description_html']))
      $aRow['sDesc'] = $aDocument['description_html'];
    elseif(!empty($aDocument['description']))
    {
      if($aDocument['description'] != $aDocument['title'])
        $aRow['sDesc'] = $aDocument['description'];
    }

    set_array($aDocument['file_size'], '');
    $aRow['sSize'] = $aDocument['file_size'];

    $sCreatedBy = 'created by '.$this->_aUserList[$aDocument['creatorfk']]['firstname'].$oHTML->getCR(1);
    $sCreatedBy .= 'on '.$aDocument['date_creation'];
    $aRow['sCreatedBy']=$sCreatedBy;
    $aRow['aActions']=array();

    if($this->_rightAdmin || ($this->_rightManager && (($bCanEdit && $bIsCustom) || $bIsPublic || $bIsCreator)))
      $aRow['aActions'][] = 'edit';

    if ($this->_rightAdmin)
      $aRow['aActions'][] = 'delete';

    $aRow['sType'] = 'folderitem';

    $pnItems = 1;


    return $aRow;
  }

  // Adds an option to a select field
  // Used to display folder tree and show children / parents folder
  // @param $poForm : form to update
  // @param $psFieldName : field name to add options to
  // @param $paFolder : array containing folder tree generated by _getFolderTree
  // @param $pnIndent : space showing inheritance
  private function _getSelectOptions($paFolder, $pnIndent = 0)
  {
    if(!assert('is_array($paFolder)') || empty($paFolder))
      return array();

    if(!assert('is_integer($pnIndent)'))
      return array();

    $oHTML = CDependency::getCpHtml();

    $aParams = array('value'=> $paFolder['folderpk'], 'label' => $oHTML->getSpace($pnIndent).$paFolder['label']);
    if(!in_array('add_item', $paFolder['rights']))
      $aParams['disabled']='disabled';

    $aOutpout[$paFolder['folderpk']] = $aParams;

    if(isset($paFolder['content']['subfolders']) && !empty($paFolder['content']['subfolders']))
    {
      foreach($paFolder['content']['subfolders'] as $aFolder)
        $aOutpout = array_merge($aOutpout,$this->_getSelectOptions($aFolder, $pnIndent+1));
    }

    return $aOutpout;
  }


  /**
   * Display the fast upload form including all the JS for drag and drop
   * @param string $psZoneToRefresh
   * @param string $psRefreshWithUrl
   * @param integer $pnFolderFk
   * @return string form html code
   */
   private function _fastUpload($psZoneToRefresh = '', $psRefreshWithUrl = '' , $pnFolderFk = 0)
   {
     if(!assert('is_string($psZoneToRefresh)'))
       return '';

     if(!assert('is_string($psRefreshWithUrl)'))
       return '';

     if(!assert('is_numeric($pnFolderFk)'))
       return '';

     $oPage = CDependency::getCpPage();
     $oHTML = CDependency::getCpHtml();

     $oPage->addJsFile(CONST_PATH_JS.'jquery.knob.js');
     $oPage->addJsFile(CONST_PATH_JS.'jquery.fileupload.js');

     $sHTML = '';
     $sHTML.= $oHTML->getBlocStart('', array('class' => 'fast-upload'));
     $sHTML.= $oHTML->getBloc('', '<ul></ul>', array('class' => 'loading-files'));

     $sURL = $oPage->getAjaxUrl($this->getComponentUid(), CONST_ACTION_SAVEADD, $this->getDefaultType());

     $oForm = $oHTML->initForm('fastUpload');
     $oForm->setFormParams('fastUpload', false, array('action' => $sURL, 'submitLabel'=>'Save Document', 'class' => 'fast-upload-form', 'noCancelButton' => 'noCancelButton'));
     $oForm->setFormDisplayParams(array('noSubmitButton' => true));
     $oForm->addField('input', 'fastupload', array('type' => 'hidden', 'value' => '1'));

     if(!empty($psZoneToRefresh))
       $oForm->addField('input', 'psZoneToRefresh', array('type' => 'hidden', 'value' => $psZoneToRefresh));

     if(!empty($psRefreshWithUrl))
       $oForm->addField('input', 'psRefreshWithUrl', array('type' => 'hidden', 'value' => $psRefreshWithUrl));

     $oForm->addField('misc', 'Browse', array('type' => 'text', 'text'=>'<br/>Quickly upload a new <b>shared</b> document...
       <span class="light italic">(Max '.round(CONST_SS_MAX_DOCUMENT_SIZE/1048576).' MB)</span><br/><br/><br/>
       <span style="color: #2A6991; font-weight: bold;" title="Drag & drop a file from your computer">Drop</span> a file in this box
       &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;or&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
       <a class="actionButton browse-button" id="browse">Browse your files</a>'));

     //100*1024*1024
     $oForm->addField('input', 'document', array('type' => 'file', 'label'=>'Upload New', 'value'=>'', 'multiple' => 'multiple', 'data-sequential-uploads' => 'true'));
     $oForm->setFieldControl('document', array('jsFieldNotEmpty' => ''));


     $oForm->addField('input', 'folderfk', array('type' => 'hidden', 'value'=> $pnFolderFk));
     $oForm->addField('input', 'private', array('type' => 'hidden', 'value'=> 0));

     $sHTML.= $oForm->getDisplay();
     $sHTML.= $oHTML->getBlocEnd();
     return $sHTML;
   }


   /**
    * Display the document details with edit and delete links
    * In safe mode, The file content and description are not displayed because those probably
    * contain badly encoded characters that make json_encode crash
    *
    * @param integer $pnPk
    * @param boolean $pbSafeMode
    * @return array to be encoded in json
   */

   private function _displayDocument($pnPk, $pbSafeMode = false)
   {
    if(!assert('is_key($pnPk) && is_bool($pbSafeMode)'))
      return array('error' => 'Bad parameters.');

    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/sharedspace.css');

    $sHTML = '';
    $oDocument = $this->_getModel()->getDocument($pnPk);
    $bRead = $oDocument->readFirst();
    if(!$bRead)
      return array('error' => 'Record could not be found. This document might have been deleted.');

    $oFolder = CDependency::getComponentByInterface('manage_folder');
    $asFolder = $oFolder->getFolderFromItemFk($pnPk, array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_SS_TYPE_DOCUMENT, CONST_CP_PK => $pnPk));

    $nCreatorFk = $oDocument->getFieldValue('creatorfk');
    $bIsCreator = ($nCreatorFk==$this->_nUserPk);
    $bCanEdit = $this->_getModel()->hasRights($pnPk, $this->_nUserPk, 'edit');
    $bIsPublic = ($oDocument->getFieldValue('private') == 0);
    $bIsCustom = ($oDocument->getFieldValue('private') == 2);

    $bAllowEdit = ($this->_rightAdmin || ($this->_rightManager && (($bCanEdit && $bIsCustom) || $bIsPublic || $bIsCreator)));
    $asUser = $oLogin->getUserDataByPk((int)$oDocument->getFieldValue('creatorfk'));


    //Display Action row based on rights
    $sHTML.= $oHTML->getBlocStart('actions_'.$pnPk, array('class' => 'document_actions', 'style' => 'right:0; position:absolute;'));
      if($bAllowEdit)
      {
        $sEditLink = $oHTML->getLink(' edit document', 'javascript:;', array('onClick' => '$(\'.view_document\').toggle(); $(\'.edit_document\').toggle(); return false;'));
        $sHTML.= $oHTML->getActionButton('', '', CONST_PICTURE_EDIT, array('width' => '100'), $sEditLink);
      }
      if($this->_rightAdmin)
      {
        $sUrlRemoveDoc = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, $this->getDefaultType(), $pnPk);
        $sHTML.= $oHTML->getActionButton(' delete', 'javascript:;', CONST_PICTURE_DELETE, array('onclick' => 'if(window.confirm(\'Are you sure you want to delete this document ?\')){ AjaxRequest(\''.$sUrlRemoveDoc.'\');} '));
      }
    $sHTML.= $oHTML->getBlocEnd();

    if($bAllowEdit)
      $sHTML.= $oHTML->getBloc('edit_'.$pnPk, $this->_displayForm($pnPk), array('class'=>'edit_document', 'style' => 'display:none;'));


    // ------------------------------------
    //Start displaying document details
    $sHTML.= $oHTML->getBlocStart('view_'.$pnPk, array('class'=> 'view_document'));

      //document title
      $sHTML.= $oHTML->getTitle($oDocument->getFieldValue('title'));

      $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetails'));

        $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetailRow'));
        $sHTML.= $oHTML->getBloc('', 'Created by ', array('class'=> 'label'));
        $sHTML.= $oHTML->getBloc('', $oLogin->getUserNameFromData($asUser).' '.$oHTML->getNiceTime($oDocument->getFieldValue('date_creation')), array('class'=> 'data'));
        $sHTML.= $oHTML->getBlocEnd();

        if(!empty($asFolder['label']))
        {
          $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetailRow'));
          $sHTML.= $oHTML->getBloc('', 'Location', array('class'=> 'label'));
          $sHTML.= $oHTML->getBloc('', $asFolder['label'], array('class'=> 'data'));
          $sHTML.= $oHTML->getBlocEnd();
        }

        $sDocSize = $oDocument->getFieldValue('file_size');
        if(empty($sDocSize))
          $sDocSize = ' - ';

        $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetailRow'));
        $sHTML.= $oHTML->getBloc('', 'Size', array('class'=> 'label'));
        $sHTML.= $oHTML->getBloc('', $sDocSize, array('class'=> 'data'));
        $sHTML.= $oHTML->getBlocEnd();

        $sDocType = $oDocument->getFieldValue('doc_type');
        if($sDocType)
        {
          $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetailRow'));
          $sHTML.= $oHTML->getBloc('', 'Document type', array('class'=> 'label'));
          $sHTML.= $oHTML->getBloc('', $sDocType, array('class'=> 'data'));
          $sHTML.= $oHTML->getBlocEnd();
        }


        if($oDocument->getFieldValue('date_creation') != $oDocument->getFieldValue('date_update'))
        {
          $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetailRow'));
          $sHTML.= $oHTML->getBloc('', 'Last revision', array('class'=> 'label'));
          $sHTML.= $oHTML->getBloc('', $oHTML->getNiceTime($oDocument->getFieldValue('date_update')), array('class'=> 'data'));
          $sHTML.= $oHTML->getBlocEnd();
        }

        if($oDocument->getFieldValue('title') != $oDocument->getFieldValue('initial_name'))
        {
          $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetailRow'));
          $sHTML.= $oHTML->getBloc('', 'File name', array('class'=> 'label'));

          if($pbSafeMode)
            $sFilename = $oDocument->getFieldValue('initial_name');
          else
            $sFilename = utf8_encode($oDocument->getFieldValue('initial_name'));

          $sHTML.= $oHTML->getBloc('', $sFilename, array('class'=> 'data'));
          $sHTML.= $oHTML->getBlocEnd();
        }

        $sRevision = $oDocument->getFieldValue('rev_filepk');
        if(!empty($sRevision))
        {
          $asRevision = explode(',', $sRevision);
          $asRevisionFile = explode(',', $oDocument->getFieldValue('rev_file'));
          $asRevisionDate = explode(',', $oDocument->getFieldValue('rev_date'));

          $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetailRow'));
          $sHTML.= $oHTML->getBloc('', 'Revisions'.$oHTML->getCR(count($asRevision)), array('class'=> 'label'));

            $sHTML.= $oHTML->getBlocStart('', array('class'=> 'data'));
            foreach($asRevision as $nKey => $sRevisionPk)
            {
              $sURL = $oPage->getUrl($this->csUid, CONST_ACTION_SEND, $this->getDefaultType(), $pnPk, array('revisionpk' => $sRevisionPk, 'target' => '_blank'));

              if($pbSafeMode)
                $asRevisionFile[$nKey] = utf8_encode($asRevisionFile[$nKey]);

              $sHTML.= $oHTML->getBlocStart('', array('class'=> 'revision_date'));
              $sHTML.= $oHTML->getText($asRevisionDate[$nKey].' :');
              $sHTML.= $oHTML->getBlocEnd();

              $sHTML.= $oHTML->getBlocStart('', array('class'=> 'revison_file'));
              $sHTML.= $oHTML->getLink($asRevisionFile[$nKey], $sURL);
              $sHTML.= $oHTML->getBlocEnd();
            }
            $sHTML.= $oHTML->getBlocEnd();

          $sHTML.= $oHTML->getBlocEnd();
        }

        //Add the download link on the right
        $sHTML.= $oHTML->getBlocStart('', array('style' => 'position: absolute; top: 10px; right: 5px;'));
          $sUrlSend = $oPage->getUrl($this->csUid, CONST_ACTION_SEND, $this->getDefaultType(), $pnPk);
          $sUrlSendPic = $this->getResourcePath().'pictures/download_22.png';
          $sHTML.= $oHTML->getActionButton('download', $sUrlSend, $sUrlSendPic, array('target' => '_blank'));
        $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getFloatHack();
      $sHTML.= $oHTML->getBlocEnd();


      if(!$pbSafeMode)
      {
        $sHTML.= $oHTML->getCR();

        $sDescription = $oDocument->getFieldValue('description');
        $sDescriptionHtml = $oDocument->getFieldValue('description_html');
        $sFileContent = $oDocument->getFieldValue('original');
        $bImage = (bool)(substr($oDocument->getFieldValue('mime_type'), 0, 5) == 'image');

        if(!$bImage && empty($sDescription) && empty($sDescriptionHtml) && empty($sFileContent))
          $sHTML.= $oHTML->getText('no more data about this file.', array('class' => 'light italic'));
        else
        {

          $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetails'));

          if($bImage)
          {
            $sHTML.= $oHTML->getText('Preview');
            $sHTML.= $oHTML->getBlocStart('', array('style' => 'text-align: center; width: 100%;'));
            $sHTML.= '<img src="'.CONST_CRM_DOMAIN.'/common/upload/sharedspace/document/'.$pnPk.'/'.$oDocument->getFieldValue('file_name').'" style="max-width: 800px; max-height: 800px;" />';
          }
          else
          {
            $sHTML.= $oHTML->getBlocStart('', array('class' => 'documentDetailRow', 'style' => 'width: 100%;'));
            $nDescSize = 0;
            if(!empty($sDescriptionHtml))
            {
              $nDescSize = mb_strlen(strip_tags($sDescriptionHtml));
              $sHTML.= $oHTML->getBloc('', 'Description', array('class'=> 'label'));
              $sHTML.= $oHTML->getBloc('', $sDescriptionHtml, array('class'=> 'data'));
            }
            elseif(!empty($sDescription))
            {
              $nDescSize = mb_strlen($sDescription);
              $sHTML.= $oHTML->getBloc('', 'Description', array('class'=> 'label'));
              $sHTML.= $oHTML->getBloc('', '<p>'.$oDocument->getFieldValue('description').'</p>', array('class'=> 'data'));
            }


            if(!empty($sFileContent) && $nDescSize < 100)
            {
              $sEncoding = mb_detect_encoding($sFileContent);
              if(!empty($sEncoding))
              {
                $sFileContent = mb_convert_encoding($sFileContent, 'utf8', $sEncoding);

                $nLength = mb_strlen($sFileContent);
                $sFileContent = mb_strimwidth($sFileContent, 0, 750, ' <b>...</b>');
                if($nLength >= 750)
                  $sFileContent.= '<br/><a href="'.$sUrlSend.'" class="read_more"> [download the file to see more] </a>';

                $sHTML.= $oHTML->getFloatHack();
                $sHTML.= $oHTML->getBloc('', 'File preview', array('class'=> 'label'));
                $sHTML.= $oHTML->getBloc('', '<p>'.$sFileContent.'</p>', array('class'=> 'data fileContent'));
              }
            }
            $sHTML.= $oHTML->getFloatHack();
            $sHTML.= $oHTML->getBlocEnd();

            $sHTML.= $oHTML->getFloatHack();
            $sHTML.= $oHTML->getBlocEnd();
          }
        }
      }

    $sHTML.= $oHTML->getBlocEnd();

    return array('data' => $sHTML);
   }


   public function viewDocument($pnPk)
   {
     if(!assert('is_key($pnPk)'))
       return 'Bad parameters';

     return $this->_sendDocument($pnPk);
   }

   /**
    * Form to add/edit document
    * @return array
    */
  private function _displayForm()
  {
    $oPage = CDependency::getCpPage();
    $oLogin = CDependency::getCpLogin();
    $oFolder = CDependency::getComponentByInterface('manage_folder');
    $oMngList = CDependency::getComponentByName('manageablelist');
    $oHTML = CDependency::getCpHtml();
    $sHTML = '';

    $aDocTypes = $oMngList->getListValues('doctypes');
    if(empty($aDocTypes))
      return 'Before uploading documents you need to define which type of document is allowed to be uploaded. Create or edit the existing manageable list called "doctypes" and fill it with mime types.';

    $oForm = $oHTML->initForm('documentForm');
    //$oForm->addField('hidden', 'MAX_FILE_SIZE', array('value' => CONST_SS_MAX_DOCUMENT_SIZE));

    $nFolderFk = 0;
    $bIsCpValuesInPost = is_cpValuesInPost();

    if(!is_key($this->cnPk))
    {
      $oRevisions = $oDocument = new CDbResult();
      $sURL = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVEADD, $this->getDefaultType());

      //set a default title if passed in parameter
      $oDocument->setFieldValue('title', getValue('document_title'));
      $sTitle = 'Add new document';
      $sLabelFile = 'Select a file';
      if($bIsCpValuesInPost)
      {
        $oForm->addField('input', CONST_CP_UID, array('type' => 'hidden', 'value' => getValue(CONST_CP_UID)));
        $oForm->addField('input', CONST_CP_ACTION, array('type' => 'hidden', 'value' => getValue(CONST_CP_ACTION)));
        $oForm->addField('input', CONST_CP_TYPE, array('type' => 'hidden', 'value' => getValue(CONST_CP_TYPE)));
        $oForm->addField('input', CONST_CP_PK, array('type' => 'hidden', 'value' => getValue(CONST_CP_PK)));

        $oForm->addField('input', 'callback', array('type' => 'hidden', 'value' => getValue('callback')));
      }
    }
    else
    {
      $oDocument = $this->_getModel()->getDocument($this->cnPk);
      $bRead = $oDocument->readFirst();
      if(!$bRead)
        return array('error' => 'The document you asked for could not be reached. It has certainly been deleted');

      if($oDocument->getFieldValue('cp_uid')!=NULL)
        $bIsCpValuesInPost = true;

      $sURL = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_SAVEEDIT, $this->getDefaultType(), $this->cnPk);
      $sTitle = 'Edit document';
      $sLabelFile = 'Add a new revision';

      $asUsersRights = $this->_getModel()->getUsersRightsOnDocument($this->cnPk);
      $oRevisions = $this->_getModel()->getByFk($this->cnPk, 'document_file', 'document', '*', 'date_creation DESC');

      $asFolder = $oFolder->getFolderFromItemFk($this->cnPk, array(CONST_CP_UID=>$this->getComponentUid(), CONST_CP_ACTION=>CONST_ACTION_VIEW, CONST_CP_TYPE => $this->getDefaultType(), CONST_CP_PK=>$this->cnPk));
      $nFolderFk = (int)$asFolder['folderpk'];
    }

    $oPage->addJsFile(CONST_PATH_JS.'jquery.fileupload.js');
    $oPage->addJsFile(self::getResourcePath().'js/sharedspace.js');
    $oForm->setFormParams('documentForm', true, array('action' => $sURL, 'submitLabel'=>'Save Document', 'inajax' => 'inajax', 'class' => 'single-upload-form' , 'noCancelButton' => 'noCancelButton'));
  //  $oForm->setFormDisplayParams(array('noSubmitButton' => true));

    $psZoneToRefresh = getValue('psZoneToRefresh');
    if(!empty($psZoneToRefresh))
      $oForm->addField('input', 'psZoneToRefresh', array('type' => 'hidden', 'value' => $psZoneToRefresh));

    $psRefreshWithUrl = getValue('psRefreshWithUrl');
    if(!empty($psRefreshWithUrl))
      $oForm->addField('input', 'psRefreshWithUrl', array('type' => 'hidden', 'value' => $psRefreshWithUrl));

    $oForm->addField('misc', 'pagetitle', array('type' => 'text', 'text'=> $oHTML->getTitle($sTitle, 'h3', true)));
    $oForm->addField('input', 'fastupload', array('type' => 'hidden', 'value' => '0'));

    $oForm->addField('input', 'title', array('type' => 'text', 'label'=>'Title', 'value' => $oDocument->getFieldValue('title')));
    $oForm->setFieldControl('title', array('jsFieldNotEmpty' => '', 'jsFieldMinSize' => 5, 'jsFieldMaxSize' => 255));

    if($bIsCpValuesInPost)
    {
      $oMngList = CDependency::getComponentByName('manageablelist');
      $asList = $oMngList->getListValues('linked_doc_type');
      if(!empty($asList))
      {
        if(!empty($this->cnPk))
          $sType = $oDocument->getFieldValue('title');
        else
          $sType = 'resume';

        $oForm->addField('select', 'doc_type', array('label' => 'Document type'));
        foreach($asList as $vLabel => $vValue)
        {
          if($sType == $vValue)
            $oForm->addOption('doc_type', array('label' => $vLabel, 'value' => $vValue, 'selected' => 'selected'));
          else
            $oForm->addOption('doc_type', array('label' => $vLabel, 'value' => $vValue));
        }
      }
    }

    $oForm->addSection('sdescription', array('class' => 'descriptionSection'));
    $oForm->addField('textarea', 'description', array('label'=>'Short description', 'value' => $oDocument->getFieldValue('description')));
    $oForm->closeSection();

    $aOptionsDescHtml = array('class' => 'descriptionSection');
    $sDescriptionHtml = $oDocument->getFieldValue('description_html');
    if(empty($sDescriptionHtml))
      $aOptionsDescHtml['style'] = 'display:none;';

    $oForm->addSection('sdescription_html', $aOptionsDescHtml);
    $oForm->addField('textarea', 'description_html', array('label'=>'Long description', 'value' => $sDescriptionHtml, 'isTinymce' => 1));
    $oForm->closeSection();

    $sLinkDesc = '<a id=\'switchDesc\' href=\'javascript:;\' onClick=\'switchDesc(); return false;\'>Hide / Show a rich text description</a>';
    $oForm->addField('misc', 'descLink', array('label' => '&nbsp;', 'type' => 'text', 'text' => $sLinkDesc));
    $oForm->addField('input', 'is_html', array('type' => 'hidden', 'value' => '0', 'id' => 'is_html'));

    if(!$bIsCpValuesInPost)
    {
      // Folder management
      $aFolders = $this->_getFolders();
      $oForm->addField('select', 'folderfk', array('label'=>'Select a folder'));

      $aSelectOptions = $this->_getSelectOptions($aFolders);
      foreach ($aSelectOptions as $aSelectOption)
      {
        if($aSelectOption['value'] == $nFolderFk)
          $aSelectOption['selected'] = 'selected';

        $oForm->addOption('folderfk', $aSelectOption);
      }
    }

    // Files managment
    $sInputText = $sLabelFile.'<p id=\'single-upload-filelist\' style=\'display:none;\'><span></span><a href=\'#\' id=\'removeFile\'>Remove</a></p>';
    //$oForm->addField('misc', '', array('type' => 'text', 'text' => $sInputText));
    $oForm->addSection('single-upload-input');
    $oForm->addField('input', 'document', array('type' => 'file', 'label'=> $sInputText, 'value'=>'', 'class' => 'single-upload-input'));
    $oForm->closeSection();

    $sJsFileUpload = "
      <script>
      var dataToUpload = new Array();
      $('.single-upload-form').fileupload({
          dataType: 'json',
          sequentialUploads : true,
          add: function (e, data)
          {
            var inputText = data.files[0].name;
            fileToUpload = data.files;
            $('#single-upload-filelist > span').text(inputText);
            $('#single-upload-filelist').show();
            $('#single-upload-input').hide();
          }
        });

      $('#removeFile').click(function(){
          $('.single-upload-input').val('');
          $('#single-upload-input').show();
          $('#single-upload-filelist > span').empty();
          $('#single-upload-filelist').hide();

          return false;
      });

      $('.single-upload-form .submitBtnClass').click(function(){
        var dataToUpload = new Array();
        dataToUpload.formData = $('.single-upload-form').serializeArray();
        dataToUpload.files = fileToUpload;
        var jqXHR = $('.single-upload-form').fileupload('send', dataToUpload)
          .success(function (result) {

              if (result.error)
                goPopup.setErrorMessage(result.error, true);

              if (result.notice)
              {
                goPopup.removeByType('layer');
                goPopup.setNotice(result.notice, {delay: 3000}, true, true);
              }

              if (result.action)
                eval(result.action);

          });
        return false;
      });
      </script>
      ";

    $bRead = $oRevisions->readFirst();
    if($bRead)
    {
      $oForm->getField('misc', '', array('type' => 'text', 'text' => 'Versions:'.$oHTML->getCR(1)));

      while($bRead)
      {
        $sURL = $oPage->getUrl($this->_getUid(), CONST_ACTION_SEND, $this->getDefaultType(), $this->cnPk);
        $aTemp = explode('_', $oRevisions->getFieldValue('file_name'));
        if(isset($aTemp[4]))
          $sFileName = $aTemp[4];
        else
          $sFileName = $oRevisions->getFieldValue('file_name');

        $sLabel = $oHTML->getLink($sFileName, $sURL, array('target' => '_blank'));
        $sLabel .= ' - by '.$oLogin->getUserNameFromPk((int)$oRevisions->getFieldValue('creatorfk'));
        $sLabel .= ' - '.$oHTML->getNiceTime($oRevisions->getFieldValue('date_creation'));

        if($oRevisions->getFieldValue('live')==1)
          $oForm->addField('radio', 'live', array('label' => $sLabel, 'value' => $oRevisions->getFieldValue('document_filepk'), 'checked' => 'checked'));
        else
          $oForm->addField('radio', 'live', array('label' => $sLabel, 'value' => $oRevisions->getFieldValue('document_filepk')));

        $bRead = $oRevisions->readNext();
      }
    }

    if(!$bIsCpValuesInPost)
    {
      // Rights managment
      $asUser = $oLogin->getUserList(0,true,false);

      $aCRParams = array('class' => 'custom_right_section');
      if($oDocument->getFieldValue('private')==2)
        $aCRParams['style'] = 'display:block;';

      $aRights = array(
          0 => array('value' => 1, 'label' => 'Private'),
          1 => array('value' => 0, 'label' => 'Public'),
          2 => array('value' => 2, 'label' => 'Custom'));

      $oForm->addField('select', 'private', array('label' => 'Document visibility'));
      foreach ($aRights as $aRight)
      {
        if($aRight['value'] == $oDocument->getFieldValue('private'))
          $aRight['selected'] = 'selected';

        $oForm->addOption('private', $aRight);
      }

      $oForm->addSection('custom_rights', $aCRParams);

        $oForm->addField('select', 'users', array('label' => 'Add user'));
        $oForm->addOption('users', array('value' => 0, 'label' => ' - '));
        foreach ($asUser as $aUser)
        {
          if(isset($asUsersRights[$aUser['loginpk']]))
          {
            $oForm->addOption('users', array('value' => $aUser['loginpk'], 'label' => $aUser['id'], 'style' => 'display:none;'));
            $sDisplay = 'display:inline-block;';
          }
          else
          {
            $oForm->addOption('users', array('value' => $aUser['loginpk'], 'label' => $aUser['id']));
            $sDisplay = 'display:none;';
          }

          $oForm->addSection('user_'.$aUser['loginpk'],array('style' => $sDisplay, 'class' => 'doc_right_section'));
          $oForm->addField('misc', 'name_'.$aUser['loginpk'], array('type' => 'text', 'text' => $aUser['id']));
          $oForm->setFieldDisplayParams('name_'.$aUser['loginpk'], array('style' => 'width: 150px; float: left;'));

          foreach ($this->_aRights as $sRight)
          {
            $aParams = array ('label' => $sRight, 'class' => $sRight.' user_'.$aUser['loginpk'], 'keepinline' => 1);

            if((isset($asUsersRights[$aUser['loginpk']])) && (in_array($sRight, $asUsersRights[$aUser['loginpk']])))
              $aParams['checked']='checked';

            $oForm->addField('checkbox', $aUser['loginpk'].'_'.$sRight, $aParams);
            $oForm->setFieldDisplayParams($aUser['loginpk'].'_'.$sRight, array('style' => 'width: 120px; float: left;'));
          }
          $oForm->addField('misc', 'removeuser_'.$aUser['loginpk'], array('type' => 'text', 'text' => '<a href=\''.$aUser['loginpk'].'\' class=\'remove_user\' >Remove</a>'));
          $oForm->setFieldDisplayParams('removeuser_'.$aUser['loginpk'], array('style' => 'width: 60px; float: left;'));

          $oForm->closeSection();
        }

        $oForm->closeSection();

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

        $oForm->addField('checkbox', 'notify', array('label' => '<em>Send an email to all the user allowed to see the document</em>', 'legend' => 'Notify users'));
    }

    //$oForm->addField('misc', 'Submit', array('type' => 'text', 'text' => '<a href=\'#\' id=\'submitForm\'>Submit</a>'));
    $oForm->addField('misc', 'js_fileupload',
              array('type' => 'text',
                  'text' => $sJsFileUpload
                  )
              );

    $sHTML .= $oForm->getDisplay();

    return $sHTML;
  }


  /**
   * Allow to use the save function below with a file alreay existing/uploaed on the server
   * !! USE posted data for the doc details !!
   * @param string $psFileName
   * @param string $psFilePath
   * @param string $psTitle
   * return string
   */
  public function saveLocalDocument($psFileName, $psFilePath, $psTitle, $psDocType  = '', $pasCpLink = array())
  {
    if(!assert('!empty($psFileName) && !empty($psFilePath) && !empty($psTitle)'))
      return 'Missing parameters';

    if(!assert('empty($pasCpLink) || is_cpValues($pasCpLink)'))
      return 'Wrong parameters';

    if(!file_exists($psFilePath))
      return  'File doesn\'t exist';

    $_FILES = array();
    $_FILES['document']['tmp_name'] = $psFilePath;
    $_FILES['document']['name'] = $psFileName;

    $_POST = array('fastupload' => 0, 'title' => $psTitle, 'private' => 0, 'doc_type' => $psDocType);
    $_POST = array_merge($_POST, $pasCpLink);

    //dump($_POST);
    //dump($_FILES);
    $asReturn = $this->_saveDocument(0, true);

    if(isset($asReturn['error']))
      return $asReturn['error'];

    return '';
  }


  /**
   * Allow to add a "private" document link to an item passed in parameter
   * !! Create and use an array to describe the files, -> posted field names doesnt matter here  except document !!
   * @param array $pasItemLink
   * @return array
   */
  public function quickAddDocument($pasItemLink, $psTitle, $psDescription = '', $pnVisibility = 0)
  {
    if(!assert('!empty($psTitle)'))
      return array( 'error' => __LINE__.' - Missing parameters.');

    if(!assert('empty($pasItemLink) || is_cpValues($pasItemLink)'))
      return array( 'error' => __LINE__.' - Missing parameters.');

    $asDoc = $pasItemLink;
    $asDoc['fast_upload'] = false;
    $asDoc['title'] = $psTitle;
    $asDoc['description'] = $psDescription;
    $asDoc['description_html'] = '';
    $asDoc['private'] = $pnVisibility;
    $asDoc['folderfk'] = 0;
    $asDoc['live'] = 1;
    $asDoc['is_html'] = 0;
    $asDoc['zone_to_refresh'] = 0;
    $asDoc['refresh_url'] = '';
    $asDoc['notify'] = 0;
    $asDoc['has_cp_link'] = $pasItemLink;
    $asDoc['doc_type'] = '';

    return $this->_saveDocument(0, false, $asDoc);
  }



  /**
   * Function to save the uploaded document
   * @param integer $pnPk
   * @return redirection to the another page/error message if halted
  */
  private function _saveDocument($pnPk = 0, $pbExternalFile = false, $pasDocData = array())
  {
    if(!assert('is_integer($pnPk)'))
      return array( 'error' => __LINE__.' - Can\'t save the document: bad parameters.');

    if(!empty($pasDocData))
    {
      $asDocument = $pasDocData;
    }
    else
    {
      $asDocument['fast_upload'] = (bool)getValue('fastupload');
      $asDocument['title'] = getValue('title');
      $asDocument['doc_type'] = getValue('doc_type');
      $asDocument['description'] = getValue('doc_description');
      $asDocument['description_html'] = getValue('description_html');
      $asDocument['private'] = (int)getValue('private', 0);
      $asDocument['folderfk'] = (int)getValue('folderfk');
      $asDocument['live'] = (int)getValue('live');
      $asDocument['is_html'] = (int)getValue('is_html');
      $asDocument['zone_to_refresh'] = getValue('psZoneToRefresh');
      $asDocument['refresh_url'] = getValue('psRefreshWithUrl');
      $asDocument['callback'] = getValue('callback');
      $asDocument['notify'] = (bool)getValue('notify', false);
      $asDocument['has_cp_link'] = is_cpValuesInPost();

      $asDocument[CONST_CP_UID] = getValue(CONST_CP_UID);
      $asDocument[CONST_CP_ACTION] = getValue(CONST_CP_ACTION);
      $asDocument[CONST_CP_TYPE] = getValue(CONST_CP_TYPE);
      $asDocument[CONST_CP_PK] = (int)getValue(CONST_CP_PK);
    }

    if(empty($asDocument['title']) && !$asDocument['fast_upload'])
      return array( 'error' => __LINE__.' - Document title is required.');

    $oLogin = CDependency::getCpLogin();
    $nUserPk = $oLogin->getUserPk();
    $dToday = date('Y-m-d H:i:s');

    // Editing document main table
    $aData = array('title' => $asDocument['title'], 'doc_type' => $asDocument['doc_type'], 'private' => $asDocument['private'], 'description' => $asDocument['description'], 'date_update' => $dToday);
    if($asDocument['is_html'] == 1)
      $aData['description_html'] = $asDocument['description_html'];

    if(empty($pnPk))
    {
      if(!isset($_FILES) || !isset($_FILES['document']) || !isset($_FILES['document']['tmp_name']))
        return array( 'error' => __LINE__.' - Please choose a file to upload.');

      if($asDocument['fast_upload'])
        $asDocument['title'] = $aData['title'] = $_FILES['document']['name'];

      $aData['creatorfk'] = $nUserPk;
      $aData['date_creation'] = $dToday;
      $nDocPk = $this->_getModel()->add($aData, 'document');
      $bUpdated = false;

      if(!is_key($nDocPk))
        return array( 'error' => 'Document could not be created in database. Please contact your administrator.');
    }
    else
    {
      $aData['documentpk'] = $pnPk;
      $nDocPk = $pnPk;

      $bUpdated = $this->_getModel()->update($aData, 'document');

      if(!$bUpdated)
        return array( 'error' => 'Document could not be edited. Please contact your administrator.');

      $oDocument = $this->_getModel()->getByFk($pnPk, 'document_link', 'document');
      $asDocument['has_cp_link'] = $oDocument->readFirst();
    }

    // Uploading the file
    if(isset($_FILES['document']['name']) && !empty($_FILES['document']['name']))
    {
      if(!empty($_FILES['document']['error']))
      {
        if($_FILES['document']['error'] == UPLOAD_ERR_INI_SIZE || $_FILES['document']['error'] == UPLOAD_ERR_FORM_SIZE)
          return array('error' => 'The file is too big. Max size is '.round((CONST_SS_MAX_DOCUMENT_SIZE/(1024*1024))).' MBytes.');

        if($_FILES['document']['error'] == UPLOAD_ERR_NO_FILE || $_FILES['document']['error'] == UPLOAD_ERR_PARTIAL)
          return array('error' => 'An error occured during transfer. The file hasn\'t been fully received.');

        return array('error' => $_FILES['document']['error'].' - An error occured. Please contact the administrator.');
      }

      if(empty($_FILES['document']['tmp_name']))
        return array('error' => 'The file hasn\'t been uploaded.');


      $asFileData = lstat($_FILES['document']['tmp_name']);
      if(empty($asFileData))
        return array('error' => __LINE__.' - Can not fetch file data.');

      if($asFileData['size'] > CONST_SS_MAX_DOCUMENT_SIZE)
        return array('error' => __LINE__.' - The file is too big. Max size is '.round((CONST_SS_MAX_DOCUMENT_SIZE/(1024*1024))).' MBytes.');


      $sFileName = preg_replace("/[^a-z0-9\.]/", "", strtolower($_FILES['document']['name']));
      $sTmpFileName = $_FILES['document']['tmp_name'];

      $oFinfo = finfo_open(FILEINFO_MIME_TYPE);
      $sMimeType = finfo_file($oFinfo, $sTmpFileName);
      $asFile = pathinfo($_FILES['document']['name']);

      if($sMimeType == 'application/zip' && isset($this->casMsZipExtension[$asFile['extension']]))
      {
        $sMimeType = $this->casMsZipExtension[$asFile['extension']];
      }

      //dump($sMimeType);
      //dump($asFile['extension']);
      $oMngList = CDependency::getComponentByName('manageablelist');
      $aDocTypes = $oMngList->getListValues('doctypes');
      //dump($aDocTypes);

      if(!in_array($sMimeType, $aDocTypes))
        return array( 'error' => 'The format of the file =[ '.$_FILES['document']['name'].' ]= you\'ve uploaded ('.$sMimeType.') is not supported. ['.implode('<br />', $aDocTypes).']');

      //$sExtension = strtolower(substr(strrchr($_FILES['document']['name'], '.'), 1));
      if(!isset($asFile['extension']) || empty($asFile['extension']))
      {
        $asMime = array_flip($aDocTypes);
        return array('error' => 'The file =[ '.$_FILES['document']['name'].' ]= doesn\'t have any extension. Please specify one and upload it again.<br /><br />
          Suggested document type: '.$asMime[$sMimeType].'');
      }



      $sNewPath = $_SERVER['DOCUMENT_ROOT'].CONST_PATH_UPLOAD_DIR.'sharedspace/document/'.$nDocPk.'/';
      $sNewName = date('YmdHis').'_'.$nUserPk.'_'.uniqid('doc'.$nDocPk.'_').'_'.$sFileName;

      if(!is_dir($sNewPath) && !makePath($sNewPath))
        return array( 'error' => __LINE__.' - Destination folder doesn\'t exist.('.$sNewPath.')');

      if(!is_writable($sNewPath))
        return array( 'error' => __LINE__.' - Can\'t write in the destination folder.');

      //move_uploaded_file only accept files uplaoded through php.
      //we need an alternative when files are already there
      if($pbExternalFile)
      {
        if(!rename($sTmpFileName, $sNewPath.$sNewName))
          return array( 'error' => __LINE__.' - Couldn\'t move the uploaded file. ['.$sTmpFileName.'|||'.$sNewPath.$sNewName.']');
      }
      else
      {
        if(!move_uploaded_file($sTmpFileName, $sNewPath.$sNewName))
          return array( 'error' => __LINE__.' - Couldn\'t move the uploaded file. ['.$sTmpFileName.'|||'.$sNewPath.$sNewName.']');
      }

      $nFileSize = filesize($sNewPath.$sNewName);
      $sUnit = 'B';

      //use 1000 instead of 1024 to not display 1008.16B, 1015.01Kb
      if($nFileSize > 1000000000)  //1024*1024*1024
      {
        $sUnit = 'GB';
        $nFileSize = $nFileSize / 1000000000;
      }
      elseif($nFileSize > 1000000)  //1024*1024
      {
        $sUnit = 'MB';
        $nFileSize = $nFileSize / 1000000;
      }
      elseif($nFileSize >= 1000)
      {
        $sUnit = 'KB';
        $nFileSize = $nFileSize / 1000;
      }

      $bUnset = $this->_getModel()->unsetLiveDocument($nDocPk);
      if(!$bUnset)
        return  array( 'error' => __LINE__.' - Couldn\'t archive previous file. Please contact the administrator.');

      // Editing document file table
      $aDataFile = array(
          'documentfk' => $nDocPk,
          'initial_name' => $_FILES['document']['name'],
          'file_name' => $sNewName,
          'file_path' => $sNewPath.$sNewName,
          'file_size' => round($nFileSize, 2).$sUnit,
          'creatorfk' => $nUserPk,
          'mime_type' => $sMimeType,
          'date_creation' => $dToday,
          'live' => 1
          );

      $aParsedDocument = $this->_parseDocument($sNewPath.$sNewName);
      $aDataFile['original'] = $aParsedDocument['text'];
      $aDataFile['compressed'] = $aParsedDocument['fulltext'];
      $aDataFile['language'] = $aParsedDocument['language'];

      $nDocFilePk = $this->_getModel()->add($aDataFile, 'document_file');

      if(!is_key($nDocFilePk))
        return array( 'error' => 'Could not create the file in database. Please contact the administrator.');
    }
    else
    {
      $bSetLive = $this->_getModel()->setFileLive($asDocument['live'], $this->cnPk);
      if(!$bSetLive)
        return  array( 'error' => __LINE__.' - An error occured while saving the file. Please contact the administrator.');
    }

    $nFolderFk = $asDocument['folderfk'];
    if(!$asDocument['has_cp_link'])
    {
      // Editing rights table
      $this->_getModel()->deleteByFk($nDocPk, 'document_rights', 'document');

      if($aData['private'] == 2)
      {
        $asUser = $oLogin->getUserList(0,true,false);
        $asNotify = array();

        $aDataRights = array();
        foreach ($asUser as $aUser)
        {
          foreach ($this->_aRights as $sRight)
          {
            $sField = $aUser['loginpk'].'_'.$sRight;
            if ((isset($_POST[$sField])) && ($_POST[$sField]=='on'))
            {
              $aDataRights['documentfk'][] = $nDocPk;
              $aDataRights['loginfk'][] = (int)$aUser['loginpk'];
              $aDataRights['rights'][] = $sRight;

              if($sRight == 'notify')
                $asNotify[$aUser['loginpk']] = $aUser['loginpk'];
            }
          }
        }

        if(!empty($aDataRights))
        {
          $nPk = $this->_getModel()->add($aDataRights, 'document_rights');
          if($nPk == 0)
            return array( 'error' => 'Could not insert document rights. Please contact your admnistrator.');
        }
      }

      // Linking the document to a folder
      $oFolder = CDependency::getComponentByInterface('manage_folder');
      if(!is_key($nFolderFk))
      {
        $nFolderFk = $oFolder->getRootFolderPk(array(CONST_CP_UID => $this->getComponentUid(), CONST_CP_TYPE => CONST_SS_TYPE_DOCUMENT,
                    CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_PK => 0));
      }

      $bMoved = $oFolder->moveItemToFolder($nDocPk, $nFolderFk, 'Documents', $asDocument['title']);
      if(!$bMoved)
        return array( 'error' => 'Could not move the document to the asked folder. Please contact your admnistrator.');
    }
    else
    {
      if(!is_key($this->cnPk))
      {
        // Linking the document to a page
        $aCpValues = array(
            'documentfk' => $nDocPk,
            CONST_CP_UID => $asDocument[CONST_CP_UID],
            CONST_CP_TYPE => $asDocument[CONST_CP_TYPE],
            CONST_CP_ACTION => $asDocument[CONST_CP_ACTION],
            CONST_CP_PK => $asDocument[CONST_CP_PK]
        );

        $nLinkPk = $this->_getModel()->add($aCpValues, 'document_link');
        if(!$nLinkPk)
          return array('error' => 'Could not save link values. Please contact your administrator.');

      }
    }

    // Notify users
    if(($aData['private'] == 2) && (!empty($asNotify)))
    {
      if(isset($asNotify[$nUserPk]))
        unlink($asNotify[$nUserPk]);
      $this->_notifyUsers($nDocPk, $asNotify);
    }

    if($asDocument['notify'] && $aData['private']==0)
    {
      $asNotify = $oLogin->getUserList(0, true, false);
      if(isset($asNotify[$nUserPk]))
        unset($asNotify[$nUserPk]);

      $this->_notifyUsers($nDocPk, $asNotify);
    }


    //Everything went well, log history, manage message and actions
    $aOutput = array('notice' => $asDocument['title'].' has been saved.', 'action' => '');

    if($bUpdated)
    {
      $oDbResult = $this->_getModel()->getByWhere('document_link', 'documentfk = '.$pnPk);
      $bRead = $oDbResult->readFirst();
      if($bRead)
      {
        $asLink = $oDbResult->getData();
        $this->_getModel()->_logChanges($asLink, 'document', 'document updated.', '', $asLink);
      }
    }
    else
      $this->_getModel()->_logChanges($aCpValues, 'document', 'new document added. ['.$asDocument['title'].']', '', $aCpValues);

    if(is_key($nFolderFk))
    {
      $oPage = CDependency::getCpPage();
      $sAxUrl = $oPage->getAjaxUrl($this->getComponentUid(), CONST_ACTION_VIEW, CONST_FOLDER_TYPE_FOLDER, $nFolderFk);
      $aOutput['action'].='if ($(\'#doc-folders\').html().length!=0) { AjaxRequest(\''.$sAxUrl.'\', \'body\', \'\', \'doc-folders\', \'\', \'\', \'\'); } goPopup.removeByType(\'layer\');';
    }

    if((!empty($asDocument['zone_to_refresh'])) && (!empty($asDocument['refresh_url'])))
    {
      $aOutput['action'].='AjaxRequest(\''.$asDocument['refresh_url'].'\', \'\', \'\', \''.$asDocument['zone_to_refresh'].'\');';
    }

    if(!empty($asDocument['callback']))
    {
      $aOutput['action'].= $asDocument['callback'];
    }

    //if action called, we close the edit popup
    if(empty($asDocument['callback']))
    {
      $aOutput['action'].= 'var oPopup = $(\'#documentFormId\').closest(\'.ui-dialog-content\'); goPopup.remove(oPopup); ';
    }

    return $aOutput;
  }



  /**
   * Function to send notification about the uploaded document to shared users
   * @param integer $pVisibility
   * @param integer $pnPk
   * @return boolean
   */
  private function _notifyUsers($pnPk, $paRecipients=array())
  {
    if(!assert('is_integer($pnPk) && !empty($pnPk)'))
      return false;

    if(!assert('is_array($paRecipients)'))
      return false;

    $oDocument = $this->_getModel()->getDocument($pnPk);
    $bRead = $oDocument->readFirst();

    if(empty($bRead))
      return false;

    $oMail = CDependency::getComponentByName('mail');
    $oHTML = CDependency::getCpHtml();
    $oLogin = CDependency::getCpLogin();
    $oPage = CDependency::getCpPage();
    $oMailComponent = CDependency::getComponentUidByName('mail');

    if(empty($paRecipients))
    {
      if($oDocument->getFieldValue('private')==1)
        return false;

      if($oDocument->getFieldValue('private')==0)
      {
        $paRecipients=$oLogin->getUserList(0, true, false);
        unlink($paRecipients[$oDocument->getFieldValue('creatorfk')]);
      }

      if($oDocument->getFieldValue('private')==2)
      {
        $paRights=$this->_getModel()->getUsersRightsOnDocument($pnPk);
        foreach ($paRights as $nUserPk => $aRights)
        {
          if(in_array('notify', $aRights))
            $paRecipients[]=$nUserPk;
        }
      }
    }

    $asUsers = $oLogin->getUserList(0, false, true);

    $sTitle = $oDocument->getFieldValue('title');
    $sDescription = $oDocument->getFieldValue('description');
    $sFileName = $oDocument->getFieldValue('file_name');
    $sUrl = $oPage->getUrl('sharedspace', CONST_ACTION_SEND, CONST_SS_TYPE_DOCUMENT, (int)$pnPk);
    $sFileLink = $oHTML->getLink($sFileName,$sUrl);

    $sUrlList = $oPage->getUrl('sharedspace', CONST_ACTION_LIST, CONST_SS_TYPE_DOCUMENT);
    if(!empty($oMailComponent))
    {
      foreach($paRecipients as $nUserPk => $aUserData)
      {
        if(!empty($asUsers[$nUserPk]['status']))
        {
          $sEmail = $asUsers[$nUserPk]['email'];
          $sContent = "<h3>Dear ".$asUsers[$nUserPk]['firstname'].",</h3><br /><br />";
          $sContent.= "A document has been shared with you on the new CRM. You can access your shared space by clicking on the followinfg link:<br /><br />";
            $sContent.= "<a href='".$sUrlList."'>Shared documents</a><br /><br />";
            $sContent.= "<strong>File informations:</strong><br /><br />";
            $sContent.= '<strong>Title:</strong> '.$sTitle.'<br />';
          $sContent.= '<strong>Description:</strong> '.$sDescription.'<br />';
          $sContent.= '<strong>File(s) name:</strong> '.$sFileLink.'<br /><br />';
          $sContent.= "Cheers. ";

          if(!CONST_DEV_SERVER)
            $oMail->sendRawEmail('info@bcm.com',$sEmail, "BCM Notifier: A new file has been shared with you.", $sContent);
        }
      }
    }
    return true;
  }

  /**
   * Function to remove the document
   * @param integer $pnPk
   * @return array
   */
  private function _removeDocument($pnPk = 0)
  {
    if(!assert('is_key($pnPk)'))
      return array('error' => 'Invalid Pk. Please contact your administrator.');

    $oDocument = $this->_getModel()->getDocument($pnPk);
    $oFolder = CDependency::getComponentByInterface('manage_folder');
    $psZoneToRefresh = getValue('psZoneToRefresh', '');
    $psRefreshWithUrl = getValue('psRefreshWithUrl', '');

    $bRead = $oDocument->readFirst();
    if(!$bRead)
      return array('error' => 'No document found. It might have been removed already');

    $this->_getModel()->deleteByFk($pnPk, 'document_link', 'document');
    $this->_getModel()->deleteByFk($pnPk, 'document_rights', 'document');
    $oFolder->deleteItem($pnPk, array(CONST_CP_UID=>$this->getComponentUid(), CONST_CP_ACTION=> CONST_ACTION_VIEW, CONST_CP_TYPE=> $this->getDefaultType(), CONST_CP_PK=>$pnPk));

    $sAttchFolderPath = $_SERVER['DOCUMENT_ROOT'].CONST_PATH_UPLOAD_DIR.'sharedspace/document/'.$pnPk;

    $sCommandLine = escapeshellcmd('rm -R ').escapeshellarg($sAttchFolderPath);
    $sLastLine = exec(escapeshellcmd($sCommandLine), $asCmdResult, $nCmdResult);

    if(!empty($sLastLine))
      return array('error' => __LINE__.' - An error occured, can\'t delete the project #'.$pnPk);

    $this->_getModel()->deleteByFk($pnPk, 'document_file', 'document');
    $this->_getModel()->deleteByPk($pnPk, 'document');

    $sActions = '';
    if((!empty($psZoneToRefresh)) && (!empty($psRefreshWithUrl)))
      $sActions = "AjaxRequest('".urldecode($psRefreshWithUrl)."', 'body', '', '".$psZoneToRefresh."', '', '', '');";
    else
      $sActions = " goPopup.removeLastByType('layer');";

    return array('notice' => 'The file has been deleted.', 'action' => $sActions);
 }

 /**
  * Function to download the document
  * @param integer $pnPk
  * @return string
  */

  private function _sendDocument($pnPk)
  {
    if(!assert('is_integer($pnPk) && !empty($pnPk)'))
      return 'No document found. It might have been removed already';

    $nRevisionPk = (int)getValue('revisionpk');

    //Get the main/last file or one of the revisions
    if(empty($nRevisionPk))
      $oResult = $this->_getModel()->getByWhere('document_file', 'documentfk='.$pnPk.' AND live = 1');
    else
      $oResult = $this->_getModel()->getByWhere('document_file', 'document_filepk = '.$nRevisionPk);

    $bRead = $oResult->readFirst();

    if(!$bRead)
      return 'No document found. It might have been removed already';


    if(!$this->_rightAdmin)
    {
      $bHasRight = $this->_getModel()->hasRights($pnPk, $this->_nUserPk, 'read');
      if(!$bHasRight)
        return 'Sorry. You were not allowed by the document creator to download it.';
    }

    $sFilePath = $oResult->getFieldValue('file_path');
    $sFileName = $oResult->getFieldValue('initial_name');

    // Must be fresh start
    if(headers_sent())
      exit(__LINE__.' Headers already sent');

    // Required for some browsers
    if(ini_get('zlib.output_compression'))
      ini_set('zlib.output_compression', 'Off');

    // File Exists?
    if(!file_exists($sFilePath) )
      exit(__LINE__.' File doesn\'t exist on the server');

    $sMimeType = $oResult->getFieldValue('mime_type');
    $sFileSize = filesize($sFilePath);

    header("Pragma: public"); // required
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false); // required for certain browsers
    header("Content-Type: ".$sMimeType);
    header("Content-Disposition: attachment; filename=\"".$sFileName."\";" );
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".$sFileSize);
    ob_clean();
    flush();
    readfile($sFilePath);

    $this->_getModel()->increment($pnPk, 'document', 'downloads');
    $aData = array(
        'documentfk' => (int)$pnPk,
        'document_filefk' => (int)$oResult->getFieldValue('document_filepk'),
        'loginfk' => (int)$this->_nUserPk
    );

    $nDocumentLogPk = $this->_getModel()->add($aData, 'document_log');
    if(!assert('is_key($nDocumentLogPk)'))
      return 'Download could not be loged.';

    exit();
  }

  public function setLanguage($psLanguage)
  {
    require_once('language/language.inc.php5');
    if(isset($gasLang[$psLanguage]))
      $this->casText = $gasLang[$psLanguage];
    else
      $this->casText = $gasLang[CONST_DEFAULT_LANGUAGE];
  }

/**
 * Read and convert the file $psFilePath into txt format and check/convert its encoding
 * If it contains japanese characters, it will parse the file looking for usefull data and return a string with space separated words
 * @param array $pasFileData (at least filepk and filepath)
 * return String
 *
 */
public function getDocumentContent($psFilePath, $pbFTSOptimized)
{
  return $this->_parseDocument($psFilePath, $pbFTSOptimized);
}

private function _parseDocument($psFilePath, $pbFTSOptimized = true)
{
  if(!assert('!empty($psFilePath)'))
    return array('text' => '', 'fulltext' => '');

  if(!is_file($psFilePath) || !is_readable($psFilePath))
  {
    assert('false; // impossible to parse file - not readable');
    return array('text' => '', 'fulltext' => '');
  }

  $sFileContent = $this->_getFileContent($psFilePath);
  //echo 'Converted<br /><br /></hr>'.$sFileContent.'<hr />';


  $sEncoding = mb_detect_encoding($sFileContent);
  //If we can't get the character encoding, we abort the process to not store crap in the db
  if(!$sEncoding)
  {
    assert('false; /* can\'t identify the document encoding :/ */');
    return array('text' => '', 'fulltext' => '');
  }

  $sEncoding = strtoupper($sEncoding);
  if($sEncoding != 'UTF8' && $sEncoding != 'UTF-8')
    $sFileContent = mb_convert_encoding($sFileContent, 'UTF-8', $sEncoding);


  $sLanguage = getTextLangType($sFileContent);

  //save the original text version
  $asResult = array('text' => $sFileContent, 'fulltext' => '', 'language' => $sLanguage);
  $sFileContent = strip_tags($sFileContent);
  $sFileContent = html_entity_decode($sFileContent);
  /*dump($sEncoding);
  dump($sLanguage);
  dump($sFileContent);*/

  //if(isCJK($sFileContent))
  if($sLanguage != 'en')
  {
    $sFileContent = $this->tokenizeCjk($sFileContent, $pbFTSOptimized);
  }
  else
  {
    if($pbFTSOptimized)
    {
      $sFileContent = $this->getFtsString($sFileContent);
    }
    else
      $sFileContent = $sFileContent;
  }

  $asResult['fulltext'] = $sFileContent;
  return $asResult;
}



  /**
   * @param type $psFilePath
   * @param type $psConvertTo
   * @return array
   */
  private function _getFileContent($psFilePath, $psConvertTo = '')
  {
    if(!assert('!empty($psFilePath)'))
      return '';

    if(!in_array($psConvertTo, array('', 'txt', 'html')))
      return '';

    $sCommandLine = '';
    $sSaveDir = $_SERVER['DOCUMENT_ROOT'].'/tmp/';

    $asFile = pathinfo($psFilePath);
    if(!isset($asFile['extension']) || !isset($asFile['filename']) || empty($asFile['extension']) || empty($asFile['filename']))
    {
      assert('false; // '.__LINE__.' - file incorrect : '.$psFilePath);
      return '';
    }

    if(!file_exists($psFilePath))
    {
      assert('false; // '.__LINE__.' - file does not existe ext: '.$asFile['extension'].' ext: '.$asFile['filename']);
      return '';
    }

    //change target extension base on psConverTo
    if($psConvertTo == 'html')
      $sSaveFile = 'ABI_'.str_replace(' ', '', $asFile['filename']).'.html';
    else
      $sSaveFile = 'ABI_'.str_replace(' ', '', $asFile['filename']).'.txt';

    //should be useless since the filename is previously cleaned
    $psFilePath = str_replace(' ', '\\ ', $psFilePath);
    $bAbiword = $bTika = false;

    switch(strtolower($asFile['extension']))
    {
      case 'txt':
        return file_get_contents($psFilePath);
        break;

      case 'html':
      case 'xhtml':
      case 'dhtml':
      case 'xml':
        if($psConvertTo == 'html')
          return file_get_contents($psFilePath);
        else
          return strip_tags(file_get_contents($psFilePath));
        break;

      /*case 'doc':
      case 'docx':
      case 'odt':
          $bAbiword = true;
          $sCommandLine = '/usr/bin/./timeout -s KILL  15s abiword --to="txt" --to-name="'.$sSaveDir.$sSaveFile.'" "'.$psFilePath.'"';
        break;
      */
      //Abi word can't create files with new formats of pdf
      //pdftotext can't read it, but still create the file...
      //http://www.cyberciti.biz/faq/converter-pdf-files-to-text-format-command/
      // -layout to keep format, -f 5 -l 10 => page 5 to 10,
      case 'pdf':
          $sCommandLine = '/usr/bin/./timeout -s KILL 15s /usr/bin/pdftotext -eol unix "'.$psFilePath.'" "'.$sSaveDir.$sSaveFile.'"  ';
       break;

      default:
        $bTika = true;
        $sCommandLine = '/usr/bin/./timeout -s KILL 15s /usr/bin/java -jar '.$_SERVER['DOCUMENT_ROOT'].'/apps/tika/tika-app-1.5.jar --text "'.$psFilePath.'" > "'.$sSaveDir.$sSaveFile.'" ';
        break;
    }

    $sLastLine = exec($sCommandLine, $asCmdResult, $nCmdResult);

    if($nCmdResult != 0)
    {
      /*//can try abiword ?
      dump($nCmdResult);
      dump($sCommandLine);
      dump($asCmdResult);
      dump($sLastLine);*/
      assert('false; /* couldn\'t convert file.['.$nCmdResult.'] - ['.addslashes(htmlspecialchars($sCommandLine.' // '.$sLastLine.' // '.  var_export($asCmdResult,  true))).'] */');
      @unlink($sSaveDir.$sSaveFile);
      return '';
    }


    if(!file_exists($sSaveDir.$sSaveFile) || !is_readable($sSaveDir.$sSaveFile))
    {
      assert('false; // No txt file found  or not readable  ['.$sSaveDir.$sSaveFile.']');
      @unlink($sSaveDir.$sSaveFile);
      return '';
    }

    $asFileData = lstat($sSaveDir.$sSaveFile);
    if(empty($asFileData))
    {
      assert('false; // can not get converted file stats.');
      @unlink($sSaveDir.$sSaveFile);
      return '';
    }

    $sContent = '';
    if($asFileData['size'] > CONST_SS_MAX_PROCESSABLE_SIZE)
    {
      assert('false; // parsed content exceed processable capacities  ['.$sSaveDir.$sSaveFile.':  '.$asFileData['size'].' > '.CONST_SS_MAX_PROCESSABLE_SIZE.']');

      //big file generated, will read until we reach the CONST_SS_MAX_PROCESSABLE_SIZE
      $oFs = fopen($sSaveDir.$sSaveFile, 'r');
      if($oFs)
      {
        $sContent = fread($oFs, CONST_SS_MAX_PROCESSABLE_SIZE);
        fclose($oFs);
      }
    }
    else
    {
      //read all the file at once
      $sContent = file_get_contents($sSaveDir.$sSaveFile);
    }

    unlink($sSaveDir.$sSaveFile);
    return $sContent;
  }


  /**
   * Parse and clean non cjk content to optimize for fulltext search
   * @param string $psContent
   * @return string
   */
  function getFtsString($psContent)
  {
    //TODO: Dictionary ? remove small words first ?

    $asData = $this->_fetchDataFromContent($psContent);
    $psContent = $asData['content'];

    //echo 'remove [^a-z0-9 @-_#]<br />';
    $psContent = preg_replace('/[^a-z0-9 @\-_#]/i', ' ', $psContent);
    $psContent = str_replace(array("\r\n", "\r", "\n"), ' ', $psContent);
    $psContent = preg_replace('/[ ]{2,}/i', ' ', $psContent);
    //dump($psContent);

    //last space is important for strickly equal searches
    return $asData['word_list'].' '.$psContent.' ';
  }


  /**
   * Only for CJK texts
   * Parse the mecab output to remove useless words
   * We have to take over mysql fulltext index that won't be abel to remove empty words in japanese
   *
   * Tried to keep the weirdly encoded strings identified as unknown (romanji letters but not [A-Za-z] :-/ )
   *
   * @param string $psContent
   * @param string $psUnknow
   * @return string
   */
  private function _getFtsCJKString($psContent, $psUnknow = '-')
  {
    if(empty($psContent))
      return '';

    //List of meaningful word types we need to keep
    //$asToKeep = array($psUnknown, '名詞', '動詞' , '副詞', '接頭詞');
    $asToRemove = array('記号', '助詞', '特殊');
    $asResult = array();

    $asFile = explode("\n", $psContent);
    foreach($asFile as $sLine)
    {
      if(!empty($sLine) && $sLine != 'EOS')
      {
        $asLine = explode("\t", $sLine);
        if(count($asLine) != 2)
        {
          assert('false; // mecab tokenized file is incorrect ['.$sLine.']');
        }
        else
        {
          $asLine[0] = trim($asLine[0]);

          //remove CJK that are not "letters"
          $asLine[0] = preg_replace('/[\x{3000}-\x{3040}\x{309B}\x{309C}\x{30FB}]/u', '', $asLine[0]);

          //since we don't get a proper date treatment when analysing the doc (see _fetchDataFromContent() ) , we're removing it
          $asLine[0] = preg_replace('/(\d{2,4} ?年 ?\d{2} ?月 ?\d{2} ?日)|(\d{2,4} ?年 ?\d{2} ?月)/', '', $asLine[0]);
          $asLine[0] = preg_replace('#(\d{2}[-/\.]\d{2}[-/\.]\d{4})|(\d{4}[-/\.]\d{2}[-/\.]\d{2})#u', '', $asLine[0]);

          //remove a few useless words and 4 digit numbers
          $asLine[0] = preg_replace('/^(平成|昭和|大正|明治|年|月|日|の|その|この|それ|これ|\d{0,4})$/u', '', $asLine[0]);

          $asData = explode(',', $asLine[1]);

          if(!empty($asLine[0]))
          {
            // Unknow means it's a non-CJK character. We filter manually
            if($asData[0] == $psUnknow)
            {

              //TODO: could try to log when numbers are following each others, to identify dates
              //keep numbers (:/), string with mb_length >=8 (probably a weird word i can sort out) and string containing at least 2 letters
              $nNumber = preg_match('/^[0-9]{5,}$/u', $asLine[0]);
              $nRomanji = preg_match('/[A-Za-z]{3,}|[A-Za-z0-9]{4,}/u', $asLine[0]);
              $nCjk = preg_match('/[\x{30A0}-\x{30FF}\x{FF65}-\x{FF9D}]{3,}/u', $asLine[0]);

              if(mb_strlen($asLine[0]) >= 8 || $nNumber || (mb_strlen($asLine[0]) > 1 && ($nRomanji || $nCjk)))
              {
                $asResult[] = $asLine[0];
                //echo '+++++++++++++++++++++++++++ accept unknow: |'.$asLine[0].'| (nb:'.mb_strlen($asLine[0]).', nb:'.$nNumber.', rom:'.$nRomanji.', cjk:'.$nCjk.')<br />';
              }
              /*else
              {
                echo '--------------------------------------- ignore unknow: |'.$asLine[0].'| ('.mb_strlen($asLine[0]).', nb: '.$nNumber.', '.$nRomanji.', '.$nCjk.')<br />';
              }*/
            }
            elseif(!in_array($asData[0], $asToRemove))
            {
              $asResult[] = $asLine[0];
              //echo 'accept ['.$asData[0].']: '.$asLine[0].'<br />';
            }
          }
        }
      }
    }

    return implode(' ', $asResult);
  }


  /**
   * List usefull/important terms from the original content (will be "broken" once parsed in japanese)
   * Looking for emails & dates & phone numbers ... can be improved
   *
   * Once we've got all those, we remove the terms from original content so it's not indexed
   *
   * @param type $psContent
   * @return array list string
   */
  private function _fetchDataFromContent($psContent)
  {


    $sPart1 = '[\s]*([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*([ ]+|)@{1,2}([ ]+|)[ a-zA-Z0-9-]+\.+[a-zA-Z]{2,})[\s]*';
    $sPart2 = '(\b[A-Z0-9._%+-]+@{1,2}[A-Z0-9.-]+\.[A-Z]{2,4}\b)';
    $sPattern = '/('.$sPart1.')|('.$sPart2.')/u';
    //$sPattern = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/u';

    $asMatches = array();
    $asResult = array();
    preg_match_all($sPattern, $psContent, $asMatches);
    $asResult = $asMatches[0];


    //Looking for phone/fax numbers
    preg_match_all('#(0\d{10})|([\(]{0,1}0\d{2}[- \(\)]{0,5}\d{4}[- \(\)]{0,5}\d{4}[\)]{0,1})#u', $psContent, $asMatches);

    //convert all the phone numbers format to XXX-XXXX-XXXX to help the search
    if(count($asMatches[0]))
    {
      $asFormated = preg_replace('#[^0-9]#u', '', $asMatches[0]);
      $asResult = array_merge($asFormated, $asResult);
    }

    preg_match_all('#(\d{2} \d{2} \d{2} \d{2} \d{2} \d{2})#u', $psContent, $asMatches);
    $asResult = array_merge($asMatches[0], $asResult);

    //Looking for urls
    preg_match_all('#(http(s)?://|www\.|http(s)?://www\.)[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?#iu', $psContent, $asMatches);
    $asResult = array_merge($asMatches[0], $asResult);

    $asResult = array_unique($asResult);
    $psContent = str_ireplace($asResult, '', $psContent);

    return array('word_list' => implode(' ', $asResult), 'content' => $psContent);
  }


  public function tokenizeCjk($psFileContent, $pbFTSOptimized = false)
  {
    // check in the original string to get usefull data (phone, emails, urls...)
    // use the original content because it will be broken after being tokenized
    $asContentData = $this->_fetchDataFromContent($psFileContent);
    $sFileContent = $asContentData['content'];


    $asToRemove = array(chr(13).chr(10), chr(13), chr(10), chr(0), chr(1), chr(2), chr(3), chr(4), chr(5), chr(6), chr(7)
            , chr(8), chr(9), chr(11), chr(12), chr(14), chr(15), chr(16), chr(17), chr(18), chr(19), chr(29), chr(21)
            , chr(22), chr(23), chr(24), chr(25), chr(26), chr(27), chr(28), chr(29), chr(30), chr(31));
    $sFileContent = str_replace($asToRemove , ' ', $sFileContent)."\n";

    $bUseFile = true;

    //----------------------------------------------------------
    //----------------------------------------------------------
    // Quick version without creating a file.
    // A bit more risky since the command has more chances to crash
    if(mb_strlen($sFileContent) < 500)
    {
      if($pbFTSOptimized)
        $sOutput = '';
      else
        $sOutput = '-Owakati';

      $sFileContent = str_replace(array('<', '>', '|', '`', '"', '\''), ' ', $sFileContent);


      $sCmd = 'echo "'.$sFileContent.'" | mecab --unk-feature "-" '.$sOutput.'; ';
      $sLastLine = exec($sCmd, $asCmdResult, $nCmdResult);
      usleep(100000);

      if(empty($asCmdResult) || $nCmdResult > 0)
      {
        $sConvertedFileContent = '';
        assert('false; // error converting file');
      }
      else
      {
        if($pbFTSOptimized)
          $sConvertedFileContent = $this->_getFtsCJKString(implode("\n", $asCmdResult));
        else
          $sConvertedFileContent = implode(' ', $asCmdResult);

        $bUseFile = false;
      }
    }

    if($bUseFile)
    {
      //for bigger files, we create a file to pass to mecab plugin
      $sFilename = uniqid('cjk_file_').'.txt';
      $sFilepath = $_SERVER['DOCUMENT_ROOT'].'/tmp/'.$sFilename;

      $sConvertedFilename = uniqid('cjk_file_converted_').'.txt';
      $sConvertedFilepath = $_SERVER['DOCUMENT_ROOT'].'/tmp/'.$sConvertedFilename;

      //create a file with the clened content. File to be passed to mecab to be tokenized
      $oFs = fopen($sFilepath, 'w+');
      if(!$oFs)
      {
        assert('false; // can not create cjk conversion file');
        return array('text' => '', 'fulltext' => '');
      }

      $asFileToClean[] = $sFilepath;

      fwrite($oFs, $sFileContent);
      fclose($oFs);
      usleep(100000);

      if(!is_readable($sFilepath))
      {
        assert('false; /* can\'t read the CJK file ['.$sFilepath.'] */');
      }
      else
      {
        $asFileToClean[] = $sConvertedFilepath;
        $sConvertedFileContent = '';

        if($pbFTSOptimized)
          $sOutput = '';
        else
          $sOutput = '-Owakati';

        //$sCmd = '/usr/bin/./timeout -s KILL 15s /usr/local/bin/mecab --unk-feature "unknown"  -Owakati -r '.escapeshellarg($sFilepath).' -o '.escapeshellarg($sConvertedFilepath);
        $sCmd = 'mecab --unk-feature "-" '.$sOutput.' -o '.escapeshellarg($sConvertedFilepath).' < '.escapeshellarg($sFilepath).'; chown www-data: '.escapeshellarg($sConvertedFilepath).'; ';

        $sLastLine = exec($sCmd, $asCmdResult, $nCmdResult);
        usleep(100000);

        if($nCmdResult != 0)
        {
          $sErrorPath = $_SERVER['DOCUMENT_ROOT'].'/tmp/error_'.$sConvertedFilename;
          assert('false; /* mecab error: impossible to tokenize japanese text.['.addslashes($sCmd).'].'."\n".
                  'File saved here: '.$sErrorPath.' */');
          rename($sConvertedFilename, $sErrorPath);
        }
        else
        {
          if(!is_file($sConvertedFilepath))
          {
            assert('false; /* Converted file not found.['.$nCmdResult.' - '.$sConvertedFilepath.'] */');
          }
          else
          {
            $sConvertedFileContent = file_get_contents($sConvertedFilepath);
            if($pbFTSOptimized)
            {
              $sConvertedFileContent = $this->_getFtsCJKString($sConvertedFileContent);
            }
          }
        }
      }
    }

    //stick the usefull words fetched at the beginning into the result
    $sFileContent = $asContentData['word_list'].' '.$sConvertedFileContent.' .';

    if(!empty($asFileToClean))
    {
      $sCmd = '';
      foreach($asFileToClean as $sFilePath)
      {
         $sCmd.= ' rm -f '.escapeshellarg($sFilePath).'; ';
      }

      $sLastLine = exec($sCmd, $asCmdResult, $nCmdResult);
    }

    return $sFileContent;
  }


}

