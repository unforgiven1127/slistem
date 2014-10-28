<?php
require_once('component/form/fields/field.class.php5');

class CStyledSelect extends CField
{
  private $casOptionData = array();
  private $_isMultiple = false;

  public function __construct($psFieldName, $pasFieldParams = array())
  {
    parent::__construct($psFieldName, $pasFieldParams);

    if(!isset($this->casFieldParams['class']))
      $this->casFieldParams['class'] = 'sSelect';
    else
      $this->casFieldParams['class'] .= ' sSelect';

    if(isset($this->casFieldParams['multiple']))
      $this->_isMultiple = true;
  }

  public function addOption($pasFieldParams)
  {
    if(!assert('is_array($pasFieldParams)'))
      return null;

    $this->casOptionData[] = $pasFieldParams;
    return $this;
  }

  public function getDisplay()
  {
    $sHTML = '';

    if(isset($this->casFieldParams['required']) && !empty($this->casFieldParams['required']))
      $this->casFieldContol['jsFieldNotEmpty'] = '';

    if(!empty($this->casFieldParams['label']) && $this->isVisible())
      $sHTML.= '<div class="formLabel">'.$this->casFieldParams['label'].'</div>';

    $sHTML.= '<div class="formField">';

      // *********************
      // 1. Setting UP Data
      // *********************

      // Setting options
      $asOptions = array(); $aValues = array();
      foreach($this->casOptionData as $nKey => $asOption)
      {
        $sLabel = (isset($asOption['label'])) ? $asOption['label'] : '';
        $sValue = $asOption['value'];
        $sGroupOption = (isset($asOption['group'])) ? $asOption['group'] : '';

        $sOptionHtml = '<li ';

        foreach($asOption as $sKey => $vValue)
        {
          if(($sKey!='label') && ($sKey!='group'))
            $sOptionHtml.= ' '.$sKey.'="'.$vValue.'" ';

          if($sKey=='selected')
            $aValues[$sValue]= array ('value' => $sValue, 'label' => $sLabel);
        }
        $sOptionHtml.= '>';

          $sOptionHtml.= $sLabel;

        $sOptionHtml.= '</li>';
        $asOptions[$sGroupOption][] =$sOptionHtml;
      }

      // Setting Title
      $sTitle = 'Select ...';
      if(!$this->_isMultiple && !empty($aValues))
      {
        $aSelected = current($aValues);
        $sTitle = $aSelected['label'];
      }

      // *********************
      // 2. Writing HTML
      // *********************

      if(empty($asOptions))
        $sHTML .= '<p class="noRecord"><i>No record found. Please create one.</i></p>';

      // Opening 'select'
      $sHTML.= '<div id="sSelect'.$this->csFieldName.'" inputname="'.$this->csFieldName.'" ';
      foreach($this->casFieldParams as $sKey => $vValue)
        $sHTML.= ' '.$sKey.'="'.$vValue.'" ';
      if(empty($asOptions))
        $sHTML .= ' style="display:none;" ';
      $sHTML.= '>';

        // 'select' label
        $sHTML .= '<a href=\'#\' class=\'sSelectTitle\'><span>'.$sTitle.'</span></a>';

        // 'select' options
        $sHTML.= '<div class=\'group\'>';
        $sDivContent = '';
        foreach($asOptions as $sGroup => $asOption)
        {
          if($sGroup!='')
            $sDivContent .= '<li class=\'sSelectGroupTitle\'>"'.$sGroup.'"</li>';
          $sDivContent.= implode('', $asOption);
        }
        $sDivContent = '<ul>'.$sDivContent.'</ul>';

        $sHTML .= $sDivContent.'</div>';

      $sSelecteds = implode(',', array_keys($aValues));

      // hidden input containing values
      $sHTML.= '<input name="'.$this->csFieldName.'" type="hidden" value="'.$sSelecteds.'" ';

      if(!empty($this->casFieldContol))
      {
        $sHTML.= ' jsControl="';
        foreach($this->casFieldContol as $sKey => $vValue)
          $sHTML.= $sKey.'@'.$vValue.'|';

        $sHTML.= '" ';

        if(isset($this->casFieldContol[$this->csFieldRequired]))
        {
          set_array($this->casFieldParams['class'], 'formFieldRequired', ' formFieldRequired');
          set_array($this->casFieldParams['title'], ' Field required', ' Field required');
        }
      }
      $sHTML .= ' />';

      $sHTML.= '</div>';

      // if mutiple, displaying selected values
      if($this->_isMultiple)
      {
        $sHTML .= '<div class=\'selectedValues\'>';
          $sHTML .= '<ul rel=\'sSelect'.$this->csFieldName.'\'>';

          if(!empty($aValues))
          {
            foreach ($aValues as $sValue => $aOption)
              $sHTML .= '<li value=\''.$sValue.'\'>'.$aOption['label'].'<span>X</span></li>';
          }

          $sHTML .= '</ul>';
        $sHTML .= '</div>';
      }

      $sHTML.= '</div>';

    return $sHTML;
  }

}
