<?php
require_once(__DIR__.'/template.tpl.class.php5');
require_once(__DIR__.'/list.conf.class.php5');

class CTemplateList extends CTemplate
{
  private $cnDefaultColSize = 70;

  public function __construct(&$poTplManager, $psUid, $pasParams, $pnTemplateNumber)
  {
    $this->csTplType = 'list';
    $this->casTplToLoad = array('CTemplatePager');
    $this->casTplToProvide = array('row');
    $this->coConfig = new CTplConfList();

    parent::__construct($poTplManager, $psUid, $pasParams, $pnTemplateNumber);
  }

  public function getDisplay($pvData)
  {
    $oDisplay = CDependency::getCpHtml();

    $oPage = CDependency::getCpPage();
    $oPage->addCssFile($oDisplay->getResourcePath().'css/list.tpl.css');
    $oPager = CDependency::getComponentByName('pager');

    if(!$this->coConfig->isConfOk())
    {
      //dump($this->coConfig);
      assert('false; // list template config not ok');
      return '';
    }

    set_array($this->cavParams['class'], 'tplListContainer', ' tplListContainer');

    if(isset($this->cavParams['id']))
      $sListUid = $this->cavParams['id'];
    else
      $sListUid = uniqid('tplList_');

    $sHTML = $oDisplay->getBlocStart($sListUid, $this->cavParams);

    //Display text bloc above the list
    $asMessage = $this->coConfig->getMessage();
    if(!empty($asMessage))
    {

      $sHTML.= $oDisplay->getBlocStart('', array('class' => 'tplListMessageContainer'));
      foreach($asMessage as $asMsgData)
      {
        switch($asMsgData['type'])
        {
          case 'notice':    $sClass = 'tplListNotice';  break;
          case 'title':     $sClass = 'tplListTitle';  break;
          case 'big_title': $sClass = 'tplListBigTitle';  break;
          case 'message':
          default:          $sClass = '';  break;
        }

        $asParam = array_merge(array('class' => 'tplListMessage light_shadow '.$sClass), $asMsgData['params']);

        $sHTML.= $oDisplay->getBlocStart('', $asParam);
        $sHTML.= $oDisplay->getBloc('', $asMsgData['text']);
        $sHTML.= $oDisplay->getBlocEnd();
      }
      $sHTML.= $oDisplay->getBlocEnd();
      $sHTML.= $oDisplay->getFloatHack();
    }

    //------------------------------------------
    //Manage List display modes   --------------
    $asPagerTop = $this->coConfig->getPagerTop();
    if(!empty($asPagerTop))
    {
      switch($asPagerTop['position'])
      {
        case 'center': $sClass = 'alignCenter';  break;
        case 'right': $sClass = 'floatRight';  break;
        case 'left':
        default:
          $sClass = 'floatLeft';  break;
      }

      if(!isset($asPagerTop['option']['ajaxTarget']))
        $asPagerTop['option']['ajaxTarget'] = $sListUid;


      $sPagerTop = $oPager->getCompactDisplay($asPagerTop['nb_result'], $asPagerTop['url'], $asPagerTop['params']);
      if(!empty($sPagerTop))
      {
        $sHTML.= $oDisplay->getBlocStart('', array('class' => 'tplListPagerTop '.$sClass));
        $sHTML.= $sPagerTop;
        $sHTML.= $oDisplay->getBlocEnd();
        $sHTML.= $oDisplay->getFloatHack();
      }
    }

    //----------------------------------------
    //create list header ---------------------

    //calculate list and column width (displayed columns = hearder)
    $asHeader = $this->coConfig->getHeader();
    $nColumns = count($asHeader);
    $asCustomCss = array('custom' => array(), 'auto' => array());
    $asRender = $this->coConfig->getRender();
    $sListId = uniqid('tpl_list_');


      if(!empty($asHeader))
      {
        $sHeader = $oDisplay->getListItemStart('', array('class' => 'tplListRowContainer tplListHeaderContainer '.$asRender['header']));
        $sHeader.= $oDisplay->getBlocStart('', array('class' => 'tplListRow'));

        $nCount = 0;
        $nListWidth = 0;
        $nNbCustomWidth = 0;
        $nExactWidth = 0;

        $asFields = array();

        foreach($asHeader as $nHeaderPos => $asColumnData)
        {

          //dump($asColumnData);
          $sText = $oDisplay->getSpan('', $asColumnData['label']);

          //Save column params + generate a uniq class for every column
          $asColumnParam[$nCount] = $asColumnData['params'];
          $asColumnParam[$nCount]['tag'] = 'col_'.uniqid();

          $asColumnData['params']['column'] = $asColumnParam[$nCount]['tag'];
          set_array($asColumnData['params']['class'], 'tplCol '.$asColumnParam[$nCount]['tag']);

          //calculate the width / min-width for columns
          $nLength = strlen(strip_tags($asColumnData['label']));
          $nAdjustedLength = $nLength + (int)($nLength/10);

          //we voluntarly underestimated the width because we want the min-width to be the really the min required
          //TODO: make a smarter system based on different ranges of $nLength or columns
          $nCellWidth = ($nAdjustedLength + 2) * 7;
          $fTotalWidth = ($nAdjustedLength + 4) * (8 + (int)($nColumns/5));

          //give at least the field name as an identifier (could be needed by sub tpl)
          $asFields[$asColumnData['field']] = $nHeaderPos;

          if(isset($asColumnData['params']['width']))
          {
            if(isset($asColumnData['params']['width_%']))
            {

              $asCustomCss['custom'][$asColumnParam[$nCount]['tag']]['width'] = $asColumnData['params']['width'];
              $asCustomCss['custom'][$asColumnParam[$nCount]['tag']]['%'] = true;
            }
            else
            {
              $asCustomCss['custom'][$asColumnParam[$nCount]['tag']]['width'] = $asColumnData['params']['width'];
              //since we undersimate the cells width above, we weigth the custom down as well
              $nListWidth+= 0.9*$asColumnData['params']['width'];
              $nExactWidth+= $asColumnData['params']['width'];
              $nNbCustomWidth++;
            }

            $asCustomCss['custom'][$asColumnParam[$nCount]['tag']]['min-width'] = $nCellWidth;
          }
          else
          {
            $asCustomCss['auto'][$asColumnParam[$nCount]['tag']] = $nCellWidth;
            $nListWidth+= $fTotalWidth;
          }

          unset($asColumnData['params']['width']);

          $sAction = '';
          if(!empty($asColumnData['sort']) || !empty($asColumnData['filter']))
          {
            $sPictAsc = $oDisplay->getPicture($oDisplay->getResourcePath().'/pictures/sort_asc.png');
            $sPictDesc = $oDisplay->getPicture($oDisplay->getResourcePath().'/pictures/sort_desc.png');

            if(!empty($asColumnData['sort']))
            {
              //TODO: allow to pass extra params for the onclick and callback in ajax and sorting mode
              if(isset($asColumnData['sort']['ajax']) && !empty($asColumnData['sort']['ajax']))
              {
                if(isset($asColumnData['sort']['ajax_target']))
                  $sContainer = $asColumnData['sort']['ajax_target'];
                else
                  $sContainer = $sListUid;

                $sAction.= '<a href="javascript:;" class="tplListSortAsc" onclick="AjaxRequest(\''.$asColumnData['sort']['up'].'\', \'body\', \'\', \''.$sContainer.'\');">'.$sPictAsc.'</a>';
                $sAction.= '<a href="javascript:;" class="tplListSortDesc" onclick="AjaxRequest(\''.$asColumnData['sort']['down'].'\', \'body\', \'\', \''.$sContainer.'\');">'.$sPictDesc.'</a>';
              }
              elseif(isset($asColumnData['sort']['javascript']) && $asColumnData['sort']['javascript'])
              {
                $sAction.= '<a href="javascript:;" list-id="'.$sListId.'" class="tplListSortAsc" onclick="sortList(this, \'up\', \''.$asColumnData['sort']['javascript'].'\');">'.$sPictAsc.'</a>';
                $sAction.= '<a href="javascript:;" list-id="'.$sListId.'" class="tplListSortDesc" onclick="sortList(this, \'down\', \''.$asColumnData['sort']['javascript'].'\');">'.$sPictDesc.'</a>';
              }
              else
              {
                $sAction.= '<a href="'.$asColumnData['sort']['up'].'" class="tplListSortAsc" >'.$sPictAsc.'</a>';
                $sAction.= '<a href="'.$asColumnData['sort']['down'].'" class="tplListSortDesc" >'.$sPictDesc.'</a>';
              }
            }

            if(!empty($asColumnData['filter']))
            {

              if(!empty($sAction))
                $sAction = '<div class="filter_bloc" >Order: '.$sAction.'<br />';
              else
                $sAction = '<div class="filter_bloc" >';

              $sAction.= 'Filter: ';
              $sAction.= '<a href="javascript:;" list-id="'.$sListId.'" class="tplListFilterEmpty" onclick="filterList(this, \'empty\');">Empty</a>';
              $sAction.= '&nbsp;&nbsp;-&nbsp;&nbsp;<a href="javascript:;" list-id="'.$sListId.'" class="tplListFilterEmpty" onclick="filterList(this, \'notempty\');">Not Empty</a>';
              $sAction.= '<br />Filer by word:<br /><input type="text" name="filter_word" />
                <a href="javascript:;" class="tplListFilterWord" onclick="filterList(this, \'word\');">Go</a>';

              $sAction.= '</div>';
            }


            set_array($asColumnData['params']['onclick'], '');
            $asColumnData['params']['onclick'].= ' displayFilter(this); ';
          }

          $sHeader.= $oDisplay->getBloc('', $sText.$sAction, $asColumnData['params']);
          $nCount++;
        }

        $sHeader.= $oDisplay->getBlocEnd();
        $sHeader.= $oDisplay->getListItemEnd();
      }

      //if all columns have a custom width, we can just use it
      if($nNbCustomWidth == $nCount)
      {
        $nListWidth = $nExactWidth + ($nNbCustomWidth*5);
      }

      //-------------------------------------------
      //Column width management   -----------------
      $sCss = '';
      $fPercentage = 100;

      if(!empty($asCustomCss['custom']))
      {
        //1 sum the custom width and calculate the leftover
        $nFixedWidth = 0;
        foreach($asCustomCss['custom'] as $sTag => $asWidth)
        {
          if(isset($asWidth['%']))
          {
            $sCss.=   'div.'.$sTag.'{  width: '.$asWidth['width'].'; min-width: '.$asWidth['width'].';  color; red; overflow: hidden;  }';
          }
          else
          {
            $sCss.=   'div.'.$sTag.'{  width: '.$asWidth['width'].'px; min-width: '.$asWidth['width'].'px;  color; red; overflow: hidden;  }';
            $nFixedWidth+= $asWidth['width'];
          }
        }

        if(empty($nListWidth))
          $fPercentage = 100;
        else
          $fPercentage = ($nListWidth - $nFixedWidth)/ $nListWidth;
      }

      //dump($fPercentage);
      $sWidth = ($fPercentage/$nColumns) - 1.15;

      foreach($asCustomCss['auto'] as $sTag => $sMinWidth)
      {
        $sCss.=   'div.'.$sTag.'{  width: '.$sWidth.'%; min-width: '.$sMinWidth.'px; color: green; overflow: hidden;  }';
      }

      $oPage->addCustomCss($sCss);
      $oPage->addJsFile('/component/display/resources/js/list.tpl.js');

      $aOptions = array('class' => $asRender['list']);
      if($nListWidth > 0)
        $aOptions['style'] = 'min-width: '.$nListWidth.'px; ';

      $sHTML.= $oDisplay->getListStart($sListId, $aOptions);
      $sHTML.= $sHeader;

      $asField = $this->coConfig->getField();

      foreach($pvData as $asRowData)
      {
        $sClass = 'tplListRowContainer '.$asRender['item'];
        if(isset($asRowData['class']))
          $sClass.= ' '.$asRowData['class'];
        $sHTML.= $oDisplay->getListItemStart('', array('class' => $sClass, 'onclick' => 'rowClic(this);'));
        $sHTML.= $this->caoSubTpl[$this->casProvidedClass['row']]->getDisplay($asRowData, $asField, $asColumnParam, $asFields);
        $sHTML.= $oDisplay->getFloatHack();
        $sHTML.= $oDisplay->getListItemEnd();
      }

    $sHTML.= $oDisplay->getListEnd();
    $sHTML.= $oDisplay->getFloatHack();
    $sHTML.= $oDisplay->getBlocEnd();


    $asPagerBottom = $this->coConfig->getPagerBottom();

    if(!empty($asPagerBottom) && $asPagerBottom['nb_result'] > 0)
    {
      switch($asPagerBottom['position'])
      {
        case 'center': $sClass = 'alignCenter';  break;
        case 'right': $sClass = 'floatRight';  break;
        case 'left':
        default:      $sClass = 'floatLeft';  break;
      }

      if(!isset($asPagerTop['option']['ajaxTarget']))
        $asPagerTop['option']['ajaxTarget'] = $sListUid;

      $sHTML.= $oDisplay->getBlocStart('', array('class' => 'tplListPagerBottom '.$sClass));

      $oPager->initPager();
      if(isset($asPagerBottom['params']['nb_result']))
        $oPager->setLimit($asPagerBottom['params']['nb_result']);

      $sHTML.= $oPager->getDisplay($asPagerBottom['nb_result'], $asPagerBottom['url'], $asPagerBottom['params']);

      $sHTML.= $oDisplay->getBlocEnd();
      $sHTML.= $oDisplay->getFloatHack();
    }

    return $sHTML;
  }
}
