<?php
require_once('component/form/fields/field.class.php5');

class CCheckbox extends CField
{
  private $casOptionData = array();

  public function __construct($psFieldName, $pasFieldParams = array())
  {
    parent::__construct($psFieldName, $pasFieldParams);

    $this->casOptionData[] = $pasFieldParams;
  }

  public function addOption($pasFieldParams)
  {
    $this->casOptionData[] = $pasFieldParams;
    return $this;
  }

  public function isVisible()
  {
    if(isset($this->casFieldParams['type']) && $this->casFieldParams['type'] == 'hidden')
      return false;

    return true;
  }


  public function getDisplay()
  {
    //$sHTML = '<div class="formField">';
    $sHTML = '';

    foreach($this->casOptionData as $nKey => $asOption)
    {
      if(isset($asOption['label']))
      {
        $sLabel = $asOption['label'];
        unset($asOption['label']);
      }
      else
        $sLabel = '';

      if(isset($asOption['legend']))
      {
        $sLegend = $asOption['legend'];
        unset($asOption['legend']);
      }
      else
        $sLegend = '';

      if(isset($asOption['id']))
      {
        $sId = $asOption['id'];
        unset($asOption['id']);
      }
      else
        $sId = $this->csFieldName.'_'.$nKey.'_'.'Id';

      set_array($asOption['textbefore'], 0);

      //manage text/box display option
      if(!isset($asOption['display_type']) || !in_array($asOption['display_type'], array('split', 'linked', 'full')))
      {
        $sType = 'linked';
      }
      else
        $sType = $asOption['display_type'];

      if(!$this->isVisible())
        $sClass = ' hidden ';
      else
        $sClass = '';


      $asOption['onfocus'] = '$(this).parent().find(\'.css-label\').addClass(\'css-label-active\');';
      $asOption['onblur'] = '$(this).parent().find(\'.css-label\').removeClass(\'css-label-active\');';

      $sBox = '<input type="checkbox" class="css-checkbox" name="'.$this->csFieldName.'" id="'.$sId.'"';

      foreach($asOption as $sKey => $vValue)
      {
        $sBox.= ' '.$sKey.'="'.$vValue.'" ';
      }

      if(isset($this->casFieldParams['value']))
      {
        $sBox.= ' value=\''.$this->casFieldParams['value'].'\'';
      }

      $sBox.= '/>';


      //display text and label in formLabel or formValue
      switch($sType)
      {
        case 'split':
        {
          if($asOption['textbefore'])
          {
            $sHTML.=' <div class="formLabel '.$sClass.'">
              <label for="'.$sId.'" class="form-label">'.$sLabel.'</label>
            </div>
            <div class="formField">'.$sBox.'<label for="'.$sId.'" class="css-label" >&nbsp;</label>';

            if(!empty($sLegend))
            {
              $sHTML.='<label for="'.$sId.'">'.$sLegend.'</label>';
            }

            $sHTML.= '&nbsp;</div>';
          }
          else
          {
            $sHTML.= '<div class="formLabel '.$sClass.'">'.$sBox.'<label for="'.$sId.'" class="css-label" >&nbsp;</label></div>';
            $sHTML.= '<div class="formField '.$sClass.'">

              <label for="'.$sId.'" class="form-label">'.$sLabel.'</label>';

            if(!empty($sLegend))
              $sHTML.='<label for="'.$sId.'" class="css-label" >'.$sLegend.'</label>';

            $sHTML.= '&nbsp;</div>';
          }
        }
        break;

        case 'full':
        {
          $sHTML.=' <div class="formFull '.$sClass.'">';

          if($asOption['textbefore'])
          {
            $sHTML.= '<label for="'.$sId.'" class="form-label">'.$sLabel.'</label> ' .$sBox.'<label for="'.$sId.'" class="css-label" >&nbsp;</label> '.$sLegend;
          }
          else
            $sHTML.= $sLegend.' '.$sBox.'<label for="'.$sId.'" class="css-label" >&nbsp;</label>
              <label for="'.$sId.'" class="form-label">'.$sLabel.'</label>';

          $sHTML.= '</div>';
        }
        break;

        case 'linked':
        default:
        {
          $sHTML.=' <div class="formLabel '.$sClass.'">'.$sLegend.'</div>';
          $sHTML.= '<div class="formField '.$sClass.'">';

          if($asOption['textbefore'])
            $sHTML.= '<label for="'.$sId.'">'.$sLabel.'</label>
              '.$sBox.'<label for="'.$sId.'" class="css-label" >&nbsp;</label>';
          else
            $sHTML.= $sBox.'<label for="'.$sId.'" class="css-label" >&nbsp;</label>
              <label for="'.$sId.'" class="form-label">'.$sLabel.'</label>';

          $sHTML.= '</div>';
        }
        break;
      }
    }

    //$sHTML.= '</div>';

    return $sHTML;
  }

}