<?php
/**
 * Version 2 of the script: V1 rss feeed disappeared, so I made an other version looking at 2 different feeds :)
 */

// =============================================
$oDb = CDependency::getComponentByName('database');
$gafCurrency = array();
$bUpdateDb = !(bool)getValue('no-db-update', 0);


$sUrl = "http://jpy.fxexchangerate.com/rss.xml";
$oXml = simplexml_load_file($sUrl);
if($oXml)
{
  $oCurrency = $oXml->channel->item;
	foreach($oCurrency as $oCurDetail)
	{
    //<description>1 Japanese Yen = 0.01280 Bahamian Dollar</description>
    $sRefCurrency = $oCurDetail->title;
    $asRefCurrency = explode('/', $sRefCurrency);

    if(count($asRefCurrency) != 2 || preg_match('/\(JPY\)$/', $asRefCurrency[0]) === false)
    {
      assert('false; // error fetching currency ['.$asRefCurrency[0].']'.__LINE__);
    }
    else
    {
      //dump($oCurDetail);

      $sRefCurrency = substr($asRefCurrency[1], -5, 5);
      //dump($sRefCurrency);
      $sRefCurrency = trim(str_replace(array('(', ')'), '', $sRefCurrency));
      //dump($sRefCurrency);

      $sValue = $oCurDetail->description;
      $sValue = str_replace('1 Japanese Yen = ', '', $sValue);
      $sValue = trim(preg_replace('/[^0-9\.]/', '', $sValue));


      if(empty($sValue) || empty($sRefCurrency) || !is_numeric($sValue))
        assert('false; // error fetching currency ['.$oCurDetail->description.']'.__LINE__);
      else
      {
        $gafCurrency[strtolower($sRefCurrency)] = (float)$sValue;


        if($bUpdateDb && strtolower($sRefCurrency) != 'jpy')
        {
          //update database currency_rate
          $sQuery = 'UPDATE sl_candidate_profile SET currency_rate = \''.(float)$sValue.'\' WHERE currency = \''.strtolower($sRefCurrency).'\' ';
          $fStart = microtime(true);
          $oDbResult = $oDb->executeQuery($sQuery);
          $fEnd = microtime(true);

          echo round((($fEnd - $fStart)*1000), 2).'msec; '.$sQuery.'<br />';
          if(!$oDbResult)
          {
            assert('false; // error updating candidate currency ['.strtolower($sRefCurrency).']'.__LINE__);
          }
        }
      }
    }
  }

  dump('fxexchangerate');
  dump($gafCurrency);
}


if(empty($gafCurrency) || count($gafCurrency) < 3 || !isset($gafCurrency['jpy']))
{
  assert('false; //main currency converter failed. Trying canadian bank.');

  $sUrl = "http://www.bankofcanada.ca/stats/assets/rates_rss/noon/en_all.xml";
  $oXml = simplexml_load_file($sUrl);

  if($oXml)
  {
    $oCurrency = $oXml->item;
    foreach($oCurrency as $oCurDetail)
    {
      //<description>1 Japanese Yen = 0.01280 Bahamian Dollar</description>
      $sRefCurrency = $oCurDetail->title;
      $asRefCurrency = explode('=', $sRefCurrency);

      if(count($asRefCurrency) != 2 || preg_match('/^CA: ([0-9\.]{1,9}) [A-Z]{3}/', $asRefCurrency[0]) === false)
      {
        assert('false; // error fetching currency ['.$asRefCurrency[0].']'.__LINE__);
      }
      else
      {
        $sRefCurrency = $asRefCurrency[0];

        $sDetail = $oCurDetail->description;
        $asDetail = explode('(', $sDetail);

        if(count($asDetail) < 2)
          assert('false; // error fetching currency ['.$sDetail.'] line '.__LINE__);
        else
        {
          $asDetail[0] = str_ireplace('1 Canadian Dollar =', '', $asDetail[0]);

          $fValue = trim(preg_replace('/[^0-9\.]/', '', $asDetail[0]));
          $sRefCurrency = trim(preg_replace('/[^A-Z]/', '', $asDetail[0]));

          $gafCurrency[strtolower($sRefCurrency)] = (float)$fValue;
        }
      }
    }
  }



  if(empty($gafCurrency) || !isset($gafCurrency['jpy']))
  {
    assert('false; // second currency converter didn not work');
  }
  else
  {
    //conver the array from canadian based to jpy based array
    $fRate = (1/$gafCurrency['jpy']);

    foreach($gafCurrency as $sCurrency => $fValue)
    {
      if($sCurrency == 'jpy')
        $gafCurrency[$sCurrency] = 1;
      else
        $gafCurrency[$sCurrency] = round(($fValue * $fRate), 5);


      if($bUpdateDb)
      {
        //update database currency_rate
        $sQuery = 'UPDATE sl_candidate_profile SET currency_rate = \''.(float)$sValue.'\' WHERE currency = \''.strtolower($sCurrency).'\' ';
        $fStart = microtime(true);
        $oDbResult = $oDb->executeQuery($sQuery);
        $fEnd = microtime(true);

        echo round((($fEnd - $fStart)*1000), 2).'msec; '.$sQuery.'<br />';
        if(!$oDbResult)
        {
          assert('false; // error updating candidate currency ['.strtolower($sRefCurrency).']'.__LINE__);
        }
      }
    }

    dump('bank of canada');
    dump($gafCurrency);
  }
}




if(empty($gafCurrency) || count($gafCurrency) < 3 || !isset($gafCurrency['jpy']))
{
  //ref from 07-02-2014
  $gafCurrency = array(
  'aud' => 0.01096,
  'all' => 1.01249,
  'dzd' => 0.76828,
  'ars' => 0.07719,
  'awg' => 0.01754,
  'gbp' => 0.006,
  'bsd' => 0.0098,
  'bhd' => 0.00369,
  'bdt' => 0.76079,
  'bbd' => 0.0196,
  'byr' => 94.89051,
  'bzd' => 0.0195,
  'bmd' => 0.0098,
  'btn' => 0.61096,
  'bob' => 0.0677,
  'bwp' => 0.08867,
  'brl' => 0.02336,
  'bnd' => 0.01243,
  'bgn' => 0.0143,
  'bif' => 15.22559,
  'cad' => 0.01084,
  'cny' => 0.05941,
  'khr' => 39.12213,
  'cve' => 0.78479,
  'kyd' => 0.00803,
  'xof' => 4.75236,
  'xaf' => 4.72893,
  'clp' => 5.40812,
  'cop' => 20.09014,
  'kmf' => 3.5467,
  'crc' => 4.99143,
  'hrk' => 0.05517,
  'cup' => 0.0098,
  'czk' => 0.19837,
  'eur' => 0.00721,
  'dkk' => 0.0538,
  'djf' => 1.77093,
  'dop' => 0.4211,
  'xcd' => 0.02645,
  'egp' => 0.0682,
  'svc' => 0.08571,
  'eek' => 0.11281,
  'etb' => 0.18858,
  'fkp' => 0.006,
  'fjd' => 0.01832,
  'hkd' => 0.07603,
  'idr' => 119.25734,
  'inr' => 0.61015,
  'gmd' => 0.37329,
  'gtq' => 0.07639,
  'gnf' => 66.89855,
  'gyd' => 2.01538,
  'htg' => 0.43149,
  'hnl' => 0.1945,
  'huf' => 2.21467,
  'isk' => 1.13291,
  'irr' => 244.02097,
  'iqd' => 11.3849,
  'ils' => 0.03456,
  'jpy' => 1,
  'jmd' => 1.05374,
  'jod' => 0.00691,
  'kzt' => 1.52359,
  'kes' => 0.84307,
  'krw' => 10.53495,
  'kwd' => 0.00277,
  'lak' => 78.71944,
  'lvl' => 0.00498,
  'lbp' => 14.72101,
  'lsl' => 0.10792,
  'lrd' => 0.8377,
  'lyd' => 0.01232,
  'ltl' => 0.02489,
  'mop' => 0.07831,
  'mkd' => 0.44676,
  'mwk' => 4.213,
  'myr' => 0.03255,
  'mvr' => 0.15079,
  'mro' => 2.83643,
  'mur' => 0.29783,
  'mxn' => 0.13007,
  'mdl' => 0.13242,
  'mnt' => 16.83731,
  'mad' => 0.08093,
  'mmk' => 9.67031,
  'nad' => 0.10907,
  'npr' => 0.97771,
  'ang' => 0.01754,
  'nzd' => 0.01189,
  'nio' => 0.2543,
  'ngn' => 1.60094,
  'kpw' => 8.81791,
  'nok' => 0.06089,
  'omr' => 0.00377,
  'xpf' => 0.86347,
  'pkr' => 1.03238,
  'pab' => 0.0098,
  'pgk' => 0.02501,
  'pyg' => 45.50767,
  'pen' => 0.0277,
  'php' => 0.44207,
  'pln' => 0.03015,
  'qar' => 0.03568,
  'ron' => 0.03219,
  'rub' => 0.34008,
  'rwf' => 6.66732,
  'chf' => 0.00882,
  'wst' => 0.02306,
  'std' => 177.87684,
  'sar' => 0.03675,
  'scr' => 0.11865,
  'sll' => 42.52192,
  'sgd' => 0.01243,
  'skk' => 0.21757,
  'sbd' => 0.07128,
  'sos' => 10.85583,
  'zar' => 0.10812,
  'lkr' => 1.28007,
  'shp' => 0.006,
  'sdg' => 0.05568,
  'szl' => 0.10878,
  'sek' => 0.0637,
  'syp' => 1.40205,
  'usd' => 0.0098,
  'thb' => 0.32131,
  'try' => 0.02165,
  'twd' => 0.29765,
  'tzs' => 15.87714,
  'top' => 0.01845,
  'ttd' => 0.0628,
  'tnd' => 0.01571,
  'aed' => 0.03598,
  'ugx' => 24.20026,
  'uah' => 0.08578,
  'uyu' => 0.21751,
  'vuv' => 0.94058,
  'vnd' => 206.53505,
  'yer' => 2.10459,
  'zmk' => 50.84505,
  'zwd' => 3.54578,
  'vef' => 0.0616,
  'uzs' => 21.57645,
  'kgs' => 0.49759,
  'ghs' => 0.02359,
  );
}




$oFs = fopen($_SERVER['DOCUMENT_ROOT'].'/component/sl_candidate/resources/currency/currency_list.inc.php5', 'w+');
if($oFs)
{
  fwrite($oFs, "<?php \n /*date: ".date('Y-m-d H:i:s')." \n */ \n\n" . '$gafCurrencyRate = '.var_export($gafCurrency, true).';');
  fclose($oFs);
  echo count($gafCurrency).' currencyUpdated.<br />';
}

echo 'test file 1/3 : empty array <br />';
unset($gafCurrency);

if(!include_once($_SERVER['DOCUMENT_ROOT'].'/component/sl_candidate/resources/currency/currency_list.inc.php5'))
{
  assert('false; // can not open the just created currency file.');
}
else
{
  echo 'test file 2/3 : here an readble<br />';

  if(empty($gafCurrencyRate) || count($gafCurrencyRate) < 3 || !isset($gafCurrencyRate['jpy']))
  {
    assert('false; // currency file just created is empty.');
  }
  else
    echo 'test file 3/3 : array here and not empty<br />';
}
