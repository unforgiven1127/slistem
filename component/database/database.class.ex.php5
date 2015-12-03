<?php

require_once('component/database/database.class.php5');
require_once ('component/database/dbresult.class.php5');

class CDatabaseEx extends CDatabase
{
  private $coConnection;
  private $cbProfiling = false;
  private $cnQueryCount = 0;
  private $casQuery = array();

  public function __construct()
  {
  }

  public function __destruct()
  {
    $this->_logProfilingData('close db connection (destruct)', '', 0, false);
    return $this->dbClose();
  }

  function dbConnect()
  {
    if($this->coConnection)
      return true;

    try
    {
      $this->coConnection = @mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD);
      if(!$this->coConnection)
      {
        $this->_logProfilingData('open db connection', 'ERROR, can not connect to server', 0, false);
        exit('error '.__LINE__.': No database connection available.');
      }

      $bConnected = @mysqli_select_db($this->coConnection, DB_NAME);

      if(!$bConnected)
      {
        $this->_logProfilingData('select database '.DB_NAME, 'ERROR, can not connect to database', 0, false);
        exit('error '.__LINE__.': Can\'t connect to the database.');
      }

      $this->_logProfilingData('connection to database '.DB_NAME.' ok');
    }
    catch (Exception $e)
    {
      $this->_logProfilingData('Unknown error while connection to database', '', 0, false);
      exit('DB connection failure.');
    }

    return true;
  }

  function dbClose()
  {
    if(!empty($this->coConnection))
    {
      @mysqli_close($this->coConnection);
      unset($this->coConnection);
      $this->_logProfilingData('close db connection', '', 0, false);
    }
    return true;
  }


  function ExecuteQuery($psQuery)
  {
    //the function should always return an dbResult object
    $oDbResult = new CDbResult();
    $fTimeEnd = $fTimeStart = 0;

    if(!$this->dbConnect())
      exit('can\'t connect db in ExecuteQuery line '.__LINE__);

    //doesn't accept UNION query for now
    $sQueryType = strtolower(substr(trim($psQuery), 0, 3));
    if($sQueryType == 'sel')
    {
      try
      {
        $fTimeStart = microtime(true);
        $oSQLResult = mysqli_query($this->coConnection, $psQuery);
        if(!$oSQLResult)
          throw new Exception();

        $oDbResult->loadDbResult($oSQLResult);
        $fTimeEnd = microtime(true);

        if(!$oDbResult->isLoaded())
          throw new Exception();

        if(isset($_SESSION['debug']) && $_SESSION['debug'] == 'sql')
          echo round(($fTimeEnd -$fTimeStart)*1000, 2).' ms--> '.$psQuery.'<br />';

      }
      catch (Exception $e)
      {
        if(isDevelopment())
        {
          echo __LINE__." - Sorry, there seems to have been a problem with your query. An administrator has been notified.";
          echo' Connection: ';dump($this->coConnection);echo '<br />';
          echo' Query: ';dump($psQuery);echo '<br />';
          echo' oResult: ';dump($oSQLResult);echo'<br />';
          echo' oDbResult: ';dump($oDbResult);echo'<br /><br />';
          echo mysqli_errno($this->coConnection).' : '.  mysqli_error($this->coConnection);
        }

        $this->_logProfilingData('Select query error', $psQuery);
        return new CDbResult();
      }

      $this->_logProfilingData('Select query ok', $psQuery, round(($fTimeEnd - $fTimeStart)*1000, 2));
    }
    else
    {
      //update, insert, delete
      try
      {
        $fTimeStart = microtime(true);
        $oSQLResult = mysqli_query($this->coConnection, $psQuery);
        if($oSQLResult === false)
          throw new Exception();

        $fTimeEnd = microtime(true);
        $oDbResult->setFieldValue('_affected_rows', (int)mysqli_affected_rows($this->coConnection));

        if(isset($_SESSION['debug']) && $_SESSION['debug'] == 'sql')
          echo round(($fTimeEnd -$fTimeStart)*1000, 2).' ms--> '.$psQuery.'<br />';

        if($sQueryType == 'ins' && mysqli_insert_id($this->coConnection))
        {
          $oDbResult->setFieldValue('pk', (int)mysqli_insert_id($this->coConnection));
          $oDbResult->setFieldValue('_affected_rows', (int)mysqli_affected_rows($this->coConnection));
          $this->_logProfilingData('Insert query ok', $psQuery);
          return $oDbResult;
        }

        if($sQueryType == 'del')
        {
          $oDbResult->setFieldValue('affected_rows', (int)mysqli_affected_rows($this->coConnection));

          $this->_logProfilingData('Delete query ok', $psQuery);
          return $oDbResult;
        }

        $this->_logProfilingData('U.I.D query ok', $psQuery, round(($fTimeEnd - $fTimeStart)*1000, 2));
        //return true;
        return $oDbResult;
      }
      catch (Exception $e)
      {
        if(isDevelopment())
        {
          echo __LINE__." - Sorry, there seems to have been a problem with your query. An administrator has been notified.";
          echo' Connection: ';dump($this->coConnection);echo '<br />';
          echo' Query: ';dump($psQuery);echo '<br />';
          echo' oResult: ';dump($oSQLResult);echo'<br />';
          echo' oDbResult: ';dump($oDbResult);echo'<br /><br />';
          echo mysqli_errno($this->coConnection).' : '.  mysqli_error($this->coConnection);
        }

        $this->_logProfilingData('U.I.D query error', $psQuery);
        return  false;
      }
    }

    return $oDbResult;
  }


  public function dbEscapeString($pvValue, $pvDefault = '', $pbNoQuotes = false)
  {
    if(!assert('!is_array($pvValue)'))
      return '';

    if(!$this->coConnection)
      $this->dbConnect();

    $vCleanValue =  mysqli_real_escape_string($this->coConnection, $pvValue);

    if(empty($vCleanValue) && !empty($pvDefault))
      $vCleanValue = mysqli_real_escape_string($this->coConnection, $pvDefault);

    if(strtolower($vCleanValue) == 'null')
      return 'NULL';

    $sEncoding = mb_detect_encoding( $vCleanValue, "auto");
    $vCleanValue = mb_convert_encoding($vCleanValue, "UTF-8", $sEncoding);

    if($pbNoQuotes)
      return $vCleanValue;
    else
      return '"'.$vCleanValue.'"';
  }

  private function _logProfilingData($psMsg, $psSql = '', $pfTime = 0, $pbCountQuery = true)
  {
    if(!CONST_SQL_PROFILING)
      return true;

    $this->casQuery[] = array('msg' => $psMsg, 'sql' => $psSql, 'time' => $pfTime);
    if($pbCountQuery)
      $this->cnQueryCount++;

    return true;
  }

  public function clearProfilingData()
  {
    $this->casQuery = array();
    $this->cnQueryCount = 0;

    return true;
  }
  public function getProfilingData($pbDisplay = false)
  {
    if(!CONST_SQL_PROFILING)
      return array();

    $asProfiling = array('nb_queries' => $this->cnQueryCount, 'sql_data' => $this->casQuery);

    if($pbDisplay)
      return dump($asProfiling);

    return $asProfiling;
  }

}