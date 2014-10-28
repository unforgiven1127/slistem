<?php
require_once(__DIR__.'/template.conf.class.php5');

class CTplConfList extends CTplConf
{

  protected $casField = array();
  protected $casHeader = array();

  protected $casRender = array('list' => '', 'header' => '', 'item' => '');
  protected $casMessage = array();

  protected $casPagerTop = array('position' => 'right');
  protected $casPagerBottom = array('value' => 50, 'option'=> array(25,50,100,200), 'position' => 'center');


  //*******************************************************************************
  //*******************************************************************************
  //Accessors

  public function getField()
  {
    return $this->casField;
  }

  public function getHeader()
  {
    return $this->casHeader;
  }

  public function getRender()
  {
    return $this->casRender;
  }

  public function getMessage()
  {
    return $this->casMessage;
  }

  public function getPagerTop()
  {
    return $this->casPagerTop;
  }

  public function getPagerBottom()
  {
    return $this->casPagerBottom;
  }


  //*******************************************************************************
  //*******************************************************************************


  public function addColumn($psLabel, $psFieldName = '', $pasParams = array())
  {
    if(!assert('!empty($psLabel) && is_array($pasParams)'))
      return false;

    $asSort = array();
    if(isset($pasParams['sortable']) && is_array($pasParams['sortable']) && !empty($pasParams['sortable']))
    {
      set_array($pasParams['sortable']['ajax'], 0);
      set_array($pasParams['sortable']['javascript'], 0);
      set_array($pasParams['sortable']['url'], '');
      set_array($pasParams['sortable']['ajax_target'], '');

      $asSort = array('ajax' => $pasParams['sortable']['ajax'], 'ajax_target' => $pasParams['sortable']['ajax_target'],
        'javascript' => $pasParams['sortable']['javascript'],
        'up' => $pasParams['sortable']['url'].'&sortfield='.$psFieldName.'&sortorder=asc',
        'down' => $pasParams['sortable']['url'].'&sortfield='.$psFieldName.'&sortorder=desc');

      unset($pasParams['sort']);
      unset($pasParams['sortable']);
    }

    $asFilter = array();
    if(isset($pasParams['filtrable']) /*&& is_array($pasParams['filtrable'])*/ && !empty($pasParams['filtrable']))
    {
      /*
       * Only javascript for mow, it overlaps the search engine
       *
      set_array($pasParams['filtrable']['ajax'], 0, (int)(bool)$pasParams['filtrable']['ajax']);
      set_array($pasParams['filtrable']['javascript'], 0);
      set_array($pasParams['filtrable']['url']);

      $asFilter = array('ajax' => $pasParams['filtrable']['ajax'], 'javascript' => $pasParams['filtrable']['javascript'],
              'empty' => $pasParams['filtrable']['url'].'&filterfield='.$psFieldName.'&filter=empty',
              'notempty' => $pasParams['filtrable']['url'].'&filterfield='.$psFieldName.'&filter=not_empty',
              'word' => $pasParams['filtrable']['url'].'&filterfield='.$psFieldName.'&filter=',
             );*/
      $asFilter = array(true);
      unset($pasParams['filtrable']);
    }

    if(isset($pasParams['width']))
    {
      if(substr($pasParams['width'], -1, 1) == '%')
      {
        if(!is_numeric(substr($pasParams['width'], 0, strlen($pasParams['width'])-1)))
        {
          assert('false; //column width must be a numeric value in pixels or percentage [given: '.$pasParams['width'].']');
          return false;
        }
        $pasParams['width_%'] = $pasParams['width'];
      }
      else
      {
        if(!is_numeric($pasParams['width']))
        {
          assert('false; //column width must be a numeric value in pixels or percentage [given: '.$pasParams['width'].']');
          return false;
        }
      }
    }

    $this->casHeader[] = array('label' => $psLabel, 'field' => $psFieldName, 'params' => $pasParams, 'sort' => $asSort, 'filter' => $asFilter);
    $this->casField[] = $psFieldName;
    return true;
  }

  public function addField($psFieldName)
  {
    if(!assert('!empty($psFieldName)'))
      return false;

    $this->casField[] = $psFieldName;
    return true;
  }

  public function setRenderingOption($psListWidth = 'float', $psHeaderWidth = 'float', $psItemWidth = 'float')
  {
    if(!assert('!empty($psListWidth) && !empty($psHeaderWidth) && !empty($psItemWidth)'))
      return false;

    if(!assert('($psListWidth == "float" || $psListWidth == "full")'))
      return false;

    if(!assert('($psHeaderWidth == "float" || $psHeaderWidth == "full")'))
      return false;

    if(!assert('($psItemWidth == "float" || $psItemWidth == "full")'))
      return false;

    if($psListWidth != 'float')
    {
      $psListWidth = 'tplListFullSize';
    }
    else
      $psListWidth = '';

    if($psHeaderWidth != 'float')
      $psHeaderWidth = 'tplListHeaderFullSize';
    else
      $psHeaderWidth = '';

    if($psItemWidth != 'float')
      $psItemWidth = 'tplListRowFullSize';
    else
      $psItemWidth = 'tplListRowFloat';

    $this->casRender = array('list' => $psListWidth, 'header' => $psHeaderWidth, 'item' => $psItemWidth);

    return true;
  }

  public function addBlocMessage($psText, $pasParams = array(), $psType = 'message')
  {
    if(!assert('!empty($psText) && is_array($pasParams)'))
      return false;

    if(!assert('!empty($psType) && ($psType == "message" || $psType == "notice" || $psType == "title"|| $psType == "big_title")'))
      return false;

    $this->casMessage[] = array('text' => $psText, 'type' => $psType, 'params' => $pasParams);

    return true;
  }

  public function clearBlocMessage()
  {
    $this->casMessage = array();
    return true;
  }

  public function setPagerTop($pbDisplay = true, $psPosition = 'right', $pnResult = 0, $psUrl = '', $pasOption = array())
  {
    if(!assert('is_bool($pbDisplay) && !empty($psPosition) && ($psPosition == "right" || $psPosition == "left" || $psPosition == "center")'))
      return false;

    if(!$pbDisplay)
      $this->casPagerTop = array();
    else
    {
      if(!assert('is_integer($pnResult) && !empty($psUrl) && is_array($pasOption)'))
        return false;

      $this->casPagerTop = array('position' => $psPosition, 'nb_result' => $pnResult, 'url' => $psUrl, 'params' => $pasOption);
    }

    return true;
  }

  public function setPagerBottom($pbDisplay = true, $psPosition = 'center', $pnResult = 0, $psUrl = '', $pasOption = array(), $pnRowNumber = 100, $panRowOption = array() )
  {
    if(!assert('is_bool($pbDisplay) && is_integer($pnRowNumber) && !empty($pnRowNumber) && is_array($panRowOption)'))
      return false;

    if(!assert('$psPosition == "right" || $psPosition == "left" || $psPosition == "center"'))
      return false;

    if(!$pbDisplay)
    {
      $this->casPagerBottom = array();
    }
    else
    {
      if(!assert('is_integer($pnResult) && !empty($psUrl) && is_array($pasOption)'))
          return false;

      if(empty($panRowOption))
        $panRowOption = array(25, 50, 100, 200);

      if(!in_array($pnRowNumber, $panRowOption))
      {
        assert('false; pager value is not an available option');
        return false;
      }

      $this->casPagerBottom = array('value' => $pnRowNumber, 'nb_result' => $pnResult, 'url' => $psUrl, 'params' => $pasOption, 'option' => $panRowOption, 'position' => $psPosition);
    }

    return true;
  }

  public function isConfOk()
  {
    if(empty($this->casField))
      return false;

    return true;
  }
}
?>
