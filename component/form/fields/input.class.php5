<?php

require_once('component/form/fields/field.class.php5');

class CInput extends CField
{

  public function __construct($psFieldName, $pasFieldParams = array())
  {
    parent::__construct($psFieldName, $pasFieldParams);
  }

  public function isVisible()
  {
    if(isset($this->casFieldParams['type']) && $this->casFieldParams['type'] == 'hidden')
      return false;

    return true;
  }

  public function getDisplay()
  {
    //--------------------------------
    //fetching field parameters

    $bSpinner = $bDatePicker = $bTimePicker = $bMonthPicker = false;
    $asPreviousFile = array();

    if(!isset($this->casFieldParams['id']))
      $this->casFieldParams['id'] = uniqid('fldid_');


    if(isset($this->casFieldParams['legend']))
    {
      $sLegend = $this->casFieldParams['legend'];
      unset($this->casFieldParams['legend']);
    }
    else
      $sLegend = '';

    //------------------------
    //add JScontrol classes
    if(isset($this->casFieldParams['required']) && !empty($this->casFieldParams['required']))
      $this->casFieldContol['jsFieldNotEmpty'] = '';

    if(isset($this->casFieldParams['type']))
    {
      $sFieldType = $this->casFieldParams['type'];
      unset($this->casFieldParams['type']);

      switch($sFieldType)
      {
        case 'date':
          $sFieldType = 'text';
          $bDatePicker = true;
          $oPage = CDependency::getCpPage();
          $oPage->addJsFile(CONST_PATH_JS_JQUERYUI);
          $oPage->addCSSFile(CONST_PATH_CSS_JQUERYUI);
        break;

        case 'time':
          $sFieldType = 'text';
          $bTimePicker = true;
          $oPage = CDependency::getCpPage();
          $oPage->addJsFile(CONST_PATH_JS_JQUERYUI);
          $oPage->addJsFile(CONST_PATH_JS_TIMEPICKER);
          $oPage->addCSSFile(CONST_PATH_CSS_JQUERYUI);
          $oPage->addCSSFile(CONST_PATH_CSS_TIMEPICKER);
       break;

        case 'datetime':
          $sFieldType = 'text';
          $bDatePicker = true;
          $bTimePicker = true;
          $oPage = CDependency::getCpPage();
          $oPage->addJsFile(CONST_PATH_JS_JQUERYUI);
          $oPage->addJsFile(CONST_PATH_JS_TIMEPICKER);
          $oPage->addCSSFile(CONST_PATH_CSS_JQUERYUI);
          $oPage->addCSSFile(CONST_PATH_CSS_TIMEPICKER);
        break;

        case 'month':
          $bMonthPicker = true;
          $sFieldType = 'text';
          $oPage = CDependency::getCpPage();
          $oPage->addJsFile($this->getResourcesPath().'js/jquery.mtz.monthpicker.js');
          break;

        case 'spinner':
          $sFieldType = 'text';
          $bSpinner = true;
          $oPage = CDependency::getComponentByName('page');
          $oPage->addJsFile(CONST_PATH_JS_JQUERYUI);
          $oPage->addCSSFile(CONST_PATH_CSS_JQUERYUI);
        break;

        case 'file':

            if(!isset($this->casFieldParams['maxfilesize']))
              $this->casFieldParams['maxfilesize'] = CONST_SS_MAX_DOCUMENT_SIZE;

            ini_set('upload_tmp_dir', CONST_PATH_UPLOAD_DIR);

            if(isset($this->casFieldParams['value']) && !empty($this->casFieldParams['value']))
            {
              $asPreviousFile = (array)$this->casFieldParams['value'];
              unset($this->casFieldParams['value']);
            }
          break;
        }
    }
    else
      $sFieldType = 'text';

    if(isset($this->casFieldParams['label']))
    {
      $sLabel = $this->casFieldParams['label'];
      unset($this->casFieldParams['label']);
    }
    else
      $sLabel = '';


    //inititlaized the datepicker with 2 months
    if(($bDatePicker || $bTimePicker ) && !isset($this->casFieldParams['monthNum']))
    {
      $this->casFieldParams['monthNum'] = 2;
    }

    //--------------------------------

    $sHTML = '';

    if(!empty($sLabel) && $this->isVisible())
      $sHTML.= '<div class="formLabel">'.$sLabel.'</div>';

     $sHTML.= '<div class="formField"><input type="'.$sFieldType.'" name="'.$this->csFieldName.'" ';


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
      $sHTML.= ' '.$sKey.'="'.$vValue.'" ';

    $sHTML.= ' />' . $sLegend ;

    $nDefaultHour = (int)date('H');
    $nMinute = (int)date('i');
    $nDefaultMinute = ($nMinute - ($nMinute % 5));


    //-----------------------------------------------------
    if(isset($this->casFieldParams['range']) && !empty($this->casFieldParams['range']))
    {
      $sRangeInit = '

        if(!datepicker_range_init)
        {
          var datepicker_range_init = true;
          var datepicker__updateDatepicker = $.datepicker._updateDatepicker;
          $.datepicker._updateDatepicker = function(inst)
          {
             console.log(\'_updateDatepicker\');
             datepicker__updateDatepicker.call(this, inst);

             var onAfterUpdate = this._get(inst, \'onAfterUpdate\');
             if(onAfterUpdate)
               onAfterUpdate.apply((inst.input ? inst.input[0] : null), [(inst.input ? inst.input.val() : \'\'), inst]);
          }
        }

        //define global variables
        var cur = -1, prv = -1;


        //load values in the calendar
        $(\'#'.$this->casFieldParams['id'].'\').on(\'focus\', function(e)
        {
          //the datepicker is open, no need to refresh the prev / cur
          if($(this).data(\'datepicker\').inline)
            return true;

          var d, v = $(this).val();
          try
          {
            //loads the dats from the field
            if(v.indexOf(\' to \') > -1)
            {
               d = v.split(\' to \');
               prv = $.datepicker.parseDate(\'yy-mm-dd\', d[0]).getTime();
               cur = $.datepicker.parseDate(\'yy-mm-dd\', d[1]).getTime();
            }
            else if(v.length > 0)
            {
              prv = cur = $.datepicker.parseDate( \'yy-mm-dd\', v).getTime();
            }
          }
          catch(e)
          {
            console.log(\' parse date error !!!!\');
            cur = prv = -1;
          }

          if(cur > -1)
            $(\'#'.$this->casFieldParams['id'].' div\').datepicker(\'setDate\', new Date(cur));

          $(\'#'.$this->casFieldParams['id'].' div\').datepicker(\'refresh\').show();
       });
        ';

      $sRangeJs = ',
        beforeShowDay: function(date)
        {
          //console.log(\'beforeShowDay for : \'+(date.getTime() /10000)+\' in (\'+ (Math.min(prv, cur) /10000)+\'  || \'+ (Math.max(prv, cur) /10000)+\')\');
          var nTime = date.getTime();
          if(nTime >= Math.min(prv, cur) && nTime <= Math.max(prv, cur))
          {
            return [true, \'date-range-selected\'];
          }

          return [true, \'\'];
        },
        onSelect: function(dateText, inst)
        {
          /*console.log(\'onSelect\');
          console.log(prv);
          console.log(cur);*/

          if(dateText)
            $(this).data(\'datepicker\').inline = true;

          prv = cur;
          cur = (new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay)).getTime();
          if(prv == -1 || prv == cur)
          {
             prv = cur;
             $(\'#'.$this->casFieldParams['id'].'\').val(dateText);
             //console.log(\'writting: \'+dateText);
          }
          else
          {
            var d1, d2;
            d1 = $.datepicker.formatDate( \'yy-mm-dd\', new Date(Math.min(prv, cur)), {} );
            d2 = $.datepicker.formatDate( \'yy-mm-dd\', new Date(Math.max(prv, cur)), {} );

            $(\'#'.$this->casFieldParams['id'].'\').val( d1+\' to \'+d2 );
            //console.log(\'writting: \'+ d1+\' to \'+d2);
          }
        },
        onClose: function()
        {
          $(this).data(\'datepicker\').inline = false;
        },
        onAfterUpdate: function(inst)
        {
          //add a done button to close the Datepicker inline mode
          if($(this).data(\'datepicker\').inline)
          {
            $(\'<button type="button" class="ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all" data-handler="hide" data-event="click">Done</button>\')
               .appendTo($(\'#ui-datepicker-div .ui-datepicker-buttonpane\'))
               .on(\'click\', function() { $(this).closest(\'.ui-datepicker\').hide(); });
          }
        }
        ';
    }
    else
      $sRangeJs = $sRangeInit = '';

    $sDateSetting = '';
    if(isset($this->casFieldParams['yearRange']))
      $sDateSetting.= ', yearRange: \''.$this->casFieldParams['yearRange'].'\'';

    if(isset($this->casFieldParams['minDate']))
      $sDateSetting.= ', minDate: \''.$this->casFieldParams['minDate'].'\'';

    if(isset($this->casFieldParams['maxDate']))
      $sDateSetting.= ', maxDate: \''.$this->casFieldParams['maxDate'].'\'';

    if(isset($this->casFieldParams['defaultDate']))
      $sDateSetting.= ', defaultDate: \''.$this->casFieldParams['defaultDate'].'\'';

    if($bDatePicker && $bTimePicker)
    {
      $sHTML.= '<img src="'.$this->getResourcesPath().'pictures/date-icon.png" onclick="$(\'#'.$this->casFieldParams['id'].'\').focus(); " width="16" height="16" />';

      $sHTML.= '<script> '.$sRangeInit;
      $sHTML.= '$(function() { $("#'.$this->casFieldParams['id'].'").datetimepicker({  numberOfMonths:'.$this->casFieldParams['monthNum'].' , showButtonPanel: true, changeYear: true, dateFormat: \'yy-mm-dd\', hourGrid: 4, minuteGrid: 10, stepMinute: 5, hour: '.$nDefaultHour.',	minute: '.$nDefaultMinute.' '.$sRangeJs.' '.$sDateSetting.'});  });';
      $sHTML.= '</script>';
    }
    elseif($bDatePicker)
    {
      $sHTML.= '<img src="'.$this->getResourcesPath().'pictures/date-icon.png" onclick="$(\'#'.$this->casFieldParams['id'].'\').focus(); " />';

      $sHTML.= '<script> '.$sRangeInit;
      $sHTML.= '$(function() { $("#'.$this->casFieldParams['id'].'").datepicker({  numberOfMonths: '.$this->casFieldParams['monthNum'].', showButtonPanel: true, changeYear: true, dateFormat: \'yy-mm-dd\' '.$sRangeJs.' '.$sDateSetting.'});  });';
      $sHTML.= '</script>';
    }
    elseif($bTimePicker)
    {
      $sHTML.= '<img src="'.$this->getResourcesPath().'pictures/date-icon.png" onclick="$(\'#'.$this->casFieldParams['id'].'\').focus(); " />';

      $sHTML.= '<script> ';
      $sHTML.= '$(function() { $("#'.$this->casFieldParams['id'].'").timepicker({stepMinute: 5, hour: '.$nDefaultHour.',	minute: '.$nDefaultMinute.' '.$sDateSetting.'});  });';
      $sHTML.= '</script>';
    }
    elseif($bMonthPicker)
    {
      $sHTML.= '<img src="'.$this->getResourcesPath().'pictures/date-icon.png" onclick="$(\'#'.$this->casFieldParams['id'].'\').focus(); " />';
      $sHTML.= '<script>';
      $sHTML.= 'options = {';
      $sHTML.= '    pattern: \'yyyy-mm\',';
      $sHTML.= '    monthNames: [\'Jan\', \'Feb\', \'Mar\', \'Apr\', \'May\', \'Jun\', \'Jul\', \'Aug\', \'Sep\', \'Oct\', \'Nov\', \'Dec\']';
      $sHTML.= '};';
      $sHTML.= '$(\'#'.$this->casFieldParams['id'].'\').monthpicker(options);';
      $sHTML.= '</script>';
    }
    elseif($bSpinner)
    {
      $sHTML.= '<script>';
      $sHTML.= '$(\'#'.$this->casFieldParams['id'].'\').spinner();';

      if(isset($this->casFieldParams['valMax']))
        $sHTML .= '$(\'#'.$this->casFieldParams['id'].'\').spinner( "option", "max", '.$this->casFieldParams['valMax'].');';

      if(isset($this->casFieldParams['valMin']))
        $sHTML .= '$(\'#'.$this->casFieldParams['id'].'\').spinner( "option", "min", '.$this->casFieldParams['valMin'].');';

      $sHTML.= '</script>';
    }

    if(!empty($asPreviousFile))
    {
      $sHTML.= '<div class="file_history">';
      $sHTML.= '<ul>';

      foreach($asPreviousFile as $sFileName)
      {
        $sHTML.= '</li><a href="javascript:;" onclick="alert(\'Sorry, not yet available. Please use the list page action.\');" >'.$sFileName.' <img src="'.CONST_PICTURE_DELETE.'" /></a></li>';
      }
      $sHTML.= '</ul>';
      $sHTML.= '</div>';
    }

    if($sFieldType == 'file')
      $sHTML.= '<input type="hidden" id="MAX_FILE_SIZE" name="MAX_FILE_SIZE" value="'.$this->casFieldParams['maxfilesize'].'" />';

    $sHTML.= '</div>';

    return $sHTML;
  }
}