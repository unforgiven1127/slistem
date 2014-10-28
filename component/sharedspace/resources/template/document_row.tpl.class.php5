<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/component/display/resources/class/template/template.tpl.class.php5');

class CDocument_row extends CTemplate
{

  public function __construct(&$poTplManager, $psUid, $pasParams, $pnTemplateNumber)
  {
    $this->csTplType = 'row';
    $this->casTplToLoad = array();
    $this->casTplToProvide = array();

    parent::__construct($poTplManager, $psUid, $pasParams, $pnTemplateNumber);
  }

  public function getTemplateType()
  {
    return $this->csTplType;
  }

  public function getRequiredFeatures()
  {
    return array('to_load' => $this->casTplToLoad, 'to_provide' => $this->casTplToProvide);
  }

  public function getDisplay($pasData, $pasField, $pasColumnParam = array())
  {
    $oPage = CDependency::getCpPage();
    $oHTML = CDependency::getCpHtml();

    $sHTML = '';
    $asAction = array();

    if($pasData['sType'] == 'folderitem')
    {
      $sUrl = $oPage->getAjaxUrl('sharedspace', CONST_ACTION_VIEW, CONST_SS_TYPE_DOCUMENT, $pasData['nDocumentPk']);
      $sAjaxView = 'var oConf = goPopup.getConfig();
                    oConf.height = 640;
                    oConf.width = 880;
                    oConf.modal = true;
                    goPopup.setLayerFromAjax(oConf, \''.$sUrl.'\'); ';

      $sHTML.= $oHTML->getBlocStart('row_'.$pasData['nDocumentPk'], array('class' => 'folderitem row_'.$pasData['nDocumentPk']));

        $sTitle = $oHTML->getPicture($pasData['sPictureUrl'], $pasData['sMime'], '', array('width' => '32px')).' '.mb_strimwidth($pasData['sTitle'], 0, 55, '...');
        $sClass = $pasColumnParam[0]['tag'];

        if(!empty($pasData['sDesc']))
        {
          $sTitle.= '<br /><span class="short_desc">'.mb_strimwidth($pasData['sDesc'], 0, 100, '...').'</span>';
          $sClass.= ' with_description';
        }

        $sHTML.= $oHTML->getBloc('title_'.$pasData['nDocumentPk'], $sTitle, array('title' => $pasData['sTitle'], 'class' => $sClass.' list-name', 'onClick' => $sAjaxView));


        $sClass = $pasColumnParam[1]['tag'];
        $sHTML.= $oHTML->getBloc('', $pasData['sSize'].'&nbsp;', array('class' => $sClass.' file_size'));

        $sClass = $pasColumnParam[2]['tag'];
        $sHTML.= $oHTML->getBloc('created_by_'.$pasData['nDocumentPk'], $pasData['sCreatedBy'], array('class' => $sClass.' creator', 'onClick' => $sAjaxView));

        $sClass = $pasColumnParam[3]['tag'];
        $sHTML.= $oHTML->getBlocStart('', array('class' => 'row-actions '.$sClass));

          $sUrlSend = $oPage->getUrl('sharedspace', CONST_ACTION_SEND, CONST_SS_TYPE_DOCUMENT, $pasData['nDocumentPk']);
          $sUrlSendPic = '/component/sharedspace/resources/pictures/download_22.png';
          $sHTML .= $oHTML->getActionButton('download', $sUrlSend, $sUrlSendPic);

          if(in_array('edit', $pasData['aActions']))
          {
            $sUrlEdit = $oPage->getAjaxUrl('sharedspace', CONST_ACTION_EDIT, CONST_SS_TYPE_DOCUMENT, $pasData['nDocumentPk']);

           /*  $sHTML .= $oHTML->getActionButton('edit', $sUrlEdit, CONST_PICTURE_EDIT, array('ajaxLayer' => 1));*/
            $sAjax = 'var oConf = goPopup.getConfig();
                        oConf.height = 640;
                        oConf.width = 880;
                        oConf.modal = true;
                        goPopup.setLayerFromAjax(oConf, \''.$sUrlEdit.'\'); ';
            $asAction[] = array('label' => ' edit', 'pic' => CONST_PICTURE_EDIT, 'url' => '', 'onclick' => $sAjax);
          }

          if(in_array('delete', $pasData['aActions']))
          {
            $sUrlRemoveDoc = $oPage->getAjaxUrl('sharedspace', CONST_ACTION_DELETE, CONST_SS_TYPE_DOCUMENT, $pasData['nDocumentPk']);

            $oTemplate = $this->coTplManager;
            $aParams = $oTemplate->getData();

            $sActions = 'if(window.confirm(\'Are you sure you want to delete this document ?\')){ ';
            if((isset($aParams['data']['sZoneToRefresh'])) && (isset($aParams['data']['sRefreshWithUrl'])))
              $sActions.= "AjaxRequest('".$sUrlRemoveDoc."&psRefreshWithUrl=".  urlencode($aParams['data']['sRefreshWithUrl'])."&psZoneToRefresh=".$aParams['data']['sZoneToRefresh']."');";
            else
              $sActions.= "AjaxRequest('".$sUrlRemoveDoc."'); ";

            $sActions.= ' } ';

            $asAction[] = array('label' => ' delete', 'pic' => CONST_PICTURE_DELETE, 'url' => 'javascript:;', 'onclick' => $sActions);
          }

          if(!empty($asAction))
            $sHTML.= $oHTML->getActionButtons($asAction, 1, 'Manage...', array('width' => '150', 'class' => ''));

        $sHTML .= $oHTML->getBlocEnd();
      $sHTML .= $oHTML->getBlocEnd();
    }
    elseif($pasData['sType'] == 'folder')
    {
      $sAjaxUrl = $oPage->getAjaxUrl('sharedspace', CONST_ACTION_VIEW, CONST_FOLDER_TYPE_FOLDER, $pasData['nFolderPk']);
      $sHTML .= $oHTML->getBlocStart('subfolder_'.$pasData['nFolderPk'], array('class' => 'subfolder'));
        $sHTML .= $oHTML->getLink($oHTML->getPicture(CONST_PICTURE_FOLDER).' '.$pasData['sTitle'].' ('.$pasData['nNbItems'].')', $sAjaxUrl, array('class' => 'subfolder', 'ajaxTarget' => 'doc-folders', 'ajaxLoadingScreen' => 'body', 'onclick' => '$(\'#fastUploadId input[name=folderfk]\').val(\''.$pasData['nFolderPk'].'\');'));
      $sHTML .= $oHTML->getBlocEnd();
    }
    elseif($pasData['sType'] == 'separator')
    {
      $sHTML .= $oHTML->getBlocStart('', array('class' => 'subfolder list_title'));
      $sHTML .= $oHTML->getText($pasData['sTitle']);
      $sHTML .= $oHTML->getBlocEnd();
    }

    return $sHTML;
  }
}