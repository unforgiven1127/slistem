<?php

require_once('common/lib/model.class.php5');

class CSettingsModelEx extends CSettingsModel
{
  public function __construct()
  {
    parent::__construct();
    return true;
  }


  // ================================================================
  // Redifining methods
  // ================================================================

  protected function _testFields($avFields, $psTablename, $pbAllFieldRequired = true, $pbAllowExtra = true, $psAction = 'add')
  {
    $this->casError = array();

    if(($psAction == 'update') && ($psTablename=='settings'))
    {

      if(!isset($avFields['value']))
      {
        $this->casError[] = __LINE__.' - upd - Missing value of the field.';
        return false;
      }

      /*if(!isset($avFields['fieldname']) || empty($avFields['fieldname']))
      {
        $this->casError[] = __LINE__.' - upd - Missing field name of the field.';
        return false;
      }*/

      return true;
    }

    return parent::_testFields($avFields, $psTablename, $pbAllFieldRequired, $pbAllowExtra, $psAction);
  }


  //
  //  Adds a new setting in database
  //  @param array $paValues
  //
  public function addSetting($paValues, $pbUserPref = false)
  {
    if(!assert('is_string($paValues[\'fieldname\']) && !empty($paValues[\'fieldname\'])'))
      return false;

    if(!assert('is_string($paValues[\'fieldtype\']) && !empty($paValues[\'fieldtype\'])'))
      return false;

    if(!isset($paValues['fields']))
      $paValues['fields'] = array();

    $aFinalValues = array();
    $aFinalValues['fieldname'] = $paValues['fieldname'];
    $aFinalValues['fieldtype'] = $paValues['fieldtype'];

    if($pbUserPref)
      $aFinalValues['is_user_setting'] = 1;

    if(!isset($paValues['options']))
      $aFinalValues['options'] = 'NULL';
    else
      $aFinalValues['options'] = serialize($paValues['options']);

    $aFinalValues['description'] = $paValues['description'];

    switch($paValues['fieldtype'])
    {
      case 'serialized':
      case 'select_multi':
        $aFinalValues['value'] = serialize($paValues['fields']);
        break;
      default:
        $aFinalValues['value'] = $paValues['value'];
        break;
    }

    $nPk = $this->add($aFinalValues, 'settings');

    if(!is_key($nPk))
      return false;

    return true;
  }

  public function getUserPreference($pnLoginfk, $psFieldname)
  {
    if(!assert('is_key($pnLoginfk) && is_string($psFieldname) && !empty($psFieldname)'))
      return '';

    $sQuery = 'SELECT suse.value
      FROM settings_user suse
      INNER JOIN settings sett ON (sett.settingspk = suse.settingsfk)
      WHERE sett.fieldname='.$this->oDB->dbEscapeString($psFieldname).' AND suse.loginfk = '.$pnLoginfk;

    $oResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return '';

    return $oResult->getFieldValue('value');
  }

  public function getUserSettings($pnLoginFk, $pasFieldname = array())
  {
    if(!assert('is_array($pasFieldname) && is_key($pnLoginFk)'))
      return new CDbResult();

    $sQuery = 'SELECT *, suse.value as pref_value FROM settings sett
      LEFT JOIN settings_user suse ON (suse.settingsfk = sett.settingspk AND suse.loginfk = '.$pnLoginFk.')
      WHERE sett.is_user_setting = 1 ';

    if(!empty($pasFieldname))
      $sQuery.= ' AND sett.fieldname IN ("'.implode('", "', $pasFieldname).'") ';

    $oResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult();

    return $oResult;
  }

  public function getDatabaseFields()
  {
    $sQuery = 'SELECT * FROM information_schema.columns WHERE table_schema = "'.DB_NAME.'" ORDER BY table_name, ordinal_position';

    $oResult = $this->oDB->executeQuery($sQuery);
    $bRead = $oResult->readFirst();
    if(!$bRead)
      return new CDbResult;

    return $oResult;
  }

  public function get_saved_searches($user)
  {
    $saved_searches = array();

    $query = 'SELECT saved_search.id, saved_search.search_label, saved_search.date_create,
      saved_search.login_activitypk, login_activity.log_link
      FROM saved_search
      INNER JOIN login_activity ON login_activity.login_activitypk = saved_search.login_activitypk
      WHERE saved_search.loginpk = '.$user.'
      ORDER BY saved_search.date_create
      ';

      $db_result = $this->oDB->executeQuery($query);
      $read = $db_result->readFirst();

      while($read)
      {
        $temp = $db_result->getData();

        $saved_searches[] = array('id' => $temp['id'], 'label' => $temp['search_label'],
          'date' => $temp['date_create'], 'link' => $temp['log_link'], 'activity_id' => $temp['login_activitypk']);

        $read = $db_result->readNext();
      }

      return $saved_searches;
  }
}
