<?php


if(!empty($_POST))
{
  /*echo '<pre>';
  var_dump($_POST);
  echo '</pre><br /><br />';*/

  $asRight = array();
  $asRight['table'] = null;
  $asRight['alias'] = null;
  $asRight['select'] = null;
  $asRight['left'] = null;
  $asRight['inner'] = null;
  $asRight['outer'] = null;
  $asRight['where'] = null;
  $asRight['order'] = null;
  $asRight['group'] = null;
  $asRight['limit'] = null;



  if(!empty($_POST['table']))
  {
    $asRight['table'] = $_POST['table'];
  }

  if(!empty($_POST['alias']))
  {
    $asRight['alias'] = $_POST['alias'];
  }

  if(!empty($_POST['select']))
  {
    $asRight['select'] = $_POST['select'];
  }

  if(!empty($_POST['where']))
  {
    $asRight['where'] = $_POST['where'];
    if(strtolower(substr(trim($asRight['where']), 0, 3)) != 'and')
      $asRight['where'] = ' AND('.$sValue.') ';
  }
  if(!empty($_POST['order']))
  {
    $asRight['order'] = $_POST['order'];
  }
  if(!empty($_POST['group']))
  {
    $asRight['group'] = $_POST['group'];
  }
  if(!empty($_POST['limit']))
  {
    $asRight['limit'] = $_POST['limit'];
  }

  foreach($_POST['join_type'] as $nCount => $sType)
  {

    $sTable = $_POST['join_table'][$nCount];
    $sAlias = $_POST['join_alias'][$nCount];
    $sClause = $_POST['join_clause'][$nCount];

    if(!empty($sTable) && !empty($sAlias) && !empty($sClause))
    {
      $asRight[strtolower($sType)][] = array('table' => $sTable, 'alias' => $sAlias, 'clause' => $sClause);
    }
  }

  echo '<pre>';
    var_dump($asRight);
  echo '</pre><br /><br />';

  $sSerialize = serialize($asRight);
  var_dump(json_encode($asRight));
    echo '<br /><br />';

  echo 'INSERT INTO `right` (`rightpk`, `label`, `description`, `type`, `data`, `cp_uid`, `cp_type`) VALUES
    ("'.$_POST['rightpk'].'", "'.$_POST['label'].'", "'.$_POST['description'].'", "'.$_POST['type'].'", \''.$sSerialize.'\', "", ""); ';

  echo '<br /><br />UPDATE `right` SET `label` =  "'.$_POST['label'].'",
    `description` = "'.$_POST['description'].'",
    `type` = "'.$_POST['type'].'",
    `data` =  \''.$sSerialize.'\'
      WHERE `rightpk` = "'.$_POST['rightpk'].'"; ';

  echo '<br /><br />';

  var_dump(unserialize($sSerialize));


  echo '<br /><hr /><br /><br /><br /><br /><br />';

}
else
{
  $_POST['table'] = null;
  $_POST['alias'] = null;
  $_POST['select'] = null;
  $_POST['left'] = null;
  $_POST['inner'] = null;
  $_POST['outer'] = null;
  $_POST['where'] = 'AND ';
  $_POST['order'] = null;
  $_POST['group'] = null;
  $_POST['limit'] = null;

  $_POST['rightpk'] = 'XXX';
  $_POST['label'] = 'Access ...';
  $_POST['description'] = 'Restrict access to ...';
  $_POST['type'] = 'data';
}

//var_dump(unserialize('a:6:{s:5:"table";s:12:"sl_candidate";s:5:"alias";s:4:"scan";s:4:"left";a:0:{}s:5:"inner";a:0:{}s:5:"outer";a:0:{}s:5:"where";s:28:"scan.sl_candidatepk > 225000";}'));
/*array(6)
{
  ["table"]=> string(12) "sl_candidate"
  ["alias"]=> string(4) "scan"
  ["left"]=> array(0) { }
  ["inner"]=> array(0) { }
  ["outer"]=> array(0) { }
  ["where"]=> string(28) "scan.sl_candidatepk > 225000"
}*/

//var_dump(unserialize('a:6:{s:5:"table";s:12:"sl_candidate";s:5:"alias";s:4:"scan";s:4:"left";a:0:{}s:5:"inner";a:1:{i:0;a:4:{s:5:"table";s:20:"sl_candidate_profile";s:5:"alias";s:4:"scpr";s:6:"clause";s:93:"scpr.candidatefk = scan.sl_candidatepk AND scpr.companyfk IS NOT NULL AND scpr.companyfk <> 0";s:3:"sql";s:139:"INNER JOIN sl_candidate_profile as scpr ON (scpr.candidatefk = scan.sl_candidatepk AND scpr.companyfk IS NOT NULL AND scpr.companyfk <> 0) ";}}s:5:"outer";a:0:{}s:5:"where";s:51:" scpr.companyfk IS NOT NULL AND scpr.companyfk <> 0";}'));
/*array(6)
{
   ["table"]=> string(12) "sl_candidate"
   ["alias"]=> string(4) "scan"
   ["left"]=> array(0) { }
   ["inner"]=> array(1)
   {
     [0]=> array(4)
     {
       ["table"]=> string(20) "sl_candidate_profile"
       ["alias"]=> string(4) "scpr"
       ["clause"]=> string(93) "scpr.candidatefk = scan.sl_candidatepk AND scpr.companyfk IS NOT NULL AND scpr.companyfk <> 0"
       ["sql"]=> string(139) "INNER JOIN sl_candidate_profile as scpr ON (scpr.candidatefk = scan.sl_candidatepk AND scpr.companyfk IS NOT NULL AND scpr.companyfk <> 0) "
     }
   }
   ["outer"]=> array(0) { }
   ["where"]=> string(51) " scpr.companyfk IS NOT NULL AND scpr.companyfk <> 0"
}
*/

?>


<html>
  <head>
    <style>
      form input[type=text]
      {
        width: 850px;
        float: left;
        font-size: 11px;
      }

      form select
      {
        width: 150px;
        float: left;
      }

      form input.medium
      {
        max-width: 500px;
      }

      form input.short
      {
        max-width: 400px;
      }

      form input.shorter
      {
        max-width: 150px;
      }
      form input.shortest
      {
        max-width: 60px;
      }

      form span.inlineLegend
      {
        float: left;
        width: 75px;
        font-size: 11px;
        font-style: italic;
        color: #999;
        text-align: center;
      }
      form span.legend
      {
        display: block;
        width: 150px;
        float: left;
        margin-right: 25px;
        text-align: right;
      }

      br
      {
        clear: both;
      }
    </style>

  </head>
  <body>


  <form method="POST" action="/component/right/resources/right_generator.php5">


  <span class="legend">RightPk:</span><input type="text" name="rightpk" value="<?php echo $_POST['rightpk']; ?>"><br />
  <span class="legend">Label:</span><input type="text" name="label" value="<?php echo $_POST['label']; ?>"><br />
  <span class="legend">Description:</span><input type="text" name="description" value="<?php echo $_POST['description']; ?>"><br />
  <span class="legend">Type:</span><input type="text" name="type" value="<?php echo $_POST['type']; ?>"><br />
  <br /><hr /><br />

  <span class="legend">Table:</span><input type="text" name="table" value="<?php echo $_POST['table']; ?>" onblur="

   var asWords = this.value.trim().split('_');
   console.log(asWords);

   var nWords = asWords.length;
   console.log(nWords);

   if(nWords >= 4)
   {
     sAlias = asWords[0].substring(0,1)+asWords[1].substring(0,1)+asWords[2].substring(0,1)+asWords[3].substring(0,1);
   }

   if(nWords >= 3)
   {
     sAlias = asWords[0].substring(0,1)+asWords[1].substring(0,1)+asWords[2].substring(0,2);
   }

   if(nWords == 2)
   {
     sAlias = asWords[0].substring(0,1)+asWords[1].substring(0,3);
   }

   if(nWords == 1)
   {
     sAlias = asWords[0].substring(0,4);
   }

   document.getElementById('alias').value = sAlias;

  "><br />

  <span class="legend">Alias:</span><input type="text" id="alias" name="alias" value="<?php echo $_POST['alias']; ?>"><br />
  <span class="legend">Select:</span><input type="text" name="select" value="<?php echo $_POST['select']; ?>"><br /><br />

  <span class="legend">Join:</span><select name="join_type[]">
            <option value="left">left join</option>
            <option value="inner">inner join</option>
            <option value="outer">outer join</option>
        </select>
    <span class="inlineLegend">table</span><input type="text" class="shorter" name="join_table[]">
    <span class="inlineLegend">alias</span><input type="text" class="shortest" name="join_alias[]">
    <span class="inlineLegend">clause</span><input type="text" class="short" name="join_clause[]"><br />

    <span class="legend">Join:</span><select name="join_type[]">
            <option value="left">left join</option>
            <option value="inner">inner join</option>
            <option value="outer">outer join</option>
        </select>
    <span class="inlineLegend">table</span><input type="text" class="shorter" name="join_table[]">
    <span class="inlineLegend">alias</span><input type="text" class="shortest" name="join_alias[]">
    <span class="inlineLegend">clause</span><input type="text" class="short" name="join_clause[]"><br />

    <span class="legend">Join:</span><select name="join_type[]">
            <option value="left">left join</option>
            <option value="inner">inner join</option>
            <option value="outer">outer join</option>
        </select>
    <span class="inlineLegend">table</span><input type="text" class="shorter" name="join_table[]">
    <span class="inlineLegend">alias</span><input type="text" class="shortest" name="join_alias[]">
    <span class="inlineLegend">clause</span><input type="text" class="short" name="join_clause[]"><br /><br />



  <span class="legend">Where:</span><input type="text" name="where" value="<?php echo $_POST['where']; ?>"><br /><br />

  <span class="legend">Order:</span><input type="text" name="order" value="<?php echo $_POST['order']; ?>"><br />
  <span class="legend">Group:</span><input type="text" name="group" value="<?php echo $_POST['group']; ?>"><br />
  <span class="legend">Limit:</span><input type="text" name="limit" value="<?php echo $_POST['limit']; ?>"><br />
  <br /><hr /><br />

  <input type="submit" value="Serialize">

  </form>


</body>
</html>
