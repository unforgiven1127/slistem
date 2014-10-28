<?php

require_once('component/manageablelist/manageablelist.class.php5');

class CManageablelistEx extends CManageablelist
{
  private $_userPk;
  private $_rightAdmin;

  public function __construct()
  {

    $oLogin = CDependency::getCpLogin();
    $this->_userPk = $oLogin->getUserPk();
    $this->_rightAdmin = $oLogin->isAdmin();

    return true;
  }

  public function getDefaultType()
  {
    return CONST_TYPE_MANAGEABLELIST;
  }

  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $asActions = array();
    return $asActions;
  }

  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csAction)
    {
      case CONST_ACTION_LIST:
        return json_encode($this->_displayList());
        break;
      case CONST_ACTION_SAVEADD :
       return json_encode($this->_saveAdd());
        break;
      case CONST_ACTION_SAVEEDIT :
       return json_encode($this->_saveEdit());
        break;
      case CONST_ACTION_DELETE :
        return json_encode($this->_delete());
        break;
      case CONST_ACTION_ADD:
      case CONST_ACTION_EDIT:
        $oPage = CDependency::getCpPage();
        return json_encode($oPage->getAjaxExtraContent($this->_displayForm(true)));
         break;
    }
  }

  private function _delete()
  {

    $this->_getModel()->deleteByFk($this->cnPk, 'manageable_list_item', 'manageable_list');
    $this->_getModel()->deleteByPk($this->cnPk, 'manageable_list');

    return array('notice' => 'List deleted successfully.', 'reload' => 1);
  }

  private function _saveAdd()
  {
    $nPk = $this->_getModel()->add(
            array (
                'shortname' => getValue('shortname'),
                'label' => getValue('mnllabel'),
                'description' => getValue('description'),
                'item_type' => getValue('item_type'),
                CONST_CP_UID => getValue(CONST_CP_UID),
                CONST_CP_ACTION => getValue(CONST_CP_ACTION),
                CONST_CP_TYPE => getValue(CONST_CP_TYPE),
                CONST_CP_PK => getValue(CONST_CP_PK),
                'manageable_listpk' => (int)getValue('manageable_listpk', 0)
                ),
            'manageable_list'
            );

    if($nPk==0)
      return array('error' => 'Error when adding manageable list.');

    $avValues = array();
    if(isset($_POST['value']))
    {
      for($nCount=0; $nCount < count($_POST['value']); $nCount++)
      {
        $avValues['label'][]=$_POST['label'][$nCount];
        $avValues['value'][]=$_POST['value'][$nCount];
        $avValues['manageable_listfk'][]=(int)$nPk;
      }
    }

    if(!empty($avValues))
    {
      $nPk = $this->_getModel()->add($avValues,'manageable_list_item');
      if($nPk==0)
        return array('error' => 'Error when adding manageable list items.');
    }

    return array('notice' => 'List created successfully.', 'reload' => 1);
  }

  private function _saveEdit()
  {
    $bUpdated = $this->_getModel()->update(
            array (
                'shortname' => getValue('shortname'),
                'label' => getValue('mnllabel'),
                'description' => getValue('description'),
                'item_type' => getValue('item_type'),
                CONST_CP_UID => getValue(CONST_CP_UID),
                CONST_CP_ACTION => getValue(CONST_CP_ACTION),
                CONST_CP_TYPE => getValue(CONST_CP_TYPE),
                CONST_CP_PK => getValue(CONST_CP_PK),
                'manageable_listpk' => (int)getValue('manageable_listpk')
                ),
            'manageable_list'
            );

    if(!$bUpdated)
      return array('error' => 'Error during manageable list update.');

    $this->_getModel()->deleteByFk((int)$_POST['manageable_listpk'],'manageable_list_item','manageable_list');

    $avValues = array();
    if(isset($_POST['value']))
    {
      for($nCount=0; $nCount < count($_POST['value']); $nCount++)
      {
        $avValues['label'][]=$_POST['label'][$nCount];
        $avValues['value'][]=$_POST['value'][$nCount];
        $avValues['manageable_listfk'][]=(int)$_POST['manageable_listpk'];
      }
    }

    for($nCount=0; $nCount<=getValue('count'); $nCount++)
    {
      if(isset($_POST['value-'.$nCount]))
      {
        $avValues['label'][]=$_POST['label-'.$nCount];
        $avValues['value'][]=$_POST['value-'.$nCount];
        $avValues['manageable_listfk'][]=(int)$_POST['manageable_listpk'];
      }
    }

    if(!empty($avValues))
    {
      $nPk = $this->_getModel()->add($avValues,'manageable_list_item');
      if($nPk==0)
        return array('error' => 'Error when adding manageable list items.');
    }

    return array('notice' => 'List updated successfully.');
  }

  private function _displayForm($paAjax = false)
  {
    $sHTML = '';
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile(self::getResourcePath().'css/manageablelist.css');
    $oPage->addJsFile(self::getResourcePath().'js/add.js');

    $oForm = $oHTML->initForm('mnlForm');

    // Setting different values if it's an edition or addition form
    if((!empty($this->cnPk)) && ($this->cnPk != 0))
    {
      $oDbResult = $this->_getModel()->getByPk($this->cnPk, 'manageable_list');
      if(!$oDbResult->readFirst())
        return $oHTML->getBlocMessage(__LINE__.' - The list doesn\'t exist.');

      $sURL = $oPage->getAjaxUrl($this->csUid,CONST_ACTION_SAVEEDIT);
      $oForm->setFormParams('mnlForm', true, array('action' => $sURL, 'submitLabel'=>'Save Changes'));
      $oForm->addField('input', 'manageable_listpk', array('type' => 'hidden','value'=> $this->cnPk));
    }
    else
    {
      $oDbResult = new CDbResult();
      $sURL = $oPage->getAjaxUrl($this->csUid,CONST_ACTION_SAVEADD);
      $oForm->setFormParams('mnlForm', true, array('action' => $sURL, 'submitLabel'=>'Save List'));
    }

    $oForm->setFormDisplayParams(array('noCancelButton' => 'noCancelButton'));

    $aFieldParam = array('style' => 'width: 150px; margin-right:10px;');

    $oForm->addField('input', 'mnllabel', array('type' => 'text', 'label'=>'Label', 'value' => $oDbResult->getFieldValue('label')));
    $oForm->addField('input', 'shortname', array('type' => 'text', 'label'=>'Short name', 'value' => $oDbResult->getFieldValue('shortname')));
    $oForm->addField('input', 'item_type', array('type' => 'text', 'label'=>'Item type', 'value' => $oDbResult->getFieldValue('item_type')));
    $oForm->addField('textarea', 'description', array('label'=>'Description', 'value' => $oDbResult->getFieldValue('description')));

    $oForm->addSection('cpvalues', array('keepInLine' => 1, 'class' => 'mngListCpSection'));
      $oForm->addField('input', CONST_CP_UID, array('type' => 'text', 'label' => CONST_CP_UID, 'value' => $oDbResult->getFieldValue(CONST_CP_UID)));
      $oForm->addField('input', CONST_CP_ACTION, array('type' => 'text', 'label' => CONST_CP_ACTION, 'value' => $oDbResult->getFieldValue(CONST_CP_ACTION)));
      $oForm->addField('input', CONST_CP_TYPE, array('type' => 'text', 'label' => CONST_CP_TYPE, 'value' => $oDbResult->getFieldValue(CONST_CP_TYPE)));
      $oForm->addField('input', CONST_CP_PK, array('type' => 'text', 'label' => CONST_CP_PK, 'value' => $oDbResult->getFieldValue(CONST_CP_PK)));
      $oForm->setFieldDisplayParams(CONST_CP_UID, $aFieldParam);
      $oForm->setFieldDisplayParams(CONST_CP_ACTION, $aFieldParam);
      $oForm->setFieldDisplayParams(CONST_CP_TYPE, $aFieldParam);
      $oForm->setFieldDisplayParams(CONST_CP_PK, $aFieldParam);
    $oForm->closeSection('');


    if($oDbResult->getFieldValue('shortname') != '')
      $aListValues = $this->getListValues($oDbResult->getFieldValue('shortname'));

      $oForm->addField('misc','', array('type' => 'text', 'text' => '<br />Options (label / valeur)'));

      $nCount = 0;
      $aFieldParam = array('style' => 'width: 170px; margin-right:10px;');

      $oForm->addSection('list_detail', array('id' => 'list_detail'));

      if(!empty($aListValues))
      {

        foreach ($aListValues as $sLabel => $Value)
        {
          $oForm->addSection('section'.$nCount, array('id' => uniqid(), 'class' => 'mngListRowSection'));
          $oForm->addField('input', 'label-'.$nCount, array('type' => 'hidden', 'value' => $sLabel));
          $oForm->addField('input', 'value-'.$nCount, array('type' => 'hidden', 'value' => $Value, 'keepNextInline' => 1));
          $oForm->addField('misc', 'label-show-'.$nCount, array('type' => 'text', 'text' => $sLabel, 'keepNextInline' => 1));
          $oForm->addField('misc', 'value-show-'.$nCount, array('type' => 'text', 'text' => $Value, 'keepNextInline' => 1));
          $oForm->setFieldDisplayParams('label-show-'.$nCount, $aFieldParam);
          $oForm->setFieldDisplayParams('value-show-'.$nCount, $aFieldParam);

          // Delete button
          $oForm->addField('misc', 'link-'.$nCount, array('text' => '<a href=\'#\' class=\'deletedetail\' onclick=\'$(this).parent().parent().parent().remove(); return false;\'><img src='.CONST_PICTURE_DELETE.'></a>', 'type' => 'text', 'keepNextInline' => 1));
          $oForm->setFieldDisplayParams('link-'.$nCount, array( 'style' => 'width:40px;'));
          $oForm->closeSection('');

          $nCount++;
        }
      }

      $oForm->addField('input', 'count', array('type' => 'hidden', 'value' => $nCount));

      $oForm->closeSection('');

    $aFieldParam = array('style' => 'width: 335px; margin-right:10px;');
    $oForm->addSection('sectionadd', array('id' => 'duplicate', 'class' => 'mngListNewRow'));

      $oForm->addField('input', 'labeladd', array('id' => 'labeladd', 'label' => 'Label', 'type' => 'text', 'value' => '', 'keepNextInline' => 1));
      $oForm->addField('input', 'valueadd', array('id' => 'valueadd', 'label' => 'Value', 'type' => 'text', 'value' => '', 'keepNextInline' => 1));
      $oForm->setFieldDisplayParams('labeladd', $aFieldParam);
      $oForm->setFieldDisplayParams('valueadd', $aFieldParam);

      $sLabelAddButton = $oHTML->getPicture(CONST_PICTURE_ADD, 'Add option').' Add';
      $sAddButton = $oHTML->getCR().$oHTML->getLink($sLabelAddButton,'javascript:;',array('style' => 'float:left; display:block;', 'onclick' => 'addMnlListOption();'));
      // '<br /><a id=\'add\' href=\'#\' style=\'float:left; display:block;\'><img src='.CONST_PICTURE_ADD.'>Add</a>';
      $oForm->addField('misc', 'linkadd', array('text' => $sAddButton, 'type' => 'text', 'keepNextInline' => 1));
      $oForm->setFieldDisplayParams('linkadd', array('style' => 'float:left; display:block; width:75px;'));

    $oForm->closeSection();

    $sHTML .= $oForm->getDisplay();

    if($paAjax)
      return $oPage->getAjaxExtraContent(array('data'=>$sHTML));
    else
      return $sHTML;
  }

  private function _displayList()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $sHTML = '';

    // Add button
    $sUrlAdd = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, $this->getDefaultType(), 0);
    $sAjaxAdd = $oHTML->getAjaxPopupJS($sUrlAdd, 'body','','600','860',1);
    $sHTML.= $oHTML->getActionButton('Add manageable list', '', CONST_PICTURE_ADD, array('onclick' => $sAjaxAdd));
    $sHTML.= $oHTML->getCR(2);

    $oLists = $this->_getModel()->getList('manageable_list');
    $bRead = $oLists->readFirst();
    if(!$bRead)
      $sHTML.= 'No manageable list in system.';
    else
    {
      $asMngList = array();
      $nCount = 0;
      while($bRead)
      {
        $nPk = $oLists->getFieldValue('manageable_listpk');

        $asMngList[$nCount]['shortname'] = $oHTML->getText($oLists->getFieldValue('shortname'));
        $asMngList[$nCount]['description'] = $oHTML->getText($oLists->getFieldValue('description'));


        $sURLEdit = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_TYPE_MANAGEABLELIST, $nPk);
        $sPicEdit= $oHTML->getPicture(CONST_PICTURE_EDIT);
        $sAjaxEdit = $oHTML->getAjaxPopupJS($sURLEdit, 'body', true, '600', '860', 1);
        $sHTMLAction = ' '.$oHTML->getLink($sPicEdit, 'javascript:;', array('onclick' => $sAjaxEdit));

        $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_TYPE_MANAGEABLELIST, $nPk);
        $sPic= $oHTML->getPicture(CONST_PICTURE_DELETE);
        $sHTMLAction.= ' '.$oHTML->getLink($sPic, $sURL, array('onclick' => 'if(!window.confirm(\'You are about to permanently delete this manageable list. \\nDo you really want to proceed ?\')){ return false; }'));

        $asMngList[$nCount]['action'] = $sHTMLAction;

        $bRead = $oLists->readNext();
        $nCount++;
      }

      //initialize the template
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
      $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam);

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->setRenderingOption('full', 'full', 'full');

      $oConf->setPagerTop(false);
      $oConf->setPagerBottom(false);

      $oConf->addColumn('List shortname', 'shortname', array('width' => '21%', 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Description', 'description', array('width' => '67%'));
      $oConf->addColumn('Actions', 'action', array('width' => '10%'));

      $sHTML .= $oTemplate->getDisplay($asMngList);
    }

    return $oPage->getAjaxExtraContent(array('data'=>$sHTML));
  }

  public function getListValues($psShortname = '', $pbSort = true)
  {
    if(!assert('is_string($psShortname)'))
      return array();

    $aResult = $this->_getModel()->getManageableList($psShortname);
    $asList = array();

    if(!empty($psShortname))
    {
      if(!empty($aResult[$psShortname]))
        $asList = $aResult[$psShortname];
      else
        $asList = array();
    }
    else
      $asList = $aResult;

    if($pbSort)
      asort($asList);

    return $asList;
  }
}

/*
 * Tried using the bsm plugin and failed - JQuery bug - Paul 2013-05-29
 *
 * private function _displayList()
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $sHTML = '';
    $aLists = $this->_getModel()->getManageableList();

    $oForm = $oHTML->initForm('mlists');
    $sURL = $oPage->getAjaxUrl($this->csUid,CONST_ACTION_SAVEEDIT);
    $oForm->setFormParams('mlists', true, array('action' => $sURL, 'class' => 'fullPageForm', 'submitLabel'=>'Save Changes'));

    foreach ($aLists as $sListLabel => $aValues)
    {
      $oForm->addField('select', $sListLabel.'[]', array('id' => $sListLabel, 'label' => $sListLabel, 'multiple' => 'multiple', 'sortable' => 'sortable'));
      foreach ($aValues as $sLabel => $sValue)
        $oForm->addOption($sListLabel.'[]', array('label' => $sLabel, 'value' => $sValue, 'selected' => 'selected'));

      $oForm->addField('input', 'label_'.$sListLabel, array('id' => 'label_'.$sListLabel, 'label' => 'Option label', 'class' => 'addOptionLabelToSelect', 'rel' => $sListLabel, 'style' => 'width:50%;'));
      $oForm->addField('input', 'value_'.$sListLabel, array('id' => 'value_'.$sListLabel, 'label' => 'Option value', 'class' => 'addOptionValueToSelect', 'rel' => $sListLabel, 'style' => 'width:50%;'));
      $sLinkHTML = "<a href='#' id='add_option_".$sListLabel."'>Add option</a>";
      $oForm->addField('misc', 'link_'.$sListLabel, array('type' => 'text', 'text' => $sLinkHTML));

      $sURLAdd = $oPage->getAjaxUrl($this->csUid,CONST_ACTION_SAVEADD,'',0, array('shortname' => $sListLabel));
      $sScript = "
      <script>
        $('#add_option_".$sListLabel."').click(function(){
          var optionlabel = $('#label_".$sListLabel."').val();
          var optionvalue = $('#value_".$sListLabel."').val();

          AjaxRequest('".$sURLAdd."', true, 'label_".$sListLabel.", #value_".$sListLabel."', '', '',  '', '', '');
          $('#".$sListLabel."').append('<option value=\''+optionvalue+'\' selected=\'selected\'>'+optionlabel+'</option>').change();
          $('#label_".$sListLabel."').val('');
          $('#value_".$sListLabel."').val('');

          return false;
        });
      </script>";

      $oForm->addField('misc', 'addscript', array('type' => 'text', 'text' => $sScript));
     }

    $sHTML .= $oForm->getDisplay();
    return $oPage->getAjaxExtraContent(array('data'=>$sHTML));
  }
*/