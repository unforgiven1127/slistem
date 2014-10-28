<?php

require_once('component/customfields/customfields.class.php5');


class CCustomfieldsEx extends CCustomfields
{
  private $_rightManage;

  public function __construct()
  {
    $oRight =  CDependency::getComponentByName('right');
    $this->_rightManage = $oRight->canAccess($this->_getUid(),'ppaall',$this->getType(),0);
    return true;
  }

  public function __destruct()
  {
    return true;
  }

   public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
   {
     $asActions = array();
       return $asActions;
   }

  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case  CONST_CF_TYPE_CUSTOMFIELD:
        switch($this->csAction)
        {
          case CONST_ACTION_ADD:
            return json_encode($this->_formCustomField(0));
              break;

            case CONST_ACTION_EDIT:
              return json_encode($this->_formCustomField($this->cnPk));
                break;

            case CONST_ACTION_FASTEDIT:
              return json_encode($this->_editCustomFieldValue($this->cnPk));
                break;

            case CONST_ACTION_SAVEADD:
              return json_encode($this->_saveCustomField());
                break;

            case CONST_ACTION_SAVEEDIT:
              return json_encode($this->_saveCustomField($this->cnPk));
                break;

            case CONST_ACTION_UPDATE:
              return json_encode($this->_updateCustomFieldValue($this->cnPk));
                break;

            case CONST_ACTION_LIST:
              return json_encode($this->_listCustomFields());
                break;

            case CONST_ACTION_DELETE:
              return json_encode($this->_delete());
                break;

            default :
              break;

         }
      }
  }

 /**
  * Save/Update a custom field value. Callable with parameters or with data in POST
  * @param integer $pnCustomPk
  * @param integer $pnLinkPk
  * @param integer $pnItemPk
  * @return array
  */

  private function _updateCustomFieldValue($pnCustomfieldPk, $pnLinkPk = 0, $pnItemPk = 0, $pvValue = null)
  {
    if(!assert('is_key($pnCustomfieldPk) && is_integer($pnLinkPk) && is_integer($pnItemPk)'))
      return array('error' => __LINE__.' - Wrong prameters.');


    if(!empty($pnLinkPk))
      $nLinkPk = $pnLinkPk;
    else
      $nLinkPk = (int)getValue('cf_linkpk', 0);

    if(!empty($pnItemPk))
      $nItemPk = $pnItemPk;
    else
      $nItemPk = (int)getValue('cf_itempk', 0);

    if(!assert('is_key($nLinkPk) && is_key($nItemPk)'))
      return 'Wrong value parameters. ['.$nLinkPk.'/'.$nItemPk.']';

    //check params are ok, and fetch existing value if exists
    $asCustomfield = $this->_getModel()->getCustomfieldValue($pnCustomfieldPk, $nLinkPk, $nItemPk);

    if($pvValue !== null)
      $sNewValue = $pvValue;
    else
      $sNewValue = getValue('value');



    if(empty($asCustomfield['customfield_valuepk']))
    {
      $bSuccess = $this->_getModel()->addCustomFieldValue($pnCustomfieldPk, $nLinkPk, $nItemPk, $sNewValue);
    }
    else
      $bSuccess = $this->_getModel()->updateCustomFieldValue((int)$asCustomfield['customfield_valuepk'], $sNewValue);

    if(!$bSuccess)
      return array('error' => __LINE__.' - Couldn\'t update the item value');

    return array('notice' => 'Custom field has been saved.', 'reload' => 1);
  }

 /**
  * Function to save the custom field data in the database
  * @return array with notice and reload page
  */

  private function _saveCustomField($pnPk = 0)
  {
    $aOpValues = getValue('optionvalue');
    $aOpLabels = getValue('optionlabel');
    $sDefaultValue = getValue('defaultvalue');
    $sType = getValue('fieldtype');
    $sCanBeEmpty = getValue('can_be_empty');
    $sNotice = 'Custom field has not been saved. Default value had wrong type.';

    // Controlling default value type
    switch($sType)
    {
      case 'text' :
      case 'textarea' :
      case 'url' :
        break;

      case 'int' :
      case 'float' :
        if(!empty($sDefaultValue) && !is_numeric($sDefaultValue))
          return array('error'=> $sNotice.' Should be an float.');
        break;

        case 'email' :
         if(!empty($sDefaultValue) && !isValidEmail($sDefaultValue))
           return array('error'=> $sNotice.' Should be a valid email address.');
         break;

        case 'radio' :
        case 'select' :
          if(!empty($sDefaultValue) && !in_array($sDefaultValue, $aOpValues))
            return array('error'=> $sNotice.' Should be one of the option values you entered.');
          break;

        case 'checkbox' :
          if(!empty($sDefaultValue) && $sDefaultValue == 'on' || empty($sDefaultValue))
            return array('error'=> $sNotice.' Should be \'on\' or an empty string');
          break;

        case 'date' :
          if(!empty($sDefaultValue) && !is_date($sDefaultValue, 'Y-m-d'))
            return array('error'=> $sNotice.' Should be a date YYYY-MM-DD');
          break;

        case 'datetime' :
          if(!empty($sDefaultValue) && !is_date($sDefaultValue, 'Y-m-d H:i:s'))
            return array('error'=> $sNotice.' Should be a date YYYY-MM-DD HH:MM:SS');
          break;

        case 'time' :
          if(!empty($sDefaultValue) && !is_date($sDefaultValue, 'H:i:s'))
            return array('error'=> $sNotice.' Should be a datetime HH:MM:SS');
          break;
    }

    // Main table
    $aValues = array('label' => getValue('label'), 'description' => getValue('description'), 'fieldtype' => getValue('fieldtype'), 'defaultvalue' => $sDefaultValue, 'can_be_empty' => (int)$sCanBeEmpty);

    if($pnPk === 0)
    {
      $nCustomFieldPk = $this->_getModel()->add($aValues, 'customfield');
      if(!is_key($nCustomFieldPk))
        return array('error' =>  __LINE__.' - Custom field could not be added.');
    }
    else
    {
      $nCustomFieldPk = $pnPk;
      $aValues['customfieldpk']=$nCustomFieldPk;
      $bUpdated = $this->_getModel()->update($aValues, 'customfield');
      if(!$bUpdated)
        return array('error'=>'Custom field could not be updated.');
    }

    // Link the custom field up to 3 items
    if(!is_array($_POST[CONST_CP_UID]) || empty($_POST[CONST_CP_UID]))
      return array('error'=>'Missing/incorrect link parameters.');

    $this->_getModel()->deleteByFk($nCustomFieldPk, 'customfield_link', 'customfield');
    $nLink = count($_POST[CONST_CP_UID]);
    for($nCount = 0; $nCount < $nLink; $nCount++)
    {
      $aCpValues = array(CONST_CP_UID => $_POST[CONST_CP_UID][$nCount], CONST_CP_ACTION => $_POST[CONST_CP_ACTION][$nCount],
                         CONST_CP_TYPE => $_POST[CONST_CP_TYPE][$nCount], CONST_CP_PK => (int)$_POST[CONST_CP_PK][$nCount]);

      $nCustomFieldLinkPk = $this->_getModel()->addCustomFieldLink($nCustomFieldPk, $aCpValues, getValue('cascading'));
      if(!is_key($nCustomFieldLinkPk))
        return array('error'=>'Custom field could not be linked to the component. ['.implode(', ', $aCpValues).']');
    }

    // Adding options
    $this->_getModel()->deleteByFk($nCustomFieldPk, 'customfield_option', 'customfield');
    if($aOpValues) {
      $bOptionsAdded = $this->_getModel()->addCustomFieldOptions($nCustomFieldPk, $aOpValues, $aOpLabels);
      if(!$bOptionsAdded)
        return array('error'=>'Custom field options could not be added.');
    }

    return array('notice'=>'Custom field has been saved.', 'url' => '#');
  }

 /**
  * Ajax function to update the value of a customfield
  * @param type $pnPk
  * @param type $psValue
  */

  private function _editCustomFieldValue($nCustomfieldPk)
  {
    if(!assert('is_key($nCustomfieldPk)'))
      return 'Wrong customfield id';

    $nLinkPk = (int)getValue('cf_linkpk', 0);
    $nItemPk = (int)getValue('cf_itempk', 0);

    if(!assert('is_key($nLinkPk) && is_key($nItemPk)'))
      return 'Wrong value parameters. ['.$nLinkPk.'/'.$nItemPk.']';


    $asCustomfield = $this->_getModel()->getCustomfieldValue($nCustomfieldPk, $nLinkPk, $nItemPk);
    $sValue = $asCustomfield['value'];
    $sFieldType = $asCustomfield['fieldtype'];

    $sLabel = $asCustomfield['label'];
    $bCanbeEmpty = (bool)$asCustomfield['can_be_empty'];
    if($bCanbeEmpty)
      $asControl = array();
    else
      $asControl = array('jsFieldNotEmpty' => '');


    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $sURL = $oPage->getAjaxUrl('customfields', CONST_ACTION_UPDATE, CONST_CF_TYPE_CUSTOMFIELD, $nCustomfieldPk, array('cf_linkpk' => $nLinkPk, 'cf_itempk' => $nItemPk));

    $oForm = $oHTML->initForm('csUpdateForm_'.$nCustomfieldPk);
    $oForm->setFormParams('csUpdateForm_'.$nCustomfieldPk, true, array('action' => $sURL, 'inajax'=> 1, 'submitLabel' => '', 'template' => 'fastedit'));
    $oForm->addField('input', 'customfieldpk', array('type' => 'hidden', 'value' => $nCustomfieldPk));

    switch ($sFieldType)
    {
      case 'text' :
        $oForm->addField('input', 'value', array('type' => 'text', 'value' => $sValue));
        $oForm->setFieldControl('value', $asControl);
        break;
      case 'textarea' :
        $oForm->addField('textarea', 'value', array('label'=> $sLabel, 'value' => $sValue));
        $oForm->setFieldControl('value', $asControl);
        break;

      case 'int' :
        $oForm->addField('input', 'value', array('type' => 'text', 'label'=> $sLabel, 'value' => $sValue));
        $asControl['jsFieldTypeIntegerPositive'] = 1;
        $oForm->setFieldControl('value', $asControl);
        break;

       case 'float' :
        $oForm->addField('input', 'value', array('type' => 'text', 'label'=> $sLabel, 'value' => $sValue));
        $asControl['jsFieldTypeFloat'] = 1;
        $oForm->setFieldControl('value', $asControl);
        break;

       case 'email' :
        $oForm->addField('input', 'value', array('type' => 'text', 'label'=> $sLabel, 'value' => $sValue));
         $asControl['jsFieldTypeEmail'] = 1;
        $oForm->setFieldControl('value', $asControl);
        break;

       case 'url' :
        $oForm->addField('input', 'value', array('type' => 'text', 'label'=> $sLabel, 'value' => $sValue));
         $asControl['jsFieldTypeUrl'] = 1;
        $oForm->setFieldControl('value', $asControl);
        break;

       case 'select' :
        $oOptions = $this->_getModel()->getOptionsByFieldFk($nCustomfieldPk);
        $bRead = $oOptions->readFirst();
        $oForm->addField('select', 'value', array('label'=> $sLabel, 'value' => $sValue));

        if($bCanbeEmpty)
          $oForm->addOption('value', array('label' => ' - select -', 'value' => ''));
        while($bRead)
        {
          if($oOptions->getFieldValue('value') == $sValue)
            $oForm->addOption('value', array('label' => $oOptions->getFieldValue('label'), 'value' => $oOptions->getFieldValue('value'), 'selected' => 'selected'));
          else
            $oForm->addOption('value', array('label' => $oOptions->getFieldValue('label'), 'value' => $oOptions->getFieldValue('value')));

          $bRead = $oOptions->readNext();
        }
        break;

        case 'radio' :
          $oOptions = $this->_getModel()->getOptionsByFieldFk($nCustomfieldPk);
          $bRead = $oOptions->readFirst();
          while($bRead)
          {
            if($oOptions->getFieldValue('value') == $sValue)
              $oForm->addField('radio', 'value', array('label' => $oOptions->getFieldValue('label'), 'value' => $oOptions->getFieldValue('value'), 'checked' => 'checked'));
            else
              $oForm->addField('radio', 'value', array('label' => $oOptions->getFieldValue('label'), 'value' => $oOptions->getFieldValue('value')));

            $bRead = $oOptions->readNext();
          }
        break;

        case 'checkbox' :
          $asOptions = array('label'=> $sLabel);
          if($sValue=='on')
            $asOptions['checked'] = 'yes';
          $oForm->addField('checkbox', 'value', $asOptions );
        break;

        case 'date' :
          $oForm->addField('input', 'value', array('type' => 'date', 'label'=> $sLabel, 'value' => $sValue));
          $oForm->setFieldControl('value', $asControl);
          break;

        case 'datetime' :
          $oForm->addField('input', 'value', array('type' => 'datetime', 'label'=> $sLabel, 'value' => $sValue));
          $oForm->setFieldControl('value', $asControl);
          break;

        case 'time' :
          $oForm->addField('input', 'value', array('type' => 'time', 'label'=> $sLabel, 'value' => $sValue));
          $oForm->setFieldControl('value', $asControl);
          break;
    }

    $sHTML = $oHTML->getBlocStart('csEdit_'.$nCustomfieldPk, array('class' => 'fasteEditContainer'));
    $sHTML .= $oForm->getDisplay();
    $sHTML .= '<a class="fastEditFormCancel" href="javascript:;" onclick=" $(this).closest(\'.holderSection\').find(\'.cf-link\').fadeIn(); $(this).closest(\'.rightSection\').hide(0);">&nbsp;</a>';
    $sHTML.= $oHTML->getBlocEnd();

    return $oPage->getAjaxExtraContent(array('data' => $sHTML));
  }

 /**
  * Function to display customfield add link in the another component
  * @param string $psUid
  * @param string $psAction
  * @param string $psType
  * @param integer $pnPk
  * @return string
  */

 public function getCustomFieldAddLink($cpValues = array())
 {
    //return html of a link to add a customfield
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    if(!empty($cpValues))
    {
      if(!assert('is_cpValues($cpValues)'))
        return array();

      $sURL =  $oPage->getAjaxURL($this->_getUid(),CONST_ACTION_ADD, CONST_CF_TYPE_CUSTOMFIELD,0,$cpValues);
    }
    else
      $sURL =  $oPage->getAjaxURL($this->_getUid(),CONST_ACTION_ADD, CONST_CF_TYPE_CUSTOMFIELD,0);

    $sAjax = $oHTML->getAjaxPopupJS($sURL, 'body','','400','850',1);
    $sHTML = $oHTML->getActionButton('Add a custom Field', '', $this->getResourcePath().'/pictures/add_16.png', array('onclick' => $sAjax));

    return $sHTML;
 }

 /**
  * Function to display the form to add custom field
  * @param integer $pnPk
  * @return array of data
  */

  private function _formCustomField($pnPk)
  {
    $oHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($this->getResourcePath().'css/customfields.css');

    $oForm = $oHTML->initForm('csForm');
    $sFormId = $oForm->getFormId();

    if($pnPk===0)
    {
      $oCustomField = new CDbResult();

      if(is_cpValuesInPost())
      {
        $oForm->addField('input', CONST_CP_UID.'[0]', array('type' => 'hidden', 'value' => getValue(CONST_CP_UID)));
        $oForm->addField('input', CONST_CP_ACTION.'[0]', array('type' => 'hidden', 'value' => getValue(CONST_CP_ACTION)));
        $oForm->addField('input', CONST_CP_TYPE.'[0]', array('type' => 'hidden', 'value' => getValue(CONST_CP_TYPE)));
        $oForm->addField('input', CONST_CP_PK.'[0]', array('type' => 'hidden', 'value' => getValue(CONST_CP_PK)));
      }

      $sURL = $oPage->getAjaxURL($this->_getUid(), CONST_ACTION_SAVEADD, CONST_CF_TYPE_CUSTOMFIELD, 0);
    }
    else
    {
      if(!assert('is_key($pnPk)'))
       return array('error' => 'Pk given is wrong type.');

      $oCustomField = $this->_getModel()->getByPk($pnPk, 0, false);
      $bRead = $oCustomField->readFirst();
      if(!$bRead)
        return array('error' => 'Custom field not found. Please contact your administrator.');

      $sURL = $oPage->getAjaxURL($this->_getUid(),CONST_ACTION_SAVEEDIT, CONST_CF_TYPE_CUSTOMFIELD,$pnPk);
    }

    $sJs = $oHTML->getAjaxJs($sURL, 'body', $sFormId);
    $oForm->setFormParams('', false, array('action' => '','inajax'=> 1,'submitLabel' => 'Save', 'onsubmit' => 'event.preventDefault(); '.$sJs));
    $oForm->setFormDisplayParams(array('columns' => 2, 'noCancelButton' => '1'));

    $oForm->addField('misc', '', array('type'=> 'title', 'title' => 'Custom field parameters'));


    $oForm->addField('input', 'label', array('label'=> 'Displayed label ', 'value'   => $oCustomField->getFieldValue('label')));
    $oForm->setFieldControl('label', array('jsFieldNotEmpty' => ''));

    $oForm->addField('input', 'description', array('label'=> 'Field legend', 'value' => $oCustomField->getFieldValue('description')));
    $oForm->setFieldControl('description', array('jsFieldNotEmpty' => '', 'jsFieldMaxSize' => '255'));

    //for later use
    if (((($pnPk===0) && (!is_cpValuesInPost())) || (is_key($pnPk))) && ($this->_rightManage))
      $bDisplayCpField = true;
    else
      $bDisplayCpField = false;

    $oForm->addField('select', 'fieldtype', array('label'=> 'Field type'));
    $oForm->setFieldControl('fieldtype', array('jsFieldNotEmpty' => ''));
    $aOptions = array(
        'raw text field' => 'text',
        'big text area' => 'textarea',
        'list of elements' => 'select',
        'email' => 'email',
        'url' => 'url',
        'integer number' => 'int',
        'float number' => 'float',
        'radio button' => 'radio',
        'checkbox' => 'checkbox',
        'date' => 'date',
        'datetime' => 'datetime',
        'time' => 'time'
    );

    foreach ($aOptions as $sLabel => $vValue)
    {
      $aParams = array('label' => $sLabel, 'value' => $vValue);
      if($vValue == $oCustomField->getFieldValue('fieldtype'))
        $aParams['selected'] = 'selected';

      $oForm->addOption('fieldtype', $aParams);
    }

    $oForm->addField('input', 'defaultvalue', array('label'=> 'Default value', 'value' => $oCustomField->getFieldValue('defaultvalue')));
    if($oCustomField->getFieldValue('can_be_empty'))
      $oForm->addField('checkbox', 'can_be_empty', array('label'=> 'Can be empty', 'value' => 1, 'checked' => 'checked'));
    else
      $oForm->addField('checkbox', 'can_be_empty', array('label'=> 'Can be empty', 'value' => 1));

    $sHide = " style='display:none;'";

    $sExistingOptionsHtml='';
    if(is_key($pnPk))
    {
      $oCsOptions = $this->_getModel()->getOptionsByFieldFk($pnPk);
      $bReadOpt = $oCsOptions->readFirst();

      if($bReadOpt)
      {
        while($bReadOpt)
        {
          $sExistingOptionsHtml.="<div class='formfield'>";
          $sExistingOptionsHtml.="<input class='optval' type='text' name='optionvalue[]' value='".$oCsOptions->getFieldValue('value')."' jscontrol='jsFieldNotEmpty@|' style='width:100px;'>";
          $sExistingOptionsHtml.="<input type='text' name='optionlabel[]' value='".$oCsOptions->getFieldValue('label')."' jscontrol='jsFieldNotEmpty@|' style='width:100px;'>";
          $sExistingOptionsHtml.="<a href='#' onclick='javascript:$(this).parent().remove();' style='margin-left:5px;'>X</a>";
          $sExistingOptionsHtml.="</div>";

          $bReadOpt = $oCsOptions->readNext();
        }
      }
      if(($oCustomField->getFieldValue('fieldtype')=='select') || ($oCustomField->getFieldValue('fieldtype')=='radio'))
        $sHide = '';
    }

    $sHtmlMoreDiv = "<div id='more'".$sHide.">
                       <a href='#'>".$oHTML->getPicture(CONST_PICTURE_ADD, 'Add an option')." Add an option</a><br /><br />
                     </div>";

    $sOptionsHtml ="<div class='formfield'>";
    $sOptionsHtml.="<input class='optval' type='text' name='optionvalue[]' placeholder='option value' jscontrol='jsFieldNotEmpty@|' style='width:100px;'>";
    $sOptionsHtml.="<input type='text' name='optionlabel[]' placeholder='option label' jscontrol='jsFieldNotEmpty@|' style='width:100px;'>";
    $sOptionsHtml.="<a href='#' onclick='javascript:$(this).parent().remove();' style='margin-left:5px;'>X</a>";
    $sOptionsHtml.="</div>";

    $sSelectBehavior = "
     $(\"select[name='fieldtype']\").change(function(){
       if ($(this).val()==\"select\" || $(this).val()==\"radio\")
       { $(\"#more\").show(); }
       else
       { $(\"#more > div\").remove(); $(\"#more\").hide(); }
     });";
     $sMoreBehavior = "
     $(\"#more a\").click(function(){
       $(\"#more\").append(\"".$sOptionsHtml."\"); return false;
     });";

     $oForm->addField('misc', '', array('type' => 'text','text'=> $sHtmlMoreDiv.$sExistingOptionsHtml."<script>".$sSelectBehavior.$sMoreBehavior."</script>"));

    if(($this->_rightManage) && (!$bDisplayCpField))
    {
      $oForm->addField('misc','',array('type'=>'br','text'=>'&nbsp;'));
      $oForm->addField('checkbox', 'cascading', array('type' => 'misc', 'label'=> 'Add this custom field to all records ?', 'value' => 1, 'id' => 'cascading_id'));
    }

    $asUid = CDependency::getComponentUidByInterface('has_customfield');
    if(empty($asUid))
    {
      $oForm->addField('misc', '', array('type'=> 'br'));
      $oForm->addField('misc', '', array('type'=> 'text', 'text' => 'No component using customfields. Probably a setup issue.'));
    }
    elseif($bDisplayCpField)
    {
      //if in admin panel, display uid/act/type/pk fields
      $oForm->addField('misc', '', array('type'=> 'title', 'title' => 'Linked to '));

      $oForm->addSection('CP', array('keepinline' => 1));

        $oForm->addField('select', CONST_CP_UID.'[0]', array('label' => 'UID', 'type' => 'text'));
        $sValue = $oCustomField->getFieldValue(CONST_CP_UID);
        foreach($asUid as $sUid)
        {
          if($sUid == $sValue)
            $oForm->addOption(CONST_CP_UID.'[0]', array('label' => $sUid, 'value' => $sUid, 'selected' => 'selected'));
          else
            $oForm->addOption(CONST_CP_UID.'[0]', array('label' => $sUid, 'value' => $sUid));
        }
        $oForm->addField('input', CONST_CP_ACTION.'[0]', array('label' => 'Action', 'type' => 'text', 'value' => $oCustomField->getFieldValue(CONST_CP_ACTION)));
        $oForm->addField('input', CONST_CP_TYPE.'[0]', array('label' => 'Type', 'type' => 'text', 'value' => $oCustomField->getFieldValue(CONST_CP_TYPE)));
        $oForm->addField('input', CONST_CP_PK.'[0]', array('label' => 'Pk', 'type' => 'text', 'value' => $oCustomField->getFieldValue(CONST_CP_PK)));

        $bRead = $oCustomField->readNext();
        if(!$bRead)
          $oCustomField->reset();

        $oForm->addField('misc', '', array('type' => 'br'));
        $oForm->addField('select', CONST_CP_UID.'[1]', array('label' => 'UID', 'type' => 'text'));
        $sValue = $oCustomField->getFieldValue(CONST_CP_UID);
        foreach($asUid as $sUid)
        {
          if($sUid == $sValue)
            $oForm->addOption(CONST_CP_UID.'[1]', array('label' => $sUid, 'value' => $sUid, 'selected' => 'selected'));
          else
            $oForm->addOption(CONST_CP_UID.'[1]', array('label' => $sUid, 'value' => $sUid));
        }
        $oForm->addField('input', CONST_CP_ACTION.'[1]', array('label' => 'Action', 'type' => 'text', 'value' => $oCustomField->getFieldValue(CONST_CP_ACTION)));
        $oForm->addField('input', CONST_CP_TYPE.'[1]', array('label' => 'Type', 'type' => 'text', 'value' => $oCustomField->getFieldValue(CONST_CP_TYPE)));
        $oForm->addField('input', CONST_CP_PK.'[1]', array('label' => 'Pk', 'type' => 'text', 'value' => $oCustomField->getFieldValue(CONST_CP_PK)));

        $bRead = $oCustomField->readNext();
        if(!$bRead)
          $oCustomField->reset();

        $oForm->addField('misc', '', array('type' => 'br'));
        $oForm->addField('select', CONST_CP_UID.'[2]', array('label' => 'UID', 'type' => 'text'));
        $sValue = $oCustomField->getFieldValue(CONST_CP_UID);
        foreach($asUid as $sUid)
        {
          if($sUid == $sValue)
            $oForm->addOption(CONST_CP_UID.'[2]', array('label' => $sUid, 'value' => $sUid, 'selected' => 'selected'));
          else
            $oForm->addOption(CONST_CP_UID.'[2]', array('label' => $sUid, 'value' => $sUid));
        }
        $oForm->addField('input', CONST_CP_ACTION.'[2]', array('label' => 'Action', 'type' => 'text', 'value' => $oCustomField->getFieldValue(CONST_CP_ACTION)));
        $oForm->addField('input', CONST_CP_TYPE.'[2]', array('label' => 'Type', 'type' => 'text', 'value' => $oCustomField->getFieldValue(CONST_CP_TYPE)));
        $oForm->addField('input', CONST_CP_PK.'[2]', array('label' => 'Pk', 'type' => 'text', 'value' => $oCustomField->getFieldValue(CONST_CP_PK)));

      $oForm->closeSection();
    }

    $sHTML = $oForm->getDisplay();

   return $oPage->getAjaxExtraContent(array('data'=>$sHTML));
 }

 /*
  * Displays custom fields related to a set of values component, action, type and pk
  * @param array $asCpValues
  * @return $sHTML
  */

 public function displayCustomFields($asCpValues)
 {
    if(!assert('is_cpValues($asCpValues)'))
      return array('error' => 'Wrong parameters assigned to displayCustomFields()');

    $oHTML =  CDependency::getCpHtml();

    $oDbResult = $this->_getModel()->getCustomFieldsFromCpValues($asCpValues, $asCpValues[CONST_CP_PK]);
    $bRead = $oDbResult->readFirst();

    $sHTML = '';
    while($bRead)
    {
      $sHTML.= $oHTML->getBlocStart('csDiv_'.$oDbResult->getFieldValue('customfieldpk'), array('class' => 'holderSection'));

        $sHTML.= $oHTML->getBlocStart('',array('class'=>'leftSection','style'=>'cursor:help;', 'title' => $oDbResult->getFieldValue('description')));
        $sHTML.= $oHTML->getText($oDbResult->getFieldValue('label'));
        $sHTML.= $oHTML->getBlocEnd();

        $sHTML.= $oHTML->getBlocStart('appenDiv_link_'.$oDbResult->getFieldValue('customfieldpk'), array('class' => 'cf-link rightSection'));
        $sHTML.= $this->_displayCustomField($oDbResult, $asCpValues);
        $sHTML.= $oHTML->getBlocEnd();

        //add a one section to receive the form
        $sHTML.= $oHTML->getBlocStart('appenDiv_'.$oDbResult->getFieldValue('customfieldpk'), array('class' => 'hidden rightSection cf-data'));
        $sHTML.= $oHTML->getBlocEnd();

      $sHTML.= $oHTML->getBlocEnd();

      $bRead = $oDbResult->readNext();
    }

   if($this->_rightManage)
   {

      $sHTML.= $oHTML->getFloatHack();
      $sHTML.= $this->getCustomFieldAddLink($asCpValues);
   }

    return $sHTML;
 }

 /*
  * Displays a custom field from which informations are given in $oDbResult
  * @param objet $oDbResult
  * @return $sHTML
  */

  private function _displayCustomField($poDbResult, $paCpValues)
  {
    if(!assert('is_object($poDbResult)'))
      return '';

    $oPage =  CDependency::getCpPage();
    $oRight =  CDependency::getComponentByName('right');
    $oHTML =  CDependency::getCpHtml();
    $bAccess = $oRight->canAccess($this->_getUid(),'ppae', CONST_CF_TYPE_CUSTOMFIELD, 0);

    $nPk = $poDbResult->getFieldValue('customfieldpk', CONST_PHP_VARTYPE_INT);
    $nLinkPk = $poDbResult->getFieldValue('customfield_linkpk', CONST_PHP_VARTYPE_INT);

    $nValuePk = $poDbResult->getFieldValue('customfield_valuepk', CONST_PHP_VARTYPE_INT);
    $sValue = $poDbResult->getFieldValue('value');

    if(empty($nValuePk))
      $sValue = $poDbResult->getFieldValue('defaultvalue');


    // if the field is a list, we need to get the options to display the label and not the value
    $sFieldType = $poDbResult->getFieldValue('fieldtype');
    if(in_array($sFieldType, array('select', 'radio')))
    {
      $oDbResult = $this->_getModel()->getOptionsByFieldFk($nPk);
      $bRead = $oDbResult->readFirst();

      while($bRead)
      {
        if($oDbResult->getFieldValue('value') == $sValue)
        {
          $sValue = $oDbResult->getFieldValue('label');
          break;
        }
        $bRead = $oDbResult->readNext();
      }
    }

    $sContent = '';
    if(empty($sValue))
      $sValue .= '&nbsp;';

    if($bAccess)
    {
      $sURL = $oPage->getAjaxUrl($this->_getUid(), CONST_ACTION_FASTEDIT, CONST_CF_TYPE_CUSTOMFIELD, $nPk, array('cf_linkpk' => $nLinkPk, 'cf_itempk' => $paCpValues[CONST_CP_PK]));
      $sJavascript = "AjaxRequest('".$sURL."','#body', '', 'appenDiv_".$nPk."', '', '', '$(\'#appenDiv_link_".$nPk."\').hide(0); $(\'#appenDiv_".$nPk."\').fadeIn();' );";
      $sContent = $oHTML->getLink($sValue,'javascript:;', array('onclick' => $sJavascript, 'class' => 'fasteditlink'));
    }
    else
    {
      $sContent = $sValue;
    }

    return $sContent;
  }

  public function getSearchSql($pnCFieldPk, $pvCFieldValue)
  {
    $asSql = array('join' => '', 'where' => '');

    if(!assert('is_integer($pnCFieldPk)') || empty($pnCFieldPk) || empty($pvCFieldValue))
      return $asSql;

    //TODO check cf exists, get type and check value is in the correct format, convert stored data if needed
    $asCustomField = $this->_getModel()->getCustomfields('', '', '', $pnCFieldPk);
    if(empty($asCustomField))
      return $asSql;

    $oDb = CDependency::getComponentByName('database');
    $pvCFieldValue = $oDb->dbEscapeString('%'.$pvCFieldValue.'%');

    switch($asCustomField[0]['type'])
    {
      case CONST_AB_TYPE_COMPANY:
        $asSql['join'] = ' INNER JOIN customfield_value as cfv ON (cfv.itemfk = cp.companypk AND cfv.customfieldfk = '.$pnCFieldPk.' AND cfv.value LIKE '.$pvCFieldValue.' ) ';
        $asSql['where'] = '';
        break;

      case CONST_AB_TYPE_CONTACT:
        $asSql['join'] = ' INNER JOIN customfield_value as cfv ON (cfv.itemfk = co.contactpk AND cfv.customfieldfk = '.$pnCFieldPk.'  AND cfv.value LIKE '.$pvCFieldValue.' ) ';
        $asSql['where'] = '';
        break;

      default: exit(__LINE__.' - searching unknown type of custom field.');
        break;
    }

    return $asSql;
  }

  private function _listCustomFields()
  {
    $oDisplay = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $oFields = $this->_getModel()->getCustomFields();

    $sHTML = $this->getCustomFieldAddLink();
    $sHTML.= $oDisplay->getFloatHack();
    $sHTML.= $oDisplay->getCR(1);

    $bRead = $oFields->readFirst();
    if(!$bRead)
      $sHTML .= 'There is no custom field in the system';
    else
    {
      $asCustomFields = array();
      $nCount = 0;
      while($bRead)
      {
        $nPk = (int)$oFields->getFieldValue('customfieldpk');

        $asCustomFields[$nCount]['label'] = $oFields->getFieldValue('label');
        $asCustomFields[$nCount]['type'] =  $oFields->getFieldValue('fieldtype');
        $asCustomFields[$nCount]['descritpion'] = $oFields->getFieldValue('description');
        $asCustomFields[$nCount]['values'] = $oFields->getFieldValue('nbValues').' record(s) in database';
        if($oFields->getFieldValue('can_be_empty'))
          $asCustomFields[$nCount]['type'].= ' / empty';


        $sURLEdit = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT,'', $nPk);
        $sPicEdit = $oDisplay->getPicture(CONST_PICTURE_EDIT);
        $sAjaxEdit = $oDisplay->getAjaxPopupJS($sURLEdit, 'body', true, '550','860',1);
        $sAction = ' '.$oDisplay->getLink($sPicEdit, 'javascript:;', array('onclick' => $sAjaxEdit));

        $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, '', $nPk);
        $sPic= $oDisplay->getPicture(CONST_PICTURE_DELETE);
        $sAction.= ' '.$oDisplay->getLink($sPic, $sURL, array('onclick' => 'if(!window.confirm(\'You are about to permanently delete this custom field and all its values.\\nDo you really want to proceed ?\')){ return false; }'));

        $asCustomFields[$nCount]['action'] = $sAction;


        $bRead = $oFields->readNext();
        $nCount++;
      }

      //initialize the template
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
      $oTemplate = $oDisplay->getTemplate('CTemplateList', $asParam);

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->setRenderingOption('full', 'full', 'full');

      $oConf->setPagerTop(false);
      $oConf->setPagerBottom(false);

      $oConf->addColumn('Label', 'label', array('width' => 200, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Type', 'type', array('width' => 95, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Description', 'descritpion', array('width' => 390));
      $oConf->addColumn('Values', 'values', array('width' => 200));
      $oConf->addColumn('Actions', 'action', array('width' => 60));

      $sHTML .= $oTemplate->getDisplay($asCustomFields);

    }


    return $oPage->getAjaxExtraContent(array('data' => $sHTML));
  }

  private function _delete()
  {
    $this->_getModel()->deleteByFk($this->cnPk, 'customfield_link', 'customfield');
    $this->_getModel()->deleteByFk($this->cnPk, 'customfield_value', 'customfield');
    $this->_getModel()->deleteByFk($this->cnPk, 'customfield_option', 'customfield');
    $this->_getModel()->deleteByPk($this->cnPk, 'customfield');

    return array('notice' => 'Custom Field deleted successfully.', 'reload' => 1);
  }



  public function getCustomfields($psUid = '', $psAction = '', $psType = '', $pnPk = 0, $pnItemfk = 0, $psFieldType = '', $pasFields = array())
  {
    $oDbResult = $this->_getModel()->getCustomFieldsFromCpValues(array(CONST_CP_UID => $psUid, CONST_CP_ACTION => $psAction, CONST_CP_TYPE =>$psType, CONST_CP_PK =>(int)$pnPk));
    if(!$oDbResult)
      return array();

    $bRead = $oDbResult->readFirst();
    $asCustomfield = array();
    while($bRead)
    {
      $asCustomfield[(int)$oDbResult->getFieldValue('customfieldpk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    if(empty($asCustomfield))
      return array();


    $oDbResult = $this->_getModel()->getOptionsByFieldFk(array_keys($asCustomfield));
    if(!is_a($oDbResult, 'CDbResult'))
      return array();

    $bRead = $oDbResult->readFirst();
    while($bRead)
    {
      $asCustomfield[(int)$oDbResult->getFieldValue('customfieldfk')]['option'][(int)$oDbResult->getFieldValue('customfield_optionpk')] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    return $asCustomfield;
  }

  public function listenerNotification($psUid, $psAction, $psType, $pnPk, $psActionToDo)
  {
    $avCpValues = array(CONST_CP_UID => $psUid, CONST_CP_ACTION => $psAction, CONST_CP_TYPE => $psType, CONST_CP_PK => $pnPk);

    if(!assert('is_CpValues($avCpValues)'))
      return false;

    switch($psActionToDo)
    {
      case CONST_ACTION_DELETE :
        $this->_getModel()->deleteFromCpValues($avCpValues);
        break;
    }

    return true;
  }



  /**
  * Save customfields values: CF auto generated in a form, we need to save it now
  * @return boolean true if save successfully, or false O_o
  */

  public function saveCustomFields($psUid, $psAction, $psType, $pnPk)
  {
    if(!assert('!empty($psUid) && !empty($psAction) && is_key($pnPk)'))
      return false;

    $asCfValue = array();
    foreach($_POST as $sVarName => $vValue)
    {
      //look for fields starting with "form_autogen_cf_4Id"
      $sChunk = substr($sVarName, 0, 16);
      if($sChunk == 'form_autogen_cf_')
      {
        $nFieldPk = (int)substr($sVarName, 16);
        $asCfValue[$nFieldPk] = $vValue;
      }
    }

    if(empty($asCfValue))
      return true;

    $oResult = $this->_getModel()->getByPk(array_keys($asCfValue));
    $bRead = $oResult->readFirst();
    $nCustomfield = $oResult->numRows();

    if($nCustomfield != count($asCfValue))
    {
      assert('false; //number of custom fields doesn\'t match. ['.count($asCustomField).' / '.count($asCfValue).']');
      return false;
    }


    while($bRead)
    {
      $asData = $oResult->getData();
      $vValue = $asCfValue[(int)$asData['customfieldpk']];

      $asResult = $this->_updateCustomFieldValue((int)$asData['customfieldpk'], (int)$asData['customfield_linkpk'], $pnPk, $vValue);
      if(isset($asResult['error']))
      {
        assert('false; // could not save custom field value');
        return false;
      }

      $bRead = $oResult->readNext();
    }

    return true;
  }

}