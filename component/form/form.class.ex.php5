<?php

require_once('component/form/form.class.php5');

class CFormEx extends CForm
{
  private $csFormName = '';
  private $cbFormAjax = false;
  private $casFormParams = array();
  private $cbFormHidden = false;
  private $cnFormNbCol = 1;
  private $cbFormNoStyle = false;
  private $cbFormAddButtons = true;
  private $cbFormCancelButton = true;
  private $cbFormCloseButton = false;
  private $cbSubmitHidden = false;
  private $casCity = array();
  private $casCountry = array();
  private $cbFormInAjax = false;
  private $csAjaxFormTarget = '';
  private $csAjaxCallback = '';
  private $csAjaxCover = '';
  private $cbFloatingFields = false;
  private $casCustomButton = array();


  private $caoFormFields = array();
  private $casFieldDisplayParams = array();
  private $casMultipleFields = array();
  private $coHTML = null;

  public function __construct($psFormName = '')
  {
    if(empty($psFormName))
      $this->csFormName = uniqid('form_', true);
    else
      $this->csFormName = $psFormName;

    $this->coHTML = CDependency::getCpHtml();
  }

  //====================================================================
  //  Interfaces
  //====================================================================
  public function getAjax()
  {
    $this->_processUrl();

    switch($this->csType)
    {
      case CONST_FORM_TYPE_CITY:
        switch($this->csAction)
        {
          case CONST_ACTION_SEARCH:
            /* custom javascript array of json data encoded in the function */
            return $this->_getSelectorCity();
            break;

          case CONST_ACTION_ADD:
            return json_encode($this->_getCityForm());
            break;

          case CONST_ACTION_SAVEADD:
            return json_encode($this->_getCitySave());
            break;
        }
        break;

      case CONST_FORM_TYPE_COUNTRY:
        switch($this->csAction)
        {
          case CONST_ACTION_SEARCH:
            /* custom javascript array of json data encoded in the function */
            return $this->_getSelectorCountry();
            break;

          case CONST_ACTION_ADD:
            return json_encode($this->_getCountryForm());
            break;
        }
        break;
    }
  }


  //====================================================================
  //  public methods
  //====================================================================



  public function getFormId()
  {
    return $this->csFormName.'Id';
  }

  /*
   * Form managment
   * Goal: implement a totally automatic form creation, control and managment
   *
   */

  public function setFormParams($psFormName = '', $pbAjax = false, $pasParams = array())
  {
    if(!assert('is_string($psFormName)'))
      return false;

    if(!assert('is_bool($pbAjax)'))
      return false;

    if(!assert('is_array($pasParams)'))
      return false;

    if(!empty($psFormName))
      $this->csFormName = $psFormName;

    $this->cbFormAjax = $pbAjax;

    //extra custom parameters
    $this->casFormParams = $pasParams;

    if(isset($this->casFormParams['noCancelButton']))
      $this->cbFormCancelButton = false;

    if(!isset($this->casFormParams['method']))
      $this->casFormParams['method'] = 'POST';

    if(isset($this->casFormParams['inajax']) && !empty($this->casFormParams['inajax']))
      $this->cbFormInAjax = true;

    if(isset($this->casFormParams['ajaxTarget']) )
      $this->csAjaxFormTarget = $this->casFormParams['ajaxTarget'];

    if(isset($this->casFormParams['ajaxCallback']) )
      $this->csAjaxCallback = $this->casFormParams['ajaxCallback'];

    if(isset($this->casFormParams['ajaxCover']))
      $this->csAjaxCover = $this->casFormParams['ajaxCover'];
    else
      $this->csAjaxCover = 'body';

    if($pbAjax && isset($this->casFormParams['action']))
    {

      $oPage = CDependency::getCpPage();
      if(!$oPage->isAjaxUrl($this->casFormParams['action']))
      {
        assert('false; // ajax form with a regular url');
        return false;
      }
    }

    return true;
  }


  public function setFormDisplayParams($pasParams)
  {
    if(!assert('is_array($pasParams)'))
      return false;

    if(empty($pasParams))
      return true;

    if(isset($pasParams['hidden']))
      $this->cbFormHidden = true;

    if(isset($pasParams['columns']) && is_numeric($pasParams['columns']) &&  (int)$pasParams['columns'] > 0)
      $this->cnFormNbCol = (int)$pasParams['columns'];

    if(isset($pasParams['noStyle']))
      $this->cbFormNoStyle = true;

    if(isset($pasParams['noCancelButton']))
      $this->cbFormCancelButton = false;

    if(isset($pasParams['noCloseButton']))
      $this->cbFormCloseButton = true;

    if(isset($pasParams['noButton']))
      $this->cbFormAddButtons = false;

     if(isset($pasParams['noSubmitButton']))
      $this->cbSubmitHidden = true;

    if(isset($pasParams['fullFloating']))
      $this->cbFloatingFields = true;

    return true;
  }

  public function addField($psFieldType, $psFieldName = '', $pasFieldParams = array())
  {
    if(!assert('is_string($psFieldType) && !empty($psFieldType)'))
      return false;

    if(!assert('is_string($psFieldName)'))
      return false;

    if(!assert('is_array($pasFieldParams)'))
      return false;

    if($psFieldType == 'misc' && empty($psFieldName))
      $psFieldName = uniqid();

    $oField = $this->getField($psFieldType, $psFieldName, $pasFieldParams);

    $this->caoFormFields[$psFieldName] = $oField;

    return true;
  }

  public function addSection($psFieldName = '', $pasFieldParams = array(), $psTitle = '')
  {
    if(!assert('is_string($psFieldName)'))
      return false;

    if(!assert('is_array($pasFieldParams)'))
      return false;

    set_array($pasFieldParams['id'], uniqid());

    if(empty($psFieldName))
      $psFieldName = uniqid();

    $pasFieldParams['type'] = 'open';

    //add a title to toggle the section opened/closed
    if(!empty($psTitle))
    {
      $oPage = CDependency::getCpPage();
      $oPage->addJsFile($this->getResourcePath().'/js/form.js');

      if( (isset($this->casOptionData['hidden']) && !empty($this->casOptionData['hidden']))
        ||(isset($this->casOptionData['folded']) && !empty($this->casOptionData['folded'])))
      {
        $this->addField('misc', '', array('type' => 'title', 'title' => $psTitle, 'class' => 'sectionTitle sectionOpened', 'onclick' => 'toggleSection(this, \''.$pasFieldParams['id'].'\');'));
      }
      else
        $this->addField('misc', '', array('type' => 'title', 'title' => $psTitle, 'class' => 'sectionTitle sectionClosed', 'onclick' => 'toggleSection(this, \''.$pasFieldParams['id'].'\');'));
    }

    $oField = $this->getField('section', $psFieldName, $pasFieldParams);
    $this->caoFormFields[$pasFieldParams['id']] = $oField;

    return true;
  }

  public function closeSection($psFieldId = '', $pasFieldParams = array())
  {
    if(!assert('is_string($psFieldId)'))
      return false;

    if(!assert('is_array($pasFieldParams)'))
      return false;

    if(empty($psFieldId))
      $psFieldId = uniqid();

    $pasFieldParams['type'] = 'close';

    $oField = $this->getField('section', $psFieldId, $pasFieldParams);
    $this->caoFormFields[$psFieldId] = $oField;

    return true;
  }

  /**
   * Alias for above functions to unify with display
   *
   * @param string $psFieldName
   * @param array $pasFieldParams
   * @param string $psTitle
   * @return type
   */
  public function sectionStart($psFieldName = '', $pasFieldParams = array(), $psTitle = '')
  {
    return $this->addSection($psFieldName, $pasFieldParams, $psTitle);
  }
  public function sectionEnd($psFieldName = '', $pasFieldParams = array(), $psTitle = '')
  {
    return $this->closeSection($psFieldName, $pasFieldParams, $psTitle);
  }




  public function addOption($psFieldName, $pasFieldParams, $pvOption = null)
  {
    if(!isset($this->caoFormFields[$psFieldName]))
    {
      assert('false; // field doesnt exist');
      return false;
    }

    return $this->caoFormFields[$psFieldName]->addOption($pasFieldParams, $pvOption);
  }

  public function addOptionHtml($psFieldName, $pasFieldParams)
  {
    if(!isset($this->caoFormFields[$psFieldName]))
    {
      assert('false; // field doesnt exist');
      return false;
    }

    return $this->caoFormFields[$psFieldName]->addOptionHtml($pasFieldParams);
  }


  public function setFieldRequired($psFieldName, $pbValue = true)
  {
    if(!isset($this->caoFormFields[$psFieldName]))
    {
      asset('false; // field doesnt exist');
      return false;
    }

    return $this->caoFormFields[$psFieldName]->setRequired($pbValue);
  }

  public function setFieldControl($psFieldName, $pasControl)
  {
    if(!isset($this->caoFormFields[$psFieldName]))
      assert('false; // field doesnt exist');

    return $this->caoFormFields[$psFieldName]->setFieldControl($pasControl);
  }

  public function setFieldDisplayParams($psFieldName, $pasFieldParams)
  {
    if(!isset($this->caoFormFields[$psFieldName]))
      assert('false; // field doesnt exist');

    return $this->casFieldDisplayParams[$psFieldName] = $pasFieldParams;
  }


  /**
   * Return an instance of a form field
   * done that way so we can directly get a field without defining adding a form in the html
   *
   * @param string $psFieldType
   * @param string $psFieldName
   * @param array $pasFieldParams
   */
  public function getField($psFieldType, $psFieldName, $pasFieldParams)
  {
   if(!assert('is_string($psFieldType) && !empty($psFieldType)'))
      return false;

    if(!assert('is_string($psFieldName) && !empty($psFieldType)'))
      return false;

    if(!assert('is_array($pasFieldParams)'))
      return false;


    if(isset($this->caoFormFields[$psFieldName]) && !in_array($psFieldType, array('radio', 'checkbox', 'select', 'section')))
    {
      assert('false; // field name ['.$psFieldName.'] already used in this form ['.$this->csFormName.']');
      return false;
    }

    $pasFieldParams = array_merge($pasFieldParams, array('inajax' => $this->cbFormInAjax));

    switch($psFieldType)
    {
      case 'radio':

        if(isset($this->caoMultipleFields[$psFieldName]))
        {
          //get the existing field and add the next checkbok/radio to its parameters
          $oField = $this->caoMultipleFields[$psFieldName]->addOption($pasFieldParams);
        }
        else
        {
          require_once('component/form/fields/radio.class.php5');
          $oField = new CRadio($psFieldName, $pasFieldParams);
          $this->caoMultipleFields[$psFieldName] = $oField;
        }
        break;

      case 'sselect':
        $this->addJsFile($this->getResourcePath().'js/sselect.js');
        $this->addCssFile($this->getResourcePath().'css/sselect.css');
        require_once('component/form/fields/styledselect.class.php5');
        $oField = new CStyledSelect($psFieldName, $pasFieldParams);
        break;

      case 'checkbox':
        require_once('component/form/fields/checkbox.class.php5');
        $oField = new CCheckbox($psFieldName, $pasFieldParams);
        break;

      case 'select':
        require_once('component/form/fields/select.class.php5');
        $oField = new CSelect($psFieldName, $pasFieldParams);
        // TO DO : Allow to pass a manageable list name in $pasFieldParams
        // and add its options here
        break;

      case 'hidden':
        require_once('component/form/fields/input.class.php5');
        $pasFieldParams['type'] = 'hidden';
        $oField = new CInput($psFieldName, $pasFieldParams);
        break;

      case 'input':
        require_once('component/form/fields/input.class.php5');
        $oField = new CInput($psFieldName, $pasFieldParams);
        break;

      case 'textarea':
        require_once('component/form/fields/textarea.class.php5');
        $oField = new CTextArea($psFieldName, $pasFieldParams);
        break;

      case 'misc':
        require_once('component/form/fields/misc.class.php5');
        $oField = new CMisc($psFieldName, $pasFieldParams);
        break;

      case 'button':
        require_once('component/form/fields/button.class.php5');
        $oField = new CRadio($psFieldName, $pasFieldParams);
        break;


      case 'selector_city':
      case 'selector_country':
      case 'selector':
        require_once('component/form/fields/autocomplete.class.php5');
        $oField = new CAutocomplete($psFieldName, $psFieldType, $pasFieldParams);
        break;

      case 'slider':
        require_once('component/form/fields/slider.class.php5');
        $oField = new CSlider($psFieldName, $pasFieldParams);
        break;

      case 'tree':
        require_once('component/form/fields/tree.class.php5');
        $oField = new CTree($psFieldName, $pasFieldParams);
        break;

      case 'section':
        require_once('component/form/fields/section.class.php5');
        $oField = new CSection($psFieldName, $pasFieldParams);
        break;

      case 'paged_tree':
        require_once('component/form/fields/ptree.class.php5');
        $oField = new CPtree($psFieldName, $pasFieldParams);
        break;

      case 'currency':
        require_once('component/form/fields/currency.class.php5');
        $oField = new CCurrency($psFieldName, $pasFieldParams);
        break;

      case 'cp_item_selector':
        require_once('component/form/fields/item_selector.class.php5');
        $oField = new CItemSelector($psFieldName, $pasFieldParams);
        break;

      default:
        assert('false; //no ['.$psFieldType.'] field available');
        return null;
    }

    return $oField;
  }

  /**
  * @return string : the html code of the form
  */
  public function getDisplay()
  {
    //-----------------------------------
    // Fetching form parameters
    if($this->cbFormNoStyle)
    {
      if(isset($this->casFormParams['style']))
        unset($this->casFormParams['style']);

      if(isset($this->casFormParams['class']))
        unset($this->casFormParams['class']);
    }

    if(isset($this->casFormParams['submitLabel']))
    {
      $sSubmitLabel = $this->casFormParams['submitLabel'];
      unset($this->casFormParams['submitLabel']);
    }
    else
      $sSubmitLabel = 'Validate';

    if(isset($this->casFormParams['onSubmit']))
    {
      $sOnSubmit = $this->casFormParams['onSubmit'];
      unset($this->casFormParams['onSubmit']);
    }
    else
      $sOnSubmit = '';

    if(!isset($this->casFormParams['id']) || empty($this->casFormParams['id']))
      $this->casFormParams['id'] = $this->csFormName.'Id';

    if(!isset($this->casFormParams['onBeforeSubmit']) )
      $this->casFormParams['onBeforeSubmit'] = '';

    //------------------------------
    //adding controls

    $oPage = CDependency::getCpPage();
    $oPage->addJsFile(self::getResourcePath().'js/fieldControl.js');

    if(!isset($this->casFormParams['template']) || empty($this->casFormParams['template']))
      $oPage->addCssFile($this->getResourcePath().'css/form.css');
    else
      $oPage->addCssFile($this->getResourcePath().'css/'.$this->casFormParams['template'].'.css');



    $sOnClick = '';

    //cbFormInAjax ==> Where the form is displayed
    //if its a form in ajax, we put the js in the submit/onsubmit

    $sJavascript = '';
    $sJavascript.= "  $('form[name=".$this->csFormName."]').submit(function(event) ";
    $sJavascript.= "  { ";

    if($this->cbFormAjax)
    {
      $sJavascript.= " event.preventDefault(); ";
      $sJavascript.= ' '.$this->casFormParams['onBeforeSubmit'].' ';

      $sJavascript.= "    if(checkForm('".$this->csFormName."')) ";
      $sJavascript.= "    { ";
      $sJavascript.= "      var sURL = $('form[name=".$this->csFormName."]').attr('action'); ";
      $sJavascript.= "      var sFormId = $('form[name=".$this->csFormName."]').attr('id'); ";
      $sJavascript.= "      var sAjaxTarget = '".$this->csAjaxFormTarget."'; ";
      //$sJavascript.= "      setCoverScreen(true, true); ";
      $sJavascript.= "      setTimeout(\" AjaxRequest('\"+sURL+\"', '.$this->csAjaxCover.', '\"+sFormId+\"', '\"+sAjaxTarget+\"', '', '', 'setCoverScreen(false); ".$this->csAjaxCallback." '); \", 350); ";
      $sJavascript.= "    } ";
      $sJavascript.= "    return false; ";
    }
    else
    {
      $sJavascript.= ' '.$this->casFormParams['onBeforeSubmit'].' ';
      $sJavascript.= "    if(!checkForm('".$this->csFormName."')) ";
      $sJavascript.= "    { event.preventDefault(); return false; } ";

      if(!empty($this->csAjaxCover))
        $sJavascript.= "    else { setCoverScreen(true, true); } ";
    }

    $sJavascript.= " }); ";


    $sHtml = '';
    if($this->cbFormHidden)
       $sHtml.= $this->coHTML->getBlocStart('', array('style' => 'display:none;'));

    $sHtml.= '<form name="'.$this->csFormName.'" enctype="multipart/form-data" submitAjax="'.(int)$this->cbFormAjax.'" ';
    foreach ($this->casFormParams as $sKey => $vValue)
    {
      $sHtml.= ' '.$sKey.'="'.$vValue.'" ';
    }

    $sHtml.= ' onsubmit="'.$sOnSubmit.'">';
    $sHtml.= $this->coHTML->getBlocStart($this->csFormName.'InnerId',array('class'=>'innerForm'));


    $sHtml.= $this->_getFormFields($this->coHTML);

    if($this->cbFormAddButtons && (!$this->cbSubmitHidden || $this->cbFormCancelButton || $this->cbFormCloseButton || !empty($this->casCustomButton)))
    {
      $sHtml.= $this->coHTML->getBloc('','&nbsp;',array('class'=>'formFieldLinebreaker formFieldWidth1'));
      $sHtml.= ' <div class="submitBtnClass formFieldWidth1">';

      if($this->cbSubmitHidden)
        $sHtml.= ' <input type="submit" value="'.$sSubmitLabel.'" onclick="'.$sOnClick.'" class="hidden"/>';
      else
        $sHtml.= ' <input type="submit" value="'.$sSubmitLabel.'" onclick="'.$sOnClick.'" />';

      if($this->cbFormCancelButton)
        $sHtml.= ' <input type="button" value="Cancel" onclick="window.history.go(-1)" />';

      if($this->cbFormCloseButton)
        $sHtml.= ' <input type="button" value="Cancel" onclick="goPopup.removeActive();" />';

      if(!empty($this->casFormParams['skipToUrl']))
        $sHtml.= '  or  <a class="skip" href="'.$this->casFormParams['skipToUrl'].'">Skip this step</a>';

      if(!empty($this->casCustomButton))
      {
        $sHtml.= implode(' ', $this->casCustomButton);
      }

      $sHtml.= $this->coHTML->getFloatHack();
      $sHtml.= ' </div>';
    }
    $sHtml.= $this->coHTML->getFloatHack();

    $sHtml.= $this->coHTML->getBlocEnd();
    $sHtml.= $this->coHTML->getFloatHack();

    $sHtml.= '</form>';
    $sHtml.= '<script>'.$sJavascript.'</script>';

    if($this->cbFormHidden)
      $sHtml.= $this->coHTML->getBlocEnd();

    return $sHtml;
  }

  /**
   *
   * @return string a the html code of all the form fields
   */
  private function _getFormFields()
  {
    $sHtml = '';

    if(!empty($this->caoFormFields))
    {
      $nField = 0;
      $bNewline = false;
      $bPreviousKIL = false;    //need to know if the previous field was exceptionally kept in line

      foreach($this->caoFormFields as $sFieldName => $oField)
      {
        //echo '==>field: '.$sFieldName.' => [#field: '.$nField.'| newL: '.(int)$bNewline.' | PKIL:'.$bPreviousKIL.']<br />';

        if($oField->isSectionStart())
        {
          $sHtml.= $oField->getDisplay();
          $nField = 0;
        }
        else
        {
          if($oField->isSectionEnd())
          {
            $sHtml.= $oField->getDisplay();
            $nField = 0;
          }
          else
          {
            $bKeepInLine = ($this->cbFloatingFields || $oField->isKeepInline() );

            //echo ' -->keepinline: '.(int)$bKeepInLine.'<br />';

            //after a serie of Keep InlineFields, the first normal field triggers a floatHack
            //and reset the counter
            if($bPreviousKIL && !$bKeepInLine)
            {
              //echo ' -->end of keepinline serie: '.$bPreviousKIL.' && !'.$bKeepInLine.'<br />';
              $sHtml.= $this->coHTML->getFloatHack();
              $nField = 0;
            }

            $bVisible = $oField->isVisible();
            //echo ' -->visible: '.(int)$bVisible.'<br />';
            if(!$bVisible)
              $sExtraClass = ' formFieldHidden ';
            else
            {
              $sExtraClass = '';
              $nField++;
            }

            $asFieldParams = CField::getFieldParams($oField);

            //Check if there are custom display parameters and add those on the field container
            if(isset($this->casFieldDisplayParams[$sFieldName]))
              $asDisplayParam = $this->casFieldDisplayParams[$sFieldName];
            else
              $asDisplayParam = array();

            if(isset($asDisplayParam['class']))
            {
              $sExtraClass.= ' '.$asDisplayParam['class'];
              unset($asDisplayParam['class']);
            }

            if(isset($asFieldParams['type']) && $asFieldParams['type'] == 'title')
            {
              $asOption = array_merge(array('class'=>'formFieldContainer fieldName'.$oField->getName().' formFieldWidth1 '.$sExtraClass), $asDisplayParam);
            }
            else
            {
              $asOption = array_merge(array('class'=>'formFieldContainer fieldName'.$oField->getName().' formFieldWidth'.$this->cnFormNbCol.' '.$sExtraClass), $asDisplayParam);
            }
            $sHtml.= $this->coHTML->getBlocStart('', $asOption);

            $sHtml.= $oField->getDisplay();
            $sHtml.= $this->coHTML->getFloatHack();
            $sHtml.= $this->coHTML->getBlocEnd();

            if($oField->isEndingLine())
            {
              //echo ' -->isEnding line: 1 <br />';
              $sHtml.= $this->coHTML->getBlocStart('',array('class'=>'formFieldLineBreaker formFieldWidth1'));
              $sHtml.= '&nbsp;';
              $sHtml.= $this->coHTML->getBlocEnd();

              $bNewline = true;
            }

            //Add empty lines in the form
            $nLineToSkip = $oField->getSkippingLine();
            //echo ' -->skip '.$nLineToSkip.' lines <br />';
            for($nCount=0; $nCount < $nLineToSkip; $nCount++)
            {
              $sHtml.= $this->coHTML->getBlocStart('',array('class'=>'formFieldSeparator formFieldWidth1'));
              $sHtml.= '&nbsp;';
              $sHtml.= $this->coHTML->getBlocEnd();

              $bNewline = true;
            }

            //echo ' -->put a float hack after the field ? '.$nLineToSkip.' lines <br />';
            //echo "if(!".(int)$bKeepInLine." && (".(int)$bNewline." || (".$this->cnFormNbCol." > 1 && $nField > 0 && ($nField % ".$this->cnFormNbCol.") == 0)))<br />";

            //detect if we put a float hack div to align fields
            if(!$bKeepInLine && ($bNewline || ($this->cnFormNbCol > 1 && $nField > 0 && ($nField % $this->cnFormNbCol) == 0)))
            {
              //echo "if(true) babawwww FloatHack<br/>";
              $sHtml.= $this->coHTML->getFloatHack();
              $bNewline = false;
              $nField = 0;
            }

            //update the previousKIL for next field
            $bPreviousKIL = $bKeepInLine;
          }
        }
      }
    }

    return $sHtml;
  }

  /**
   * return a form fields html to be included in an existing form
   */
  public function getFormContent($psContainerId = '')
  {
    if(empty($psContainerId))
      return $this->_getFormFields();

    return $this->coHTML->getBloc($psContainerId, $this->_getFormFields(), array('class' => 'innerForm'));
  }

  public function addCssFile($pasCssFile)
  {
    $oPage = CDependency::getComponentByName('page');
    return $oPage->addCssFile($pasCssFile);
  }

  public function addJsFile($pasJsFile)
  {
    $oPage = CDependency::getComponentByName('page');
    return $oPage->addJsFile($pasJsFile);
  }

  public function addCustomJs($pasJavascript)
  {
    if(!assert('is_array($pasJavascript)') || empty($pasJavascript))
       return false;

    $oPage = CDependency::getCpPage();
    return $oPage->addCustomJs($pasJavascript);
  }


  //*************************************************************************************************
  //*************************************************************************************************
  //*************************************************************************************************
  //Selectors management methods

  /**
   * return the ajax url used for the country selector
  */
  public function getCountrySelectorAjaxUrl()
  {
    $oPage = CDependency::getCpPage();
    return $oPage->getAjaxurl('form', CONST_ACTION_SEARCH, CONST_FORM_TYPE_COUNTRY);
  }
  /**
   * return the ajax url used to add a country
  */
  public function getCountrySelectorAddUrl()
  {
    $oPage = CDependency::getCpPage();
    return $oPage->getAjaxurl('form', CONST_ACTION_ADD, CONST_FORM_TYPE_COUNTRY);
  }

  /**
   * return the ajax url used for the city selector
  */
  public function getCitySelectorAjaxUrl()
  {
    $oPage = CDependency::getCpPage();
    return $oPage->getAjaxurl('form', CONST_ACTION_SEARCH, CONST_FORM_TYPE_CITY);
  }
  /**
   * return the ajax url used for to add a new city to the list
  */
  public function getCitySelectorAddUrl()
  {
    $oPage = CDependency::getCpPage();
    return $oPage->getAjaxurl('form', CONST_ACTION_ADD, CONST_FORM_TYPE_CITY);
  }

  /**
   * return the name of a country (save the country list for later usage)
   * @param integer $pnCountryFk
   * @return string
   */
  public function getCountryData($pnCountryFk)
  {
    if(!assert('is_integer($pnCountryFk) && !empty($pnCountryFk)'))
      return array();

    if(!empty($this->casCountry))
    {
      if(isset($this->casCountry[$pnCountryFk]))
        return $this->casCountry[$pnCountryFk];
      else
        return array();
    }

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM system_country WHERE 1';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
    {
      assert('false; //no country found');
      return '';
    }

    while($bRead)
    {
      $this->casCountry[$oDbResult->getFieldValue('system_countrypk', CONST_PHP_VARTYPE_INT)] = $oDbResult->getData();
      $bRead = $oDbResult->readNext();
    }

    if(isset($this->casCountry[$pnCountryFk]))
      return $this->casCountry[$pnCountryFk];
    else
      return array();
  }

  public function addCountrySelectorOption($psFieldName, $pnCountryFk)
  {
    if(!assert('is_integer($pnCountryFk) && !empty($pnCountryFk)'))
      return false;

    if(!assert('!empty($psFieldName)'))
      return false;

    $asCountryData = $this->getCountryData($pnCountryFk);

    if(empty($asCountryData))
      $this->addOption($psFieldName, array('label' => ' unknown ', 'value' => 0));

    if(isset($asCountryData['printable_name']) && !empty($asCountryData['printable_name']))
      $sLabel = $asCountryData['printable_name'];
    else
      $sLabel = $asCountryData['country_name'];

    if(isset($asCountryData['iso3']) && !empty($asCountryData['iso3']))
      $sLabel.= ' - '.$asCountryData['iso3'];

    return $this->addOption($psFieldName, array('label' => $sLabel, 'value' => $pnCountryFk));
  }

  private function _getSelectorCountry()
  {
    $sSearch = getValue('q');
    if(empty($sSearch))
      return json_encode(array());

    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM system_country WHERE lower(country_name) LIKE '.$oDB->dbEscapeString('%'.strtolower($sSearch).'%').' OR lower(printable_name) LIKE '.$oDB->dbEscapeString('%'.strtolower($sSearch).'%').' OR lower(iso3) = '.$oDB->dbEscapeString(strtolower($sSearch)).' ORDER BY printable_name ';
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return json_encode(array());

    $asJsonData = array();
    while($bRead)
    {
      $asData['id'] = $oDbResult->getFieldValue('system_countrypk');
      $sPrintableName = $oDbResult->getFieldValue('printable_name');
      $sName = $oDbResult->getFieldValue('country_name');
      $sIso = $oDbResult->getFieldValue('iso3');

      if(!empty($sPrintableName))
        $asData['name'] = $sPrintableName;
      else
        $asData['name'] = $sName;

      if(!empty($sIso))
        $asData['name'].= ' - '.$sIso;

      $asJsonData[] = json_encode($asData);
      $bRead = $oDbResult->readNext();
    }
    echo '['.implode(',', $asJsonData).']';
  }

  /**
   * return the name of a city
   * @param integer $pnCityFk
   * @return string
   */
  public function getCityData($pnCityFk)
  {
    if(!assert('is_integer($pnCityFk) && !empty($pnCityFk)'))
      return array();

    if(!empty($this->casCity) && isset($this->casCity[$pnCityFk]))
      return $this->casCity[$pnCityFk];


    $oDB = CDependency::getComponentByName('database');
    $sQuery = 'SELECT * FROM system_city WHERE system_citypk = '.$pnCityFk;
    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
    {
      assert('false; //no city found');
      return array();
    }

    $nCityPk = $oDbResult->getFieldValue('system_citypk', CONST_PHP_VARTYPE_INT);
    $this->casCity[$nCityPk] = $oDbResult->getData();

    if(isset($this->casCity[$pnCityFk]))
      return $this->casCity[$pnCityFk];
    else
      return array();
  }

  public function addCitySelectorOption($psFieldName, $pnCityFk)
  {
    if(!assert('is_integer($pnCityFk) && !empty($pnCityFk)'))
      return false;

    if(!assert('!empty($psFieldName)'))
      return false;

    $asCityData = $this->getCityData($pnCityFk);

     $sFullname = ucfirst($asCityData['EngLocal']).' '.ucfirst($asCityData['EngCity']);
      $sFullname.= ' '.ucfirst($asCityData['EngStreet']);
      $asData['name'] = $sFullname.' - '.$asCityData['postcode'].' - '.$asCityData['KanjiCity'];

    if(empty($asCityData))
      $this->addOption($psFieldName, array('label' => ' unknown ', 'value' => 0));
    else
      $sLabel = $asData['name'];

    return $this->addOption($psFieldName, array('label' => $sLabel, 'value' => $pnCityFk));
  }


  private function _getSelectorCity()
  {
    $sSearch = getValue('q');
    if(empty($sSearch))
      return json_encode(array());

    $sCleanSearch = str_replace('-', '', $sSearch);
    $sCleanSearch = str_replace(' ', '', $sCleanSearch);
    $oDB = CDependency::getComponentByName('database');

    if(preg_match('/^[0-9]{1,9}$/', $sCleanSearch))
    {
      $sQuery = 'SELECT * FROM system_city WHERE postcode LIKE '.$oDB->dbEscapeString($sCleanSearch.'%').' ORDER BY postcode, EngLocal, EngCity LIMIT 200';
    }
    else
    {
      $sQuery = 'SELECT * FROM system_city WHERE EngLocal LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' OR EngCity LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' OR EngStreet LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' ';
      $sQuery.= ' OR KanaLocal LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' OR KanaCity LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' OR KanaStreet LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' ';
      $sQuery.= ' OR KanjiLocal LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' OR KanjiCity LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' OR KanjiStreet LIKE '.$oDB->dbEscapeString('%'.$sSearch.'%').' ';
      $sQuery.= ' ORDER BY postcode, EngLocal, EngCity LIMIT 200';
    }

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return json_encode(array());

    $asJsonData = array();
    while($bRead)
    {
      $asData['id'] = $oDbResult->getFieldValue('system_citypk');
      $sFullname = ucfirst($oDbResult->getFieldValue('EngLocal')).' '.ucfirst($oDbResult->getFieldValue('EngCity'));
      $sFullname.= ' '.ucfirst($oDbResult->getFieldValue('EngStreet'));


      $asData['name'] = $sFullname.' - '.$oDbResult->getFieldValue('postcode').' - '.$oDbResult->getFieldValue('KanjiCity');

      $asJsonData[] = json_encode($asData);
      $bRead = $oDbResult->readNext();
    }

    if(count($asJsonData) >= 200)
    {
      $asData['id'] = 0;
      $asData['name'] = 'More than 200 results...';
      $asJsonData[] = json_encode($asData);
    }
    echo '['.implode(',', $asJsonData).']';
  }


  private function _getCityForm()
  {
    $this->coHTML = CDependency::getCpHtml();
    $oPage = CDependency::getCpPage();

    $sURL = $oPage->getAjaxUrl('form', CONST_ACTION_SAVEADD, CONST_FORM_TYPE_CITY);

    $sHTML= $this->coHTML->getBlocStart();

      $sHTML.= $this->coHTML->getBlocStart('');

      $oForm = $this->coHTML->initForm('cityAddForm');
      $oForm->setFormParams('', true, array('action' => $sURL, 'inajax' =>1));
      $oForm->setFormDisplayParams(array('noCancelButton' => 1));

      $oForm->addField('misc', '', array('type' => 'text','text'=> '<br/><span class="h4">Add a new city</span><hr /><br />'));

      $oForm->addField('input', 'perfecture_name', array('label'=>'Perfecture'));
      $oForm->setFieldControl('perfecture_name', array('jsFieldMinSize' => '3', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

      $oForm->addField('input', 'name_city', array('label'=> 'City name'));
      $oForm->setFieldControl('name_city', array('jsFieldMinSize' => '3', 'jsFieldNotEmpty' => '', 'jsFieldMaxSize' => 255));

      $oForm->addField('input', 'sub_city', array('label'=> 'Sub-City Name'));
      $oForm->setFieldControl('sub_city', array('jsFieldMinSize' => '2', 'jsFieldMaxSize' => 255));

      $oForm->addField('input', 'postcode', array('label'=> 'Postcode'));
      $oForm->setFieldControl('postcode', array('jsFieldTypeIntegerPositive' => '', 'jsFieldMinValue' => 1000));

      $oForm->addField('selector_country', 'countrykey', array('label'=> 'Country', 'url' => CONST_FORM_SELECTOR_URL_COUNTRY));
      $oForm->setFieldControl('countrykey', array('jsFieldTypeIntegerPositive' => ''));

      $oForm->addField('misc', '', array('type'=> 'br'));
      $sHTML.= $oForm->getDisplay();
      $sHTML.= $this->coHTML->getBlocEnd();

    $sHTML.= $this->coHTML->getBlocEnd();

    return array('data' => $sHTML);
  }

  private function _getCitySave()
  {

    $sNameCity = getValue('name_city');
    if(empty($sNameCity))
      return array('alert' => 'City name is required.');

    $sPostcode = getValue('postcode');
    if(empty($sPostcode))
      return array('alert' => 'Postcode is required.');

    $sNamePerfecture = getValue('perfecture_name');
    $sNameSubCity = getValue('sub_city');
    $countryfk = (int)getValue('countrykey', 0);

    $oDB = CDependency::getComponentByName('database');

    $sQuery = 'INSERT INTO system_city (EngLocal, EngCity, EngStreet,postcode, countryfk) VALUES ';
    $sQuery.= '('.$oDB->dbEscapeString($sNamePerfecture).', '.$oDB->dbEscapeString($sNameCity).','.$oDB->dbEscapeString($sNameSubCity).', ';
    $sQuery.= $oDB->dbEscapeString($sPostcode).', '.$oDB->dbEscapeString($countryfk).') ';

    $oDbResult = $oDB->ExecuteQuery($sQuery);
    if(!$oDbResult)
      return array('error' => 'Could not add the city.['.$sQuery.']');

    return array('notice' => 'City added successfully', 'action' => 'goPopup.removeActive()');
  }

  private function _getCountryForm()
  {
    return array('data' => 'No need for now, got already most of them :)');
  }


  public function addCustomButton($psHTML)
  {
    if(!assert('!empty($psHTML)'))
      return false;

    $this->casCustomButton[] = $psHTML;
    return true;
  }


  public function addCustomFields($psUid, $psAction, $psType = '', $pnPk = 0, $psMode = '')
  {
    if(!assert('!empty($psUid) && !empty($psAction) && is_integer($pnPk)'))
      return array();

    $oCustomfield = CDependency::getComponentByName('customfields');
    $asCustomfield = $oCustomfield->getCustomfields($psUid, $psAction, $psType, $pnPk);

    if(empty($asCustomfield))
      return array();


    $sSectionId = uniqid('section_');
    switch($psMode)
    {
      case 'closed':
      case 'folded':
        $asSectionParam = array($psMode => 1);

        // 1st class is used in js to know which field should be activated/deactived on section toggle
        // second is used by fieldcontrol to apply controls
        $sClass = 'form_field_inactive field_inactive';
        $sDisabled = 'disabled';
        break;

      default:
        $asSectionParam = array();
        $sClass = '';
        $sDisabled = '';
    }


    $this->addSection($sSectionId, $asSectionParam, 'Custom fields <span>(keep closed to ignore fields)</span>');

    foreach($asCustomfield as $nPk => $asData)
    {
      if($asData['can_be_empty'])
        $asControl = array();
      else
        $asControl = array('jsFieldNotEmpty' => '');

      if(empty($asData['value']))
        $asData['value'] = $asData['defaultvalue'];

      $sFieldId = 'form_autogen_cf_'.$nPk;
      $asFieldParam = array('label' => $asData['label'], 'value' => $asData['value'], 'class' => $sClass, 'disabled' => $sDisabled);
      set_array($asData['option'], array());

      switch($asData['fieldtype'])
      {
        case 'int':
          $asControl['jsFieldTypeInteger'] = 1;
          $this->addField('input', $sFieldId, $asFieldParam);
          $this->setFieldControl($sFieldId, $asControl);
          break;

        case 'float':
          $asControl['jsFieldTypeFloat'] = 1;
          $this->addField('input', $sFieldId, $asFieldParam);
          $this->setFieldControl($sFieldId, $asControl);
          break;

        case 'email':
          $asControl['jsFieldTypeEmail'] = 1;
          $this->addField('input', $sFieldId, $asFieldParam);
          $this->setFieldControl($sFieldId, $asControl);
          break;

        case 'url':
          $asControl['jsFieldTypeUrl'] = 1;
          $this->addField('input', $sFieldId, $asFieldParam);
          $this->setFieldControl($sFieldId, $asControl);
          break;

        case 'textarea':
          $this->addField('textarea', $sFieldId, $asFieldParam);
          $this->setFieldControl($sFieldId, $asControl);
          break;

        case 'select':

          $this->addField($asData['fieldtype'], $sFieldId, $asFieldParam);
          $this->setFieldControl($sFieldId, $asControl);

          if($asData['can_be_empty'])
            $this->addOption($sFieldId, array('value' => '', 'label' => '- select -'));

          foreach($asData['option'] as $asOption)
          {
            if($asOption['value'] == $asFieldParam['value'])
              $this->addOption($sFieldId, array('value' => $asOption['value'], 'label' => $asOption['label'], 'selected' => 'selected'));
            else
              $this->addOption($sFieldId, array('value' => $asOption['value'], 'label' => $asOption['label']));
          }
          break;

        case 'checkbox':

          $asFieldParam['textbefore'] = 1;
          if($asOption['value'] == 'on')
            $asFieldParam['checked'] = 'checked';

          $this->addField('checkbox', $sFieldId, $asFieldParam);
          break;

        case 'radio':

          $asFieldParam['textbefore'] = 1;
          $asFieldParam['legend'] = $asFieldParam['label'];
          //$this->addField('radio', $sFieldId, $asFieldParam);

          foreach($asData['option'] as $asOption)
          {
            if($asOption['value'] == $asFieldParam['value'])
              $asFieldParam['checked'] = 'checked';
            else
              $asFieldParam['checked'] = '';

            $asFieldParam['label'] = $asOption['label'];
            $asFieldParam['value'] = $asOption['value'];
            $this->addField('radio', $sFieldId, $asFieldParam);
          }
          break;

        case 'date':
        case 'datetime':
        case 'time':
          $asControl['jsFieldTypeInteger'] = 1;
          $asFieldParam['type'] = $asData['fieldtype'];

          $this->addField('input', $sFieldId, $asFieldParam);
          $this->setFieldControl($sFieldId, $asControl);
          break;

        default:
          $this->addField('input', $sFieldId, $asFieldParam);
          $this->setFieldControl($sFieldId, $asControl);
          break;
      }

    }

    $this->closeSection();

    return $asCustomfield;
  }


  public function getStandaloneField($psFieldType, $psFieldname = '', $pasParameters = array())
  {
    if(empty($psFieldname))
      $psFieldname = uniqid();

    return $this->getField($psFieldType, $psFieldname, $pasParameters);
  }

}
