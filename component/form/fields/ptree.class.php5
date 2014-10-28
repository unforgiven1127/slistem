<?php

require_once('component/form/fields/field.class.php5');

class CPtree extends CField
{
  private $casOption = array();
  private $csFieldId = '';
  private $csContainerId = '';
  private $csOnclick = '';
  private $bCanSelectCategory = false;
  private $cvDefaultValue = 0;


  public function __construct($psFieldName, $pasFieldParams = array())
  {
    parent::__construct($psFieldName, $pasFieldParams);
  }

  public function addOption($pasOption)
  {
    $this->casOption = $pasOption;
    return $this;
  }


  public function getDisplay()
  {
    if(isset($this->casFieldParams['id']))
    {
      $this->csFieldId = $this->casFieldParams['id'];
      unset($this->casFieldParams['id']);
    }

    if(empty($this->csFieldId))
      $this->csFieldId = uniqid('id_');

    //needed for hidden container
    $this->csDummyFieldId = uniqid('id_');


    if(isset($this->casFieldParams['option']) && !empty($this->casFieldParams['option']))
      $this->casOption = $this->casFieldParams['option'];

    if(empty($this->casOption))
      return ' - empty tree - ';

    if(isset($this->casFieldParams['onclick']) && !empty($this->casFieldParams['onclick']))
    {
      $this->csOnclick = $this->casFieldParams['onclick'];
      unset($this->casFieldParams['onclick']);
    }

    if(isset($this->casFieldParams['text']) && !empty($this->casFieldParams['text']))
    {
      $this->sMainLabel = $this->casFieldParams['text'];
      unset($this->casFieldParams['text']);
    }

    if(empty($this->sMainLabel))
      $this->sMainLabel = ' Select ';

    if(isset($this->casFieldParams['categorySelectable']) && !empty($this->casFieldParams['categorySelectable']))
    {
      $this->bCanSelectCategory = true;
      unset($this->casFieldParams['categorySelectable']);
    }

    if(isset($this->casFieldParams['value']) && !empty($this->casFieldParams['value']))
    {
      $this->cvDefaultValue = $this->casFieldParams['value'];
      unset($this->casFieldParams['value']);
    }

    if(isset($this->casFieldParams['label']))
    {
      $sFieldLabel = $this->casFieldParams['label'];
      unset($this->casFieldParams['label']);
    }
    else
      $sFieldLabel = '&nbsp;';



    $oPage = CDependency::getCpPage();
    $oPage->addJsFile('/component/form/resources/js/ptree.js');
    $oPage->addCssFile('/component/form/resources/css/ptree.css');

    $this->csContainerId = 'cont'.$this->csDummyFieldId;

    //display hidden divs containing all the options
    $sTree = '<div id="'.$this->csContainerId.'" class="TSelect_mainContainer" >';
    $sTree.= '<span class="TSelect_close" onclick="jQuery(\'#'.$this->csContainerId.'\').fadeOut(\'fast\');">X Close</span>';
    $sTree.= '<input type="hidden" id="'.$this->csFieldId.'" name="'.$this->csFieldName.'" value="'.$this->cvDefaultValue.'" />';
    $sTree.= '<input type="hidden" name="'.$this->csFieldId.'_lvl_0" value="" />';
    $sTree.= '<input type="hidden" name="'.$this->csFieldId.'_lvl_1" value="" />';
    $sTree.= '<input type="hidden" name="'.$this->csFieldId.'_lvl_2" value="" />';

    $nLevl0 = 0;
    $nLevl1 = 0;
    $nLevl2 = 0;
    $anCounter = array();
    $sLastSelected = '';
    //dump($this->casFieldValues);

    foreach($this->casOption as $avValue)
    {
      if(!isset($avValue['level']) || empty($avValue['level']))
      {
        $nLevl0++;
      }
      else
      {
        if(!isset($avValue['parent']))
          assert('false; /* missing parent attribute parent ['. var_export($avValue, true).'] */ ');


        if((int)$avValue['level'] == 1)
        {
          $nLevl1++;
        }
        else
          $nLevl2++;

        if(!isset($anCounter[(int)$avValue['parent']]))
          $anCounter[(int)$avValue['parent']] = 1;
        else
          $anCounter[(int)$avValue['parent']] = $anCounter[(int)$avValue['parent']] +1;
      }
    }

    //dump($anCounter);
    $asValues = $this->casOption;

      //-------------------------------------
      //root level of the tree
      $sTree.= '<div class="TSelectLevel TSelect_lvl_0" level="0" >';
      $sTree.= '<ul>';

      foreach($asValues as $nKey => $avValue)
      {
        if(!isset($avValue['level']) || empty($avValue['level']))
        {
          //remove empty categories if i can select those
          if($this->bCanSelectCategory || isset($anCounter[(int)$avValue['value']]))
          {
            $sTree.= '<li ';
            $sLabel = $avValue['label'];
            unset($avValue['label']);

            //if the option has to be selected or match the default value, we select it
            if( (isset($avValue['selected']) && !empty($avValue['selected']))
              ||(isset($avValue['value']) && $avValue['value'] == $this->cvDefaultValue) )
            {
              $sLastSelected = $sLabel;
              $sTree.= ' class="selected" ';
            }


            foreach($avValue as $sParam => $vValue)
                $sTree.= ' '.$sParam.'="'.$vValue.'" ';

            if(isset($anCounter[(int)$avValue['value']]))
              $sTree.= '>'.$sLabel.' ('.$anCounter[(int)$avValue['value']].') <img src="/component/form/resources/pictures/category.png" style="float: right; margin-right: 10px;" /></li> ';
            else
            {
              if($this->bCanSelectCategory)
                $sTree.= ' class="final">'.$sLabel.'</li> ';
              else
                $sTree.= '>'.$sLabel.'</li> ';
            }
          }

          unset($asValues[$nKey]);
        }
      }

      $sTree.= '</ul>';
      $sTree.= '<div class="floatHack"></div>';
      $sTree.= '</div>';

      if(empty($nLevl0))
        return ' no values ';

      if(empty($nLevl1) && !empty($nLevl2))
      {
        assert('false; // 2nd or more level options, but no level 1');
        return ' error ';
      }


      //-------------------------------------
      //second level of the tree (same as first)
      $sTree.= '<div class="TSelectLevel TSelect_lvl_1" level="1" >';
      $asUL = array();

        foreach($asValues as $nKey => $avValue)
        {
          if((int)$avValue['level'] === 1 && !empty($avValue['value']))
          {
            $sLI = '<li ';
            $sLabel = $avValue['label'];
            unset($avValue['label']);

            //if the option has to be selected or match the default value, we select it
            if( (isset($avValue['selected']) && !empty($avValue['selected']))
              ||(isset($avValue['value']) && $avValue['value'] == $this->cvDefaultValue) )
            {
              $sLastSelected = $sLabel;
              $sLI.= ' class="selected" ';
            }

            foreach($avValue as $sParam => $vValue)
               $sLI.= ' '.$sParam.'="'.$vValue.'" ';

            if(isset($anCounter[$avValue['value']]))
              $sLI.= '>'.$sLabel.' ('.$anCounter[$avValue['value']].') <img src="/component/form/resources/pictures/category.png" style="float: right;" /></li> ';
            else
              $sLI.= ' class="final" >'.$sLabel.'</li> ';

            $asUL[$avValue['parent']][] = $sLI;
            unset($asValues[$nKey]);
          }
        }

        foreach($asUL as $sKey => $asLI)
        {
          $sTree.= '<ul parent="'.$sKey.'">'.implode(' ', $asLI).'</ul>';
        }
      $sTree.= '<div class="floatHack"></div>';
      $sTree.= '</div>';


      //-------------------------------------
      //third level of the tree (display all the sub levels here)
      $sTree.= '<div class="TSelectLevel TSelect_lvl_2" level="2" >';
      $asUL = array();

      foreach($this->casOption as $avValue)
      {
        if((int)$avValue['level'] > 1)
        {
          $sLI = '<li style="margin-left:'.(((int)$avValue['level']-1) * 5).'px;"';
          $sLabel = $avValue['label'];
          unset($avValue['label']);

          //if the option has to be selected or match the default value, we select it
            if( (isset($avValue['selected']) && !empty($avValue['selected']))
              ||(isset($avValue['value']) && $avValue['value'] == $this->cvDefaultValue) )
            {
              $sLastSelected = $sLabel;
              $sLI.= ' class="selected" ';
            }

          foreach($avValue as $sParam => $vValue)
              $sLI.= ' '.$sParam.'="'.$vValue.'" ';

          $sLI.= ' onclick="return saveTreeValue(this, \''.$this->csDummyFieldId.'\', \''.$this->csFieldId.'\', \''.$this->csContainerId.'\'); " >'.$sLabel.'</li> ';

          $asUL[$avValue['parent']][] = $sLI;
        }
      }

      foreach($asUL as $sKey => $asLI)
      {
        $sTree.= '<ul parent="'.$sKey.'">'.implode(' ', $asLI).'</ul>';
      }
      $sTree.= '<div class="floatHack"></div>';
      $sTree.= '</div>';

    $sTree.= '<div class="floatHack"></div>';
    $sTree.= '</div>';

    $sTree.= '<div class="floatHack"></div>';


    //we create now the form buttons and the javascript to display the tree
    //display the main button first

    if(empty($sLastSelected))
      $sLastSelected = $this->sMainLabel;

    $sHtml = '<input type="text" readonly="readonly" class="TSelect_mainBtn" id="'.$this->csDummyFieldId.'" value="'.$sLastSelected.'"';

    foreach($this->casFieldParams as $sFieldName => $vValue)
      $sHtml.= ' '.$sFieldName.'="'.$vValue.'" ';


    $sHtml.= 'is_clicked="0"
      onmousedown=" jQuery(this).attr(\'is_clicked\', 1); "

      onclick="jQuery(this).attr(\'is_clicked\', 0);
      paneControl(\'#'.$this->csContainerId.'\', \'#'.$this->csDummyFieldId.'\');
      '.$this->csOnclick.' "

      onfocus="if(jQuery(this).attr(\'is_clicked\') != 1){ jQuery(this).click(); }
      jQuery(this).attr(\'is_clicked\', 0);
      jQuery(this).attr(\'current_position\', 0); "/>

      &nbsp;
      <span class="TSelect_clear" title="Clear field"
      onclick="
        jQuery(\'#'.$this->csDummyFieldId.'\').val(\''.addslashes($this->sMainLabel).'\');
        jQuery(\'#'.$this->csContainerId.' input\').val(\'\');
        jQuery(\'#'.$this->csContainerId.':visible\').fadeOut(\'fast\');
        jQuery(\'#'.$this->csContainerId.' li.selected\', this).removeClass(\'selected\'); ">
        <img src="/component/form/resources/pictures/tree_clear.png" style="margin-bottom: 4px;" /></span>

      <script>
      jQuery(document).ready(function()
      {
        init_ptree(\''.$this->csFieldId.'\', \''.$this->csContainerId.'\', \''.$this->csDummyFieldId.'\');
      });
      </script>';

    //return the form fields and all the tree hidden in the container

    $sHTML = '<div class="formLabel">'.$sFieldLabel.'</div>';
    $sHTML.= '<div class="formField posRelative">'.$sHtml.$sTree.'</div>';
    return $sHTML;
  }
}