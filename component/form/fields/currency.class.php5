<?php

require_once('component/form/fields/field.class.php5');

class CCurrency extends CField
{

  public function __construct($psFieldName, $pasFieldParams = array())
  {
    parent::__construct($psFieldName, $pasFieldParams);
  }

  public function isVisible()
  {
    if(isset($this->casFieldParams['class']) && $this->casFieldParams['class'] == 'hidden')
      return false;

    return true;
  }

  public function getDisplay()
  {
    //--------------------------------
    //fetching field parameters

    //display option
    set_array($this->casFieldParams['with_unit'], '');
    set_array($this->casFieldParams['with_currency'], '');
    $bDisplayUnit = $bDisplayCurrency = false;

    if(!empty($this->casFieldParams['with_unit']))
      $bDisplayUnit = true;

    if(!empty($this->casFieldParams['with_currency']))
      $bDisplayCurrency = true;

    if($bDisplayCurrency && (!isset($this->casFieldParams['currency_list']) || empty($this->casFieldParams['currency_list'])))
    {
      assert('false; // no currency data provided.');
      return '';
    }


    set_array($this->casFieldParams['currency_unit'], 0);
    set_array($this->casFieldParams['default_currency'], 'jpy');
    set_array($this->casFieldParams['default_unit'], 0);
    set_array($this->casFieldParams['linked_to'], '');


    $this->casFieldParams['default_currency'] = strtolower($this->casFieldParams['default_currency']);
    $this->casFieldParams['default_unit'] = strtolower($this->casFieldParams['default_unit']);


    if(!isset($this->casFieldParams['id']))
      $this->casFieldParams['id'] = uniqid('fldid_');

    if(isset($this->casFieldParams['required']) && !empty($this->casFieldParams['required']))
      $this->casFieldContol['jsFieldNotEmpty'] = '';

    if(isset($this->casFieldParams['label']))
    {
      $sLabel = $this->casFieldParams['label'];
      unset($this->casFieldParams['label']);
    }
    else
      $sLabel = '';

    set_array($this->casFieldParams['class'], '');
    $this->casFieldParams['class'].= 'currency_field';


    //--------------------------------
    $sHTML = '';

    if(!empty($sLabel) && $this->isVisible())
      $sHTML.= '<div class="formLabel">'.$sLabel.'</div>';

     $sHTML.= '<div class="formField"><input type="input" name="'.$this->csFieldName.'" ';

    if(!empty($this->casFieldContol))
    {
      $sHTML.= ' jsControl="';
      foreach($this->casFieldContol as $sKey => $vValue)
      {
        $sHTML.= $sKey.'@'.$vValue.'|';
      }

      $sHTML.= '" ';

      if(isset($this->casFieldContol[$this->csFieldRequired]))
      {
        set_array($this->casFieldParams['class'], ' formFieldRequired', ' formFieldRequired');
        set_array($this->casFieldParams['title'], ' Field required', ' Field required');
      }
    }

    foreach($this->casFieldParams as $sKey => $vValue)
    {
      if(!is_array($vValue))
        $sHTML.= ' '.$sKey.'="'.$vValue.'" ';
    }

    $sHTML.= ' />';



    if(!empty($this->casFieldParams['linked_to']))
    {
      if($bDisplayUnit || $bDisplayUnit)
      {
        $sHTML.= '
          <script>
          linkCurrencyFields("'.$this->casFieldParams['id'].'", "'.$this->csFieldName.'", "'.$this->casFieldParams['linked_to'].'");
          </script>';
      }

      if($bDisplayUnit)
        $sHTML.= '<input id="'.$this->csFieldName.'_unitId" name="'.$this->csFieldName.'_unit" class="currency_unit currency_locked" value="" readonly="readonly" /> ';

      if($bDisplayCurrency)
        $sHTML.= '<input id="'.$this->csFieldName.'_currencyId" name="'.$this->csFieldName.'_currency" class="currency_money currency_locked" value="" readonly="readonly" /> ';
    }
    else
    {
      //add the currency converter
      if($bDisplayUnit)
        $sHTML.= '<select id="'.$this->csFieldName.'_unitId" name="'.$this->csFieldName.'_unit" class="currency_unit currency_spinner_field">
          <option value=""></option>
          <option value="K" '.(($this->casFieldParams['default_unit'] == 'k')? 'selected="selected"' : '').'>K</option>
          <option value="M" '.(($this->casFieldParams['default_unit'] == 'm')? 'selected="selected"' : '').'>M</option>
          </select>';

      if($bDisplayCurrency)
      {
        $sHTML.= '<select id="'.$this->csFieldName.'_currencyId" name="'.$this->csFieldName.'_currency" class="currency_money currency_spinner_field">';

        foreach($this->casFieldParams['currency_list'] as $sCode => $sRate)
        {
          if($this->casFieldParams['default_currency'] == $sCode)
            $sHTML.= '<option value="'.$sCode.'" title="Rate: 1'.$sCode.' = '.(1/$sRate).'&yen;" selected="selected">'.$sCode.'</option>';
          else
            $sHTML.= '<option value="'.$sCode.'" title="Rate: 1'.$sCode.' = '.(1/$sRate).'&yen; ">'.$sCode.'</option>';
        }

        $sHTML.= '</select>';
      }
    }

    $oPage = CDependency::getCpPage();
    $oPage->addJsFile('/component/form/resources/js/currency.js');
    $sHTML.= '<script>

      $("#'.$this->casFieldParams['id'].'").blur(function()
      {
        sValue = format_currency($(this).val());
        $(this).val(sValue);
      });

    </script>';

    $sHTML.= '</div>';
    return $sHTML;
  }


  public function getCurrencyFromPost($psFieldName, $psDecimal = '.')
  {
    $sValue = trim(getValue($psFieldName, 0));
    $sUnit = getValue($psFieldName.'_unit', '');
    $sCurrency = getValue($psFieldName.'_currency', 'JPY');

    if(empty($sValue))
      return array('raw_value' => 0, 'value' => 0, 'unit' => $sUnit, 'currency' => $sCurrency);

    if($psDecimal == '.')
      $psRegDecimal = '\.';
    else
      $psRegDecimal = ',';

    //remove everything not necessary and cast in float
    $psRegDecimal = str_replace('.', '\.', $psRegDecimal);
    $fValue = (float)preg_replace('/[^0-9'.$psRegDecimal.']/i', '', $sValue);

    switch($sUnit)
    {
      case 'K':
        return array('raw_value' => $sValue, 'value' => ($fValue*1000), 'unit' => $sUnit, 'currency' => $sCurrency);
        break;

      case 'M':
        return array('raw_value' => $sValue, 'value' => ($fValue*1000000), 'unit' => $sUnit, 'currency' => $sCurrency);
        break;
    }

    return array('raw_value' => $sValue, 'value' => $fValue, 'unit' => $sUnit, 'currency' => $sCurrency);
  }
}