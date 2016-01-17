<?php

require_once('component/sl_position/sl_position.class.php5');
class CSl_positionEx extends CSl_position
{
  private $_oPage;
  private $_oDisplay;
  private $oCandidate;
  private $casUserData;
  private $casStatus = array();
  private $csSearchId = '';


  public function __construct()
  {
    $this->_oPage = CDependency::getCpPage();
    $this->_oDisplay = CDependency::getCpHtml();
    $this->oCandidate = CDependency::getComponentByName('sl_candidate');

    $oLogin = CDependency::getCpLogin();
    $this->casUserData = $oLogin->getUserData();

    //initialize the status array`
    $this->casStatus[1] = 'pitched';
    $this->casStatus[2] = 'resume sent';

    for($nCount = 51; $nCount < 61; $nCount++)
      $this->casStatus[$nCount] = 'CCM'.($nCount-50);

    $this->casStatus[100] = 'offer';
    $this->casStatus[101] = 'placed';
    $this->casStatus[150] = 'stalled';
    $this->casStatus[200] = 'fallen off';
    $this->casStatus[201] = 'not interested';

    return true;
  }

  public function getDefaultType()
  {
    return CONST_POSITION_TYPE_JD;
  }

  public function getDefaultAction()
  {
    return CONST_ACTION_LIST;
  }
  //====================================================================
  //  accessors
  //====================================================================



  //====================================================================
  //  interface
  //====================================================================

  /**   !!! Generic component method but linked to interfaces !!!
   *
   * Return an array listing the public "items" the component filtered by the interface
   * @param string $psInterface
   * @return array
   */
  public function getComponentPublicItems($psInterface = '')
  {
    $asItem = array();

    $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_POSITION_TYPE_JD);
    $asItem[] = array(CONST_CP_UID => $this->csUid, CONST_CP_ACTION => CONST_ACTION_VIEW,
        CONST_CP_TYPE => CONST_POSITION_TYPE_JD, 'label' => 'Position title/company', 'search_url' => $sURL);

    return $asItem;
  }


  //notification_item => 1 function

  /**
   * Return an array that MUST contain 4 fields: label. description, url, link
   * @param variant $pvItemPk (integer or array of int)
   * @param string $psAction
   * @param string $psItemType
   * @return array of string
  */
  public function getItemDescription($pvItemPk, $psAction = '', $psItemType = 'jd')
  {
    if(!assert('is_arrayOfInt($pvItemPk) || is_key($pvItemPk)'))
      return array();

    if(!assert('!empty($psItemType)'))
      return array();

    $asItem = array();
    $asItem[0]['label'] = 'position title -- not implemented ';
    $asItem[0]['url'] = 'url title -- not implemented ';
    $asItem[0]['link'] = 'link title -- not implemented ';
    $asItem[0]['description'] = 'position desc -- not implemented ';

    return $asItem;
  }




  //remove if the interface is not used
  public function getPageActions($psAction = '', $psType = '', $pnPk = 0)
  {
    $asActions = array();
    return $asActions;
  }

  public function getSearchFields($psType = '')
  {
    $asFields = array();

    if(isset($asFields[$psType]))
      return $asFields[$psType];

    return $asFields;
  }

  public function getSearchResult($psType = '')
  {
    $nResult = rand(0,20);
    $asResult = array();
    for($nCount = 0; $nCount < $nResult; $nCount++)
    {
      $asResult[] = md5(uniqid());
    }

    return array('total' => $nResult, 'results' => $asResult);
  }
  public function getSearchResultMeta()
  {
    return array();
  }


  //remove if the interface is not used
  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_POSITION_TYPE_JD:

        switch($this->csAction)
        {
          case CONST_ACTION_ADD:
          case CONST_ACTION_EDIT:
            return json_encode($this->_oPage->getAjaxExtraContent(array('data' => $this->_getPositionForm($this->cnPk))));
            break;

          case CONST_ACTION_SAVEADD:
          case CONST_ACTION_SAVEEDIT:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_savePosition($this->cnPk)));
            break;


          case CONST_ACTION_DELETE:
            return json_encode($this->_deletePosition($this->cnPk));
            break;


          case CONST_ACTION_SEARCH:

            if(getValue('qs'))
              return json_encode($this->_oPage->getAjaxExtraContent($this->_searchPosition($this->cnPk)));

            return $this->_selectorCompanyPosition($this->cnPk);
            break;

           case CONST_ACTION_VIEW:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_viewPosition($this->cnPk)));
            break;

          case CONST_ACTION_LIST:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_positionList()));
            break;
        }
        break;

      case CONST_POSITION_TYPE_LINK:

        switch($this->csAction)
        {
          case CONST_ACTION_ADD:
            return json_encode($this->_oPage->getAjaxExtraContent(array('data' => $this->_linkPositionForm())));
            break;

          case CONST_ACTION_SAVEADD:
          case CONST_ACTION_SAVEEDIT:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_savePositionLink($this->cnPk)));
            break;

          case CONST_ACTION_EDIT:
            return json_encode($this->_oPage->getAjaxExtraContent(array('data' => convertToUtf8($this->_editLinkstatus($this->cnPk)))));
            break;

          case CONST_ACTION_DELETE:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_deleteLinkstatus($this->cnPk)));
            break;

        }
        break;

      case CONST_POSITION_TYPE_PLACEMENT:
        //admin: 101,  199: yuko, 343 rossana, stephane: 367, Nic: 278, 309
        /*if(!in_array($this->casUserData['loginpk'], array(101,199,343,367,278,309,468)))
        {
          return json_encode(array('data' =>
              $this->_oDisplay->getCR(3).
              $this->_oDisplay->getBlocMessage('Accounting usage only...<br>Ask Yuko or Rossana for help.')));
        }*/

        switch($this->csAction)
        {
          case CONST_ACTION_ADD:
          case CONST_ACTION_EDIT:
            return json_encode($this->_oPage->getAjaxExtraContent(array('data' => $this->_getPlacementForm($this->cnPk))));
            break;

          case CONST_ACTION_SAVEADD:
          case CONST_ACTION_SAVEEDIT:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_savePlacement($this->cnPk)));
            break;

          case CONST_ACTION_VALIDATE:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_setPlacementPaid($this->cnPk)));
            break;

          case CONST_ACTION_DELETE:
            return json_encode($this->_oPage->getAjaxExtraContent($this->_deletePlacement($this->cnPk)));
            break;

          case CONST_ACTION_SEARCH:
            return json_encode($this->_getPlacementOptions());
            break;
        }
        break;

    }
  }

  //remove if the interface is not used
  public function getHtml()
  {

    $this->_processUrl();
    switch($this->csType)
    {
      case CONST_POSITION_TYPE_PLACEMENT:

        switch($this->csAction)
        {
          case CONST_ACTION_LIST:
            return $this->_getPlacementList();
            break;

          case CONST_ACTION_ADD:
            $asData = $this->addPlacement($this->cnPk);
            return $asData['data'];
            break;

          case CONST_ACTION_DOWNLOAD:
            return $this->export_placements();
            break;
        }
        break;

      default:
        switch($this->csAction)
        {
          case CONST_ACTION_LIST:
            return $this->_getPositionList();
            break;

          case CONST_ACTION_VIEW:
            $asData = $this->_viewPosition($this->cnPk);
            return $asData['data'];
            break;
        }
    }
    return '';
  }

  //==> cron interface:  1 fct
  public function getCronJob($pbSilent)
  {
    if(!$pbSilent)
    {
      echo 'Sl_position cron job <br /> notify_expiration=1 to send email to consultants<br />
        do_jd_expiration=1 make position expire, update candidate status, notify consultant.<br />
        seekPositionFrom='.strtotime('-3 months').' (-3m,-6m = '.strtotime('-6 months').',-12m = '.strtotime('-1 year').') <br /><br />';
    }

    if(getValue('notify_jd_expiration'))
      $this->_checkExpiring();

    if(getValue('do_jd_expiration'))
      $this->_updateExpiring();

    if(getValue('export_position'))
    {
      $location_list = $this->oCandidate->getVars()->getLocationList();

      $this->_exportPositionXml($location_list);
    }

    return '';
  }

  //====================================================================
  //  Component core
  //====================================================================




    //------------------------------------------------------
    //  Private methods
    //------------------------------------------------------

    private function _getPositionForm($pnPositionPk = 0)
    {
      if(!assert('is_integer($pnPositionPk)'))
        return array('error' => 'Bad parameteres.');

      if(!empty($pnPositionPk))
      {
        $oDbResult = $this->_getModel()->getPositionByPk($pnPositionPk);
        $bread = $oDbResult->readFirst();
        if(!$bread)
          return array('error' => 'Could not find the position.');

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_POSITION_TYPE_JD, $pnPositionPk);
      }
      else
      {
        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_POSITION_TYPE_JD, 0);
        $oDbResult = new CDbResult();
      }


      $this->_oPage->addCssFile(self::getResourcePath().'css/sl_position.css');
      $this->_oPage->addJsFile(self::getResourcePath().'js/sl_position.js');

      $oForm = $this->_oDisplay->initForm('positionForm');
      $oForm->setFormParams('positionForm', true, array('action' => $sURL));
      $oForm->setFormDisplayParams(array('noCancelButton' => true, /*'noSubmitButton' => 1,*/ 'columns' => 1));


      $oForm->addField('input', 'userfk', array('type' => 'hidden', 'value' => $this->casUserData['pk']));
      $oForm->addField('misc', '', array('type' => 'title', 'title'=> 'Add/edit position'));


      $oForm->addField('select', 'is_public', array('label' => 'Position public', 'onchange' => 'if($(this).val() == 1)
        {
          $(this).closest(\'form\').find(\'.public_important_field\').addClass(\'on\');
          $(this).closest(\'form\').find(\'#public_section\').show(0);
        }
        else
        {
           $(this).closest(\'form\').find(\'.public_important_field\').removeClass(\'on\');
           $(this).closest(\'form\').find(\'#public_section\').hide(0);
        }'));

      $oForm->addOption('is_public', array('label' => 'No', 'value' => '0'));
      $nPublic = (int)$oDbResult->getFieldValue('is_public');
      if($nPublic)
        $oForm->addOption('is_public', array('label' => 'Yes', 'value' => '1', 'selected' => 'selected'));
      else
        $oForm->addOption('is_public', array('label' => 'Yes', 'value' => '1'));


      //common fields to every languages
      $oForm->addField('select', 'location', array('class' => 'public_important_field', 'label' => 'Location'));
      $oForm->addOptionHtml('location', $this->oCandidate->getVars()->getLocationOption($oDbResult->getFieldValue('location')));


      $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_COMP);
      $oForm->addField('selector', 'companyfk', array('label' => 'Company', 'url' => $sURL));
      $oForm->setFieldControl('companyfk', array('jsFieldNotEmpty'));
      $nCompanyFk = (int)$oDbResult->getFieldValue('companyfk');
      if($nCompanyFk)
      {
        $asCompany = $this->oCandidate->getItemDescription($nCompanyFk, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP);
        $oForm->addOption('companyfk', array('label' => $asCompany[$nCompanyFk]['label'], 'value' => $nCompanyFk));
      }

      $oForm->addField('paged_tree', 'industryfk', array('text' => ' -- Industry --', 'label' => 'Industry', 'value' => (int)$oDbResult->getFieldValue('industryfk')));
      $oForm->addoption('industryfk', $this->oCandidate->_getTreeData('industry'));
      $oForm->setFieldControl('industryfk', array('jsFieldNotEmpty'));

      $oForm->addField('input', 'age_from', array('label' => 'Age from', 'value' => $oDbResult->getFieldValue('age_from')));
      $oForm->setFieldDisplayparams('age_from', array('class' => 'position_inline', 'keepNextInline' => 1));

      $oForm->addField('input', 'age_to', array('label' => 'to', 'value' => $oDbResult->getFieldValue('age_to')));
      $oForm->setFieldDisplayparams('age_to', array('class' => 'position_inline position_inline2'));

      $oForm->addField('input', 'salary_from', array('label' => 'Salary from', 'value' => $oDbResult->getFieldValue('salary_from')));
      $oForm->setFieldDisplayparams('salary_from', array('class' => 'position_inline', 'keepNextInline' => 1));

      $oForm->addField('input', 'salary_to', array('label' => 'to', 'value' => $oDbResult->getFieldValue('salary_to')));
      $oForm->setFieldDisplayparams('salary_to', array('class' => 'position_inline position_inline2'));

      $oForm->addField('slider', 'english', array('label' => 'English level', 'value' => $oDbResult->getFieldValue('lvl_english'), 'min' => 1, 'max' => 9, 'legend' => array(1,2,3,4,5,6,7,8,9)));
      $oForm->setFieldDisplayparams('english', array('class' => 'position_inline', 'keepNextInline' => 1));

      $oForm->addField('slider', 'japanese', array('label' => 'Japanese level', 'value' => $oDbResult->getFieldValue('lvl_japanese'), 'min' => 1, 'max' => 9, 'legend' => array(1,2,3,4,5,6,7,8,9)));
      $oForm->setFieldDisplayparams('japanese', array('class' => 'position_inline position_inline2'));


      if($nPublic)
        $oForm->addSection('pubField', array('id' => 'public_section', 'style' => 'display: block;'));
      else
        $oForm->addSection('pubField', array('id' => 'public_section'));

        $oForm->addField('misc', '', array('type' => 'title', 'title' => 'Jobboard display options'));

        $sCompanyName = $oDbResult->getFieldValue('company_text');
        if(empty($sCompanyName))
          $sCompanyName = 'company name not publicy visible';

        $oForm->addField('input', 'display_company', array('label' => 'Company label', 'value' => $sCompanyName));
        $oForm->setFieldDisplayParams('display_company', array('class' => 'public_field'));

        $oForm->addField('select', 'display_age', array('label' => 'Show age', 'value' => $oDbResult->getFieldValue('company_text')));
        $oForm->addOption('display_age', array('label' => 'No', 'value' => '0'));
        $oForm->addOption('display_age', array('label' => 'Yes', 'value' => '1'));
        $oForm->setFieldDisplayParams('display_age', array('class' => 'public_field'));

        $oForm->addField('select', 'display_salary', array('label' => 'Show salary', 'value' => $oDbResult->getFieldValue('company_text')));
        $oForm->addOption('display_salary', array('label' => 'No', 'value' => '0'));
        $oForm->addOption('display_salary', array('label' => 'Yes', 'value' => '1'));
        $oForm->setFieldDisplayParams('display_salary', array('class' => 'public_field'));

        $oForm->addField('select', 'display_date', array('label' => 'Show posting date', 'value' => $oDbResult->getFieldValue('company_text')));
        $oForm->addOption('display_date', array('label' => 'No', 'value' => '0'));
        $oForm->addOption('display_date', array('label' => 'Yes', 'value' => '1'));
        $oForm->setFieldDisplayParams('display_date', array('class' => 'public_field'));

        $oForm->addField('select', 'moderation', array('label' => 'Moderation', 'value' => $oDbResult->getFieldValue('company_text')));
        $oForm->addOption('moderation', array('label' => 'Yes', 'value' => '1'));
        $oForm->addOption('moderation', array('label' => 'No', 'value' => '0'));
        $oForm->setFieldDisplayParams('moderation', array('class' => 'public_field'));


        $oForm->addField('misc', '', array('type' => 'text', 'text' => '<div class="position_tip"><b>note</b>:<br/>
          If the position is not moderated, it will be sent straigth to the job board <span style="text-decoration: underline;">without</span> correction, translation or any SEO optimization that could make it more visible on the web.<br />
          In this case, please be careful with the "company label" field: this is the text displayed instead of the real company name.
          (examples: leading IT company, international financial group, automotive company...)</div>'));

         $oForm->addField('misc', '', array('type' => 'title', 'title' => 'Position details'));
       $oForm->closeSection('pubField');


      $oForm->addField('select', 'language', array('label' => 'Language',
        'onchange' => 'alert(\'Please fill all the fields below in \'+ $(\'option:selected\', this).text()); '));
      $oForm->addOption('language', array('label' => 'English', 'value' => 'en'));

      if($oDbResult->getFieldValue('language') == 'jp')
        $oForm->addOption('language', array('label' => 'Japanese', 'value' => 'jp', 'selected' => 'selected'));
      else
        $oForm->addOption('language', array('label' => 'Japanese', 'value' => 'jp'));

      //specific for each language
      $oForm->addField('input', 'title', array('label' => 'Title', 'value' => $oDbResult->getFieldValue('title'),
        'class' => 'public_important_field'));
      $oForm->addField('input', 'career_level', array('label' => 'career level',
        'value' => $oDbResult->getFieldValue('career_level'), 'class' => 'public_important_field'));

      $oForm->addField('textarea', 'description', array('label' => 'Company/Job description',
        'value' => $oDbResult->getFieldValue('description'), 'class' => 'public_important_field', 'allowTinymce' => 1));
      $oForm->addField('textarea', 'responsabilities', array('label' => 'Responsibilities',
        'value' => $oDbResult->getFieldValue('responsabilities'), 'allowTinymce' => 1));
      $oForm->addField('textarea', 'requirements', array('label' => 'Requirements',
        'value' => $oDbResult->getFieldValue('requirements'), 'class' => 'public_important_field', 'allowTinymce' => 1));


      return $oForm->getDisplay();
    }

    /**
     * Create a position from form data
     * @param integer $pnPositionPk
     * @return array to be json encoded
     */
    private function _savePosition($pnPositionPk = 0)
    {
      if(!assert('is_integer($pnPositionPk)'))
        return array('error' => 'Missing parameters to save the position.');

      //update is tricky, need tabs in the form and manage multi lingual descriptions
      if(!empty($pnPositionPk))
      {
        $oDbResult = $this->_getModel()->getByPk($pnPositionPk, 'sl_position');
        $bread = $oDbResult->readFirst();
        if(!$bread)
          return array('error' => 'Could not find the position.');

        $asPosition = $oDbResult->getData();
      }
      else
        $asPosition = array();

      $oLogin = CDependency::getCpLogin();


      //field for sl_position table
      $asPosition['companyfk'] = (int)getValue('companyfk');
      if(empty($asPosition['companyfk']))
        return array('error' => __LINE__.' - You must select a company.');

      $asPosition['industryfk'] = (int)getValue('industryfk');
      if(empty($asPosition['industryfk']))
        return array('error' => __LINE__.' - You must select an industry.');

      $asPosition['location'] = (int)getValue('location');
      if(empty($asPosition['location']))
        return array('error' => __LINE__.' - You must select a location.');

      $asPosition['age_from'] = (int)getValue('age_from');
      $asPosition['age_to'] = (int)getValue('age_to');
      $asPosition['salary_from'] = (int)getValue('salary_from');
      $asPosition['salary_to'] = (int)getValue('salary_to');
      $asPosition['salary_to'] = (int)getValue('salary_to');
      $asPosition['salary_to'] = (int)getValue('salary_to');
      $asPosition['lvl_japanese'] = (int)getValue('japanese');
      $asPosition['lvl_english'] = (int)getValue('english');

      if(empty($pnPositionPk))
      {
        $asPosition['date_created'] = date('Y-m-d H:i:s');
        $asPosition['created_by'] = $oLogin->getUserPk();
        $asPosition['status'] = 1;
      }


      //field for sl_position_detail table
      $asPosition['language'] = filter_var(getValue('language'), FILTER_SANITIZE_STRING);
      $asPosition['title'] = filter_var(getValue('title'), FILTER_SANITIZE_STRING);
      if(empty($asPosition['title']))
        return array('error' => __LINE__.' - You must enter title.');

      $asPosition['career_level'] = filter_var(getValue('career_level'), FILTER_SANITIZE_STRING);
      $asPosition['description'] = filter_var(getValue('description'), FILTER_SANITIZE_STRING);
      $asPosition['requirements'] = filter_var(getValue('requirements'), FILTER_SANITIZE_STRING);
      $asPosition['responsabilities'] = filter_var(getValue('responsabilities'), FILTER_SANITIZE_STRING);
      $asPosition['content_html'] = filter_var(getValue('content_html'), FILTER_SANITIZE_STRING);
      $asPosition['is_public'] = (int)getValue('is_public');


      if(empty($pnPositionPk))
      {
        $nPositionPk = $this->_getModel()->add($asPosition, 'sl_position');
        if(empty($nPositionPk))
          return array('error' => __LINE__.' - Error while saving the position.');

        $asPosition['positionfk'] = $nPositionPk;

        $nPositionPk = $this->_getModel()->add($asPosition, 'sl_position_detail');
        if(empty($nPositionPk))
          return array('error' => __LINE__.' - Error while saving the position.');

      }
      else
      {
        $asPosition['sl_positionpk'] = (int)$asPosition['sl_positionpk'];
        $asPosition['created_by'] = (int)$asPosition['created_by'];
        $asPosition['status'] = (int)$asPosition['status'];
        $bUpdate = $this->_getModel()->update($asPosition, 'sl_position', 'sl_positionpk = '.$pnPositionPk);
        if(!$bUpdate)
          return array('error' => __LINE__.' - Error while saving the position.');

        $asPosition['positionfk'] = $pnPositionPk;

        $nPositionPk = $this->_getModel()->update($asPosition, 'sl_position_detail', 'positionfk = '.$pnPositionPk.' AND language = "'.$asPosition['language'].'"');
        if(empty($nPositionPk))
          return array('error' => __LINE__.' - Error while saving the position.');

      }

      $sURL = $this->_oPage->getAjaxUrl('555-005', CONST_ACTION_LIST, CONST_POSITION_TYPE_JD);
      return array('notice' => 'Position successfully saved.', 'action' => '
        goPopup.removeLastByType(\'layer\');
        var oConf = goPopup.getConfig();
        oConf.height = 725;  oConf.width = 1080;
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');
        ');
    }



    private function _deletePosition($pnPositionPk = 0)
    {
      if(!assert('is_integer($pnPositionPk)'))
        return array('error' => 'Missing parameters to save the position.');

      $oDbResult = $this->_getModel()->getPositionByPk($pnPositionPk);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array('error' => __LINE__.' - could not find the position');

      $oApplicant = $this->_getModel()->getPositionApplicant($pnPositionPk);
      if($oApplicant->numRows() > 0)
        return array('error' => __LINE__.' - Can not delete a position with attached candidates.');

      $this->_getModel()->deleteByPk($pnPositionPk,'sl_position');
      $this->_getModel()->deleteByFk($pnPositionPk,'sl_position_detail', 'positionfk');

      return array('notice' => 'Position has been deleted', 'action' => 'goPopup.removeLastByType(\'layer\'); ');
    }


    public function getPositionList($poQb = null, $pbAllData = false)
    {
      if(empty($poQb))
        $poQb = $this->_getModel()->getQueryBuilder();

      $poQb->setTable('sl_position', 'spos');
      if(!$poQb->hasSelect())
        $poQb->addSelect('*');

      $poQb->addJoin('inner', 'sl_position_detail', 'spde', 'spde.positionfk = spos.sl_positionpk');

      if($pbAllData)
      {
        $poQb->addJoin('inner', 'sl_company', 'scom', 'scom.sl_companypk = spos.companyfk');
        $poQb->addJoin('inner', 'sl_industry', 'sind', 'sind.sl_industrypk = spos.industryfk');
        $poQb->addJoin('inner', 'login', 'logi', 'logi.loginpk = spos.created_by');

      }
      $poQb->addOrder('positionfk DESC');

      $oDbResult = $this->_getModel()->executeQuery($poQb->getSql());
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array();

      return $oDbResult->getAll();
    }

    private function _getPositionList($poQb = null)
    {
      if(empty($poQb))
        $poQB = $this->_getModel()->getQueryBuilder();

      $poQB->setTable('sl_position', 'spos');
      $poQB->addSelect('*');
      $poQB->addJoin('inner', 'sl_position_detail', 'spde', 'spde.positionfk = spos.sl_positionpk');
      $poQB->addOrder('positionfk DESC');
      $oDbResult = $this->_getModel()->executeQuery($poQB->getSql());
      $bRead = $oDbResult->readFirst();

      if(!$bRead)
        return 'No position found';

      $sHTML = $this->_oDisplay->getTitleLine('Position list');
      $sHTML.= $this->_oDisplay->getCR(2);

      while($bRead)
      {
        $asPosition = $oDbResult->getData();

        $sHTML.= '['.var_export($asPosition, true).']<br /><br />';
        $bRead = $oDbResult->readNext();
      }

      return $sHTML;
    }


    private function _selectorCompanyPosition()
    {
      $sSearchString = getValue('q');
      if(empty($sSearchString))
        return 'no query string';

      $nLimit = 100;
      $pbAllPosition = (bool)getValue('all_pos', 0);
      $bActive = (bool)getValue('pos_active', 0);
      $bPlacement = (bool)getValue('placement', 0);
      $placement_manager = (bool)getValue('placement_manager', 0);

      $poQB = $this->_getModel()->getQueryBuilder();
      $poQB->setTable('sl_company', 'scom');
      $poQB->addSelect('spos.*, scom.name, scom.sl_companypk, spde.title');

      $poQB->addJoin('left', 'sl_position', 'spos', 'spos.companyfk = scom.sl_companypk');
      $poQB->addJoin('left', 'sl_position_detail', 'spde', 'spde.positionfk = spos.sl_positionpk');


      $sTerm = preg_replace('/[^0-9]/i', '', $sSearchString);
      if($sTerm == $sSearchString)
      {
        $poQB->addWhere('(sl_positionpk = '.(int)$sTerm.' OR sl_companypk = '.(int)$sTerm.')');
      }
      else
      {
        $asSearchTerm = explode(' ', $sSearchString);
        $asWhere = array();
        foreach($asSearchTerm as $sTerm)
        {
          $asWhere['cp'][] = ' scom.name LIKE '.$this->_getModel()->dbEscapeString('%'.$sTerm.'%').' ';
          $asWhere['pos'][] = ' spde.title LIKE '.$this->_getModel()->dbEscapeString('%'.$sTerm.'%').' ';
        }

        $poQB->addWhere('( ('.implode(' AND ', $asWhere['cp']).') OR ('.implode(' AND ', $asWhere['pos']).') )');
      }

      if($bActive)
      {
        $poQB->addJoin('inner', 'sl_position_link', 'spli',
                'spli.positionfk = spos.sl_positionpk AND spli.active = 1 AND spli.status > 0  AND spli.status < 200 ');
        $poQB->addWhere(' spde.title LIKE '.$this->_getModel()->dbEscapeString('%'.$sSearchString.'%'));
      }

      if($bPlacement)
      {
        //need all the active positions potentially not updated
        // OR the recently placed ones
        $sAMonthAgo = date('Y-m-d', strtotime('-1 month')).' 00:00:00';
        $poQB->addSelect('spli.date_created as date_filled');

        $poQB->addJoin('inner', 'sl_position_link', 'spli',
                'spli.positionfk = spos.sl_positionpk
                 AND(
                      (spli.active = 1 AND spli.status > 0  AND spli.status < 200)
                      OR
                      (spli.active = 0 AND spli.status >= 101  AND spli.status < 150)
                     )
                  ');
        // (spli.active = 0 AND spli.status >= 101  AND spli.status < 150 AND spli.date_created > "'.$sAMonthAgo.'" )
      }


      //$poQB->addOrder('spos.status DESC, scom.name ASC');
      $poQB->addOrder('spos.status DESC, sl_positionpk DESC');
      $poQB->addLimit($nLimit);

      $oDbResult = $this->_getModel()->executeQuery($poQB->getSql());
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
      {
        $asJsonData = array(0 => json_encode(array('id' => 'token_clear', 'title' => '', 'name' => 'no results found')));
        exit('['.implode(',', $asJsonData).']');
      }

      $nbResult = $oDbResult->numRows();

      $asJsonData = array();
      $asNotAvailable = array();
      $bFoundCompany = false;
      $bAvailablePosition = false;
      while($bRead)
      {
        $bFoundCompany = true;
        $asPosition = $oDbResult->getData();
        $asPosition['name'] = preg_replace('/[^a-z0-9 &]/i', '', $asPosition['name']);

        if($bPlacement && empty($asPosition['status']))
          $sFilled = '[closed]';
        else
          $sFilled = '';

        $asEntry = array();

        if($pbAllPosition || (int)$asPosition['status'] === 1
          || $sFilled == '[closed]' || $placement_manager)
        {
          $bAvailablePosition = true;
          $asEntry['id'] = $asPosition['sl_companypk'].'_'.$asPosition['sl_positionpk'];
          $asEntry['name'] = '['.$asPosition['name'].'] - position #'.$asPosition['sl_positionpk'].' '.$sFilled.' - '.$asPosition['title'];
          $asJsonData[$asEntry['id']] = json_encode($asEntry);
        }
        else
        {
          $asEntry['id'] = 'token_clear';
          $asEntry['name'] = '<em class="no_position">'.$asPosition['name'].' (no position)</em>';
          $asNotAvailable[$asEntry['id']] = json_encode($asEntry);
        }

        $bRead = $oDbResult->readNext();
      }

      if($bFoundCompany && !$bAvailablePosition)
      {
        $asEntry['id'] = 'token_clear';
        $asEntry['name'] = '';

        if(count($asNotAvailable) > 1)
          $asEntry['name'] = '<span class="search_warning">'.count($asNotAvailable).' companies found, but none has any available position.</span>';
        else
          $asEntry['name'] = '<span class="search_warning">1 company found, but there is no available position.</span>';

        $asNotAvailable[$asEntry['id']] = json_encode($asEntry);
        ksort($asNotAvailable);
      }


      if($nbResult >= $nLimit)
      {
        $asEntry['id'] = 'token_clear';
        $asEntry['name'] = '<span class="search_warning">'.$nLimit.'+ positions found... Search criteria too wide.</span>';
        $asNotAvailable[$asEntry['id']] = json_encode($asEntry);
        ksort($asNotAvailable);
      }

      $asJsonData = array_merge_recursive($asJsonData, $asNotAvailable);
      if(empty($asJsonData))
      {
        $asJsonData = array(0 => json_encode(array('id' => 'token_clear', 'title' => '', 'name' => 'no results found')));
      }
      exit('['.implode(',', $asJsonData).']');
    }


    private function _linkPositionForm($pasLink = array())
    {
      if(!assert('is_array($pasLink)'))
        return '';

      $bLocked = false;

      if(empty($pasLink))
      {
        $nCandidatePk = (int)getValue('candidatepk', 0);
        $nCompanyPk = (int)getValue('companypk', 0);
        $nPositionPk = (int)getValue('positionpk', 0);
        $nCurrentStatus = 0;

        $pasLink['credited_user'] = array();
        $pasLink['created_by'] = 0;

        //get the label for the autocomplete
        if(!empty($nCompanyPk) && !empty($nPositionPk))
        {
          $asCompany = CDependency::getComponentByUid('555-001')->getItemDescription($nCompanyPk, 'ppav', 'comp');
          if(!assert('!empty($asCompany)'))
            return '';

          $sCompany = $asCompany['label'];

          $oPosition = $this->_getModel()->getByPk($nPositionPk, 'sl_position');
          $bRead = $oPosition->readFirst();
          if(!$bRead)
          {
            assert('false; // '.__LINE__.' - can not fetch the position.');
            return '';
          }

          if((int)$oPosition->getFieldValue('status') != 1)
            $bLocked = true;

          $sPosition = $oPosition->getFieldValue('title');
          $sCpJdLabel = '['.$sCompany.'] - position #'.$nPositionPk.' - '.$sPosition;
          $sCpJdKey = $nCompanyPk.'_'.$nPositionPk;
        }
        else
          $sCpJdLabel = $sCpJdKey = '';

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_POSITION_TYPE_LINK, 0);
        $sTitle = 'POSITION ACTIVITY ...';
      }
      else
      {
        $nCandidatePk = (int)$pasLink['candidatefk'];
        $nCompanyPk = (int)$pasLink['companyfk'];
        $nPositionPk = (int)$pasLink['positionfk'];
        $nCurrentStatus = (int)$pasLink['status'];
        $sCompany = $pasLink['name'];
        $sPosition = $pasLink['title'];

        $oPosition = $this->_getModel()->getByPk($nPositionPk, 'sl_position');
        $bRead = $oPosition->readFirst();
        if(!$bRead)
        {
          assert('false; // '.__LINE__.' - can not fetch the position.');
          return '';
        }

        if((int)$oPosition->getFieldValue('status') != 1)
          $bLocked = true;


        $sCpJdLabel = '['.$sCompany.'] - position #'.$nPositionPk.': '.$sPosition;
        $sCpJdKey = $nCompanyPk.'_'.$nPositionPk;

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEEDIT, CONST_POSITION_TYPE_LINK, (int)$pasLink['sl_position_linkpk']);
        $sTitle = 'NEXT STAGE...';
      }


      $this->_oPage->addCssFile(self::getResourcePath().'css/sl_position.css');
      $sHTML = '';

      $nCandidateStatus = 0;
      if(!empty($nCandidatePk))
      {
        $asCandidate = $this->oCandidate->getItemDescription($nCandidatePk, '');

        if(empty($asCandidate))
        {
          $nCandidatePk = 0;
          $sHTML.= $this->_oDisplay->getBlocMessage('The candidate #'.$nCandidatePk.' could not be found.');
          $sCanddiate = '';
        }
        else
        {
          $asCandidate = $asCandidate[$nCandidatePk];
          $sCanddiate = '#'.$nCandidatePk.' '.$asCandidate['label'];
          $nCandidateStatus = (int)$asCandidate['status'];

          //candidate can be pitched no matter his status, but it needs to be assessed to go further
          if(!empty($nCurrentStatus) && $asCandidate['status'] < 4)
          {
            return $this->_oDisplay->getBlocMessage('<span class="font-bigger"><strong>Candidate needs to be updated:</strong><br /><br />
              The candidate status must be [ met ] or [ assessed ] in order to be keep goingh.<br />
              Please update the candidate profile before pitching the candidate to a position.<br /><br /></span>');
          }
        }
      }

      $oRight = CDependency::getComponentByName('right');
      $bAdmin = $oRight->canAccess('555-005', 'mng_credit');

      if($bLocked)
      {
        $sTitle = $this->_oDisplay->getTitle('Position inactive').$this->_oDisplay->getCR();
        $sTitle.= 'Position has been <b>filled</b> or <b>cancelled</b>. The candidate is not active anymore and the status can\'t be changed.'.$this->_oDisplay->getCR(2);

        if(!$bAdmin)
        {
          $sHTML.= $this->_oDisplay->getCR(2) . $this->_oDisplay->getBlocMessage($sTitle);
          return $sHTML;
        }
      }


      $oLogin = CDependency::getCpLogin();

      $oForm = $this->_oDisplay->initForm('linkPositionForm');
      $oForm->setFormParams('linkPositionForm', true, array('action' => $sURL));
      $oForm->setFormDisplayParams(array('noCancelButton' => true));

      $oForm->addField('input', 'userfk', array('type' => 'hidden', 'value' => $this->casUserData['pk']));

      $oForm->addField('misc', '', array('type' => 'title', 'title'=> $sTitle));
      $oForm->addField('misc', '', array('type' => 'text', 'text'=> ''));


      if(empty($nCurrentStatus))
      {
        $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_CANDI, 0, array('autocomplete' => 1));
        $oForm->addField('selector', 'candidatefk', array('label' => 'Candidate', 'url' => $sURL));
        if(!empty($nCandidatePk))
        {
          $oForm->addOption('candidatefk', array('label' => $sCanddiate, 'value' => $nCandidatePk));
        }

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_POSITION_TYPE_JD);
        $oForm->addField('selector', 'cp_jd_key', array('label' => 'Company name <br />or position title', 'url' => $sURL));
      }
      else
      {
        $oForm->addField('input', 'candidatefk', array('type' => 'hidden', 'value' => $nCandidatePk));
        $oForm->addField('input', 'cp_jd_key', array('type' => 'hidden', 'value' => $sCpJdKey));


        $oForm->addField('misc', '', array('type' => 'text', 'class' => 'readOnlyField', 'label' => 'Candidate', 'text' => $sCanddiate));

        $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, $nCompanyPk);
        $oForm->addField('misc', '', array('type' => 'text', 'class' => 'readOnlyField', 'label' => 'Company', 'text' => '<a href="javascript:;" onclick="popup_candi(this, \''.$sURL.'\');" >'.$sCompany.'</a>'));
        $oForm->addField('misc', '', array('type' => 'text', 'class' => 'readOnlyField', 'label' => 'Position', 'text' => '#'.$nPositionPk.' - '.mb_strimwidth($sPosition, 0, 70, '...')));
      }


      $oForm->addSection('availablePosition', array('class' => 'position_container'));
      $oForm->closeSection();

      $oForm->addField('select', 'status', array('label' => 'Status', 'prev-status' => $nCurrentStatus, 'onchange' =>
          'if($(this).val() == 101 && !$(this).attr(\'data-notified\'))
           {
             $(this).attr(\'data-notified\', 1);
             alert(\'If this candidate is set to [ placed ], his company will automatically be updated and all other candidates in play for this position will fall off. \');
           }

           if($(this).val() == 200 && !$(this).attr(\'data-alerted\'))
           {
             $(this).attr(\'data-alerted\', 1);
             alert(\'If set to [ fallen off ], you have to input the reason on the field below. \');
           }

           if($(this).val()> 100)
             $(\'#in_play_0_Id\').removeAttr(\'checked\').closest(\'.formFieldContainer\').css(\'opacity\', 0.3);
           else
             $(\'#in_play_0_Id\').closest(\'.formFieldContainer\').css(\'opacity\', 1);

           if($(this).val() >= 50 && $(this).attr(\'prev-status\') < 50)
           {
             var oBoxContainer = $(\'input#in_play_0_Id\').parent();

             $(\'label.css-label\', oBoxContainer).animate({\'marginLeft\': \'10px\'}, 75).animate({\'marginLeft\': \'0px\'}, 75).
             animate({\'marginLeft\': \'10px\'}, 75).animate({\'marginLeft\': \'0px\'}, 75).
             animate({\'marginLeft\': \'10px\'}, 75).animate({\'marginLeft\': \'0px\'}, 75).
             animate({\'marginLeft\': \'10px\'}, 75).animate({\'marginLeft\': \'0px\'}, 75,
             function()
             {
               $(\'input#in_play_0_Id\').attr(\'checked\', \'checked\');
             });
           }

           '));

      $asStatusList = $this->_getStatusList($nCurrentStatus);
      if($nCandidateStatus < 2 && !$bAdmin)
      {
         $oForm->addoption('status', array('label' => $asStatusList[1], 'value' => 1));
         $oForm->addField('misc', '', array('type' => 'text', 'label' => '&nbsp;', 'text' => '<em style="color: #999; font-size: 11px;">* limited by candidate status. Assessed the candidate to unlock next stages.</em>'));

      }
      else
      {
        foreach($asStatusList as $nStatus => $sLabel)
        {
          if($nCurrentStatus <= 101)
          {
            if($nStatus > $nCurrentStatus)
            {
                $oForm->addoption('status', array('label' => $sLabel, 'value' => $nStatus));
            }
          }
          else
          {
            $oForm->addoption('status', array('label' => $sLabel, 'value' => $nStatus, 'selected' => (($nCurrentStatus == $nStatus)? 'selected': '') ));
          }
        }

        if($bAdmin)
        {
          $oForm->addoption('status', array('selected' => 'selected' , 'label' => '-- DBA: keep same status --', 'value' => -999));
        }
      }


      // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
      // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
      //asked to change it back ...  so I just hide it for now, I feel it coming again

      /*
      $asBoxOption = array('legend' => '<span class="warning">Candidate in play ?</span>', 'label' => '&nbsp;', 'value' => 1);
      if(!empty($nCurrentStatus))
      {
        //If previous stage was in play, this new stage inherit it
        $nPreviouslyInPlay = $this->_getModel()->isCandidateInPlay($nCandidatePk, $nPositionPk);
        if($nPreviouslyInPlay > 0)
        {
          $asBoxOption['checked'] = 'checked';
          $asBoxOption['label'] = '<em class="imp_notice">Was in play in previous stage</em>';
          $asBoxOption['onchange'] = '
            if($(this).is(\':checked\'))
            {     $(this).parent().find(\'label em\').html(\'maintain in play status\'); }
            else{ $(this).parent().find(\'label em\').html(\'will not be in play for this position\'); } ';
        }
      }
      $oForm->addField('checkbox', 'in_play', $asBoxOption);
      $oForm->setFieldDisplayParams('in_play', array('class' => 'hidden'));*/



      $oForm->addField('textarea', 'comment', array('label' => 'Note / comment'));
      $oForm->addField('select', 'validity', array('label' => 'Reset after'));
      $oForm->addoption('validity', array('label' => '1 week', 'value' => 0.25));
      $oForm->addoption('validity', array('label' => '2 weeks', 'value' => 0.5));
      $oForm->addoption('validity', array('label' => '1 month', 'value' => 1));
      $oForm->addoption('validity', array('label' => '3 months', 'value' => 3));
      $oForm->addoption('validity', array('label' => '6 months', 'value' => 6, 'selected' => 'selected'));
      $oForm->addField('misc', '', array('type' => 'text', 'label' => '&nbsp;', 'text'=> '<em style="color: #999; font-size: 11px;">* after N months (above) without update, the status will automatically switch to "stalled". </em>'));

      //dump($pasLink['credited_user']);
      //dump($pasLink['credited_by']);

      $nCurrentUser = $oLogin->getUserPk();
      //if(empty($nCurrentStatus) || in_array($nCurrentUser, $pasLink['credited_by']) || $bAdmin)
      //{
        $oForm->addField('misc', '', array('type' => 'text', 'text'=> ''));

        $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_user' => 1));
        $oForm->addField('selector', 'credited_user', array('label' => 'Credited researcher', 'nbresult' => 8, 'url' => $sURL));

        if(!empty($pasLink['credited_user']))
        {
          foreach($pasLink['credited_user'] as $nKey => $nUserPk)
          {
            if($nCurrentUser == $pasLink['credited_by'][$nKey] || $bAdmin)
            {
              $oForm->addOption('credited_user', array('label' => $oLogin->getUserName($nUserPk), 'value' => $nUserPk));
              unset($pasLink['credited_user'][$nKey]);
            }
          }
        }
      //}

      if(!empty($pasLink['credited_user']))
      {
        $asCredited = array();
        foreach($pasLink['credited_user'] as $sLoginFk)
          $asCredited[] = $oLogin->getUserName((int)$sLoginFk);

        $oForm->addField('input', 'credited_user_locked', array('type' => 'hidden', 'value' => implode(',', $pasLink['credited_user'])));
        $oForm->addField('misc', '', array('type' => 'text', 'class' => 'readOnlyField', 'label' => 'Set before', 'text'=> implode('&nbsp;,&nbsp;&nbsp;&nbsp;', $asCredited)));
      }

      $oForm->addField('misc', '', array('label' => '&nbsp;', 'type' => 'text', 'text'=> '<em style="color: #999; font-size: 11px;">* select who should be credited for this candidate.</em>'));
      $oForm->addField('input', 'confirm', array('type' => 'hidden', 'value' => 0));



      return $sHTML . $oForm->getDisplay();
    }


    private function _savePositionLink($pnLinkPk = 0)
    {
      if(!assert('is_integer($pnLinkPk)'))
        return array('error' => 'Missing parameters.');

      $oRight = CDependency::getComponentByName('right');
      $bAdmin = $oRight->canAccess('555-005', 'mng_credit');

      $asData = array();
      if(!empty($pnLinkPk))
      {
        //check the link and fetch data
        $asCurrentPhase = $this->_getModel()->getPositionByLinkPk($pnLinkPk);
        if(empty($asCurrentPhase))
          return array('error' => __LINE__.' - Could not find current position data .');

        if(!$bAdmin && (int)$asCurrentPhase['pos_active'] != 1)
          return array('error' => __LINE__.' - The position has been filled or cancelled.');
      }

      $asData['candidatefk'] = (int)getValue('candidatefk', 0);
      $asData['status'] = (int)getValue('status', 0);
      $asData['created_by'] = (int)getValue('userfk', 0);
      $asData['comment'] = getValue('comment');
      $asData['date_created'] = date('Y-m-d H:i:s');
      $asData['date_created'] = date('Y-m-d H:i:s');

      /*
      $asData['in_play'] = (int)getValue('in_play', 0);*/
      if($asData['status'] >= 50 && $asData['status'] <= 100)
        $asData['in_play'] = 1;
      else
        $asData['in_play'] = 0;

      $asData['credited_user'] = array();
      $sLoginPks = getValue('credited_user');
      $sLockedPks = getValue('credited_user_locked');
      if(!empty($sLoginPks))
      {
        $asData['credited_user'] = explode(',', $sLoginPks);
        if(empty($asData['credited_user']) && !is_arrayOfInt($asData['credited_user']))
          return array('error' => __LINE__.' - The selected researchers are incorrect.');
      }


      if(empty($asData['comment']))
        $asData['comment'] = null;
      else
        $asData['comment'] = sanitizeHtml($asData['comment']);

      $nValidity = (float)getValue('validity', 0);
      $sCpJdKey = getValue('cp_jd_key');


      if(!is_integer($asData['status']) || !is_key($asData['created_by']) || empty($nValidity))
        return array('error' => __LINE__.' - Form parameters are missing.');

      if(!is_key($asData['candidatefk']))
        return array('error' => __LINE__.' - You need to select a candidate.');

      $asKeys = explode('_', $sCpJdKey);
      if(count($asKeys) != 2)
        return array('error' => __LINE__.' - Missing company / position');

      $nCompanyPk = (int)$asKeys[0];
      $nPositionPk = (int)$asKeys[1];
      if(!is_key($nCompanyPk) || !is_key($nPositionPk))
        return array('error' => __LINE__.' - Form parameters are missing.');

      //----------------------
      //check if candidate and company are ok
      $asCompany = $this->oCandidate->getItemDescription($nCompanyPk, '', 'comp');
      if(empty($asCompany))
        return array('error' => __LINE__.' - Could not find the company.');

      $asCandidate = $this->oCandidate->getCandidateData($asData['candidatefk'], true);
      if(empty($asCandidate))
        return array('error' => __LINE__.' - Could not find the candidate.');
      //----------------------


      //check and fetch position data
      $oDbResult = $this->_getModel()->getPositionByPk($nPositionPk, 'sl_position');
      $bRead = $oDbResult->readFirst();
      if(empty($bRead))
        return array('error' => __LINE__.' - Could not find the position.');

      $asPosition = $oDbResult->getData();


      if($asData['status']!= -999)
      {
        $bAdminUpdate = false;

        $asStatus = $this->_getStatusList($asData['status']);
        if(!isset($asStatus[$asData['status']]))
          return array('error' => __LINE__.' - Status is incorrect.');


        if(empty($pnLinkPk))
        {
          //====================================================================================
          //check if the candidate is already in play on this position
          $oDbResult = $this->_getModel()->getByWhere('sl_position_link', 'positionfk = '.$nPositionPk.' AND candidatefk = '.$asData['candidatefk'].' AND active = 1 ');
          $bRead = $oDbResult->readFirst();
          if($bRead)
            return array('error' => __LINE__.' - It seems that candidate is already in play for this position.');

        }
        else
        {
          //====================================================================================
          //check if the candidate is already in THIS STATUS for THIS position
          if($asCurrentPhase['status'] == $asData['status'])
            return array('error' => __LINE__.' - This candidate is already - '.$asStatus[$asData['status']].'- for this position.');
        }


        //-----------------------------
        //check status
        if(empty($pnLinkPk) && $asData['status'] > 3)
          return array('error' => __LINE__.' - Status can\'t be more than a CCM1 since it\'s the first application.');

        if($asData['status'] == 200 && empty($asData['comment']))
          return array('error' => __LINE__.' - Please write the reason this candidate has fallen off.');


        if($asData['status'] > 1 && $asData['status'] < 150)
        {
          $asItem = array(CONST_CP_UID => '555-001', CONST_CP_ACTION => CONST_ACTION_VIEW, CONST_CP_TYPE => CONST_CANDIDATE_TYPE_CANDI, CONST_CP_PK => $asData['candidatefk']);
          $oShareSpace = CDependency::getComponentByName('sharedspace');
          $asDocument = $oShareSpace->getDocuments(0, $asItem);
          if(empty($asDocument))
            return array('error' => __LINE__.' - There is no resume on this candidate profile. Please upload the resume before adding the candidate to the position.
              <br /><a href="javascript:;" style="color: red; font-size: inherit;" onclick="$(this).closest(\'.ui-dialog\').find(\'.ui-button\').click(); var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550;  goPopup.setLayerFromAjax(oConf, \'/index.php5?uid=999-111&ppa=ppaa&ppt=shdoc&ppk=0&cp_uid=555-001&cp_action=ppav&cp_type=candi&cp_pk='.$asData['candidatefk'].'&callback=refresh_candi&pg=ajx&pclose=1 \'); " >Add a document here</a>');

          $bHasResume = false;
          foreach($asDocument as $asDoc)
          {
            if($asDoc['doc_type'] == 'resume')
            {
              $bHasResume = true;
              break;
            }
          }

          if(!$bHasResume)
           return array('error' => __LINE__.' - There is no resume on this candidate profile. Please upload the resume before adding the candidate to the position.
              <br /><a href="javascript:;" style="color: red; font-size: inherit;" onclick="$(this).closest(\'.ui-dialog\').find(\'.ui-button\').click(); var oConf = goPopup.getConfig(); oConf.width = 950; oConf.height = 550;  goPopup.setLayerFromAjax(oConf, \'/index.php5?uid=999-111&ppa=ppaa&ppt=shdoc&ppk=0&cp_uid=555-001&cp_action=ppav&cp_type=candi&cp_pk='.$asData['candidatefk'].'&callback=refresh_candi&pg=ajx&pclose=1 \'); " >Add a document here</a>');
        }

        //check if the link already exists:
        //same candidate + same position + same user (alert if different user)


        //needed below
        $oPage = CDependency::getCpPage();
        $oLogin = CDependency::getCpLogin();
        $nUser = $oLogin->getUserPk();

        //====================================================================================
        // check if the candidate is in play for another position -> email to pos_link_creators
        // check if there are other candidates, from other cons, in play for this position.
        if(empty($pnLinkPk))
        {
          $asNotification = $this->_monitorNewApplication($asPosition, $asData, $asCandidate);
          if(!empty($asNotification))
            return $asNotification;
        }
        else
        {
          //====================================================================================
          // !! We never delete an application. !!
          // Editing means changing status (new row), and settup up previous entry to inactive
          $asUpdate = array();
          $asUpdate['active'] = 0;
          $asUpdate['date_expire'] = date('Y-m-d H:i:s');
          $bUpdate = $this->_getModel()->update($asUpdate, 'sl_position_link', 'positionfk = '.$asCurrentPhase['positionfk'].' AND candidatefk = '.$asCurrentPhase['candidatefk']);

          if(!$bUpdate)
            return array('error' => __LINE__.' - Unable to update the candidate status.');
        }


        $asData['sl_position_linkpk'] = null;
        $asData['positionfk'] = $nPositionPk;
        $asData['date_created'] = date('Y-m-d H:i:s');

        if($nValidity >= 1)
        {
          $asData['date_expire'] = date('Y-m-d H:i:s', strtotime('+'.$nValidity.' months'));
        }
        else
        {
          $asData['date_expire'] = date('Y-m-d H:i:s', strtotime('+'.(4*$nValidity).' weeks'));
        }


        // -=- -=- -=- -=- =- -=- -=- -=- =- -=- -=- -=- =- -=- -=- -=-
        //last status is always the one active
        $asData['active'] = 1;

        $nLinkPk = $this->_getModel()->add($asData, 'sl_position_link');
        if(empty($nLinkPk))
          return array('error' => __LINE__.' - Could not link the candidate to the position.');

        $sMessage = 'Status changed to ['.$asStatus[$asData['status']].'] for position #'.$nPositionPk.' - '.$asPosition['title'];
        $this->_getModel()->_logChanges($asData, 'position ', $sMessage, '',
                array('cp_uid' => '555-001', 'cp_action' => 'ppae', 'cp_type' => 'candi', 'cp_pk' => $asData['candidatefk']));

        //add a note
        $oNote = CDependency::getComponentByName('sl_event');
        $oNote->addNote($asData['candidatefk'], 'activity', $sMessage, $nUser);
      }
      else
        $bAdminUpdate = true;
      // end of -999 // no change to the status


      //====================================================================================
      //delete credited researchers and add it again

      $sWhere = 'positionfk = '.$nPositionPk.' AND candidatefk = '.$asData['candidatefk'];
      if(!empty($sLockedPks))
        $sWhere.= ' AND loginfk NOT IN ('.$sLockedPks.')';

      $bDone = $this->_getModel()->deleteByWhere('sl_position_credit', $sWhere);
      if(!$bDone)
        return array('error' => __LINE__.' - Error while saving data.');

      if(!empty($asData['credited_user']))
      {
        $asCredited = array();
        foreach($asData['credited_user'] as $sUserFk)
        {
          $asCredited['positionfk'][] = $nPositionPk;
          $asCredited['candidatefk'][] = $asData['candidatefk'];
          $asCredited['created_by'][] = $asData['created_by'];
          $asCredited['date_created'][] = $asData['date_created'];
          $asCredited['loginfk'][] = (int)$sUserFk;
        }
        $bDone = (bool)$this->_getModel()->add($asCredited, 'sl_position_credit');
        if(!$bDone)
          return array('error' => __LINE__.' - Could not save data.');
      }


      $sURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $asData['candidatefk'], array('check_profile' => 1));
      $asReturn = array();

      //====================================================================================
      //====================================================================================
      //====================================================================================
      //Lot of things to do when placing a candidate... big update coming next
      if($asData['status'] == 101)
      {

        $bUpdate = $this->_updatePlacedposition($asPosition, (int)$asData['candidatefk'], (int)$asData['created_by']);
        if(!$bUpdate)
          return array('error' => __LINE__.' - Could update position data.');

        // Add company history log entry
        $oNote = CDependency::getComponentByName('sl_event');
        $sNote = 'Placement !<br />';
        $sNote.= 'The '.date('Y-m-d').', this candidate has moved from [ #'.$asCandidate['sl_companypk'].' - '.$asCandidate['company_name'].'] ';
        $sNote.= 'to '.$asCompany[$nCompanyPk]['label'].'<br />';

        //add a note from system user
        $oNote->addNote($asData['candidatefk'], 'cp_history', $sNote, $nUser);
        $oNote->addNote($asData['candidatefk'], 'cp_hidden', $asCandidate['company_name'], $nUser);


        //Update candidate company... least we can do to make sure data is correct, we'll open the form after
        //update industry with position industry too ?
        $asUpdate = array('companyfk' => $nCompanyPk);
        $bUpdate = $this->oCandidate->quickUpdateProfile($asUpdate, $asData['candidatefk'], true);
        if(!$bUpdate)
          return array('error' => __LINE__.' - Could update candidate company.');


        // open form update
        $sEditURL = $oPage->getAjaxUrl('555-001', CONST_ACTION_EDIT, CONST_CANDIDATE_TYPE_CANDI, $asData['candidatefk']);
        $asReturn = array('notice' => __LINE__.' - Candidate updated.',
          'action' => ' alert(\'Candidate company has been changed, please update the rest of his profile.\');
            goPopup.removeLastByType(\'layer\');
            view_candi(\''.$sURL.'\', \'#tabLink8\');
            var oConf = goPopup.getConfig(); oConf.height = 725; oConf.width = 1080;
            goPopup.setLayerFromAjax(oConf, \''.$sEditURL.'\');
            ');
      }

      //Finally: notify people the candidate status has changed (remove the current user obviosuly)
      $asCandidate['sl_candidatepk'] = (int)$asCandidate['sl_candidatepk'];
      $asFollower = $this->oCandidate->getCandidateRm($asCandidate['sl_candidatepk'], true, false);
      if(isset($asFollower[$nUser]))
        unset($asFollower[$nUser]);

      //no RM alert if admin doesn't change status
      if(!$bAdminUpdate && !empty($asFollower))
      {
        $oMail = CDependency::getComponentByName('mail');
        $sDirectURL = $oPage->getUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $asCandidate['sl_candidatepk']);

        $sSubject = 'RM alert - Candidate #'.$asCandidate['sl_candidatepk'].' - status changed ';
        $sContent = 'The candidate <a href="'.$sDirectURL.'">#'.$asCandidate['sl_candidatepk'].' - '.$asCandidate['firstname'].' '.$asCandidate['lastname'].
                '</a> has been updated.<br /> His status changed to ['.$asStatus[$asData['status']].']
                  for the position #'.$nPositionPk.' - '.$asPosition['title'].'.<br /><br />
                  Please access Slistem for more details.';

        foreach($asFollower as $asUserData)
        {
          $sEmail = 'Dear '.$asUserData['name'].', <br /><br />';
          $sEmail.= $sContent;

          $oMail->createNewEmail();
          $oMail->setFrom(CONST_PHPMAILER_DEFAULT_FROM, CONST_CRM_MAIL_SENDER);
          $oMail->addRecipient($asUserData['email'], $asUserData['name']);
          $oMail->send($sSubject, $sEmail);
        }
      }


      /*//Link all saved... We update the candidate status if needed
       ====> check before opening the form status is > 3
      if($asCandidate['statusfk'] < 6)
      {
        $sQuery = 'UPDATE sl_candidate SET statusfk = 6 WHERE statusfk < 6 AND sl_candidatepk = '.$asCandidate['sl_candidatepk'];
        $this->_getModel()->executeQuery($sQuery);

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$asCandidate['sl_candidatepk']);

        $this->_getModel()->_logChanges(array('statusfk' => '6'), 'user_history', 'Candidate '.$asTmp['date_meeting'].'.<br /> &rarr; status changed to [Interview set]', '',
              array('cp_uid' =>$this->csUid, 'cp_action' => 'ppae', 'cp_type' => CONST_CANDIDATE_TYPE_CANDI, 'cp_pk' => $asTmp['candidatefk']));
      }*/

      if(!empty($asReturn))
        return $asReturn;

      return array('notice' => __LINE__.' - Candidate status updated.',
          'action' => 'goPopup.removeLastByType(\'layer\'); view_candi(\''.$sURL.'\',\'#tabLink8\'); ');
    }


    private function _updatePlacedposition($pasPosition, $pnCandidatePk, $pnUserPk)
    {
      if(!assert('is_array($pasPosition) && is_key($pnUserPk)'))
        return false;

      if(!assert('isset($pasPosition[\'sl_positionpk\']) && !empty($pasPosition[\'sl_positionpk\'])'))
        return false;

      $sNow = date('Y-m-d H:i:s');
      $nPositionPk = (int)$pasPosition['sl_positionpk'];
      $nCandidatePk = (int)$pnCandidatePk;

      //1. get all pso_link/candidates still active on this position (except currenlty placed one).
      $sQuery = 'SELECT DISTINCT(spli.positionfk || spli.candidatefk) as link_key, spli.*, slog.email,
        slog.firstname, slog.lastname,
        scan.firstname as candi_firstname, scan.lastname as candi_lastname
        FROM sl_position_link as spli
        LEFT JOIN shared_login as slog ON (slog.loginpk = spli.created_by)
        LEFT JOIN sl_candidate as scan ON (scan.sl_candidatepk = spli.candidatefk)
        WHERE spli.active = 1 AND spli.candidatefk != '.$nCandidatePk.' AND spli.positionfk = '.$nPositionPk;

      $oDbResult =  $this->_getModel()->executeQuery($sQuery);
      $bRead = $oDbResult->readFirst();

      $asCandidate = array();
      $asConsultant = array();
      while($bRead)
      {
        $asCandidate[] = (int)$oDbResult->getFieldValue('candidatefk');
        $sURL = $this->_oPage->getUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$oDbResult->getFieldValue('candidatefk'));

        $nLoginPk = (int)$oDbResult->getFieldValue('created_by');
        $asConsultant[$nLoginPk]['email'] = $oDbResult->getFieldValue('email');
        $asConsultant[$nLoginPk]['firstname'] = $oDbResult->getFieldValue('firstname');
        $asConsultant[$nLoginPk]['lastname'] = $oDbResult->getFieldValue('lastname');
        $asConsultant[$nLoginPk]['candidate'][] = '<a href="'.$sURL.'">#'.$oDbResult->getFieldValue('candidatefk').'</a>&nbsp;&nbsp; - &nbsp;&nbsp;<a href="'.$sURL.'">'.$oDbResult->getFieldValue('candi_lastname').' '.$oDbResult->getFieldValue('candi_firstname').'</a>';

        $bRead = $oDbResult->readnext();
      }

      if(empty($asCandidate))
        return true;

      //2. set all those to inactve
      $sQuery = 'UPDATE sl_position_link SET active = 0 WHERE positionfk = '.$nPositionPk.' AND candidatefk != '.$nCandidatePk;
      $this->_getModel()->executeQuery($sQuery);


      $oLogin = CDependency::getComponentByName('login');

      //3. add a system fallen off step
      foreach($asCandidate as $nCanduidatefk)
      {
        $asLink = array('positionfk' => $nPositionPk, 'candidatefk' => $nCanduidatefk, 'date_created' => $sNow,
            'status' => 251, 'created_by' => -1, 'comment' => 'Position filled by '.$oLogin->getUserLink($pnUserPk),
                'date_expire' => $sNow, 'active' => 1);

        $this->_getModel()->add($asLink, 'sl_position_link');
      }


      //===========================================================
      //Notify consultants their candidates are not in play anymore

      $oMail = CDependency::getComponentByName('mail');

      $sURL = $this->_oPage->getUrl($this->csUid, CONST_ACTION_VIEW, CONST_POSITION_TYPE_JD, $nPositionPk);
      $sPosition = 'Position #'.$nPositionPk.' - <a href="'.$sURL.'" >'.$pasPosition['title'].'</a> has been <b>filled</b>.<br />
        Following candidates status has been switched to "position filled".<br /><br />';

      foreach($asConsultant as $asLoginData)
      {

        $oMail->createNewEmail();
        $oMail->setFrom(CONST_CRM_MAIL_SENDER, 'Slistem notification');
        $oMail->addRecipient($asLoginData['email'], $asLoginData['lastname'].' '.$asLoginData['firstname']);

        $sContent = 'Dear '.$asLoginData['firstname'].',<br /><br />';
        $sContent.= count($asLoginData['candidate']).' of your active candidates has/have been edited. The position they were pitched for has been filled.';
        $sContent.= '<br/>Please see the details below.<br /><br />';
        $sContent.= $sPosition;
        $sContent.= '<div style="margin: 5px 0; padding: 10px; border: 1px solid #dedede;">&rarr; '.implode('<br />&rarr; ', $asLoginData['candidate']).'</div>';

        $oMail->send('Sl[i]stem alert - A position has been filled', $sContent);
      }

      return true;
    }






    //------------------------------------------------------
    //  Public methods
    //------------------------------------------------------

    /**
     * Return the list of the available status with the ccm adjusted to the current value
     * Stalled should be a status used only by cron jobs and display (specific case)
     * @param integer $pnCurrentStatus
     * @param boolean $pbAddStalled
     */
    public function getStatusList($pbAllStatus = true, $pbFormatedList = false, $pbActiveOnly = false)
    {
      if($pbAllStatus)
        $asStatus = $this->_getStatusList(0, true, true);
      else
        $asStatus = $this->_getCurrentStatusList($pbActiveOnly);

      if(!$pbFormatedList)
        return $asStatus;

      $asFormated = array();
      foreach($asStatus as $nStatus => $sLabel)
      {
        $asFormated[] = array('label' => $sLabel, 'value' => $nStatus);
      }

      return $asFormated;

    }

    private function _getStatusList($pnCurrentStatus = 0, $pbAddStalled = false, $pbAll = false)
    {
      $asStatus = array();
      $asStatus[1] = 'pitched';
      $asStatus[2] = 'resume sent';

      if($pbAll || ($pnCurrentStatus > 50 && $pnCurrentStatus <= 76))
      {
        for($nCount = 51; $nCount < 76; $nCount++)
        {
          $asStatus[$nCount] = 'CCM'.($nCount-50);
          if(!$pbAll && $nCount > $pnCurrentStatus)
            break;
        }
      }
      else
        $asStatus[51] = 'CCM1';

      $asStatus[100] = 'offer';
      $asStatus[101] = 'placed';

      if($pbAll || $pbAddStalled)
      {
        $asStatus[150] = 'stalled';
        $asStatus[151] = 'expired';
      }

      if($pnCurrentStatus == 150)
        $asStatus[150] = 'stalled';

      if($pnCurrentStatus == 151)
        $asStatus[151] = 'expired';


      $asStatus[200] = 'fallen off';
      $asStatus[201] = 'not interested';

      if($pbAll)
      {
        $asStatus[250] = 'Cancelled by client';
        $asStatus[251] = 'Position filled';
      }

      return $asStatus;
    }


    private function _getCurrentStatusList($pbActiveOnly = false)
    {
      $asAllStatus = $this->_getStatusList(0, true, true);

      $oDbResult = $this->_getModel()->getCurrentStatus($pbActiveOnly);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return $asAllStatus;

      $asCurrentStatus = array();
      while($bRead)
      {
        $nStatus = (int)$oDbResult->getFieldValue('status');
        $asCurrentStatus[$nStatus] = $asAllStatus[$nStatus];
        $bRead = $oDbResult->readNext();
      }

      return $asCurrentStatus;
    }




    public function getApplication($pnCandidatePk, $pbActiveOnly = true, $pbActiveAndFinal = false)
    {
      $asPosition = array('active' => array(), 'history' => array());

      if(!assert('is_key($pnCandidatePk) && is_bool($pbActiveOnly) && is_bool($pbActiveAndFinal)'))
        return $asPosition;


      $oDbResult = $this->_getModel()->getApplication($pnCandidatePk, $pbActiveOnly, $pbActiveAndFinal,  true);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return $asPosition;

      $asStatus = $this->_getStatusList(0, false, true);
      //dump($oDbResult->getAll());

      while($bRead)
      {
        $asPositionData = $oDbResult->getData();
        $asPositionData['current_status'] = (int)$asPositionData['current_status'];

        if(!assert('isset($asStatus[$asPositionData[\'current_status\']]) /* ['.$asPositionData['current_status'].'] */'))
          $asPositionData['status_label'] = 'unknown';
        else
          $asPositionData['status_label'] = $asStatus[$asPositionData['current_status']];


        //active shows what link in the history is the active (usually the last one)
        //can only have 1 active link position/candidate active at the time
        if($asPositionData['active'])
        {
          if($asPositionData['current_status'] <= 151)
            $sSection = 'active';
          else
            $sSection = 'inactive';

          //only get the first one
          if(!isset($asPosition[$sSection][(int)$asPositionData['sl_positionpk']]))
            $asPosition[$sSection][(int)$asPositionData['sl_positionpk']] = $asPositionData;
        }
        else
        {
          $asPosition['history'][(int)$asPositionData['sl_positionpk']][] = $asPositionData;
        }

        $bRead = $oDbResult->readNext();
      }

      return $asPosition;
    }


    public function isCandidateInPlay($pnCandidatePk)
    {
      if(!assert('is_key($pnCandidatePk)'))
        return 0;

     return $this->_getModel()->isCandidateInPlay($pnCandidatePk);
    }

    public function getMaxActiveStatus($pnCandidatePk, $pnMaxStatus = 101, $psLimitDate = '')
    {
      return $this->_getModel()->getMaxActiveStatus($pnCandidatePk, $pnMaxStatus, $psLimitDate);
    }
    public function getLastInactiveStatus($pnCandidatePk, $pnMaxStatus = 250, $psLimitDate = '')
    {
      return $this->_getModel()->getLastInactiveStatus($pnCandidatePk, $pnMaxStatus, $psLimitDate);
    }


    public function getEmployeeApplicantTabContent($pnCompanyPk, $pbActiveOnly = true, $pbActiveAndFinal = false)
    {
      if(!assert('is_key($pnCompanyPk) && is_bool($pbActiveOnly) && is_bool($pbActiveAndFinal)'))
        return array('nb_result' => 0, 'content' => '<div class="entry"><div class="note_content">No employee in play.</div></div>');

      $oDbResult = $this->_getModel()->getCompanyApplication($pnCompanyPk, $pbActiveOnly, $pbActiveAndFinal);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array('nb_result' => 0, 'content' => '<div class="entry"><div class="note_content">No employee in play.</div></div>');

      $asStatus = $this->_getStatusList(0, false, true);
      $asPosition = array();

      while($bRead)
      {
        $asPositionData = $oDbResult->getData();

        $sRow = $this->_oDisplay->getBlocStart('', array('class' => 'entry'));

          $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$asPositionData['candidatefk']);
          $sRow.= '<div class="note_header">Employee #<a href="javascript:;" onclick="view_candi(\''.$sURL.'\');">'.$asPositionData['candidatefk'].'</a>
            - <a href="javascript:;" onclick="view_candi(\''.$sURL.'\');">'.$asPositionData['lastname'].' '.$asPositionData['firstname'].'</a></div>';

          if(isset($asStatus[$asPositionData['status']]))
            $sRow.= '<div class="note_content">[<b>'.$asStatus[$asPositionData['status']].'</b>] to position #'.$asPositionData['sl_positionpk'];
          else
            $sRow.= '<div class="note_content">[<b>-'.$asPositionData['status'].'-</b>] to position #'.$asPositionData['sl_positionpk'];

          $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, (int)$asPositionData['companyfk']);
          $sRow.= ' from <a href="javascript:;" onclick="view_comp(\''.$sURL.'\');">'.$asPositionData['company_name'].'</a></div>';

        $sRow.= $this->_oDisplay->getBlocEnd();
        $asPosition[] = $sRow;

        $bRead = $oDbResult->readNext();
      }

      if(empty($asPosition))
        return array('nb_result' => 0, 'content' => '<div class="entry"><div class="note_content">No employee in play.</div></div>');

      return array('nb_result' => count($asPosition), 'content' => implode('', $asPosition));
    }


    public function getCompanyPositionTabContent($pnCompanyPk)
    {
      if(!assert('is_key($pnCompanyPk)'))
        return array('nb_result' => 0, 'content' => 'No position found.');

      //$oDbResult = $this->_getModel()->getCompanyPosition($pnCompanyPk);
      /*
       * FROM sl_position as spos
      INNER JOIN sl_position_detail as spde ON (spde.positionfk = spos.sl_positionpk)
      WHERE companyfk = '.$pnCompanyPk.'
      GROUP BY spde.positionfk
      ORDER BY spos.status DESC, spos.date_created DESC ';
       */

      $oQb = $this->_getModel()->getQueryBuilder();
      $oQb->addWhere('scom.sl_companypk = '.$pnCompanyPk);
      $oQb->addOrder('spos.status DESC, spos.date_created DESC');

      $oDbResult = $this->_getModel()->getPositionList($oQb);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array('nb_result' => 0, 'content' => 'No employee in play.', 'nb_critical' => 0, 'nb_open' => 0, 'nb_close' => 0);

      $this->_oPage->addCssFile('/component/sl_event/resources/css/sl_event.css');

      $asPosition = array();
      $nOpened = 0;
      $nClosed = 0;
      $nCritical = 0;
      $sDateCritical = date('Y-m-d', strtotime('-1 year'));

      while($bRead)
      {
        $asPositionData = $oDbResult->getData();
        if($asPositionData['status'])
        {
          $nOpened++;
          if($asPositionData['date_created'] < $sDateCritical)
          {
            $nCritical++;
            $sClass = ' critical';
          }
          else
            $sClass = '';

          $sRow = $this->_oDisplay->getBlocStart('', array('class' => 'entry tab_cp_position active'.$sClass));
          $sRow.= '<div class="note_header"><span style="padding-left: 5px;">Open position</span>';
        }
        else
        {
          $nClosed++;
          $sRow = $this->_oDisplay->getBlocStart('', array('class' => 'entry tab_cp_position'));
          $sRow.= '<div class="note_header"><span style="padding-left: 5px;">Closed position</span>';
        }

        $nPitched = ($asPositionData['nb_play'] - $asPositionData['nb_active']);
        if($nPitched > 0)
          $sPlay = '<span class="candi-picthed">'.$nPitched.' candidate(s) pitched</span>&nbsp;&nbsp;|&nbsp;&nbsp;';
        else
          $sPlay = $nPitched.' candidate(s) pitched&nbsp;&nbsp;|&nbsp;&nbsp;';

          if($asPositionData['nb_active'] > 0)
            $sPlay.= '<span class="position_playing">'.$asPositionData['nb_active'].'</span>';
          else
            $sPlay.= $asPositionData['nb_active'];

        $sPlay.= ' currently in play';

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_POSITION_TYPE_JD, (int)$asPositionData['sl_positionpk']);
        $sRow.= ' <span class="note_date">'.$asPositionData['date_created'].'</span></div>
          <div>#<a href="javascript:;" onclick="var oConf = goPopup.getConfig();
          oConf.height = 600;
          oConf.width = 900;
          goPopup.setLayerFromAjax(oConf,\''.$sURL.'\');">'.$asPositionData['sl_positionpk'].' - '.$asPositionData['title'].'</a>
           <span style="color: #666; float: right; margin-right: 30px;"> '.$sPlay.'</span></div>';


        $sRow.= $this->_oDisplay->getBlocEnd();
        $asPosition[] = $sRow;

        $bRead = $oDbResult->readNext();
      }


      if(empty($asPosition))
        return array('nb_result' => 0, 'content' => 'No employee in play.', 'nb_critical' => 0, 'nb_open' => 0, 'nb_close' => 0);

      return array('nb_result' => count($asPosition), 'content' => implode('', $asPosition), 'nb_critical' => $nCritical, 'nb_open' => $nOpened, 'nb_close' => $nClosed);
    }


    private function _viewPosition($pnPositionPk)
    {
      if(!assert('is_key($pnPositionPk)'))
        return array('error' => __LINE__.' - bad parameter');

      $oDbResult = $this->_getModel()->getPositionByPk($pnPositionPk);

      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array('error' => __LINE__.' - could not find the position');


      $oLogin = CDependency::getCpLogin();
      $oApplicant = $this->_getModel()->getPositionApplicant($pnPositionPk);

      $this->_oPage->addCssFile(self::getResourcePath().'css/sl_position.css');
      $sHTML = $this->_oDisplay->getTitle('Position details', 'h3', true);
      //foreach($asPosition as $sVar => $sValue)

      $sURL = $this->_oPage->getAjaxUrl('555-005', CONST_ACTION_EDIT, CONST_POSITION_TYPE_JD, $pnPositionPk);
      $sHTML.= $this->_oDisplay->getBloc('', '<a href="javascript:;" onclick="
        goPopup.removeLastByType(\'layer\');
        var oConf = goPopup.getConfig();
        oConf.width = 950;
        oConf.height = 660;
        goPopup.setLayerFromAjax(oConf, \''.$sURL.'\');
        ">Edit position</a>', array('class' => 'position_edit'));

      if($oDbResult->getFieldValue('created_by') == $oLogin->getuserPk())
      {
        //delete position
        if($oApplicant->numRows() == 0)
        {
          $sURL = $this->_oPage->getAjaxUrl('555-005', CONST_ACTION_DELETE, CONST_POSITION_TYPE_JD, $pnPositionPk);

          $sHTML.= $this->_oDisplay->getBloc('', '<a href="javascript:;" style="color: red;" onclick="
            if(window.confirm(\'Delete this position ?\'))
            { AjaxRequest(\''.$sURL.'\'); }"
            >Delete position</a>', array('class' => 'position_edit', 'style' => 'top: 80px;'));
        }

      }


      $sDetail = '';
      $asLanguage = array();
      $bFirst = true;
      $bRead = $oDbResult->readFirst();
      while($bRead)
      {
        $asPosition = $oDbResult->getData();
        $asLanguage[] = '<a href="javascript:;" onclick="$(\'.pos_detail_lang\').hide(0); $(\'#pos_detail_'.$asPosition['language'].'\').fadeIn();" >'.$asPosition['language'].'</a>';

        if(!$bFirst)
          $sClass = 'hidden';
        else
          $sClass = '';



        $sText = strip_tags($asPosition['description']);
        if(mb_strlen($sText) > 300)
        {
          $asPosition['description'] = $this->_oDisplay->getTogglingText(substr($sText, 0, 300), $asPosition['description'], '... [more]', 'back');
        }

        $sText = strip_tags($asPosition['requirements']);
        if(mb_strlen($sText) > 300)
        {
          $asPosition['requirements'] = $this->_oDisplay->getTogglingText(substr($sText, 0, 300), $asPosition['requirements'], '... [more]', 'back');
        }

        $sText = strip_tags($asPosition['responsabilities']);
        if(mb_strlen($sText) > 300)
        {
          $asPosition['responsabilities'] = $this->_oDisplay->getTogglingText(substr($sText, 0, 300), $asPosition['responsabilities'], '...', 'back');
        }

        $sDetail.= $this->_oDisplay->getBlocStart('pos_detail_'.$asPosition['language'], array('class' => 'pos_detail_lang '.$sClass));

          $sDetail.= $this->_oDisplay->getBlocStart('', array('class' => 'position_detail_row'));
          $sDetail.= $this->_oDisplay->getBloc('', 'Position', array('class' => 'label'));
          $sDetail.= $this->_oDisplay->getBloc('', '#'.$asPosition['positionfk'].' - '.$asPosition['title'], array('class' => 'value'));
          $sDetail.= $this->_oDisplay->getBlocEnd();

          $sDetail.= $this->_oDisplay->getBlocStart('pos_detail_'.$asPosition['language'], array('class' => 'position_detail_row'));
          $sDetail.= $this->_oDisplay->getBloc('', 'Company/Job description', array('class' => 'label'));
          $sDetail.= $this->_oDisplay->getBloc('', $asPosition['description'], array('class' => 'value'));
          $sDetail.= $this->_oDisplay->getBlocEnd();

          $sDetail.= $this->_oDisplay->getBlocStart('pos_detail_'.$asPosition['language'], array('class' => 'position_detail_row'));
          $sDetail.= $this->_oDisplay->getBloc('', 'Responsibilities', array('class' => 'label'));
          $sDetail.= $this->_oDisplay->getBloc('', $asPosition['responsabilities'], array('class' => 'value'));
          $sDetail.= $this->_oDisplay->getBlocEnd();

          $sDetail.= $this->_oDisplay->getBlocStart('pos_detail_'.$asPosition['language'], array('class' => 'position_detail_row'));
          $sDetail.= $this->_oDisplay->getBloc('', 'Requiremensts', array('class' => 'label'));
          $sDetail.= $this->_oDisplay->getBloc('', $asPosition['requirements'], array('class' => 'value'));
          $sDetail.= $this->_oDisplay->getBlocEnd();

        $sDetail.= $this->_oDisplay->getBlocEnd();

        $bRead = $oDbResult->readNext();
        $bFirst = false;
      }


      if(count($asLanguage) > 1)
        $sHTML.= implode('&nbsp;&nbsp;', $asLanguage).'<br />'.$sDetail;
      else
        $sHTML.= $sDetail;

      if(empty($asPosition['industry']))
        $asPosition['industry'] = '<em class="light">no industry</em>';


      if(empty($asPosition['age_from']))
        $sAge = ' - ';
      else
        $sAge = $asPosition['age_from'].' to '.$asPosition['age_to'];

      if(empty($asPosition['salary_from']))
        $sSalary = ' - ';
      else
        $sSalary = formatNumber($asPosition['salary_from'], 'M').'M&yen; &nbsp;&nbsp;to&nbsp;&nbsp; '.formatNumber($asPosition['salary_to'], 'M').'M&yen;';


      $sHTML.= $this->_oDisplay->getBlocStart('', array('style' => 'margin: 15px 0; float: left;'));

        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'position_detail_row'));
          $sHTML.= $this->_oDisplay->getBloc('', 'Company', array('class' => 'label'));
          $sHTML.= $this->_oDisplay->getBloc('', '#'.$asPosition['sl_companypk'].' - '.$asPosition['name'], array('class' => 'value'));
        $sHTML.= $this->_oDisplay->getBlocEnd();

        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'position_detail_row'));
          $sHTML.= $this->_oDisplay->getBloc('', 'Industry', array('class' => 'label'));
          $sHTML.= $this->_oDisplay->getBloc('', $asPosition['industry'], array('class' => 'value'));
        $sHTML.= $this->_oDisplay->getBlocEnd();

        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'position_detail_row'));
          $sHTML.= $this->_oDisplay->getBloc('', 'Age', array('class' => 'label'));
          $sHTML.= $this->_oDisplay->getBloc('', $sAge, array('class' => 'value'));
        $sHTML.= $this->_oDisplay->getBlocEnd();

        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'position_detail_row'));
          $sHTML.= $this->_oDisplay->getBloc('', 'Salary', array('class' => 'label'));
          $sHTML.= $this->_oDisplay->getBloc('', $sSalary, array('class' => 'value'));
        $sHTML.= $this->_oDisplay->getBlocEnd();

        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'position_detail_row'));
          $sHTML.= $this->_oDisplay->getBloc('', 'Language lvl', array('class' => 'label'));
          $sHTML.= $this->_oDisplay->getBloc('', 'english: '.$asPosition['lvl_english'].'&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;japanese: '.$asPosition['lvl_japanese'], array('class' => 'value'));
        $sHTML.= $this->_oDisplay->getBlocEnd();


      $sHTML.= $this->_oDisplay->getFloathack();
      $sHTML.= $this->_oDisplay->getBlocEnd();



      $sHTML.= '<br /><br />';
      $sHTML.= $this->_oDisplay->getFloatHack();

      $bRead = $oApplicant->readFirst();
      if(!$bRead)
      {
        $sHTML.= '<br />';
        $sHTML.= $this->_oDisplay->getTitle('Activity on this position');
        $sHTML.= $this->_oDisplay->getBloc('', '<br /><em>No candidate in play for this posistion.</em>');
      }
      else
      {
        $asStatus = $this->_getStatusList(0, true, true);
        $asInPlay = array('active' => array(), 'inactive' => array());
        while($bRead)
        {
          $asCandidate = $oApplicant->getData();
          $sRow = $this->_oDisplay->getBlocStart('', array('class' => 'candidate_detail_row'));

            $sRow.= $this->_oDisplay->getBlocStart('', array('class' => 'candidate_detail_date'));
            $sRow.= substr($asCandidate['app_date'],0 , 10);
            $sRow.= $this->_oDisplay->getBlocEnd();

            $sRow.= $this->_oDisplay->getBlocStart('', array('class' => 'candidate_detail_name'));
            $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$asCandidate['sl_candidatepk']);
            $sRow.=  '<a href="javascript:;" onclick="popup_candi(this, \''.$sURL.'\'); ">#'.$asCandidate['sl_candidatepk'].' - '.$asCandidate['firstname'].' '.$asCandidate['lastname'].'</a>';
            $sRow.=  '<a href="javascript:;" class="floatRight"  onclick="goPopup.removeByType(\'layer\'); view_candi(\''.$sURL.'\'); ">
              <img style="padding-right: 20px; height: 14px; width: 14px;" title="View candidate details" src="/component/sl_candidate/resources/pictures/goto_16.png" /></a>';

            $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, (int)$asCandidate['companyfk']);
            $sRow.=  '<br />Working at <a href="javascript:;" onclick="view_comp(\''.$sURL.'\'); ">#'.$asCandidate['companyfk'].' - '.$asCandidate['company_name'].'</a>';
            $sRow.= $this->_oDisplay->getBlocEnd();

            $sRow.= $this->_oDisplay->getBlocStart('', array('class' => 'candidate_detail_status'));

            if($asCandidate['status'] < 101)
              $sRow.=  ' <span class="in_play">[<b>'.$asStatus[$asCandidate['app_status']].'</b>]</span>';
            else
              $sRow.=  ' [<b>'.$asStatus[$asCandidate['app_status']].'</b>]';

            $sRow.= $this->_oDisplay->getBlocEnd();

            $sRow.= $this->_oDisplay->getBlocStart('', array('class' => 'candidate_detail_expire'));

          if($asCandidate['status'] < 101)
          {
            $sRow.= ' expires the '.substr($asCandidate['date_expire'], 0, 10);
            $sRow.= $this->_oDisplay->getBlocEnd();
            $sRow.= $this->_oDisplay->getBlocEnd();
            $asInPlay['active'][] = $sRow;
          }
          else
          {
            if($asCandidate['status'] == 151)
              $sRow.= ' expired the '.substr($asCandidate['date_expire'], 0, 10);
            else
              $sRow.= ' the '.substr($asCandidate['date_expire'], 0, 10);

            $sRow.= $this->_oDisplay->getBlocEnd();
            $sRow.= $this->_oDisplay->getBlocEnd();
            $asInPlay['inactive'][] = $sRow;
          }

          $bRead = $oApplicant->readNext();
        }
      }

      $sHeader = $this->_oDisplay->getBlocStart('', array('class' => 'candidate_detail_row header'));
        $sHeader.= $this->_oDisplay->getBloc('', 'Started', array('class' => 'candidate_detail_date'));
        $sHeader.= $this->_oDisplay->getBloc('', 'Candidate & company', array('class' => 'candidate_detail_name'));
        $sHeader.= $this->_oDisplay->getBloc('', 'Status', array('class' => 'candidate_detail_status'));
        $sHeader.= $this->_oDisplay->getBloc('', 'Ends', array('class' => 'candidate_detail_expire'));
      $sHeader.= $this->_oDisplay->getBlocEnd();

      if(!empty($asInPlay['active']))
      {
        $sHTML.= $this->_oDisplay->getCR();
        $sHTML.= $this->_oDisplay->getTitle(count($asInPlay['active']).' candidates currently in play', 'h3 title_border_inplay');

        $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'position_candidates candi_active'));
        $sHTML.= $sHeader;
        $sHTML.= implode('', $asInPlay['active']);
        $sHTML.= $this->_oDisplay->getFloatHack();
        $sHTML.= $this->_oDisplay->getBlocEnd();

        if(count($asInPlay['inactive']))
        {
          $sHTML.= $this->_oDisplay->getCR();
          $sHTML.= $this->_oDisplay->getLink('show/hide details', 'javascript:;', array('onclick' => '$(this).parent().find(\'.candi_active\').fadeToggle();  '));
          $sHTML.= $this->_oDisplay->getSpace(5).' -- '.$this->_oDisplay->getSpace(5);
        }

         $sHTML.= $this->_oDisplay->getLink('view candidate list', 'javascript:;', array('class' => 'applicant_list',
          'onclick' => '
          $(\'#quickSearchForm input:visible\').val(\'\');
          $(\'#quickSearchForm input[name=position]\').val(\''.$pnPositionPk.'\');
          $(\'#quickSearchForm input[name=position_status]\').val(\'-100\');
          $(\'#quickSearchForm #alt_submit\').click();
          goPopup.removeByType(\'layer\');
          '));
      }

      if(!empty($asInPlay['inactive']))
      {

        $sHTML.= $this->_oDisplay->getCR(2);
        $sHTML.= $this->_oDisplay->getTitle(count($asInPlay['inactive']).' candidates previously in play', 'h3 title_border_played');

        if(count($asInPlay['active']))
          $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'position_candidates candi_inactive hidden'));
        else
          $sHTML.= $this->_oDisplay->getBlocStart('', array('class' => 'position_candidates candi_inactive'));

        $sHTML.= $sHeader;
        $sHTML.= implode('', $asInPlay['inactive']);
        $sHTML.= $this->_oDisplay->getFloatHack();
        $sHTML.= $this->_oDisplay->getBlocEnd();

        $sHTML.= $this->_oDisplay->getCR();
        $sHTML.= $this->_oDisplay->getLink('show/hide details', 'javascript:;', array('onclick' => '$(this).parent().find(\'.candi_inactive\').fadeToggle();  '));

        $sHTML.= $this->_oDisplay->getSpace(5).' -- '.$this->_oDisplay->getSpace(5);
        $sHTML.= $this->_oDisplay->getLink('view candidate list', 'javascript:;', array('class' => 'applicant_list',
          'onclick' => '
          $(\'#quickSearchForm input:visible\').val(\'\');
          $(\'#quickSearchForm input[name=position]\').val(\''.$pnPositionPk.'\');
          $(\'#quickSearchForm input[name=position_status]\').val(\'+101\');
          $(\'#quickSearchForm #alt_submit\').click();
          goPopup.removeByType(\'layer\');
          '));
      }

      $sHTML.= $this->_oDisplay->getCR(3);

      return array('data' => convertToUtf8($sHTML));
    }


    private function _positionList($poQb = null)
    {
      $oPage = CDependency::getCpPage();
      $oLogin = CDependency::getCpLogin();
      $oHTML = CDependency::getCpHtml();

      $oPage->addCssFile($this->getResourcePath().'css/sl_position.css');
      $oPage->addJsFile($this->getResourcePath().'js/sl_position.js');

      //dump($poQb);

      $sHTML = '';
      $nLimit = 150;
      $nPosition = 0;

      $bFilteredList = (bool)getValue('__filtered', 0);
      if(!$bFilteredList)
        $sHTML.= $this->_oDisplay->getBlocStart($this->csSearchId, array('class' => 'scrollingContainer'));

      $bSplitted = empty($poQb);
      if($bSplitted)
      {
        //dump($_SESSION['position_filter']);
        $sTitle = getValue('title');
        $nCompanyfk= (int)getValue('companyfk');
        $sPositionDate = getValue('pos_date');
        $nLoginfk = (int)getValue('loginfk');
        $sIndustryfk = (int)getValue('industryfk');

        if(empty($sTitle) && isset($_SESSION['position_filter']['title']))
          $sTitle = $_SESSION['position_filter']['title'];

        if(empty($nCompanyfk) && isset($_SESSION['position_filter']['company']))
          $nCompanyfk = (int)$_SESSION['position_filter']['company'];

        if(empty($sPositionDate) && isset($_SESSION['position_filter']['pos_date']))
          $sPositionDate = $_SESSION['position_filter']['pos_date'];

        if(empty($nLoginfk) && isset($_SESSION['position_filter']['created_by']))
          $nLoginfk = $_SESSION['position_filter']['created_by'];

        if(empty($sIndustryfk) && isset($_SESSION['position_filter']['industryfk']))
          $sIndustryfk = $_SESSION['position_filter']['industryfk'];


        $sHTML.= $oHTML->getTitle('Position list', 'h3', true);
        $sHTML.= $oHTML->getCR();

        $sHTML.= $oHTML->getBlocStart('', array('style' => 'border: 1px solid #ededed; padding: 5px 10px; margin: 0 0 15px 0;'));

        $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_POSITION_TYPE_JD, 0, array('qs' => 1, 'layer_stays' => 1));
        /*$sSubmitJs = 'var asContainer = goTabs.create();
        AjaxRequest(\''.$sURL.'\', \'body\', \'positionFilterFormId\',  asContainer[\'id\'], \'\', \'\', \'initHeaderManager();\');
        goTabs.select(asContainer[\'number\']); ';
        $sSubmitJs = ' AjaxRequest(\''.$sURL.'\', \'body\', \'positionFilterFormId\',  \'position_list_container\'); ';*/

        $oForm = $oHTML->initForm('positionFilterForm');
        $oForm->setFormParams('positionFilterForm', true, array('action' => $sURL, 'ajaxTarget' =>'position_list_container'));
        $oForm->setFormDisplayParams(array('noCancelButton' => true, /*'noSubmitButton' => true, */'columns' => 6));

        $oForm->addField('input', 'title', array('label' => 'ID/title', 'value' => $sTitle));
        if($sTitle)
          $oForm->setFieldDisplayParams('title', array('onclick' => 'expandField(this);'));
        else
          $oForm->setFieldDisplayParams('title', array('class' => 'compact', 'onclick' => 'expandField(this);'));


        $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_COMP, 0, array('autocomplete' => 1));
        $oForm->addField('selector', 'company', array('label' => 'Company', 'url' => $sURL));
        if(!empty($nCompanyfk))
        {
          $asCompany = $this->oCandidate->getItemDescription($nCompanyfk, CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP);
          if(!empty($asCompany))
          {
            $oForm->addOption('company', array('label' => $asCompany[$nCompanyfk]['label'], 'value' => $nCompanyfk));
            $sClass = '';
          }
        }
        else
          $sClass = 'compact';

        $oForm->setFieldDisplayParams('company', array('class' => $sClass, 'onclick' => 'expandField(this);', 'style' => 'z-index: 5;'));


        $oForm->addField('input', 'pos_date', array('type' => 'date', 'label' => 'Date', 'range' => 1, 'value' => $sPositionDate));
        if($sPositionDate)
          $oForm->setFieldDisplayParams('pos_date', array('onclick' => 'expandField(this);'));
        else
          $oForm->setFieldDisplayParams('pos_date', array('class' => 'compact', 'onclick' => 'expandField(this);'));

        $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER);
        $oForm->addField('selector', 'created_by', array('label' => 'Consultant', 'url' => $sURL));
        if(!empty($nLoginfk))
        {
          $oForm->addOption('created_by', array('label' => $oLogin->getUserName($nLoginfk), 'value' => $nLoginfk));
          $sClass = '';
        }
        else
          $sClass = 'compact';

        $oForm->setFieldDisplayParams('created_by', array('class' => $sClass, 'onclick' => 'expandField(this);'));


        $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_INDUSTRY);
        $oForm->addField('selector', 'industryfk', array('label' => 'Industry', 'url' => $sURL));
        if(!empty($sIndustryfk))
        {
          $asIndustry = $this->oCandidate->getVars()->getIndustryList();
          $oForm->addOption('industryfk', array('label' => $asIndustry[$sIndustryfk]['label'], 'value' => $sIndustryfk));
          $sClass = '';
        }
        else
          $sClass = 'compact';

        $oForm->setFieldDisplayParams('industryfk', array('class' => $sClass, 'onclick' => 'expandField(this);'));

        $sHTML.= $oForm->getDisplay();
        $sHTML.= $oHTML->getBlocEnd();
      }


      $oDbResult = $this->_getModel()->getPositionList($poQb, $nLimit);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array('data' => 'Could not find any position.', 'sql' => $poQb->getSql());


      $asPosition = array();
      if($bSplitted)
        $asPosition = array('free' => array(), 'filled' => array());

      while($bRead)
      {
        $asData = $oDbResult->getData();

        if(empty($asData['nb_play']))
          $asData['nb_play'] = '';

        if((int)$asData['status'] !== 0 && $asData['nb_play'])
        {
          $asData['nb_play'] = ($asData['nb_play'] - $asData['nb_active']).' / ';

          if($asData['nb_active'] > 0)
            $asData['nb_play'].= '<span class="candi-picthed-active">'.$asData['nb_active'].' now</span>';
          else
            $asData['nb_play'].= $asData['nb_active'].' now';
        }

        $sURL = $oPage->getAjaxUrl($this->csUid, CONST_ACTION_VIEW, CONST_POSITION_TYPE_JD, (int)$asData['sl_positionpk']);
        $asData['title'] = $oHTML->getLink($asData['title'].'&nbsp;', 'javascript:;', array('onclick' => 'view_position(\''.$sURL.'\');'));

        if($asData['is_public'])
        {
          $sPic = $oHTML->getPicture($this->getResourcePath().'/pictures/public_position.png', 'Public position');
          $asData['title'].= '<span  style="position: absolute; top: 2px; right: 2px; opacity: 0.5;">'.$sPic.'</span>';
        }


        $sURL = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_COMP, (int)$asData['companyfk']);
        $asData['company_name'] = $oHTML->getLink($asData['name'], 'javascript:;', array('onclick' => 'view_comp(\''.$sURL.'\');'));
        $asData['language'] = 'E:'.$asData['lvl_english'].'&nbsp;&nbsp; J:'.$asData['lvl_japanese'];

        if(empty($asData['salary_from']))
          $asData['salary_from'] = '';
        else
          $asData['salary_from'] = round($asData['salary_from']/1000000, 1).'M&yen;';

        if(empty($asData['salary_to']))
          $asData['salary_to'] = '';
        else
          $asData['salary_to'] = round($asData['salary_from']/1000000, 1).'M&yen;';

        $asData['salary'] = $asData['salary_from'].' - '.$asData['salary_to'];
        $asData['age'] = $asData['age_from'].' - '.$asData['age_to'];
        $asData['created_by'] = $oLogin->getUserLink((int)$asData['created_by'], true);

        if($bSplitted)
        {
          if((int)$asData['status'] === 0)
            $asPosition['filled'][] = $asData;
          else
            $asPosition['free'][] = $asData;
        }
        else
          $asPosition[] = $asData;

        $nPosition++;
        $bRead = $oDbResult->readNext();
      }

      //----------------------------------------------------------------------------
      //Prepare the array for get Tabs

      $sHTML.= $this->_oDisplay->getBlocStart('position_list_container');

      if($bSplitted)
      {
        $asTabs = array();
        $sTabSelected = '';

        //incoming reminders
        if(empty($asPosition['free']) && !empty($asPosition['filled']))
          $sTabSelected = 'filled';
        else
          $sTabSelected = 'free';


        if(!empty($asPosition['free']))
        {
          $sTabContent = $this->_getPositionTabList($asPosition['free']);
        }
        else
        {
          $sTabContent = $oHTML->getBlocMessage('No available positions.');
        }
        $asTabs[] = array('label' => 'free', 'title' => 'Available', 'content' => $sTabContent);

        if(!empty($asPosition['filled']))
        {
          $sTabContent = $this->_getPositionTabList($asPosition['filled']);
        }
        else
        {
          $sTabContent = $oHTML->getBlocMessage('No available positions.');
        }
        $asTabs[] = array('label' => 'filled', 'title' => 'Filled', 'content' => $sTabContent);


        $sHTML.= $oHTML->getTabs('position_tabs', $asTabs, $sTabSelected);
        $sAction = '';
      }
      else
      {
        $sHTML.= $this->_getPositionTabList($asPosition);
        //$sAction = ' goPopup.removeLastByType(\'layer\'); initHeaderManager(); ';
        $sAction = ' ';
      }

      if($bSplitted && $nPosition >= $nLimit)
      {
        $sHTML.= $this->_oDisplay->getBloc('', 'Only '.$nPosition.' positions are displayed. Use the filter above to refine those results.');
      }

      $sHTML.= $this->_oDisplay->getBlocEnd();
      $sHTML.= $this->_oDisplay->getBlocEnd();

      return array('data' => convertToUtf8($sHTML), 'action' => $sAction);
    }


    private function _getPositionTabList($pasPosition)
    {
      if(empty($pasPosition))
        return '';

      //dump($pasPosition);
      $oHTML = CDependency::getCpHtml();

      //initialize the template
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
      $oTemplate = $oHTML->getTemplate('CTemplateList', $asParam);

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->setRenderingOption('full', 'full', 'full');

      $oConf->setPagerTop(false);
      $oConf->setPagerBottom(false);

      $oConf->addColumn('ID #', 'sl_positionpk', array('width' => 40, 'sortable'=> array('javascript' => 'integer')));
      $oConf->addColumn('Title', 'title', array('width' => 255, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Company', 'company_name', array('width' => 220, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Industry', 'industry', array('width' => 135, 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('Lang.', 'language', array('width' => 55));
      $oConf->addColumn('Salary', 'salary', array('width' => 80));
      $oConf->addColumn('Age', 'age', array('width' => 45));
      $oConf->addColumn('played/in play', 'nb_play', array('width' => 85, 'class' => 'inplay_col'));
      $oConf->addColumn('by', 'created_by', array('width' => 65));

      return $oTemplate->getDisplay($pasPosition);
    }


    private function _editLinkStatus($pnLinkPk)
    {
      if(!assert('is_key($pnLinkPk)'))
        return array('error' => __LINE__.' - bad parameter');

      $asPosition = $this->_getModel()->getPositionByLinkPk($pnLinkPk);
      if(empty($asPosition))
        return __LINE__.' - could not find the position/application';

      //look for the application history
      $oDbResult = $this->_getModel()->getByWhere('sl_position_link', 'sl_position_linkpk <> '.$pnLinkPk.' AND positionfk = '.(int)$asPosition['positionfk'].' AND candidatefk = '.$asPosition['candidatefk'], '*', 'date_created DESC');
      $bHistory = $oDbResult->readFirst();

      $oLogin = CDependency::getCpLogin();
      $asStatus = $this->_getStatusList(0, false, true);
      $sStart = substr($asPosition['date_created'], 0, 10);
      $sEnd = substr($asPosition['date_expire'], 0, 10);


      $sHTML = $this->_oDisplay->getBlocStart('', array('style' => 'padding: 0 10px;'));

        $sHTML.= $this->_oDisplay->getTitle('Current status...', 'h3', true);
        $sHTML.= '<div style="background-color: #f0f0f0; width: 80%; padding: 10px; margin: 20px auto 0 auto; line-height: 20px;">';

        if($asPosition['active'] && $asPosition['in_play'])
          $sHTML.= 'Candidate playing for '.$oLogin->getuserLink((int)$asPosition['created_by']).'.<br />';
        else
          $sHTML.= 'Last update from '.$oLogin->getuserLink((int)$asPosition['created_by']).'.<br />Not currently in play for this position.<br />';

        $sHTML.= 'Status set to <span style="color: #BD4646;">'.$asStatus[$asPosition['status']].'</span> on the '.$sStart.', and expires on the <span style="color: #BD4646;">'.$sEnd.'</span>.';

        if(!empty($asPosition['comment']))
        {
          $sHTML.= '<br />Comment:<br />
            <div style="border-left: 2px solid #bbb; margin-left: 20px; padding-left: 8px; font-style: italic;">'.$asPosition['comment'].'</div>';
        }
        else
           $sHTML.= '<br />';

        $sHTML.= '<br /><span style="font-style: italic; color: #999; font-size: 10px;">expiring date : the date the status will automaticlally shift to "stalled".</span>
        </div>';

        if(CDependency::getComponentByName('right')->canAccess($this->csUid, 'admin'))
        {
          $bAdmin = true;
          $sPic = $this->_oDisplay->getPicture($this->getResourcePath().'pictures/delete_16.png');
          $sURL = $this->_oPage->getAjaxURL('555-005', CONST_ACTION_DELETE, CONST_POSITION_TYPE_LINK, (int)$asPosition['sl_position_linkpk']);

          if($bHistory)
            $sMessage = 'Are you sure you want to delete this stage ? <br />Status will roll back to previous stage.';
          else
            $sMessage = 'Are you sure you want to delete this application ? <br />The candidate will not be in play for this position anymore. ';

           $sHTML.= $this->_oDisplay->getLink('Delete '.$sPic, 'javascript:;', array('class' => 'position_delete', 'style' => 'position: relative; float: right;',
                'onclick' => ' goPopup.setPopupConfirm(\''.$sMessage.'\', \' AjaxRequest(\\\''.$sURL.'\\\'); \'); '));
        }
        else
        {
          $bAdmin = false;
          $sPic = '';
        }


        if($bHistory)
        {
          $nCount = $oDbResult->numRows();
          $sHTML.= '<br />';
          $sHTML.= $this->_oDisplay->getLink('Previous stages ('.$nCount.')', 'javascript:;', array('id' => 'position_history_link', 'onclick' => '$(\'#position_history\').fadeToggle();  $(this).hide();', 'style' => 'margin-left: 75px;'));

          $sHTML.= $this->_oDisplay->getBlocStart('position_history', array('class' => 'hidden'));
          $sHTML.= '<br />'.$this->_oDisplay->getTitle('History...', 'h3', true, array('onclick' => '$(\'#position_history_link, #position_history\').fadeToggle();', 'style' => 'cursor: pointer;'));

          while($bHistory)
          {
            $asHistory = $oDbResult->getData();

            if($asHistory['in_play'])
              $sPrefix = '<span class="play_status">In play</span>';
            else
              $sPrefix = '<span class="play_status">Not in play</span>';

            $sHTML.= '<div class="position_step_list">'.$sPrefix.'The '.substr($asHistory['date_created'], 0, 10).' candidate was set in&nbsp;&nbsp;&nbsp;<b>'.$asStatus[$asHistory['status']].'</b>&nbsp;&nbsp;&nbsp; by '.$oLogin->getUserLink((int)$asHistory['created_by']);

            if($bAdmin)
            {
              $sURL = $this->_oPage->getAjaxURL('555-005', CONST_ACTION_DELETE, CONST_POSITION_TYPE_LINK, (int)$asHistory['sl_position_linkpk']);
              $sMessage = 'Are you sure you want to delete this stage ? <br />Status will roll back to previous stage.';

              $sHTML.= $this->_oDisplay->getLink($sPic, 'javascript:;', array('class' => 'position_delete',
                'onclick' => ' goPopup.setPopupConfirm(\''.$sMessage.'\', \' AjaxRequest(\\\''.$sURL.'\\\'); \'); '));
            }

            $sHTML.= '</div>';
            $bHistory = $oDbResult->readNext();
          }

          $sHTML.= $this->_oDisplay->getBlocEnd();
        }

      $sHTML.= $this->_oDisplay->getBlocEnd();

      $sHTML.= $this->_linkPositionForm($asPosition);
      return $sHTML;
    }


    private function _checkExpiring()
    {
      $sDate = getValue('date_expire');
      if(empty($sDate) || !is_date($sDate))
        $sDate = date('Y-m-d', strtotime('+2 week'));

      $oDbResult = $this->_getModel()->getExpiringPosition($sDate);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
      {
        echo 'no candidate/position to treat';
        return '';
      }

      $asExpiring = array();
      $asEmail = array();
      while($bRead)
      {
        $asPosition = $oDbResult->getData();
        $nConsultant = (int)$asPosition['created_by'];

        $asExpiring[$nConsultant][(int)$asPosition['candidatefk']]['name'] = $asPosition['candidate'];
        $asExpiring[$nConsultant][(int)$asPosition['candidatefk']]['position'][] = 'position #'.$asPosition['positionfk'].' will expire the '.substr($asPosition['date_expire'], 0, 10);

        $asEmail[$nConsultant]['firstname'] = $asPosition['firstname'];
        $asEmail[$nConsultant]['lastname'] = $asPosition['lastname'];
        $asEmail[$nConsultant]['email'] = $asPosition['email'];
        $bRead = $oDbResult->readNext();
      }

      if(empty($asExpiring))
        return 'No expiring in play candidates, nothing to do';

      $oMail = CDependency::getComponentByName('mail');


      foreach($asExpiring as $nConsultant => $asCandidate)
      {
        $sHTML = 'Dear '.$asEmail[$nConsultant]['firstname'].', <br /><br /> ';
        $sHTML.= count($asCandidate).' of your candidates currently <b>in play</b> will expire soon. This/these will automatically be switched to <b>stalled</b> if not updated soon.<br /> ';
        $sHTML.= 'You\'ll find more the details below.<br />';

        foreach($asCandidate as $nCandidatePk => $asPosition)
        {
          $sURL = $this->_oPage->getUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $nCandidatePk);

          $sHTML.= '<br /><br />Candidate <a href="'.$sURL.'" >#'.$nCandidatePk.'</a>&nbsp;&nbsp;&nbsp;<a href="'.$sURL.'" >'.$asPosition['name'].'</a><br />';
          $sHTML.= '&nbsp;&nbsp;&nbsp;-&nbsp;'.implode('<br />&nbsp;&nbsp;&nbsp;-&nbsp;', $asPosition['position']);
        }

        $oMail->createNewEmail();
        $oMail->setFrom(CONST_CRM_MAIL_SENDER, 'Slistem notification');
        $oMail->addRecipient($asEmail[$nConsultant]['email'], $asEmail[$nConsultant]['lastname'].' '.$asEmail[$nConsultant]['firstname']);

        $oMail->send('Stalled candidate(s)', $sHTML);
      }
    }


    private function _updateExpiring()
    {
      $sDate = getValue('date_expire');
      $oDbResult = $this->_getModel()->getExpiringPosition($sDate);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
      {
        echo __LINE__.' - no position expired today';
        return '';
      }

      $asExpiring = array();
      $asEmail = array();
      $sNow = date('Y-m-d H:i:s');
      $sThreeMonth = date('Y-m-d', strtotime('+3 months')).' 00:00:00';

      while($bRead)
      {
        $asPosition = $oDbResult->getData();
        $nConsultant = (int)$asPosition['created_by'];

        //--------------------------------------------------
        //update Current JD application --> inactive
        $asValues = array('date_expire' => $sNow, 'active' => 0);
        $bUpdated = $this->_getModel()->update($asValues, 'sl_position_link', 'sl_position_linkpk = '.(int)$asPosition['sl_position_linkpk']);
        //dump($asValues);

        //--------------------------------------------------
        //add a new entry with a expired status 151
        $asValues = array('positionfk' => (int)$asPosition['positionfk'],
            'candidatefk' => (int)$asPosition['candidatefk'],
            'date_created' => $sNow,
            'created_by' => $nConsultant,
            'status' => 151,
            'comment' => 'This is an automatic update from Slistem.<br />
              The previous status has reached the expiration date, the candidate is now [expired], not considered active/in play anymore.<br />
              If the candidate is not updated during the next 3 months, it will be considered [fallen off].',
            'date_expire' => $sThreeMonth,
            'active' => 1);
        $bUpdated = $this->_getModel()->add($asValues, 'sl_position_link');
        //dump($asValues);

        //--------------------------------------------------
        //update candidate status based on all his positions
        $asUpdate = $this->oCandidate->updateCandidateProfile((int)$asPosition['candidatefk']);
        //dump($asUpdate);

        //prepare content for email
        $asExpiring[$nConsultant][(int)$asPosition['candidatefk']]['name'] = $asPosition['candidate'];
        $asExpiring[$nConsultant][(int)$asPosition['candidatefk']]['position'][$asPosition['positionfk']] = '#'.$asPosition['positionfk'].' - has expired today.';

        $asEmail[$nConsultant]['firstname'] = $asPosition['firstname'];
        $asEmail[$nConsultant]['lastname'] = $asPosition['lastname'];
        $asEmail[$nConsultant]['email'] = $asPosition['email'];
        $bRead = $oDbResult->readNext();
      }

      if(empty($asExpiring))
      {
        echo __LINE__.'No expiring position today.';
        return '';
      }


      $oMail = CDependency::getComponentByName('mail');

      foreach($asExpiring as $nConsultant => $asCandidate)
      {
        $sHTML = 'Dear '.$asEmail[$nConsultant]['firstname'].', <br /><br /> ';

        $nCandi = count($asCandidate);
        if($nCandi > 1)
          $sHTML.= count($asCandidate).' of your <strong>active</strong> candidates have expired today.';
        else
          $sHTML.= 'One of your <strong>active</strong> candidates has expired today.';

        $sHTML.= ' Those candidates are now considered <b>expired/stalled</b>.<br /> See more the details below.<br />';

        foreach($asCandidate as $nCandidatePk => $asPosition)
        {
          $sURL = $this->_oPage->getUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, $nCandidatePk);

          $sHTML.= '<br /><br />Candidate <a href="'.$sURL.'" >#'.$nCandidatePk.'</a>&nbsp;&nbsp;&nbsp;<a href="'.$sURL.'" >'.$asPosition['name'].'</a> was in play for position(s): <br />';
          $sHTML.= '&nbsp;&nbsp;&nbsp;-&nbsp;'.implode('<br />&nbsp;&nbsp;&nbsp;-&nbsp;', $asPosition['position']);
        }

        $oMail->createNewEmail();
        $oMail->setFrom(CONST_CRM_MAIL_SENDER, 'Slistem notification');
        $oMail->addRecipient($asEmail[$nConsultant]['email'], $asEmail[$nConsultant]['lastname'].' '.$asEmail[$nConsultant]['firstname']);

        dump('email for '.$asEmail[$nConsultant]['email']);

        if($nCandi > 1)
          $oMail->send('[Important message] '.count($asCandidate).' candidates stalled / expired', $sHTML);
        else
          $oMail->send('[Important message] 1 candidate stalled / expired', $sHTML);
      }
    }

    private function _deleteLinkstatus($pnLinkPk)
    {
       if(!assert('is_key($pnLinkPk)'))
        return array('error' => __LINE__.' - bad parameter');

      $asPosition = $this->_getModel()->getPositionByLinkPk($pnLinkPk);
      if(empty($asPosition))
        return array('error' => __LINE__.' - could not find the position/application');

      //dump($asPosition);

      if(!empty($asPosition['active']))
      {
        $oDbResult = $this->_getModel()->getByWhere('sl_position_link', 'sl_position_linkpk <> '.$pnLinkPk.' AND positionfk = '.(int)$asPosition['positionfk'].' AND candidatefk = '.$asPosition['candidatefk'], '*', 'date_created DESC');
        $bHistory = $oDbResult->readFirst();

        //deleting current status... roll back to previous
        if($bHistory)
        {
          $asPreviousStatus = $oDbResult->getData();

          //update previous status -> active = 1
          $asData = array('active' => 1);
          $bDeleted = $this->_getModel()->update($asData, 'sl_position_link', 'sl_position_linkpk = '.(int)$asPreviousStatus['sl_position_linkpk']);
          if(!$bDeleted)
            return array('error' => __LINE__.' - could not delete the position/application');
        }
        else
        {
          //deleting last stages (not in play anymore for this position)
          //we remove credited users
          $bDeleted = $this->_getModel()->deleteByWhere('sl_position_credit', 'positionfk = '.(int)$asPosition['positionfk'].' AND candidatefk = '.(int)$asPosition['candidatefk']);
          if(!$bDeleted)
            return array('error' => __LINE__.' - could not delete the position/application');
        }
      }

      //delete selected one. If only one, candi won't be in play anymore for this position
      $bDeleted = $this->_getModel()->deleteByPk($pnLinkPk, 'sl_position_link');
      if(!$bDeleted)
        return array('error' => __LINE__.' - could not delete the position/application');


      return array('data' => 'ok', 'notice' => 'Application / stage deleted', 'action' => ' goPopup.removeByType(\'layer\'); refresh_candi('.(int)$asPosition['candidatefk'].', true);');
    }

    private function _searchPosition()
    {
      $_SESSION['position_filter'] = array();

      //get search param, load it in the querybuilder
      $oQb = $this->_getModel()->getQueryBuilder();

      // ============================================
      // search management
      if(empty($this->csSearchId))
      {
        $this->csSearchId = manageSearchHistory($this->csUid, CONST_CANDIDATE_TYPE_CANDI);

        $oQb->setTable('sl_position', 'spos');
        $oQb->addSelect('*');
        $oQb->addLimit('0, 50');

        $nLimit = 50;
      }
      else
      {
        $this->csSearchId = manageSearchHistory($this->csUid, CONST_CANDIDATE_TYPE_CANDI);

        $oPager = CDependency::getComponentByName('pager');
        $oPager->initPager();
        $nLimit = $oPager->getLimit();
        $nPagerOffset = $oPager->getOffset();

        $oQb->addLimit(($nPagerOffset*$nLimit).' ,'. $nLimit);
      }


      $oQb->setTable('sl_position');
      $oQb->addSelect('*');

      if(getValue('qs'))
      {
        //$this->_addQSFilter(&$oQb);
        $sCompany = getValue('company');
        if(!empty($sCompany) && $sCompany != 'Company name')
        {
          $oQb->addJoin('inner', 'sl_company', 'scom', 'scom.sl_companypk = spos.companyfk');
          $_SESSION['position_filter']['company'] = $sCompany;

          if(!empty($sCompany) && is_numeric($sCompany))
          {
            $oQb->addWhere('scom.sl_companypk = "'.$sCompany.'" ');
          }
          else
          {
            $oQb->addWhere('scom.name LIKE "%'.$sCompany.'%" ');
          }
        }

        $sTitle = getValue('title');
        if(!empty($sTitle) && $sTitle != 'Position title')
        {
          $sPositionId = str_replace('#', '',$sTitle);
          $_SESSION['position_filter']['title'] = $sTitle;

          if(is_numeric($sPositionId))
            $oQb->addWhere('spos.sl_positionpk = '.(int)$sPositionId.' ');
          else
            $oQb->addWhere(' (spde.title LIKE "'.$sTitle.'%" or spde.title LIKE "%'.$sTitle.'") ');
        }

        $sContent = getValue('content');
        if(!empty($sContent) && $sContent != 'Content')
        {
          $_SESSION['position_filter']['content'] = $sContent;
          $oQb->addWhere('( spde.title LIKE "%'.$sContent.'%" OR spde.description LIKE "%'.$sContent.'%" OR spde.requirements LIKE "%'.$sContent.'%")');
        }

        $nLoginfk = (int)getValue('created_by');
        if(!empty($nLoginfk))
        {
          $_SESSION['position_filter']['created_by'] = $nLoginfk;
          $oQb->addWhere('spos.created_by = '.$nLoginfk);
        }

        //other fields from position filter
        $sDate = getValue('pos_date');
        if(!empty($sDate))
        {
          $_SESSION['position_filter']['pos_date'] = $sDate;
          //explode date ...
          $asDate = getDateRange('pos_date', '-2 months', '+2 months');
          $oQb->addWhere(' (spos.date_created >= "'.$asDate['start'].'" AND spos.date_created <= "'.$asDate['end'].'") ');
        }


        $nIndustry = (int)getValue('industryfk');
        if(!empty($nIndustry))
        {
          $_SESSION['position_filter']['industryfk'] = $nIndustry;
          $oQb->addJoin('inner', 'sl_industry', 'sind', '(sind.sl_industrypk = spos.industryfk AND (sind.sl_industrypk = '.$nIndustry.' OR sind.parentfk = '.$nIndustry.'))');
          //$oQb->addWhere('(sind.sl_industrypk = '.$nIndustry.' OR sind.parentfk = '.$nIndustry.')');
        }

        $sIndustry = getValue('industry');
        if(!empty($sIndustry) && $sIndustry != 'Industry')
        {
          $_SESSION['position_filter']['industry'] = $sIndustry;
          $oQb->addJoin('inner', 'sl_industry', 'sind', '(sind.sl_industrypk = spos.industryfk AND (sind.label LIKE "%'.$sIndustry.'%" ))');
        }


        //need to close the QS form

        $asResult = $this->_positionList($oQb);

        //From QS, we remove the form once result come.
        //From position list, we refresh the windows so no removeLayer
        if(!getValue('layer_stays'))
        {
          set_array($asResult['action']);
          $asResult['action'].= ' goPopup.removeActive(\'layer\'); ';
        }
        return $asResult;
      }

      return $this->_positionList($oQb);
    }










    /* ************************************************************* */
    /* ************************************************************* */
    /* ************************************************************* */
    /* ************************************************************* */
    // section relative to placemnet management


    private function _getPlacementList()
    {
      $this->_oPage->addCssFile($this->getResourcePath().'css/sl_placement.css');
      $this->_oPage->addJsFile($this->getResourcePath().'js/sl_placement.js');
      $html_object = $this->_oDisplay;


      $html = $html_object->getBlocStart('placementFullContainer');
      $html.= $html_object->getTitle('Placements', 'h3', true);

      $html.= $html_object->getBlocStart('', array('class' => 'placement_filter'));
      $html.= $this->_getPlacementFilterForm();
      $html.= $html_object->getBlocEnd();

      $url =  $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_ADD, CONST_POSITION_TYPE_PLACEMENT);
      $html.= $html_object->getBlocStart('', array('class' => 'placement_add_button'));
      $html.= $html_object->getLink('+ add a new placement', 'javascript:;', array('onclick' => ' editPop(\''.$url.'\'); '));
      $html.= $html_object->getBlocEnd();

      $consultant_var = (int)getValue('loginpk', 0);
      $candidate_var = (int)getValue('candidate', 0);

      $position_var = getValue('cp_jd_key', '');
      $period_var = getValue('date_start', '');
      $date_filter = getValue('date_filter', '');

      $url =  $this->_oPage->getUrl($this->csUid, CONST_ACTION_DOWNLOAD, CONST_POSITION_TYPE_PLACEMENT, 0,
        array('loginpk' => $consultant_var, 'candidate' => $candidate_var, 'cp_jd_key' => $position_var,
          'date_start' => $period_var, 'date_filter' => $date_filter));
      $html.= $html_object->getBlocStart('', array('class' => 'placement_export_button'));
      $html.= $html_object->getLink('export placements', $url);
      $html.= $html_object->getBlocEnd();

      $html.= $html_object->getBlocStart('', array('class' => 'placement_list'));

      $filter = $this->_getPlacementFilter();
      $revenue_data = $this->_getModel()->getPlacement($filter, true);

      if(!$revenue_data)
      {
        $html.= $html_object->getBlocMessage('no placement to display.');
      }
      else
      {
        $login_object = CDependency::getCpLogin();
        $placement_array = array();

        foreach ($revenue_data as $key => $revenue)
        {
          if(!isset($placement_array[$revenue['id']]))
          {

            if(empty($revenue['closed_by']))
              $revenue['consultant'] = ' - ';
            else
              $revenue['consultant'] = $login_object->getUserLink((int)$revenue['closed_by']);

            $position = $revenue['position_id'].' '.$revenue['position_title'];

            $encoding = mb_detect_encoding($position);
            $revenue['position'] = mb_convert_encoding($position, 'UTF-8', $encoding);


            $paid = !empty($revenue['date_paid']);

            $url = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_EDIT, CONST_POSITION_TYPE_PLACEMENT, (int)$revenue['id']);
            $picture = $html_object->getPicture(self::getResourcePath().'/pictures/edit_16.png');
            $revenue['action'] = $html_object->getLink($picture, 'javascript:;', array('onclick' => ' editPop(\''.$url.'\'); '));

            if(!$paid)
            {
              $url =  $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_VALIDATE, CONST_POSITION_TYPE_PLACEMENT, (int)$revenue['id']);
              $picture = $html_object->getPicture(self::getResourcePath().'/pictures/pay_inactive_16.png', 'Set this placement paid ?');
              $revenue['action'].= '&nbsp;&nbsp;&nbsp;'.$html_object->getLink($picture, 'javascript:;', array('onclick' => 'if(window.confirm(\'Set this placement paid ?\')){ AjaxRequest(\''.$url.'\'); }; '));
            }
            else
            {
              $revenue['action'].= '&nbsp;&nbsp;&nbsp;'.$html_object->getPicture(self::getResourcePath().'/pictures/pay_16.png', 'Paid');
            }

            $url =  $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_DELETE, CONST_POSITION_TYPE_PLACEMENT, (int)$revenue['id']);
            $picture = $html_object->getPicture(self::getResourcePath().'/pictures/delete_16.png');
            $revenue['action'].= '&nbsp;&nbsp;&nbsp;'.$html_object->getLink($picture, 'javascript:;', array('onclick' => 'if(window.confirm(\'Delete this placement ?\')){ AjaxRequest(\''.$url.'\'); }; '));

            if ($revenue['candidate'] == 'retainer')
              $candidate_info = '<b>'.$revenue['candidate_name'].'</b>';
            else
              $candidate_info = $revenue['candidate'].' <b>'.$revenue['candidate_name'].'</b>';

            $revenue['candidate'] = $html_object->getBloc('', $candidate_info, array('class' => 'placement_candidate'));

            // $revenue['position'] = $revenue['position'];
            $revenue['amount_formatted'] = number_format($revenue['amount'], 0, '.', ',');
            $revenue['amount_formatted'] = $html_object->getBloc('', $revenue['amount_formatted'].'&yen;', array('style' => 'width: 100%; text-align: center; '));
            $placement_array[$revenue['id']] = $revenue;
          }

          foreach ($revenue['paid_users'] as $user)
          {
            $revenue['paid_amount'] = number_format($revenue['amount'], 0, '.', ',');

            if(isset($placement_array[$revenue['id']]['recipient']))
              $placement_array[$revenue['id']]['recipient'].= ', ';
            else
              $placement_array[$revenue['id']]['recipient']= '';

            $placement_array[$revenue['id']]['recipient'].= $login_object->getUserLink((int)$user['user'], true).'
              <span class="placement_share" title="Amount: '.$revenue['paid_amount'].' yen"> ('.$user['percentage'].'%)</span>';
          }
        }


        //initialize the template
        $template_param = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateRow'))));
        $template = $html_object->getTemplate('CTemplateList', $template_param);

        //get the config object for a specific template (contains default value so it works without config)
        $template_config = $template->getTemplateConfig('CTemplateList');
        $template_config->setRenderingOption('full', 'full', 'full');

        $template_config->setPagerTop(false);
        $template_config->setPagerBottom(false);

        $template_config->addColumn('Date', 'date_signed', array('width' => 85, 'sortable'=> array('javascript' => 'integer')));
        $template_config->addColumn('Closed by', 'consultant', array('width' => 140, 'sortable'=> array('javascript' => 1)));
        $template_config->addColumn('Position', 'position', array('width' => 225, 'sortable'=> array('javascript' => 1)));
        $template_config->addColumn('Inv. amount', 'amount_formatted', array('width' => 90, 'sortable'=> array('javascript' => 1)));
        $template_config->addColumn('Payment(s) to', 'recipient', array('width' => 230, 'sortable'=> array('javascript' => 1)));
        $template_config->addColumn('Candidate', 'candidate', array('width' => 175, 'sortable'=> array('javascript' => 1)));
        $template_config->addColumn('Action', 'action', array('width' => 85));


        $html.= $template->getDisplay($placement_array);
      }

      $html.= $html_object->getBlocEnd();
      return $html;
    }

    private function export_placements()
    {
      $login_object = CDependency::getCpLogin();
      $filter = $this->_getPlacementFilter();

      $revenue_data = $this->_getModel()->getPlacement($filter, true, 2000);

      $file_name = 'placement_export_'.date('Y_m_d').'.csv';

      $csv_string = 'position id, position name, consultant, consultant position, company, candidate id, candidate name,';
      $csv_string .= ' placement, start working on, date signed, payment due date, payment date, billable salary,';
      $csv_string .= " invoice rate, invoice amount, split, revenue credit, status, comment \n";

      foreach ($revenue_data as $revenue)
      {
        $prebuilt_string = '';

        $prebuilt_string .= $revenue['position_id'].', '.str_replace(',', ' ', $revenue['position_title'].', ');
        $prebuilt_string .= ', <consultant_name>, <user_position>, '.str_replace(',', ' ', $revenue['company_name']).', ';
        $prebuilt_string .= $revenue['candidate'].', '.str_replace(',', ' ', $revenue['candidate_name']).', ';
        $prebuilt_string .= '<closed_by>, '.$revenue['date_start'].', '.$revenue['date_signed'].', ';
        $prebuilt_string .= $revenue['date_due'].', '.$revenue['date_paid'].', ';
        $prebuilt_string .= number_format($revenue['salary'], 0, '.', '').', ';
        $prebuilt_string .= $revenue['salary_rate'].'%, '.number_format($revenue['amount'], 0, '.', '').', ';
        $prebuilt_string .= '<split>, <split_amount>, '.$revenue['status'].', ';
        $prebuilt_string .= preg_replace('/[^A-Za-z0-9\-\' ]/', '', $revenue['comment']);

        foreach ($revenue['paid_users'] as $value)
        {
          $temp_string = str_replace('<consultant_name>',
            $login_object->getUserName($value['user']), $prebuilt_string);

          if ($revenue['closed_by'] == $value['user'] && $revenue['candidate'] != 'retainer'
            && $revenue['placement_count'] == 'yes' && $value['user_position'] == 'Consultant')
            $closed_by = 1;
          else
            $closed_by = 0;

          $temp_string = str_replace('<user_position>', $value['user_position'], $temp_string);
          $temp_string = str_replace('<closed_by>', $closed_by, $temp_string);
          $temp_string = str_replace('<split>', $value['percentage'].'%', $temp_string);

          $split_amount = number_format($value['split_amount'], 0, '.', '');

          $temp_string = str_replace('<split_amount>', $split_amount, $temp_string);

          $csv_string .= $temp_string."\n";
        }
      }

      header('Content-type: text/csv');
      header('Content-Disposition: attachment; filename="'.$file_name.'"');

      echo $csv_string;
      die();
    }

    private function _getPlacementFilterForm()
    {
      $login = CDependency::getCpLogin();

      $this->_oPage->addJsFile('/common/js/moment.min.js');
      $this->_oPage->addJsFile('/common/js/jquery.comiseo.daterangepicker.js');
      $this->_oPage->addCSSFile('/common/style/jquery.comiseo.daterangepicker.css');
      $this->_oPage->addCssFile('/component/form/resources/css/form.css');
      $this->_oPage->addCssFile('/component/form/resources/css/token-input-mac.css');

      $consultant = (int)getValue('loginpk', 0);
      $candidate = (int)getValue('candidate', 0);
      $position = getValue('cp_jd_key');
      $date_filter = getValue('date_filter');

      $consultant_token = $position_token = $candidate_token = '';

      $date_filter_array = array('date_created' => 'Date created', 'date_signed' => 'Date signed',
        'date_due' => 'Date due', 'date_paid' => 'Date paid', 'date_start' => 'Date start work');

      $form_url = $this->_oPage->getUrl($this->csUid, CONST_ACTION_LIST, CONST_POSITION_TYPE_PLACEMENT, 0);
      $user_token_url = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER);
      $position_token_url = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_POSITION_TYPE_JD);
      $candidate_token_url = $url = $this->_oPage->getAjaxUrl('555-001', CONST_ACTION_SEARCH, CONST_CANDIDATE_TYPE_CANDI,
        0, array('autocomplete' => 1));

      $start_date = getValue('date_start');
      $end_date = '';

      if(!empty($start_date))
      {
        $date_array = json_decode($start_date, true);
        $start_date = $date_array['start'];

        if(isset($date_array['end']))
          $end_date =  $date_array['end'];

        if(empty($start_date))
          $start_date = date('Y-m', strtotime('-2 months')).'-01';

        if(empty($end_date))
          $end_date = date('Y-m', strtotime('+2 months', strtotime($start_date))).'-01';

        $start_end_date = htmlspecialchars(json_encode(array('start' => $start_date, 'end' => $end_date)));
      }
      else
        $start_end_date = '';

      if($consultant)
      {
        $user = strip_tags($login->getUserLink($consultant));
        $consultant_token = '[{id:"'.$consultant.'", name:"'.$user.'"}]';
      }

      if($position)
      {
        $key = explode('_', $position);
        $position_data = $this->_getModel()->getPositionByPk((int)$key[1]);
        $position_data->readFirst();

        $position_token = '[{id:"'.$position.'", name:"'.$position_data->getFieldValue('title').'"}]';
      }

      if($candidate)
      {
        $candidate_object = CDependency::getComponentByName('sl_candidate');
        $candidate_data = $candidate_object->getCandidateData($candidate, false);

        $candidate_token = '[{id:"'.$candidate.'", name:"'.$candidate_data['firstname'].' '.$candidate_data['lastname'].'"}]';
      }

      $data = array('form_url' => $form_url, 'user_token_url' => $user_token_url, 'position_token_url' => $position_token_url,
        'candidate_token_url' => $candidate_token_url, 'consultant' => $consultant, 'candidate' => $candidate,
        'position' => $position, 'start_end_date' => $start_end_date, 'consultant_token' => $consultant_token,
        'position_token' => $position_token, 'candidate_token' => $candidate_token, 'date_filter_array' => $date_filter_array,
        'date_filter' => $date_filter);

      return $this->_oDisplay->render('placement_filter', $data);
    }

    private function _getPlacementFilter()
    {
      $consultant = (int)getValue('loginpk', 0);
      $candidate = (int)getValue('candidate', 0);

      $position = getValue('cp_jd_key');
      $period = getValue('date_start');

      $date_filter = getValue('date_filter', 'date_signed');

      $sql_array = array();

      if(!empty($consultant))
      {
        $sql_array['member'] = $consultant;
      }

      if(!empty($candidate))
        $sql_array['revenue'][] = ' revenue.candidate = '.$candidate;

      if(!empty($position))
      {
        $position_list = explode('_', $position);
        if(count($position_list) == 2)
          $sql_array['revenue'][] = ' revenue.position = '.(int)$position_list[1];
      }

      if(!empty($period))
      {
        $date_array = json_decode($period, true);
        $start_date = $date_array['start'];

        if(isset($date_array['end']))
          $end_date =  $date_array['end'];

        if(empty($start_date))
          $start_date = date('Y-m', strtotime('-2 months')).'-01';

        if(empty($end_date))
          $end_date = date('Y-m', strtotime('+2 months', strtotime($start_date))).'-01';

        $sql_array['revenue'][] = '(revenue.'.$date_filter.' BETWEEN "'.$start_date.'" AND "'.$end_date.'") ';
      }

      return $sql_array;
    }



    private function _getPlacementForm($revenue_id = 0)
    {
      $this->_oPage->addCssFile($this->getResourcePath().'css/sl_placement.css');
      $this->_oPage->addJsFile($this->getResourcePath().'js/sl_placement.js');
      $oLogin = CDependency::getCpLogin();

      $asPaymentRow = array();
      $asPaymentRow[0] = array(array(), 100, '');
      $asPaymentRow[1] = array(array(), '', '');
      $asPaymentRow[2] = array(array(), '', '');
      $asPaymentRow[3] = array(array(), '', '');
      $asPaymentRow[4] = array(array(), '', '');


      if(empty($revenue_id))
      {
        $oDdPlacement = new CDbResult();
        $oDdPlacement->setFieldValue('date_signed', date('Y-m-d'));
        $sCandidate = '';
      }
      else
      {
        $oDdPlacement = $this->_getModel()->get_revenue_info($revenue_id);
        $bRead = $oDdPlacement->readFirst();
        if(!$bRead)
          return __LINE__.' - Can not find the placement.';

        $oDdPlacement->setFieldValue('date_signed', substr($oDdPlacement->getFieldValue('date_signed'), 0, 10));
        $oDdPlacement->setFieldValue('date_start', substr($oDdPlacement->getFieldValue('date_start'), 0, 10));

        $nPositionfk = (int)$oDdPlacement->getFieldValue('position');
        $oPosition = $this->_getModel()->getPositionByPk($nPositionfk);
        $oPosition->readFirst();
        $sPosition = '#'.$nPositionfk.' - '.$oPosition->getFieldValue('title');
        $sPositionKey = $oPosition->getFieldValue('companyfk').'_'.$nPositionfk;

        //fetch candidate data
        $nCandidatefk = (int)$oDdPlacement->getFieldValue('candidate');

        if ($oDdPlacement->getFieldValue('candidate') == 'retainer')
        {
          $sCandidate = 'Retainer';
        }
        else
        {
          $asCandidate = $this->oCandidate->getCandidateData($nCandidatefk , false);
          $sCandidate = '#'.$nCandidatefk. ' - '.$asCandidate['firstname'].' '.$asCandidate['lastname'];
        }

        //fetch consultant name
        $nLoginfk = (int)$oDdPlacement->getFieldValue('closed_by');
        $sConsultant = strip_tags($oLogin->getUserLink($nLoginfk));


        $oDdPayment = $this->_getModel()->get_revenue_members($revenue_id);
        $bRead = $oDdPayment->readFirst();
        $nCount = 0;
        while($bRead)
        {
          $nPaidLoginfk = (int)$oDdPayment->getFieldValue('loginpk');
          $sPaidConsultant = strip_tags($oLogin->getUserLink($nPaidLoginfk));

          $asPaymentRow[$nCount] = array(
            array('label' =>$sPaidConsultant, 'value' => $nPaidLoginfk),
            $oDdPayment->getFieldValue('percentage'),
            $oDdPayment->getFieldValue('split_amount'));

          $bRead = $oDdPayment->readNext();
          $nCount++;
        }
      }


      $oForm = $this->_oDisplay->initForm('placementFilterForm');
      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SAVEADD, CONST_POSITION_TYPE_PLACEMENT, $revenue_id);
      $oForm->setFormDisplayParams(array('noCancelButton' => true));


      $oForm->setFormParams('placementForm', true, array('action' => $sURL));
      $oForm->addField('misc', '', array('type' =>'title', 'title' => 'Placement details'));


      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_POSITION_TYPE_PLACEMENT);
      $sJavascript = 'refreshPlacementForm(oItem, \''.$sURL.'\' ); ';

      $sURL = $this->_oPage->getAjaxUrl($this->csUid, CONST_ACTION_SEARCH, CONST_POSITION_TYPE_JD, 0,
        array('placement' => 0, 'placement_manager' => 1));
      $oForm->addField('selector', 'pla_cp_jd_key', array('label' => 'Company or position', 'url' => $sURL, 'onadd' => $sJavascript, 'required' => 1));
      $oForm->setFieldControl('pla_cp_jd_key', array('jsFieldNotEmpty' => 1));
      if(!empty($revenue_id))
      {
        $oForm->addOption('pla_cp_jd_key', array('label' => $sPosition, 'value' => $sPositionKey));
      }

      //filled in javascript
      $oForm->addField('select', 'pla_candidatefk', array('label'=>'Candidate', 'onchange' => 'mirrorSelection(this, \'pla_loginfkId\'); '));
      $oForm->setFieldControl('pla_candidatefk', array('jsFieldNotEmpty' => 1));
      if(!empty($revenue_id))
        $oForm->addOption('pla_candidatefk', array('label' => $sCandidate, 'value' => $oDdPlacement->getFieldValue('candidate')));

      if ($oDdPlacement->getFieldValue('candidate') != 'retainer')
      {
        $oForm->addOption('pla_candidatefk', array('label' => '-', 'value' => ''));
        $oForm->addOption('pla_candidatefk', array('label' => 'Retainer', 'value' => 'retainer'));
      }

      $oForm->addField('select', 'pla_loginfk', array('label'=>'Deal closed by', 'onchange' => 'mirrorSelection(this, \'pla_candidatefkId\');'));

      $url = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER);
      $oForm->addField('selector', 'pla_loginfk_retainer', array('label'=>'&nbsp;', 'url' => $url,
        'type' => 'hidden'));

      $oForm->setFieldControl('pla_loginfk', array('jsFieldNotEmpty' => 1));
      if(!empty($revenue_id))
        $oForm->addOption('pla_loginfk', array('label' => $sConsultant, 'value' => $oDdPlacement->getFieldValue('closed_by')));

      $sLocation = $oDdPlacement->getFieldValue('location');
      $oForm->addField('select', 'location', array('label' => 'Location', 'value' => $sLocation));

      $oForm->addOption('location', array('label' => 'Tokyo', 'value' => 'Tokyo'));
      $oForm->addOption('location', array('label' => 'Manila', 'value' => 'Manila'));
      $oForm->addOption('location', array('label' => 'Canada', 'value' => 'Canada'));
      $oForm->addOption('location', array('label' => 'Hong Kong', 'value' => 'Hong Kong'));
      $oForm->addOption('location', array('label' => 'Singapore', 'value' => 'Singapore'));

      $oForm->addField('misc', '', array('type' => 'br'));


      $current_revenue_status = $oDdPlacement->getFieldValue('status');
      $oForm->addField('select', 'revenue_status', array('label' => 'Status', 'value' => $current_revenue_status));

      $oForm->addOption('revenue_status', array('label' => 'Signed', 'value' => 'signed'));
      $oForm->addOption('revenue_status', array('label' => 'Paid', 'value' => 'paid'));
      $oForm->addOption('revenue_status', array('label' => 'Delayed', 'value' => 'delayed'));
      $oForm->addOption('revenue_status', array('label' => 'Refund', 'value' => 'refund'));

      $oForm->addField('misc', '', array('type' => 'br'));

      $placement_count = $oDdPlacement->getFieldValue('placement_count');

      if($placement_count === 'no')
      {
        $oForm->addField('checkbox', 'placement_count', array('legend' => 'Count as placement',
          'value' => 'yes'));
      }
      else
      {
        $oForm->addField('checkbox', 'placement_count', array('legend' => 'Count as placement',
          'value' => 'yes', 'checked' => 'checked'));
      }

      $oForm->addField('misc', '', array('type' => 'br'));


      $oForm->addField('input', 'date_signed', array('type' => 'date',
        'label' => 'Date signed', 'value' => $oDdPlacement->getFieldValue('date_signed')));
      $oForm->setFieldControl('date_signed', array('jsFieldNotEmpty' => 1, 'jsFieldMinSize' => 10));

      $oForm->addField('input', 'date_start', array('type' => 'date',
        'label' => 'Start working on', 'value' => $oDdPlacement->getFieldValue('date_start')));
      $oForm->setFieldControl('date_start', array('jsFieldNotEmpty' => 1, 'jsFieldMinSize' => 10));

      $oForm->addField('input', 'date_due', array('type' => 'date',
        'label' => 'Payment due date', 'value' => $oDdPlacement->getFieldValue('date_due')));
      $oForm->setFieldControl('date_due', array('jsFieldNotEmpty' => 1, 'jsFieldMinSize' => 10));

      $oForm->addField('input', 'date_paid', array('type' => 'date',
        'label' => 'Date paid', 'value' => $oDdPlacement->getFieldValue('date_paid')));
      $oForm->setFieldControl('date_paid', array('jsFieldMinSize' => 10));


      $oForm->addField('misc', '', array('type' => 'br'));


      $oForm->addField('input', 'salary', array('type' => 'text', 'label' => 'Billable salary (&yen;)',
        'id' => 'full_salary', 'value' => $oDdPlacement->getFieldValue('salary')));
      $oForm->setFieldControl('salary', array('jsFieldNotEmpty' => 1, 'jsFieldTypeCurrencyJpy' => 1));

      $oForm->addField('input', 'rate', array('type' => 'text', 'label' => 'Invoice rate (%)',
        'id' => 'salary_rate', 'value' => $oDdPlacement->getFieldValue('salary_rate')));
      $oForm->setFieldControl('rate', array('jsFieldNotEmpty' => 1));

      $oForm->addField('input', 'amount', array('type' => 'text', 'label' => 'Invoice amount (&yen;)',
        'id' => 'pla_amountId', 'value' => $oDdPlacement->getFieldValue('amount')));
      $oForm->setFieldControl('amount', array('jsFieldNotEmpty' => 1, 'jsFieldTypeCurrencyJpy' => 1));

      $oForm->addField('input', 'refund_amount', array('type' => 'text', 'label' => 'Refund (&yen;)',
        'id' => 'refund_amount', 'value' => $oDdPlacement->getFieldValue('refund_amount')));

      $oForm->addField('misc', '', array('type' =>'text',  'label' => '&nbsp;',
        'text' => '
          <div style="float: left;">
            <a href=\'javascript:;\' onclick=\'updatePaymentAmount($("#pla_amountId"));\'>calculate split amount</a>
          </div>
          <div style="float: right;">
            <a href=\'javascript:;\' onclick=\'update_payment_percentage($("#pla_amountId"));\'>calculate split percentage</a>
          </div>
        '));


      $oForm->addField('textarea', 'comment', array('label' => 'Contact & notes', 'value' => $oDdPlacement->getFieldValue('comment')));



      $oForm->addSection('payment_section', array('class' => 'payment_section'), 'Payment details');

      $sURL = $this->_oPage->getAjaxUrl('login', CONST_ACTION_SEARCH, CONST_LOGIN_TYPE_USER, 0, array('all_users' => 1));
      for($nCount = 0; $nCount < 5; $nCount++)
      {

        $oForm->addField('selector', 'pay_loginfk['.$nCount.']', array('url' => $sURL, 'label' => 'Consultant'));
        $oForm->setFieldDisplayparams('pay_loginfk['.$nCount.']', array('class' => 'widerField'));

        if(!empty($asPaymentRow[$nCount][0]))
        {
          $oForm->addOption('pay_loginfk['.$nCount.']', array('label' => $asPaymentRow[$nCount][0]['label'], 'value' => $asPaymentRow[$nCount][0]['value']));
        }

        if (empty($asPaymentRow[$nCount][2]))
          $asPaymentRow[$nCount][2] = 0;

        $oForm->addField('input', 'pay_split['.$nCount.']', array('type' =>'text', 'label' => 'split %',
          'class' => 'split', 'id' => 'split'.$nCount, 'value' =>  $asPaymentRow[$nCount][1]));
        $oForm->addField('input', 'pay_amount['.$nCount.']', array('type' =>'text', 'label' => 'amount &yen;',
          'id' => 'pay_amount'.$nCount, 'class' => 'pay_amount',
          'value' =>  number_format($asPaymentRow[$nCount][2], 0, '.', ',') ));


        if($nCount === 0)
        {
          $oForm->setFieldControl('pay_loginfk[0]', array('jsFieldNotEmpty' => 1));
          $oForm->setFieldControl('pay_split[0]', array('jsFieldNotEmpty' => 1));
          $oForm->setFieldControl('pay_amount[0]', array('jsFieldNotEmpty' => 1));
        }
      }

      $oForm->closeSection();

      return $oForm->getDisplay();
    }

    private function _savePlacement($revenue_id = 0)
    {
      $revenue_array = array();

      if(empty($revenue_id))
      {
        $revenue_array['date_created'] = date('Y-m-d H:i:s');
      }
      else
      {
        //editing
        $existing_placement = $this->_getModel()->get_revenue_info($revenue_id);
        $read = $existing_placement->readFirst();
        if(!$read)
          return __LINE__.' - Can not find the placement.';
      }

      $revenue_array['candidate'] = getValue('pla_candidatefk');

      $revenue_array['position'] = getValue('pla_cp_jd_key');
      if(empty($revenue_array['position']))
        return array('error' => 'You must select a position');

      $asKey = explode('_', $revenue_array['position']);
      if((count($asKey) !== 2 || !is_numeric($asKey[1])))
        return array('error' => __LINE__.' - The selected position is incorrect');

      $revenue_array['position'] = (int)$asKey[1];

      if(empty($revenue_array['candidate']))
        return array('error' => 'You must select a candidate');

      $revenue_array['closed_by'] = (int)getValue('pla_loginfk');
      if(empty($revenue_array['closed_by']))
        return array('error' => 'You must select a consultant');

      $revenue_array['date_start'] = getValue('date_start');
      if(!is_date($revenue_array['date_start']))
        return array('error' => 'The start date is incorrect ');

      $revenue_array['date_due'] = getValue('date_due');
      if(!is_date($revenue_array['date_due']))
        return array('error' => 'The due date is incorrect ');

      $revenue_array['date_paid'] = getValue('date_paid', '');
      if(!empty($revenue_array['date_paid']) && !is_date($revenue_array['date_paid']))
        return array('error' => 'The date paid is incorrect ');

      if (empty($revenue_array['date_paid']))
        $revenue_array['date_paid'] = null;

      $revenue_array['date_signed'] = getValue('date_signed');
      if(!is_date($revenue_array['date_signed']))
        return array('error' => 'The signed date is incorrect ');


      $revenue_array['amount'] = filter_var(getValue('amount', 0), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      if (empty($revenue_array['amount']))
        $revenue_array['amount'] = 0;

      $revenue_array['salary'] = filter_var(getValue('salary', 0), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      if (empty($revenue_array['salary']))
        $revenue_array['salary'] = 0;

      $revenue_array['salary_rate'] = filter_var(getValue('rate', 0), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      if($revenue_array['salary_rate'] > 100)
        return array('error' => 'Invoice rate must be between 0 - 100 % [value: '.$revenue_array['salary_rate'].']');

      $revenue_array['comment'] = getValue('comment');
      $revenue_array['status'] = getValue('revenue_status');

      $revenue_array['placement_count'] = getValue('placement_count', 'no');

      $revenue_array['currency'] = 'jpy';

      $revenue_array['refund_amount'] = filter_var(getValue('refund_amount', 0), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      if (empty($revenue_array['refund_amount']))
        $revenue_array['refund_amount'] = 0;

      if($revenue_array['refund_amount'] > $revenue_array['amount'] && $revenue_array['amount'] > 0)
        return array('error' => 'Refund amount cannot be higher than invoice amount');

      $revenue_array['location'] = getValue('location');
      if(empty($revenue_array['location']))
        return array('error' => 'Location can\'t be empty.');


      if (empty($revenue_array['amount']) && empty($revenue_array['salary']) &&
          empty($revenue_array['salary_rate']) && empty($revenue_array['refund_amount']))
        return array('error' => '');

      // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
      //check payment rows
      $revenue_members = array();
      $paid_user = array();
      $split_amount = 0;

      for($count = 0; $count < 4; $count++)
      {
        if(isset($_POST['pay_loginfk'][$count]) && !empty($_POST['pay_loginfk'][$count]))
        {
          if(empty($_POST['pay_split'][$count]) || empty($_POST['pay_amount'][$count]))
            return array('error' => 'Every payment needs a split % and an amount.');

          if(in_array((int)$_POST['pay_loginfk'][$count], $paid_user))
            return array('error' => 'You have multiple payments for the same user.');

          $_POST['pay_split'][$count] = str_replace(',', '', $_POST['pay_split'][$count]);
          $_POST['pay_amount'][$count] = str_replace(',', '', $_POST['pay_amount'][$count]);

          if(!is_numeric($_POST['pay_split'][$count]))
            return array('error' => 'The split value ['.$_POST['pay_split'][$count].'] is incorrect on payment line #'.($count+1));

          if(!is_numeric($_POST['pay_amount'][$count]))
            return array('error' => 'The amount value ['.$_POST['pay_amount'][$count].'] is incorrect on payment line #'.($count+1));


          $split_amount+= $_POST['pay_split'][$count];

          $user = (int)$_POST['pay_loginfk'][$count];

          $db_result = $this->_getModel()->getByPk($user, 'login');
          $user_data = $db_result->getData();

          $revenue_members[] = array(
            'revenue_id' => 0,
            'loginpk' => $user,
            'user_position' => $user_data['position'],
            'percentage' => (float)$_POST['pay_split'][$count],
            'split_amount' => $_POST['pay_amount'][$count]);

          $paid_user[] = (int)$_POST['pay_loginfk'][$count];
        }
      }

      //check sum of splits
      if($split_amount > 100.0000001)
        return array('error' => 'The total split ['.$split_amount.'%] is exceeding 100% ');
      elseif($split_amount < 100)
        return array('error' => 'The total split ['.$split_amount.'%] is lower than 100% ');



      // -----------------------------------------------------------------
      //Everything checked... Save placement and get PK to create/update payments
      if(empty($revenue_id))
      {
        // Last test:  check we're not creating a duplicate placement except for retainers
        /*if ($revenue_array['candidate'] != 'retainer')
        {
          $check_existing_payment = $this->_getModel()->getByWhere('revenue', 'position = "'.$revenue_array['position'].'"');

          $bRead = $check_existing_payment->readFirst();
          if($bRead)
            return array('error' => 'There is already a placement for this position.');
        }*/
        $revenue_array['status'] = 'signed';

        $revenue_id = $this->_getModel()->add($revenue_array, 'revenue');
        if(!$revenue_id)
          return array('error' => __LINE__.' - Could not save the placement');
      }
      else
      {
        $update = $this->_getModel()->update($revenue_array, 'revenue', 'id = '.$revenue_id);
        if(!$update)
          return array('error' => __LINE__.' - Could not update the placement');

        //need to delete the payment
        $this->_getModel()->deleteByWhere('revenue_member', 'revenue_id = '.$revenue_id);
      }



      foreach($revenue_members as $member)
      {
        $member['revenue_id'] = (int)$revenue_id;
        $this->_getModel()->add($member, 'revenue_member');
      }

      return array('notice' => 'Placement saved.', 'action' => ' setTimeout(\' $(\\\'#placementFilterFormId\\\').submit();\', 1000); ');
    }


    /**
     * based on a positionpk, return the available candidate/consultant possibilities
     */
    private function _getPlacementOptions()
    {
      $nPositionFk = (int)getValue('positionfk');
      if(empty($nPositionFk))
        return array();

      $oDbResult = $this->_getModel()->getPlacementOptions($nPositionFk);
      $bRead = $oDbResult->readFirst();
      if(!$bRead)
        return array();

      $oLogin = CDependency::getCpLogin();

      $asResult = array();
      $asContrib = $asMeeting = array();
      while($bRead)
      {
        $nLoginfk = (int)$oDbResult->getFieldValue('created_by');
        $nCandidatefk = (int)$oDbResult->getFieldValue('candidatefk');

        if(!isset($asResult[$nCandidatefk]))
        {
          $nCandiCreator = (int)$oDbResult->getFieldValue('creatorfk');

          $asResult[$nCandidatefk] = array(
            'candidatepk' => $nCandidatefk,
            'candidate' => '#'.$nCandidatefk.' '.$oDbResult->getFieldValue('candidate'),
            'consultantpk' => $nLoginfk,
            'consultant' => $oDbResult->getFieldValue('consultant').' [ placing ]',
            'contributor' => array($nCandiCreator),
            'contributor_name' => array($oLogin->getUserName((int)$oDbResult->getFieldValue('creatorfk')).' [ created candidate ]')
          );
        }

        //might be multiple ones
        $nContributor = (int)$oDbResult->getFieldValue('contributorfk');
        if($nContributor > 0 && !in_array($nContributor, $asContrib))
        {
          $asContrib[] = $nContributor;

          $asResult[$nCandidatefk]['contributor'][] = $nContributor;
          $asResult[$nCandidatefk]['contributor_name'][] = $oLogin->getUserName($nContributor).' [ contributed ]';
        }

        $nContributor = (int)$oDbResult->getFieldValue('meeting_creatorfk');
        if($nContributor > 0 && !in_array($nContributor, $asMeeting))
        {
          $asMeeting[] = $nContributor;

          $asResult[$nCandidatefk]['contributor'][] = $nContributor;
          $asResult[$nCandidatefk]['contributor_name'][] = $oLogin->getUserName($nContributor).' [ set meeting ]';
        }

        $bRead = $oDbResult->readNext();
      }

      return array('data' => $asResult);
    }

    private function _setPlacementPaid($revenue_id)
    {
      if(!assert('is_key($revenue_id)'))
        return array('error' => 'Wrong parameters');

      $revenue = $this->_getModel()->getByWhere('revenue', 'id = '.$revenue_id);
      $read = $revenue->readFirst();
      if(!$read)
        return array('error' => 'Could not find the placement.');

      $revenue_status = $revenue->getFieldValue('status');
      if ($revenue_status == 'paid' || $revenue_status == 'refund')
        return array('error' => 'Placement already set \'paid\'.');

      $data = array();
      $data['date_paid'] = date('Y-m-d');
      $data['status'] = 'paid';

      $updated = $this->_getModel()->update($data, 'revenue', 'id = '.$revenue_id);

      if(!$updated)
        return array('error' => 'Sorry, can not update the placement.');

      return array('notice' => 'Placement is now set to paid.', 'action' => ' setTimeout(\' $(\\\'#placementFilterFormId\\\').submit();\', 1000); ');
    }

    private function _deletePlacement($revenue_id)
    {
      if(!assert('is_key($revenue_id)'))
        return array('error' => 'Wrong parameters');

      $revenue = $this->_getModel()->getByWhere('revenue', 'id = '.$revenue_id);
      $read = $revenue->readFirst();
      if(!$read)
        return array('error' => 'Could not find the placement.');

      $revenue_status = $revenue->getFieldValue('status');
      if ($revenue_status == 'paid' || $revenue_status == 'refund')
        return array('error' => 'Sorry, you can not delete a paid placement.');


      $this->_getModel()->deleteByWhere('revenue', 'id = '.$revenue_id);
      $this->_getModel()->deleteByWhere('revenue_member', 'revenue_id = '.$revenue_id);

      return array('notice' => 'Placement deleted.', 'action' => ' setTimeout(\' $(\\\'#placementFilterFormId\\\').submit();\', 1000); ');
    }


    private function _exportPositionXml($location_list)
    {
      $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><root></root>');

      //https://slistem.devserv.com/index.php5?pg=cron&cronSilent=1&hashCron=1&custom_uid=555-005&export_position=1&seekPositionFrom=1363593824
      $start_time = (int)getValue('start_time', 0);
      $end_time = (int)getValue('end_time', 0);

      if(!empty($start_time) && !empty($end_time))
      {
        $start_date = date('Y-m-d H:i:s', $start_time);
        $end_date = date('Y-m-d H:i:s', $end_time);

        $language = filter_var(getValue('language', 'en'), FILTER_SANITIZE_STRING);

        $query_builder = $this->_getModel()->getQueryBuilder();
        $query_builder->addSelect('*, sind.label as industry, spde.date_created as position_date_created, spde.description as position_description ');
        $query_builder->addWhere('spos.date_created BETWEEN "'.$start_date.'" AND "'.$end_date.'" AND spde.is_public = 1 AND spde.language = "'.$language.'"');

        $raw_position_data = $this->getPositionList($query_builder, true);
        if(!empty($raw_position_data))
        {
          $new_position = $xml->addChild('new_position');
          $new_position->addAttribute('nb', count($raw_position_data));

          foreach($raw_position_data as $posistion_data)
          {
            $posistion_data['sl_positionpk'] = (int)$posistion_data['sl_positionpk'];

            try
            {
              $position_details = $new_position->addChild('position');

              $position_details->addChild('category', 0);
              $position_details->addChild('job_type', 1);

              $position_details->addChild('position_id', cleanXmlString($posistion_data['sl_positionpk']));
              $position_details->addChild('company_name', cleanXmlString('Company name not publicy visible'));
              $position_details->addChild('company_id', cleanXmlString($posistion_data['companyfk']));

              $position_details->addChild('page_title', cleanXmlString($posistion_data['title']));
              $position_details->addChild('position_title', cleanXmlString($posistion_data['title']));

              $position_details->addChild('salary',
                cleanXmlString(number_format($posistion_data['salary_from'], 0, '.', ',').' - '.
                  number_format($posistion_data['salary_to'], 0, '.', ',')));
              $position_details->addChild('salary_low', cleanXmlString($posistion_data['salary_from']));
              $position_details->addChild('salary_high', cleanXmlString($posistion_data['salary_to']));

              $position_details->addChild('station', '');
              $position_details->addChild('work_hours', '');
              $position_details->addChild('holidays', '');

              $temp_meta_keywords = preg_replace('/[^ \w]+/', '', strtolower($posistion_data['title']));
              $temp_meta_keywords = str_replace(' ', ', ', str_replace('  ', ' ', $temp_meta_keywords));

              $position_details->addChild('meta_keywords', cleanXmlString($temp_meta_keywords));

              $position_details->addChild('industry_name', cleanXmlString($posistion_data['industry']));
              $position_details->addChild('industry_id', cleanXmlString($posistion_data['sl_industrypk']));
              $position_details->addChild('industry_parent', cleanXmlString($posistion_data['parentfk']));

              $position_details->addChild('career', cleanXmlString($posistion_data['career_level']));

              if(!empty($posistion_data['moderation']))
              {
                if($posistion_data['language'] == 'en')
                  $position_details->addChild('priority', cleanXmlString('1'));
                else
                  $position_details->addChild('priority', cleanXmlString('11'));
              }
              else
                $position_details->addChild('priority', cleanXmlString('0'));

              $position_details->addChild('cons_name', cleanXmlString($posistion_data['firstname'].' '.$posistion_data['lastname']));
              $position_details->addChild('cons_email', cleanXmlString($posistion_data['email']));
              $position_details->addChild('date_created', $posistion_data['position_date_created']);

              switch($posistion_data['lvl_english'])
              {
                case $posistion_data['lvl_english'] >= 8: $english_level_word = 'Native'; $english_level_number = 4; break;
                case $posistion_data['lvl_english'] >= 6: $english_level_word = 'Fluent'; $english_level_number = 3; break;
                case $posistion_data['lvl_english'] >= 4: $english_level_word = 'Business'; $english_level_number = 2; break;
                case $posistion_data['lvl_english'] >= 2: $english_level_word = 'Basic'; $english_level_number = 1; break;
                default:                            $english_level_word = '-'; $english_level_number = 0; break;
              }
              $position_details->addChild('english', cleanXmlString($english_level_word));
              $position_details->addChild('english_nb', $english_level_number);
              $position_details->addChild('lvl_english', $posistion_data['lvl_english']);

              switch($posistion_data['lvl_japanese'])
              {
                case $posistion_data['lvl_japanese'] >= 8: $japanese_level_word = 'Native'; $japanese_level_number = 4; break;
                case $posistion_data['lvl_japanese'] >= 6: $japanese_level_word = 'Fluent'; $japanese_level_number = 3; break;
                case $posistion_data['lvl_japanese'] >= 4: $japanese_level_word = 'Business'; $japanese_level_number = 2;  break;
                case $posistion_data['lvl_japanese'] >= 2: $japanese_level_word = 'Basic'; $japanese_level_number = 1;  break;
                default:                             $japanese_level_word = '-'; $japanese_level_number = 0; break;
              }
              $position_details->addChild('japanese', cleanXmlString($japanese_level_word));
              $position_details->addChild('japanese_nb', $japanese_level_number);
              $position_details->addChild('lvl_japanese', $posistion_data['lvl_japanese']);

              $posistion_data['position_description'] = addslashes($posistion_data['position_description']);
              // $posistion_data['responsabilities'] = addslashes($posistion_data['responsabilities']);
              $posistion_data['requirements'] = addslashes($posistion_data['requirements']);

              if(!empty($posistion_data['age_from']))
                $position_details->addChild('age', $posistion_data['age_from'].' - '.$posistion_data['age_to']);

              if(!empty($posistion_data['requirements']))
                $position_details->addChild('requirements', cleanXmlString($posistion_data['requirements']));

              if(!empty($posistion_data['position_description']))
              {
                $position_description = $posistion_data['position_description'];

                if(!empty($posistion_data['responsabilities']))
                  $position_description .= " \n".$posistion_data['responsabilities'];

                $position_details->addChild('position_desc', cleanXmlString($position_description));
                $position_details->addChild('meta_desc', cleanXmlString(substr($posistion_data['position_description'], 0, 200).'...'));
              }

              $position_details->addChild('display_age', $posistion_data['display_age']);
              $position_details->addChild('display_salary', $posistion_data['display_salary']);
              $position_details->addChild('display_date', $posistion_data['display_date']);


              if (!empty($posistion_data['location']))
              {
                $position_details->addChild('location', cleanXmlString($location_list[$posistion_data['location']]));
              }

              unset($posistion_data['password'], $posistion_data['pseudo'], $posistion_data['birthdate'], $posistion_data['gender'],
                    $posistion_data['courtesy'], $posistion_data['id'], $posistion_data['is_admin'], $posistion_data['valid_status'],
                    $posistion_data['hashcode'], $posistion_data['date_expire'], $posistion_data['date_reset'],
                    $posistion_data['date_last_log'], $posistion_data['log_hash'], $posistion_data['webmail'],
                    $posistion_data['webpassword'], $posistion_data['mailport'], $posistion_data['Imap'],
                    $posistion_data['aliasName'], $posistion_data['signature']);

              $position_details->addChild('data', base64_encode(serialize($posistion_data)));
            }
            catch(Exception $e)
            {
              echo 'error with this row : ';  dump($posistion_data);
            }
          }
        }

        //trace
        $xml_string = $xml->saveXML();
        $xml_string = str_replace('&gt;&lt;', '&gt;<br />&lt;', htmlentities($xml_string));
        $xml_string = str_replace('&lt;/position&gt;', '&lt;/position&gt;<br />', $xml_string);
      }


      //https://slistem.devserv.com/index.php5?pg=cron&cronSilent=1&hashCron=1&custom_uid=555-005&export_position=1&position_to_check=5600,3215,4523,6520,3569,5412
      $positions_to_check = getValue('position_to_check');
      if(!empty($positions_to_check))
      {
        $position_ids = explode(',', $positions_to_check);
        if(!empty($position_ids))
        {
          //check if the parameters
          $bValidIds = true;
          foreach($position_ids as $position_id)
          {
            if(!is_numeric($position_id))
              $bValidIds = false;
          }

          $check = $xml->addChild('check_position');

          if(!$bValidIds)
          {
            $check->addChild('error', 'Wrong parameters to check positions');
          }
          else
          {
            $query_builder = $this->_getModel()->getQueryBuilder();
            $query_builder->addSelect('*, sind.label as industry ');
            $query_builder->addWhere('spos.sl_positionpk IN ('.$positions_to_check.') ');
            $raw_position_data = $this->getPositionList($query_builder, true);

            foreach($raw_position_data as $posistion_data)
            {
              $oPosition = $check->addChild('position');
              $oPosition->addChild('id', (int)$posistion_data['sl_positionpk']);
              $oPosition->addChild('status', $posistion_data['status']);
            }
          }
        }

        //trace
        $xml_string = $xml->saveXML();
        $xml_string = str_replace('&gt;&lt;', '&gt;<br />&lt;', htmlentities($xml_string));
        $xml_string = str_replace('&lt;/position&gt;', '&lt;/position&gt;<br />', $xml_string);
      }

      echo $xml->saveXML();
      // echo $xml_string;
      exit();
  }


  private function _monitorNewApplication($pasPosition, $pasData, $pasCandidate)
  {
    $asResult = array();

    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    //Check if other people are already in play for this position
    $oDbResult = $this->_getModel()->getPositionApplicant( (int)$pasPosition['sl_positionpk'], $pasData['candidatefk'], 0, 100);
    $bRead = $oDbResult->readFirst();

    if($bRead)
    {
      if(!getValue('confirm'))
      {
        return array('data' =>'', 'action' => '
          goPopup.setPopupConfirm(\''.$oDbResult->numRows().' candidate(s) are already in play for this position. Keep going ?\'
            , \' $(\\\'#linkPositionFormId input[name=confirm]\\\').val(1); $(\\\'#linkPositionFormId\\\').submit(); \')');
      }
      else
      {
        $anRecipient = array();
        while($bRead)
        {
          $nRecipeientPk = (int)$oDbResult->getFieldValue('app_by');
          if($nRecipeientPk != $this->casUserData['loginpk']  && $oDbResult->getFieldValue('active') && (int)$oDbResult->getFieldValue('app_status') < 101)
            $anRecipient[$nRecipeientPk] = $nRecipeientPk;

          $bRead = $oDbResult->readNext();
        }

        if(!empty($anRecipient))
          $this->_notifyPositionCompetition($pasPosition, $pasCandidate, $anRecipient);
      }
    }

    // -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=- -=-
    //Check if this candidate is active on other positions
    $oDbResult = $this->_getModel()->getPositionApplicant(0, 0, $pasData['candidatefk'], 100);
    $bRead = $oDbResult->readFirst();
    if($bRead)
    {
      if(!getValue('confirm'))
      {
        return array('data' =>'', 'action' => '
          goPopup.setPopupConfirm(\'This candidate is already active on '.$oDbResult->numRows().' positions. Keep going ?\'
            , \' $(\\\'#linkPositionFormId input[name=confirm]\\\').val(1); $(\\\'#linkPositionFormId\\\').submit(); \')');
      }
      else
      {
        $anRecipient = array();
        while($bRead)
        {
          $nRecipeientPk = (int)$oDbResult->getFieldValue('app_by');
          if($nRecipeientPk != $this->casUserData['loginpk']  && $oDbResult->getFieldValue('active') && (int)$oDbResult->getFieldValue('app_status') < 101)
            $anRecipient[$nRecipeientPk] = $nRecipeientPk;

          $bRead = $oDbResult->readNext();
        }

        if(!empty($anRecipient))
          $this->_notifyPositionCompetition($pasPosition, $pasCandidate, $anRecipient);
      }
    }

    return $asResult;
  }

  private function _notifyPositionCompetition($pasPosition, $pasCandidate, $anRecipient)
  {
    $oMail = CDependency::getComponentByName('mail');
    $oLogin = CDependency::getCpLogin();
    $asUserData = $oLogin->getUserList($anRecipient, true, true);

    $sURL = $this->_oPage->getUrl('555-001', CONST_ACTION_VIEW, CONST_CANDIDATE_TYPE_CANDI, (int)$pasCandidate['sl_candidatepk']);

    foreach($anRecipient as $nRecipient)
    {
      //only active users
      if(isset($asUserData[$nRecipient]))
      {
        $sContent = 'Dear '.$asUserData[$nRecipient]['firstname'].', <br /><br />
          '.ucfirst($this->casUserData['firstname']).' '.$this->casUserData['lastname'].' has just pitched the candidate
            [<a href="'.$sURL.'">#'.$pasCandidate['sl_candidatepk'].' - '.$pasCandidate['firstname'].' '.$pasCandidate['lastname'].'</a>] to the position
          [#'.$pasPosition['positionfk'].' - '.$pasPosition['title'].'].<br /><br /><br />
          <span style="font-style: italic; color; #666;">You are receiving this email because Sl[i]stem indicates you are working with this candidate or on this position:<br /><br />
          - you have pitched this candidate to another position<br />
          - you have pitched other candidate to this position<br />
          </span>';

        $oMail->createNewEmail();
        $oMail->setFrom(CONST_PHPMAILER_EMAIL, CONST_PHPMAILER_DEFAULT_FROM);
        $oMail->addRecipient($asUserData[$nRecipient]['email'], $asUserData[$nRecipient]['firstname'].' '.$asUserData[$nRecipient]['lastname']);

        $bSent = $oMail->send('Slistem notification - event on position #'.$pasPosition['positionfk'], $sContent);
        assert('!empty($bSent)');
      }
    }

    return true;
  }
}

