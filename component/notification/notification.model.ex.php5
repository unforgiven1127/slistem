<?php

class CNotificationModelEx extends CNotificationModel
{

  public function __construct()
  {
    parent::__construct();
    return true;
  }


  public function getNotificationDetails($pnPk = 0, $psDate = '', $pbDelivered = false)
  {
    if(!assert('is_integer($pnPk)'))
      return new CDbResult();

    if(!empty($psDate) && !assert('is_date($psDate) || is_datetime($psDate)'))
      return new CDbResult();

    if(empty($pnPk) && empty($psDate))
      return new CDbResult();


    $sQuery = 'SELECT * FROM notification as noti ';
    $sQuery.= 'INNER JOIN notification_action as nact ON (nact.notificationfk = noti.notificationpk) ';
    $sQuery.= 'LEFT JOIN notification_recipient as nrec ON (nrec.notificationfk = noti.notificationpk) ';
    $sQuery.= 'LEFT JOIN notification_link as nlin ON (nlin.notificationfk = noti.notificationpk AND linked_to = \'item\') ';
    $sQuery.= 'WHERE noti.type IN ("reminder", "email") ';

    if($pbDelivered === true)
    {
      $sQuery.= ' AND noti.delivered = 1 ';
    }
    elseif($pbDelivered === false)
    {
      $sQuery.= ' AND noti.delivered < 1 ';
    }

    if(!empty($pnPk))
    {
      $sQuery.= ' AND noti.notificationpk = '.$pnPk;
    }

    if(!empty($psDate))
    {
      $sQuery.= ' AND noti.date_notification <= "'.$psDate.'" ';
    }


    return $this->oDB->ExecuteQuery($sQuery);
  }


  public function getUserNotification($pnPk = 0, $psDateStart = '', $psDateEnd = '', $pbDelivered = null, $psOrderByDistance = true, $pnLimit = 0, $pasFilter = array())
  {
    if(!assert('is_integer($pnPk)'))
      return new CDbResult();

    if(!empty($psDateStart) && !assert('is_date($psDateStart) || is_datetime($psDateStart)'))
      return new CDbResult();

    if(!empty($psDateEnd) && !assert('is_date($psDateEnd) || is_datetime($psDateEnd)'))
      return new CDbResult();

    if(!assert('is_bool($psOrderByDistance) && is_integer($pnLimit)'))
      return new CDbResult();

    if(empty($pnPk) && empty($psDateStart) && empty($psDateEnd))
      return new CDbResult();


    $sQuery = 'SELECT *, ABS(TIMEDIFF("'.date('Y-m-d H:i:s').'", noti.date_notification)) as distance
            , TIMEDIFF("'.date('Y-m-d H:i:s').'", noti.date_notification) as d
            FROM notification as noti ';
    $sQuery.= 'INNER JOIN notification_action as nact ON (nact.notificationfk = noti.notificationpk) ';
    $sQuery.= 'LEFT JOIN notification_recipient as nrec ON (nrec.notificationfk = noti.notificationpk) ';
    $sQuery.= 'LEFT JOIN notification_link as nlin ON (nlin.notificationfk = noti.notificationpk AND linked_to = \'item\') ';
    $sQuery.= 'LEFT JOIN shared_login as slog ON (slog.loginpk = nrec.loginfk) ';
    $sQuery.= 'WHERE noti.type IN ("reminder", "email") ';

    if($pbDelivered === true)
    {
      $sQuery.= ' AND noti.delivered = 1 ';
    }
    elseif($pbDelivered === false)
    {
      $sQuery.= ' AND noti.delivered < 1 ';
    }

    if(!empty($pasFilter))
    {
      if(isset($pasFilter['content']) && !empty($pasFilter['content']))
      {
        $pasFilter['content'] = $this->dbEscapeString('%'.$pasFilter['content'].'%');
        $sQuery.= ' AND (noti.title LIKE '.$pasFilter['content'].' OR noti.content LIKE '.$pasFilter['content'].')';
      }

      if(isset($pasFilter['pk']) && !empty($pasFilter['pk']))
      {
        //$sQuery.= ' AND noti.notificationpk = '.(int)$pasFilter['pk'].' ';
        $sQuery.= ' AND noti.notificationpk = '.(int)$pasFilter['pk'].' ';
      }

      if(isset($pasFilter['loginfk']) && !empty($pasFilter['loginfk']))
      {
        $sQuery.= ' AND nrec.loginfk = '.(int)$pasFilter['loginfk'].' ';
      }

    }


    if(!empty($pnPk))
    {
      $sQuery.= ' AND (noti.creatorfk = '.$pnPk.' OR nrec.loginfk = '.$pnPk.') ';
    }
    if(!empty($psDateStart))
    {
      $sQuery.= ' AND noti.date_notification >= "'.$psDateStart.'" ';
    }
    if(!empty($psDateEnd))
    {
      $sQuery.= ' AND noti.date_notification <= "'.$psDateEnd.'" ';
    }

    $sQuery.= ' GROUP BY noti.notificationpk ';

    if($psOrderByDistance)
      $sQuery.= ' ORDER BY distance ASC ';
    else
      $sQuery.= 'ORDER BY noti.date_notification DESC ';


    if($pnLimit > 0)
      $sQuery.= ' LIMIT 0, '.$pnLimit;

    //dump($sQuery);
    return $this->oDB->ExecuteQuery($sQuery);
  }

}
