<?php

class CGbTestModelEx extends CGbTestModel
{

  public function __construct()
  {
    parent::__construct();
    return true;
  }

  // Returns all assignments

  public function getAll()
  {
    $sQuery = 'SELECT t.*, tc.rank as chapterrank, tc.name as chaptername, tc.gbtest_chapterpk as chapterpk
                FROM gbtest t
                LEFT JOIN gbtest_chapter tc ON t.gbtest_chapterfk = tc.gbtest_chapterpk
                ORDER BY chapterrank, rank';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  public function getPastGroups($pnWeekCount)
  {
    if(!assert('is_key($pnWeekCount)'))
      return array();

    $dDeadline = date('Y-m-d', strtotime('-'.$pnWeekCount.' weeks'));

    $sQuery = 'SELECT gbuser_groupfk, MAX(deadline) as date_end
                FROM gbtest_chapter_group
                  GROUP BY gbuser_groupfk
                  HAVING MAX(deadline)>\''.$dDeadline.'\' AND MAX(deadline)<\''.date('Y-m-d').'\'';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  public function deleteGroupSchedule($pnGroupPk)
  {
    if(!assert('is_key($pnGroupPk)'))
      return false;

    $this->deleteByFk($pnGroupPk, 'gbtest_chapter_group', 'gbuser_group');

    return true;
  }

  public function addGroupSchedule($paValues)
  {
    if(!assert('is_array($paValues) && !empty($paValues)'))
      return 0;

    return $this->add($paValues, 'gbtest_chapter_group');
  }

  // ------------------------------------------------------------- //
  //                  STUDENT VIEW METHODS
  // ------------------------------------------------------------- //


  // Returns tests that have been corrected by teacher but not read by the student
  public function getUnreadCorrectedTest($pnStudentPk)
  {
    $sQuery = 'SELECT ta.gbtest_answerpk, tc.status, tc.gbtest_correctionpk, ta.gbuserfk, t.gbtestpk, t.rank
                FROM gbtest t
                  LEFT JOIN gbtest_answer ta ON ta.gbtestfk = t.gbtestpk
                  LEFT JOIN gbtest_correction tc ON tc.gbtest_answerfk = ta.gbtest_answerpk
                  WHERE t.esa=0 AND ta.gbuserfk ='.$pnStudentPk.' AND tc.status="sent" ';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  // Returns students who have replyed to a test
  public function getStudentsNotToRemind($pnTestPk)
  {
    if(!assert('is_key($pnTestPk)'))
      return array();

    $sQuery = 'SELECT gbuserfk FROM gbtest_answer WHERE gbtestfk='.$pnTestPk.' AND status IN (\'returned\', \'sent\')';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    $aOutput = array();
    while($bRead)
    {
      $aOutput[]=(int)$oDbResult->getFieldValue('gbuserfk');

      $bRead = $oDbResult->readNext();
    }
    return $aOutput;
  }

  // Returns assignments or ESA that are close to reach the deadline
  public function getPendingTests()
  {
    $sQuery = 'SELECT *, t.name as test_name
                FROM gbtest t
                  LEFT JOIN gbtest_chapter tc ON tc.gbtest_chapterpk = t.gbtest_chapterfk
                  LEFT JOIN gbtest_chapter_group tcg ON tcg.gbtest_chapterfk = tc.gbtest_chapterpk
                  WHERE tcg.deadline <= \''.date('Y-m-d', strtotime('+1 day')).'\' AND tcg.deadline >= \''.date('Y-m-d').'\'';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  // Returns assignment from a chapter that hasnt been answered by a student
  public function getActiveTestForChapter($pnChapterPk, $pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return 0;

    if(!assert('is_key($pnChapterPk)'))
      return 0;

    $sQuery = 'SELECT t.gbtestpk, a.status FROM gbtest t
                  LEFT JOIN gbtest_chapter c ON t.gbtest_chapterfk=c.gbtest_chapterpk
                  LEFT JOIN gbtest_answer a ON (a.gbtestfk=t.gbtestpk AND  a.gbuserfk='.$pnUserPk.')
                  WHERE (a.status = \'draft\' OR ISNULL(a.status)) AND t.gbtest_chapterfk='.$pnChapterPk;

    $oActiveTests = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oActiveTests->readFirst();
    if(!$bRead)
      return 0;
    else
      return (int)$oActiveTests->getFieldValue('gbtestpk');
  }

  // Returns assignment history for a student

  public function getAnsweredTestsForStudent($pnUserPk, $pbNbOnly = false)
  {
    if(!assert('is_key($pnUserPk)'))
      return new CDbResult();

    if(!assert('is_bool($pbNbOnly)'))
      return new CDbResult();

    $sField = ($pbNbOnly) ? 'COUNT(*) as nb' : '*';

    $sQuery = 'SELECT '.$sField.' FROM gbtest_answer a
                LEFT JOIN gbtest t ON a.gbtestfk=t.gbtestpk
                WHERE a.gbuserfk='.$pnUserPk;

    return $this->oDB->ExecuteQuery($sQuery);
  }

  // Returns number of active tests for a user

  public function getNbTestsForUser($pnUserPk, $pnGroupPk)
  {
    if(!assert('is_key($pnGroupPk)'))
      return array();

    if(!assert('is_key($pnUserPk)'))
      return array();

    $sQuery = 'SELECT COUNT(*) as nb, t.esa
                FROM gbtest t
                LEFT JOIN gbtest_chapter tc ON t.gbtest_chapterfk = tc.gbtest_chapterpk
                LEFT JOIN gbtest_chapter_group tcs ON (t.gbtest_chapterfk = tcs.gbtest_chapterfk AND tcs.gbuser_groupfk='.$pnGroupPk.')
                LEFT JOIN gbtest_answer ta ON (t.gbtestpk = ta.gbtestfk AND ta.gbuserfk='.$pnUserPk.')
                WHERE tcs.deadline>\''.date('Y-m-d').'\' AND (ta.status = \'draft\' OR ISNULL(ta.status))
                  GROUP BY t.esa';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);

    $aData = array( 0 => 0, 1 => 0);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return $aData;

    while($bRead)
    {
      $aData[(int)$oDbResult->getFieldValue('esa')]=(int)$oDbResult->getFieldValue('nb');

      $bRead = $oDbResult->readNext();
    }

    return $aData;
  }

  // Returns a student schedule

  public function getStudentSchedule($pnUserPk, $pnGroupPk, $psFilter = '', $pbIsEsa = false)
  {
    if(!assert('is_key($pnUserPk)'))
      return new CDbResult();

    if(!assert('is_integer($pnGroupPk)') || empty($pnGroupPk))
      return new CDbResult();

    if(!assert('is_string($psFilter)'))
      return new CDbResult();

    if(!assert('is_bool($pbIsEsa) || $pbIsEsa === NULL '))
      return new CDbResult();

    $sQuery = 'SELECT t.*, tc.rank as chapterrank, tc.name as chaptername, tcs.deadline, ta.*, IF(tc.name = "ESA2", 10000, IF(tc.name = "ESA1", -1, t.rank)) as sortOrder
                FROM gbtest t
                LEFT JOIN gbtest_chapter tc ON t.gbtest_chapterfk = tc.gbtest_chapterpk
                LEFT JOIN gbtest_chapter_group tcs ON (t.gbtest_chapterfk = tcs.gbtest_chapterfk AND tcs.gbuser_groupfk='.$pnGroupPk.')
                LEFT JOIN gbtest_answer ta ON (t.gbtestpk = ta.gbtestfk AND ta.gbuserfk='.$pnUserPk.')
                WHERE tcs.deadline IS NOT NULL AND tcs.deadline <> "0000-00-00" ';
                //WHERE (ISNULL(tcs.deadline)=0)';

    if($psFilter == 'completed')
      $sQuery .= ' AND ta.status = \'sent\'';

    if($psFilter == 'active')
      $sQuery .= ' AND (ta.status = \'draft\' OR ISNULL(ta.status))';

    if($psFilter == 'returned')
      $sQuery .= ' AND ta.status = \'returned\'';

    if($pbIsEsa === true)
      $sQuery.= ' AND t.esa = 1';
    elseif($pbIsEsa === false)
      $sQuery.= ' AND t.esa = 0';

    $sQuery.= ' ORDER BY CASE
                  WHEN (ISNULL(ta.status) OR (ta.status=\'draft\')) THEN 1
                  WHEN (ta.status=\'returned\') THEN 2
                  ELSE 3 END,
                  sortOrder,
                  chapterrank, t.rank ';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  // Returns an assignment and the student answer

  public function getTestForStudent($pnTestPk, $pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return new CDbResult();

    if(!assert('is_key($pnTestPk)'))
      return new CDbResult();

    $sQuery = 'SELECT t.*, ta.*, tc.gbtest_correctionpk, tc.corrected_by, tc.status as correction_status,
      tc.good, tc.date_send as correction_date_returned, gcha.name as chapter_name, t.rank as t_rank

      FROM gbtest t
      LEFT JOIN gbtest_answer ta ON (t.gbtestpk = ta.gbtestfk AND ta.gbuserfk='.$pnUserPk.')
      LEFT JOIN gbtest_correction tc ON (ta.gbtest_answerpk = tc.gbtest_answerfk)

      LEFT JOIN gbtest_chapter gcha ON (gcha.gbtest_chapterpk = t.gbtest_chapterfk)
      WHERE t.gbtestpk='.$pnTestPk;

    return $this->oDB->ExecuteQuery($sQuery);
  }

  // ------------------------------------------------------------- //
  //                  TEACHER VIEW METHODS
  // ------------------------------------------------------------- //

  public function getTrainerComments($pnTrainerPk)
  {
    if(!assert('is_key($pnTrainerPk)'))
      return new CDbResult();

    $sQuery = 'SELECT p.comment
      FROM gbtest_correction_point p
        LEFT JOIN gbtest_correction c ON p.gbtest_correctionfk=c.gbtest_correctionpk
        WHERE c.corrected_by='.$pnTrainerPk;

    return $this->oDB->executeQuery($sQuery);
  }

  public function getAnswerFromEsaScore($pnEsaScorePk)
  {
    if(!assert('is_key($pnEsaScorePk)'))
      return new CDbResult();

    $sQuery = 'SELECT ta.*
                FROM gbtest_answer ta
                  LEFT JOIN gbtest_esa_score te ON te.gbtest_answerfk=ta.gbtest_answerpk
                  WHERE te.gbtest_esa_scorepk='.$pnEsaScorePk;

    return $this->oDB->ExecuteQuery($sQuery);
  }

  public function getEsaFromAnswerPk($pnAnswerPk)
  {
    if(!assert('is_key($pnAnswerPk)'))
      return new CDbResult();

    $sQuery = 'SELECT t.*, es.*, ta.*, gcha.name as chapter_name
                FROM gbtest t
                LEFT JOIN gbtest_answer ta ON (t.gbtestpk = ta.gbtestfk AND ta.gbtest_answerpk='.$pnAnswerPk.')
                LEFT JOIN gbtest_esa_score es ON (ta.gbtest_answerpk = es.gbtest_answerfk)

                LEFT JOIN gbtest_chapter as gcha ON (gcha.gbtest_chapterpk = t.	gbtest_chapterfk)
                WHERE ta.gbtest_answerpk='.$pnAnswerPk;

    return $this->oDB->ExecuteQuery($sQuery);
  }

  public function getEsaScoresFromAnswerPk($pnAnswerPk)
  {
    if(!assert('is_key($pnAnswerPk)'))
      return new CDbResult();

    $sQuery = 'SELECT s.*, d.*
                FROM      gbtest_esa_skill s
                LEFT JOIN gbtest_esa_score_detail d ON s.gbtest_esa_skillpk=d.gbtest_esa_skillfk
                LEFT JOIN gbtest_esa_score es ON d.gbtest_esa_scorefk = es.gbtest_esa_scorepk
                WHERE es.gbtest_answerfk='.$pnAnswerPk.'
                  ORDER BY s.category DESC';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  public function getEsaScores($pnEsaScorePk)
  {
    if(!assert('is_key($pnEsaScorePk)'))
      return new CDbResult();

    $sQuery = 'SELECT * FROM
                gbtest_esa_skill s LEFT JOIN
                gbtest_esa_score_detail d ON (s.gbtest_esa_skillpk=d.gbtest_esa_skillfk AND d.gbtest_esa_scorefk='.$pnEsaScorePk.')';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  public function getEsaTotalScore($pnEsaScorePk)
  {
    if(!assert('is_key($pnEsaScorePk)'))
      return array();

    $sQuery = 'SELECT s.category, (SUM(d.score*s.importance))/(SUM(s.valmax*importance))*100 as totalCat FROM
                gbtest_esa_skill s LEFT JOIN
                gbtest_esa_score_detail d ON (s.gbtest_esa_skillpk=d.gbtest_esa_skillfk AND d.gbtest_esa_scorefk='.$pnEsaScorePk.')
                  GROUP BY s.category';

    $oDbResult = $this->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $aData = array();
    while($bRead)
    {
      $aData[$oDbResult->getFieldValue('category')] = $oDbResult->getFieldValue('totalCat');

      $bRead = $oDbResult->readNext();
    }

    return $aData;
  }

  // Returns an assignment and the student answer

  public function getTestFromAnswerPk($pnAnswerPk)
  {
    if(!assert('is_key($pnAnswerPk)'))
      return new CDbResult();

    $sQuery = 'SELECT t.*, ta.*, tc.gbtest_correctionpk, tc.corrected_by, tc.status as correction_status, tc.good, tc.date_send as correction_date_returned
                FROM gbtest t
                LEFT JOIN gbtest_answer ta ON (t.gbtestpk = ta.gbtestfk AND ta.gbtest_answerpk='.$pnAnswerPk.')
                LEFT JOIN gbtest_correction tc ON (ta.gbtest_answerpk = tc.gbtest_answerfk)
                WHERE ta.gbtest_answerpk='.$pnAnswerPk;

    return $this->oDB->ExecuteQuery($sQuery);
  }

  // Returns number of active tests for a teacher

  public function getNbTestsForTeacher($paStudentIds)
  {
    if(!assert('is_array($paStudentIds) && !empty($paStudentIds)'))
      return 0;

    $sQuery = 'SELECT COUNT(*) as nb
                FROM gbtest t
                LEFT JOIN gbtest_answer ta ON t.gbtestpk = ta.gbtestfk
                WHERE ta.gbuserfk IN ('.implode(',',$paStudentIds).') AND ta.status = \'sent\'
                AND
                (t.esa = 0
                OR
                 ta.gbtest_answerpk IN
                 (
                    SELECT ta.gbtest_answerpk
                    FROM gbtest as t
                    LEFT JOIN gbtest_answer ta ON t.gbtestpk = ta.gbtestfk
                    WHERE t.esa = 1 AND ta.gbuserfk IN ('.implode(',',$paStudentIds).') AND ta.status = \'sent\'

                    GROUP BY t.gbtest_chapterfk, ta.gbuserfk

                    HAVING  count(t.gbtestpk) = 2
                 )
                )';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return 0;

    return (int)$oDbResult->getFieldValue('nb');
  }

  public function getTestsForTeacher($paStudentIds, $psTestType='', $psStatus = '')
  {
    if(!assert('is_array($paStudentIds) && !empty($paStudentIds)'))
      return new CDbResult();

    if(!assert('is_string($psTestType)'))
      return new CDbResult();

    if(!assert('is_string($psStatus)'))
      return new CDbResult();

    $sQuery = 'SELECT t.*, ta.*, gcha.name as chapter_name
                FROM gbtest t
                LEFT JOIN gbtest_answer ta ON t.gbtestpk = ta.gbtestfk
                LEFT JOIN gbtest_chapter as gcha ON (gcha.gbtest_chapterpk = t.gbtest_chapterfk)
                WHERE ta.gbuserfk IN ('.implode(',',$paStudentIds).')';

    if(!empty($psStatus))
      $sQuery .= ' AND ta.status=\''.$psStatus.'\'';
    else
      $sQuery .= ' AND ta.status!=\'draft\'';

    if($psTestType=='esa')
      $sQuery .= ' AND t.esa=1';
    elseif ($psTestType=='test')
      $sQuery .= ' AND t.esa=0';

    $sQuery .=     ' ORDER BY
                    CASE ta.status
                      WHEN \'sent\' THEN 1
                      WHEN \'returned\' THEN 2
                    END,
                    ta.date_submitted ASC,
                    ta.date_returned DESC ';

    return $this->oDB->ExecuteQuery($sQuery);
  }

  // Returns assignment history for a student

  public function getReturnedTestsForTeacher($pnUserPk, $pbNbOnly = false)
  {
    if(!assert('is_key($pnUserPk)'))
      return new CDbResult();

    if(!assert('is_bool($pbNbOnly)'))
      return new CDbResult();

    $sField = ($pbNbOnly) ? 'COUNT(*) as nb' : '*';

    $sQuery = 'SELECT '.$sField.' FROM gbtest_correction c
                LEFT JOIN gbtest_answer a ON c.gbtest_answerfk=a.gbtest_answerpk
                LEFT JOIN gbtest t ON a.gbtestfk=t.gbtestpk
                WHERE c.corrected_by='.$pnUserPk;

    return $this->oDB->ExecuteQuery($sQuery);
  }

  // ------------------------------------------------------------- //
  //                  HRMANAGER VIEW METHODS
  // ------------------------------------------------------------- //

  public function getEsaResults($pnEsa, $paStudentsIds = array())
  {
    if(!assert('is_key($pnEsa)'))
      return array();

    $sQuery = 'SELECT AVG(es.tone) as tone, AVG(es.language) as language, AVG(es.phrases) as phrases,
                      AVG(es.logic) as logic, AVG(es.layout) as layout, AVG(es.speed) as speed
                FROM gbtest_esa_score es
                  LEFT JOIN gbtest_answer ta
                    ON (ta.gbtest_answerpk=es.gbtest_answerfk';

    if(!empty($paStudentsIds))
      $sQuery.= ' AND ta.gbuserfk IN ('.  implode(',', $paStudentsIds).')';

    $sQuery.=')';

    $sQuery .= ' LEFT JOIN gbtest t ON t.gbtestpk=ta.gbtestfk
                  WHERE t.esa=1 AND t.rank='.$pnEsa;

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $oDbResult->readFirst();
    return $oDbResult->getData();
  }

  public function getStudentResults($paStudentIds, $pnGroupPk = 0)
  {
    if(!assert('is_array($paStudentIds) && !empty($paStudentIds)'))
      return array();

   /*$sQuery = 'SELECT tc.*, ta.*, t.esa,
      (SELECT COUNT(*) FROM gbtest t WHERE t.gbtest_chapterfk=tc.gbtest_chapterpk) as nbTests, tcs.deadline
      FROM gbtest_chapter tc

      INNER JOIN gbtest_chapter_group tcs ON (tc.gbtest_chapterpk = tcs.gbtest_chapterfk AND tcs.deadline <> "0000-00-00" AND tcs.deadline IS NOT NULL)
      INNER JOIN gbuser_group_member ugm ON (ugm.gbuser_groupfk = tcs.gbuser_groupfk AND ugm.gbuserfk IN ('.implode(',', $paStudentIds).'))

      LEFT JOIN gbtest t ON (t.gbtest_chapterfk = tcs.gbtest_chapterfk)
      LEFT JOIN gbtest_answer ta ON
      ( ta.gbtestfk=t.gbtestpk
        AND ta.gbuserfk = ugm.gbuserfk
        AND (ta.status=\'sent\' || ta.status=\'returned\')
      )

      GROUP BY  tcs.gbuser_groupfk, gbuserfk, t.gbtestpk, ta.gbtest_answerpk
      ORDER BY tc.rank';*/

    /*$sQuery = 'SELECT tc.*, tcs.gbuser_groupfk, ta.gbuserfk,  ta.*, t.esa, (gu.gbuserpk || gugm.gbuser_groupfk) as in_the_group, gu.gbuserpk as member,
      (SELECT COUNT(*) FROM gbtest t WHERE t.gbtest_chapterfk=tc.gbtest_chapterpk) as nbTests, tcs.deadline
      FROM gbtest_chapter tc

      LEFT JOIN gbtest_chapter_group tcs ON
	  (tc.gbtest_chapterpk = tcs.gbtest_chapterfk
        AND tcs.gbuser_groupfk IN
        (
          select ugm.gbuser_groupfk from gbuser_group_member AS ugm where ugm.gbuserfk IN ('.implode(',', $paStudentIds).')
        )
      )



      LEFT JOIN gbtest t ON (t.gbtest_chapterfk = tcs.gbtest_chapterfk)
      LEFT JOIN gbtest_answer ta ON
      ( ta.gbtestfk=t.gbtestpk
        AND ta.gbuserfk IN ('.implode(',', $paStudentIds).')
        AND (ta.status="sent" || ta.status="returned")
      )


      LEFT JOIN gbuser_group_member gugm ON (gugm.gbuser_groupfk = tcs.gbuser_groupfk)
      LEFT JOIN gbuser gu ON (gu.gbuserpk = gugm.gbuserfk AND gu.type = "student")



      GROUP BY ta.gbuserfk, tcs.gbuser_groupfk, t.gbtestpk, ta.gbtest_answerpk
      ORDER BY tc.rank';*/


    //check all the students for all each group
    $sQuery = 'SELECT * FROM  gbuser_group_member AS ugm
      INNER JOIN gbuser gu ON (gu.gbuserpk = ugm.gbuserfk AND gu.type = "student")

      WHERE ugm.gbuserfk IN ('.implode(',', $paStudentIds).')';

    if(!empty($pnGroupPk))
      $sQuery.= ' AND gbuser_groupfk = '.$pnGroupPk;
    //dump($sQuery);

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $asGroupMembers = array();
    while($bRead)
    {
      $nUserPk = (int)$oDbResult->getFieldValue('gbuserfk');
      $asGroupMembers[$oDbResult->getFieldValue('gbuser_groupfk')][$nUserPk] = $nUserPk;
      $bRead = $oDbResult->readNext();
    }

    //dump($asGroupMembers);



    //Fetch all the chapter/groups and answers if there is
    $sQuery = 'SELECT tc.*, tcs.gbuser_groupfk, ta.gbuserfk,  ta.*, t.esa,
      (SELECT COUNT(*) FROM gbtest t WHERE t.gbtest_chapterfk=tc.gbtest_chapterpk) as nbTests, tcs.deadline
      FROM gbtest_chapter tc

      LEFT JOIN gbtest_chapter_group tcs ON
	  (tc.gbtest_chapterpk = tcs.gbtest_chapterfk
        AND tcs.gbuser_groupfk IN
        (
          select ugm.gbuser_groupfk from gbuser_group_member AS ugm where ugm.gbuserfk IN ('.implode(',', $paStudentIds).')
        )
      )


      LEFT JOIN gbtest t ON (t.gbtest_chapterfk = tcs.gbtest_chapterfk)
      LEFT JOIN gbtest_answer ta ON
      ( ta.gbtestfk=t.gbtestpk
        AND ta.gbuserfk IN ('.implode(',', $paStudentIds).')
        AND (ta.status="sent" || ta.status="returned")
      )


      GROUP BY ta.gbuserfk, tcs.gbuser_groupfk, t.gbtestpk, ta.gbtest_answerpk
      ORDER BY tc.rank';


    //dump($sQuery);
    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    $aData = array();
    while($bRead)
    {
      $sDeadline = $oDbResult->getFieldValue('deadline');
      if(empty($sDeadline) || $sDeadline == 'NULL' || $sDeadline == '0000-00-00')
         $nActive = 0;
      else
         $nActive = 1;

      $nChapterPk = $oDbResult->getFieldValue('gbtest_chapterpk');
      $nGroupPk = $oDbResult->getFieldValue('gbuser_groupfk');

      if(!isset($aData[$nChapterPk]['data'][$nGroupPk]))
      {
        $aData[$nChapterPk]['data'][$nGroupPk] = array(
            'pk' => $nChapterPk,
            'rank' => $oDbResult->getFieldValue('rank'),
            'esa' => $oDbResult->getFieldValue('esa'),
            'nbtests' => $oDbResult->getFieldValue('nbTests'),
            'chapter_name' => $oDbResult->getFieldValue('name'),
            'active' => $nActive,
            'results' => array()
        );
      }

      //if it's a user group we have to treat, and the resul;t array doesn't contain yet the list of members
      // we add those here
      if(isset($asGroupMembers[$nGroupPk]) && !isset($aData[$nChapterPk]['members'][$nGroupPk]))
      {
        $aData[$nChapterPk]['members'][$nGroupPk] = $asGroupMembers[$nGroupPk];
      }


      $nStudentAnswered = (int)$oDbResult->getFieldValue('gbuserfk');
      $nStudentPk = (int)$oDbResult->getFieldValue('member');
      $aData[$nChapterPk]['members'][$nGroupPk][$nStudentPk] = $nStudentPk;

      /*dump($nActive);
      dump($nStudentPk);
      dump($nStudentAnswered);
      dump($nInGroup);
      dump('- - - - - - - - -');*/

      if(is_key($nStudentAnswered))
      {
         if(!isset($aData[$nChapterPk]['data'][$nGroupPk]['results'][$nStudentAnswered]))
          $aData[$nChapterPk]['data'][$nGroupPk]['results'][$nStudentAnswered] = 0;

        //inactive assignment
        if($nActive == 0)
          $aData[$nChapterPk]['data'][$nGroupPk]['results'][$nStudentAnswered] = -1;
        else
          $aData[$nChapterPk]['data'][$nGroupPk]['results'][$nStudentAnswered]++;
      }

      $bRead = $oDbResult->readNext();
    }
    //dump($aData);

    return $aData;
  }

  public function getStudentSheet($pnStudentPk, $pnGroupFk)
  {
    if(!assert('is_key($pnStudentPk)'))
      return array();

    if(!assert('is_key($pnGroupFk)'))
      return array();

    $sQuery = 'SELECT t.*, ta.*, tc.name as chaptername, tcg.deadline
                FROM gbtest t
                LEFT JOIN gbtest_answer ta ON (t.gbtestpk = ta.gbtestfk AND ta.gbuserfk='.$pnStudentPk.')
                LEFT JOIN gbtest_chapter tc ON t.gbtest_chapterfk = tc.gbtest_chapterpk
                LEFT JOIN gbtest_chapter_group tcg ON (tcg.gbtest_chapterfk=tc.gbtest_chapterpk AND tcg.gbuser_groupfk='.$pnGroupFk.')
                  ORDER BY t.esa, t.rank';

    $oDbResult = $this->oDB->ExecuteQuery($sQuery);
    $bRead = $oDbResult->readFirst();
    if(!$bRead)
      return array();

    return $this->formatOdbResult($oDbResult, 'gbtestpk');
  }


  // ------------------------------------------------------------- //
  //                  GBAMIN VIEW METHODS
  // ------------------------------------------------------------- //

  public function deleteTests($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return false;

    $sSqlSelect = 'SELECT gbtest_answerpk FROM gbtest_answer WHERE gbuserfk='.$pnUserPk;

    $sQuery = 'DELETE FROM gbtest_correction WHERE gbtest_answerfk IN ('.$sSqlSelect.'); ';
    $this->oDB->ExecuteQuery($sQuery);
    $sQuery = 'DELETE FROM gbtest_answer WHERE gbuserfk='.$pnUserPk.';';
    $this->oDB->ExecuteQuery($sQuery);

    return true;
  }

  public function deleteCorrections($pnUserPk)
  {
    if(!assert('is_key($pnUserPk)'))
      return false;

    $sSqlSelect = 'SELECT gbtest_correctionpk FROM gbtest_correction WHERE corrected_by='.$pnUserPk;

    $sQuery = 'DELETE FROM gbtest_correction_point WHERE gbtest_correctionfk IN ('.$sSqlSelect.'); ';
    $this->oDB->ExecuteQuery($sQuery);
    $sQuery = 'DELETE FROM gbtest_correction WHERE corrected_by='.$pnUserPk.';';
    $this->oDB->ExecuteQuery($sQuery);

    return true;
  }





   /**
    * fetch all tests belonging to groups that have the ESA2
    * @param type $pnUserPk
    * @return type
    */
   public function getNbTestForEsaGroup($pnUserPk)
   {
     //esa2 = chapter 4
      $sQuery = '
        SELECT gcgr.gbuser_groupfk, count(*) as nb_test
        FROM gbtest_chapter_group gcgr
        INNER JOIN gbtest as gbte ON (gbte.gbtest_chapterfk = gcgr.gbtest_chapterfk)

        WHERE gcgr.gbuser_groupfk IN
        (
          SELECT DISTINCT(gcgr.gbuser_groupfk) FROM gbtest_chapter_group gcgr
          INNER JOIN gbuser_group as ggro ON (ggro.gbuser_grouppk = gcgr.gbuser_groupfk)
          WHERE gcgr.gbtest_chapterfk = 4
          AND ggro.active = 1
          AND gcgr.deadline IS NOT NULL
          AND gcgr.deadline <> "0000-00-00"
        )
        AND gcgr.deadline IS NOT NULL
        AND gcgr.deadline <> "0000-00-00"

  GROUP BY gcgr.gbuser_groupfk
';
     //dump($sQuery);
     $oDbResult =  $this->oDB->ExecuteQuery($sQuery);
     return $oDbResult->getAll();
   }

   public function getNbStudentAnswerByGroup($panGroup)
   {
     //esa2 = chapter 4
      $sQuery = '
        SELECT DISTINCT gcgr.gbuser_groupfk, gans.gbuserfk, SUM(IF(gans.status = "returned", 1, 0)) as nb_answer, slog.*, gcgr.deadline

        FROM gbtest_answer as gans
        INNER JOIN gbtest as gbte ON (gbte.gbtestpk = gans.gbtestfk)
        INNER JOIN gbtest_chapter_group as gcgr ON (gcgr.gbtest_chapterfk = gbte.gbtest_chapterfk
        AND gcgr.gbuser_groupfk IN ('.implode(',', $panGroup).')
        AND gcgr.deadline IS NOT NULL
        AND gcgr.deadline <> "0000-00-00")

        INNER JOIN gbuser_group as ggro ON (ggro.gbuser_grouppk = gcgr.gbuser_groupfk)
        INNER JOIN gbuser_group_member as ggme ON (ggme.gbuser_groupfk = gcgr.gbuser_groupfk AND ggme.gbuserfk = gans.gbuserfk)

        INNER JOIN gbuser as gbus ON (gbus.gbuserpk = gans.gbuserfk)
        INNER JOIN shared_login as slog ON (slog.loginpk = gbus.loginfk)

        WHERE gcgr.gbuser_groupfk IN ('.implode(',', $panGroup).')


        GROUP BY gcgr.gbuser_groupfk, gans.gbuserfk
        ORDER BY gans.gbuserfk ';

     //dump($sQuery);
     $oDbResult =  $this->oDB->ExecuteQuery($sQuery);
     return $oDbResult->getAll();
   }

}