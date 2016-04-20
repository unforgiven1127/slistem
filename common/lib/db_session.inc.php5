<?php
class CDbSessionHandler
{
  private $coSessDb = null;

  function _session_open()
  {
    //if($this->coSessDb = mysqli_connect('127.0.0.1', 'slistem', 'THWj8YerbMWfK3yW'))
    //if($this->coSessDb = mysqli_connect('172.31.29.60', 'slistem', 'THWj8YerbMWfK3yW'))
    if($this->coSessDb = mysqli_connect('localhost', 'slistem', '7088762'))
    {
      return (bool)mysqli_select_db($this->coSessDb, 'php_session');
    }

    exit('error - session unavailable');
    return false;
  }

  function _session_close()
  {
    return mysqli_close($this->coSessDb);
  }


  function _session_read($nId)
  {
    $nId = mysqli_real_escape_string($this->coSessDb, $nId);
    $sSql = "SELECT data FROM sessions WHERE id = '$nId' ";

    if($result = mysqli_query($this->coSessDb, $sSql))
    {
      if(mysqli_num_rows($result))
      {
        $record = mysqli_fetch_assoc($result);
        return $record['data'];
      }
    }

    return '';
  }


  function _session_write($nId, $data)
  {

    $access = time();

    $nId = mysqli_real_escape_string($this->coSessDb, $nId);
    $access = mysqli_real_escape_string($this->coSessDb, $access);
    $data = mysqli_real_escape_string($this->coSessDb, $data);

    $sSql = "REPLACE INTO sessions VALUES ('$nId', '$access', '$data')";

    return mysqli_query($this->coSessDb, $sSql);
  }

  function _session_destroy($nId)
  {
    $nId = mysqli_real_escape_string($this->coSessDb, $nId);
    $sSql = "DELETE FROM sessions  WHERE id = '$nId'";

    return mysqli_query($this->coSessDb, $sSql);
  }

  function _session_clean($max)
  {
    $old = time() - $max;
    $old = mysqli_real_escape_string($this->coSessDb, $old);

    $sSql = "DELETE FROM sessions WHERE access < '$old'";

    return mysqli_query($this->coSessDb, $sSql);
  }
}

$oSession = new CDbSessionHandler();
session_set_save_handler(array(&$oSession,'_session_open'),
        array(&$oSession,'_session_close'),
        array(&$oSession,'_session_read'),
        array(&$oSession,'_session_write'),
        array(&$oSession,'_session_destroy'),
        array(&$oSession,'_session_clean'));
