<?php

//--------------------------------------------------------------------------
class A
{
  function __construct()
  {
        /*trace('gaaaaaaa '.rand(999999, 999999999));
        $sHTML.= '<br /><br /><br /><br />';
        $sHTML.= $this->_testTemplate1($oDisplay);
        $sHTML.= '<br /><hr /><br />';
        $sHTML.= $this->_testTemplate2($oDisplay);
        $sHTML.= '<br /><hr /><br />';
        $sHTML.= $this->_testTemplate3($oDisplay);
        $sHTML.= '<br /><hr /><br />';
        $sHTML.= $this->_testTemplate4($oDisplay);
        trace('guuuuuu '.rand(999999, 999999999));
        $sHTML.= $this->_testTemplate5($oDisplay);
        $sHTML.= $this->_testTemplate6($oDisplay);
        $sHTML.= $this->_testTemplate7($oDisplay);
        $sHTML.= $this->_testTemplate8($oDisplay);
        $sHTML.= $this->_testTemplate9($oDisplay);
        $sHTML.= $this->_testTemplate10($oDisplay);*/

        $sHTML = $this->_testTemplate11();

      /*$sHTML.= '<script> goPopup.setLayerFromAjax(null, "https://slistem.devserv.com/index.php5?uid=665-544&ppa=ppaa&ppt=stgusr&ppk=0&pg=ajx"); </script>';
          $sHTML.= '<script> goPopup.setPopupMessage("fsdfsdfsdf"); </script>';
          $sHTML.= '<script> goPopup.setPopupMessage("sdfew wer we rwe"); </script>';
          $sHTML.= '<script> goPopup.setPopupMessage("fsdfe rwer htyi oloup sdfsdf"); </script>';

          $sHTML.= '<script> goPopup.setNotice("fsdfe rwer htyi oloup sdfsdf", {delay: 95000}); </script>';

          $sHTML.= '<script> setTimeout(\' console.log("remove layers ..."); goPopup.removeByType("layer"); \', 5000); </script>';
          $sHTML.= '<script> setTimeout(\' console.log("remove layers ..."); goPopup.removeByType("msg"); \', 12000); </script>';
          $sHTML.= '<script> setTimeout(\' console.log("remove notice ..."); goPopup.removeByType("notice"); \', 20000);

      sPopupId = goPopup.setNotice("88888   disapear 9500ms", {delay: 9500, url: \'#aaa\'});

      </script>';*/

  }




    private function _testTemplate1($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 100; $nKey++)
      {
        $asData[$nKey] = array('a' =>rand(999, 9999), 'b' => '<span style="color: green;"><em>'.rand(999, 9999).'</em></span>' , 'c' => rand(999, 9999),
            'e' => rand(999, 9999), 'f' => rand(99999, 9999999),
            'g' => htmlentities(chr(rand(80, 120)).chr(rand(20, 120)).chr(rand(20, 120)).chr(rand(20, 120)).chr(rand(20, 120)).chr(rand(20, 120))));
      }

      /* $oTemplate = $this->_oDisplay->getTemplate('CTemplatePage', array());
      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');*/



      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtRow'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplatePageList', $asParam);

      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');

      $oConf->addColumn('aaaaaaa', 'a', array('id' => 'aaaaaa', 'onclick' => 'alert(\'aaaaaa\');'));
      $oConf->addColumn('bbbbbbb', 'b', array('id' => 'bbbbbbb', 'onclick' => '', 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('ddddddd', 'd', array('id' => 'ddddddd', 'onclick' => '', 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('eeeeeee', 'e', array('id' => 'eeeeeee', 'onclick' => 'alert(\'eeeeeee\');'));
      $oConf->addColumn('fffffff', 'f', array('id' => 'fffffff', 'onclick' => '', 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('gggggggggggggg', 'g', array('id' => 'ggggggg', 'sortable'=> array('javascript' => 1)));

      $oConf->addBlocMessage('First page-list template is test...', array('style' => 'cursor: help'), 'message');

      $oConf->setPagerTop(false);
      $oConf->setPagerBottom(false);


      $sHTML = $oTemplate->getDisplay($asData, array('text' => 'Template page list', 'picture' => '/common/pictures/skull.png'));


      //TODO: test with template in other components
      //TODO: test with sub-template in other components

      return $sHTML;
    }


    private function _testTemplate2($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 5; $nKey++)
      {
        $asData[$nKey] = array('a' =>rand(999, 9999), 'b' => rand(999, 9999) , 'c' => rand(999, 9999),
            'e' => rand(999, 9999), 'f' => rand(99999, 9999999));
      }


      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtBloc'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');

      $oConf->setRenderingOption('full', 'full', 'float');

      $oConf->addColumn('aaaaaaa', 'a', array('id' => 'aaaaaa', 'onclick' => 'alert(\'aaaaaa\');'));
      $oConf->addColumn('bbbbbbb', 'b', array('id' => 'bbbbbbb', 'onclick' => '', 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('ddddddd', 'd', array('id' => 'ddddddd', 'onclick' => 'alert(\'ddddddddd\');'));
      $oConf->addColumn('eeeeeee', 'e', array('id' => 'eeeeeee', 'onclick' => 'alert(\'eeeeeee\');'));
      $oConf->addColumn('fffffff', 'f', array('id' => 'fffffff', 'onclick' => '', 'sortable'=> array('javascript' => 1)));


      $oConf->addBlocMessage('Search result: blocccccckkkkks ', array('style' => 'cursor: help'), 'notice');

      $oConf->setPagerTop(true, 'left', 7200, '#');
      $oConf->setPagerBottom(true, 'left', 7200, '#');


      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');

      return $sHTML;
    }


    private function _testTemplate3($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 5; $nKey++)
      {
        $asData[$nKey] = array('a' =>rand(999, 9999), 'b' => rand(999, 9999) , 'c' => rand(999, 9999),
            'e' => rand(999, 9999), 'f' => rand(99999, 9999999), 'g' => uniqid(rand(999, 9999), true));
      }



      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtRow'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');

      $oConf->addColumn('aaaaaaa', 'a', array('id' => 'aaaaaa', 'onclick' => 'alert(\'aaaaaa\');', 'width' => 250));
      $oConf->addColumn('bbbbbbb', 'b', array('id' => 'bbbbbbb', 'onclick' => 'alert(\'bbbbbbbbb\');'));
      $oConf->addColumn('ddddddd', 'd', array('id' => 'ddddddd', 'onclick' => 'alert(\'ddddddddd\');'));
      $oConf->addColumn('eeeeeee', 'e', array('id' => 'eeeeeee', 'onclick' => 'alert(\'eeeeeee\');'));
      $oConf->addColumn('ffffffffffffffffff', 'f', array('id' => 'fffffff', 'sortable'=> array('javascript' => 1)));
      $oConf->addColumn('ggggggg', 'g', array('id' => 'ggggggg', 'width' => 300));

      $oConf->addBlocMessage('Search result: ', array('style' => 'cursor: crossair'), 'title');

      $oConf->setPagerTop(true, 'right', count($asData), '#');
      $oConf->setPagerBottom(true, 'right', count($asData), '#');


      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');

      return $sHTML;
    }

    private function _testTemplate4($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 5; $nKey++)
      {
        $asData[$nKey] = array('a' =>rand(999, 9999), 'b' => rand(999, 9999) , 'c' => rand(999, 9999),
            'e' => rand(999, 9999), 'f' => rand(99999, 9999999));
      }



      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtRow'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');

      $oConf->setRenderingOption('full', 'full', 'full');

      $oConf->addColumn('aaaaaaa', 'a', array('id' => 'aaaaaa', 'onclick' => 'alert(\'aaaaaa\');'));
      $oConf->addColumn('bbbbbbb', 'b', array('id' => 'bbbbbbb', 'onclick' => 'alert(\'bbbbbbbbb\');'));
      $oConf->addColumn('ddddddd', 'd', array('id' => 'ddddddd', 'onclick' => 'alert(\'ddddddddd\');'));
      $oConf->addColumn('eeeeeee', 'e', array('id' => 'eeeeeee', 'onclick' => 'alert(\'eeeeeee\');'));
      $oConf->addColumn('fffffff', 'f', array('id' => 'fffffff', 'onclick' => 'alert(\'fffffff\');'));

      $oConf->addBlocMessage('Search  ', array('style' => 'cursor: help'), 'message');
      $oConf->addBlocMessage('result ', array('style' => 'cursor: help'), 'notice');
      $oConf->addBlocMessage(':::TITLE::: ', array('style' => 'cursor: help'), 'title');
      $oConf->addBlocMessage(':::TITLE::: ', array('style' => 'cursor: help'), 'big_title');


      $oConf->setPagerTop(true, 'center', 6102, '#');
      $oConf->setPagerBottom(true, 'center', 6120, '#');


      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');

      return $sHTML;
    }


    private function _testTemplate5($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 5; $nKey++)
      {
        $asData[$nKey] = array('a' =>rand(999, 9999), 'b' => rand(999, 9999) , 'c' => rand(999, 9999),
            'e' => rand(999, 9999), 'f' => rand(99999, 9999999), 'g' => uniqid(rand(999, 9999), true),
            'h' =>rand(999, 9999), 'i' =>rand(999, 9999), 'j' =>rand(999, 9999),
            );
      }



      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtRow'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');

      //$oConf->addColumn('aaaaaaa', 'a', array('id' => 'aaaaaa', 'onclick' => 'alert(\'aaaaaa\');', 'width' => 250));
      $oConf->addColumn('aaaaaaa', 'a', array('id' => 'aaaaaa', 'onclick' => 'alert(\'aaaaaa\');'));
      $oConf->addColumn('bbbbbbb', 'b', array('id' => 'bbbbbbb', 'onclick' => 'alert(\'bbbbbbbbb\');'));
      $oConf->addColumn('ddddddd', 'd', array('id' => 'ddddddd', 'onclick' => 'alert(\'ddddddddd\');'));
      $oConf->addColumn('eeeeeee', 'e', array('id' => 'eeeeeee', 'onclick' => 'alert(\'eeeeeee\');'));
      $oConf->addColumn('ffffffffffffffffff', 'f', array('id' => 'fffffff', 'sortable'=> array('javascript' => 1)));
      //$oConf->addColumn('ggggggg', 'g', array('id' => 'ggggggg', 'width' => 300));
      $oConf->addColumn('ggggggg', 'g', array('id' => 'ggggggg'));
      $oConf->addColumn('hhhhhhh', 'h', array('id' => 'hhhhhhh'));
      $oConf->addColumn('iiiiiii', 'i', array('id' => 'iiiiiii'));
      $oConf->addColumn('jjjjjjj', 'j', array('id' => 'jjjjjjj'));


      $oConf->addBlocMessage('Search result: ', array('style' => 'cursor: crossair'), 'title');

      $oConf->setPagerTop(true, 'right', count($asData), '#');
      $oConf->setPagerBottom(true, 'right', count($asData), '#');


      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');

      return $sHTML;
    }


    private function _testTemplate6($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 5; $nKey++)
      {
        $asData[$nKey] = array('a' =>rand(999, 9999), 'b' => rand(999, 9999) , 'c' => rand(999, 9999),
            'e' => rand(999, 9999), 'f' => rand(99999, 9999999), 'g' => uniqid(rand(999, 9999), true),
            'h' =>rand(999, 9999), 'i' =>rand(999, 9999), 'j' =>rand(999, 9999),
            );
      }

      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtRow'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->addColumn('aaaaaaa', 'a', array('id' => 'aaaaaa', 'onclick' => 'alert(\'aaaaaa\');'));

      $oConf->addBlocMessage('Search result: ', array('style' => 'cursor: crossair'), 'title');

      $oConf->setPagerTop(true, 'right', count($asData), '#');
      $oConf->setPagerBottom(true, 'right', count($asData), '#');


      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');

      return $sHTML;
    }

    private function _testTemplate7($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 5; $nKey++)
      {
        $asData[$nKey] = array('a' =>rand(999, 9999), 'b' => rand(999, 9999) , 'c' => rand(999, 9999),
            'e' => rand(999, 9999), 'f' => rand(99999, 9999999), 'g' => uniqid(rand(999, 9999), true),
            'h' =>rand(999, 9999), 'i' =>rand(999, 9999), 'j' =>rand(999, 9999),
            );
      }

      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtRow'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->addColumn('aaaaaaa', 'a', array('id' => 'aaaaaa', 'width' => '600'));
      $oConf->addColumn('bbbbbbbbbbbbbbbbb', 'b', array('id' => 'bbbbbb', 'width' => '25'));
      $oConf->addColumn('mmmmmmmmmmmmmmmmmmmmmmmm', 'c', array('id' => 'mmmm', 'sortable'=> array('javascript' => 1)));

      $oConf->addBlocMessage('Search result: ', array('style' => 'cursor: crossair'), 'title');

      $oConf->setPagerTop(true, 'right', 515, '#');
      $oConf->setPagerBottom(true, 'right', 515, '#');


      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');

      return $sHTML;
    }



    private function _testTemplate8($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 5; $nKey++)
      {
        $asData[$nKey] = array('a' =>rand(999, 9999), 'b' => rand(999, 9999) , 'c' => rand(999, 9999),
            'e' => rand(999, 9999), 'f' => rand(99999, 9999999), 'g' => uniqid(rand(999, 9999), true),
            'h' =>rand(999, 9999), 'i' =>rand(999, 9999), 'j' =>rand(999, 9999),
            );
      }

      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtRow'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->addColumn('aaaaaaa', 'a', array('id' => 'aaaaaa', 'width' => '20'));
      $oConf->addColumn('bbbbbbbbbbbbbbbbb', 'b', array('id' => 'bbbbbb', 'width' => '220'));
      $oConf->addColumn('cc', 'c', array('id' => '', 'width' => '165'));
      $oConf->addColumn('ddd', 'd', array('id' => '', 'width' => '50'));
      $oConf->addColumn('eeeee', 'e', array('id' => '', 'width' => '75'));
      $oConf->addColumn('ffff', 'f', array('id' => '', 'width' => '80'));
      $oConf->addColumn('gggggg', 'g', array('id' => '', 'width' => '125'));
      $oConf->addColumn('hhh', 'h', array('id' => '', 'width' => '125'));

      $oConf->addBlocMessage('Search result: ', array('style' => 'cursor: crossair'), 'title');

      $oConf->setPagerTop(true, 'right', count($asData), '#');
      $oConf->setPagerBottom(true, 'right', 312, '#');


      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');

      return $sHTML;
    }


    private function _testTemplate9($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 20; $nKey++)
      {
        $asData[$nKey] = array('a' => 'X', 'b' => rand(111111, 999999) , 'c' => 'X', 'd' => 'X',
            'e' => 'X', 'f' => 'X', 'g' => uniqid().' '. uniqid(),
            'h' =>uniqid().' '. uniqid(), 'i' =>rand(20, 120), 'j' =>rand(1, 99).'MY',
            'k' => uniqid(), 'l' => 'XX', 'm' => 'XX', 'n' => uniqid()
            );
      }

      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtRow'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);

      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');
      $oConf->addColumn(' - ', 'a', array('id' => 'aaaaaa', 'width' => '20'));
      $oConf->addColumn('ID', 'b', array('id' => 'bbbbbb', 'width' => '50'));
      $oConf->addColumn('C', 'c', array('id' => '', 'width' => '16'));
      $oConf->addColumn('S', 'd', array('id' => '', 'width' => '16'));
      $oConf->addColumn('G', 'e', array('id' => '', 'width' => '16'));
      $oConf->addColumn('R', 'f', array('id' => '', 'width' => '16'));
      $oConf->addColumn('Lastname / firstname', 'g', array('id' => '', 'width' => '200'));
      $oConf->addColumn('Company', 'h', array('id' => '', 'width' => '200'));
      $oConf->addColumn('Age', 'i', array('id' => '', 'width' => '30'));
      $oConf->addColumn('Salary', 'j', array('id' => '', 'width' => '45'));
      $oConf->addColumn('Department', 'k', array('id' => '', 'width' => '115'));
      $oConf->addColumn('Ch', 'l', array('id' => '', 'width' => '25'));
      $oConf->addColumn('No', 'm', array('id' => '', 'width' => '25'));
      $oConf->addColumn('Title', 'n', array('id' => '', 'width' => '100'));
      $oConf->addColumn('Actions', 'o', array('id' => '', 'width' => '50'));

      $oConf->addBlocMessage('Big list slistem like, col width fixed ', array('style' => 'cursor: crossair'), 'title');

      $oConf->setPagerTop(true, 'right', 240, '#');
      $oConf->setPagerBottom(true, 'right', 240, '#');


      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');

      return $sHTML;
    }


    private function _testTemplate10($oDisplay)
    {
      //---------------
      $asData = array();
      for($nKey = 0; $nKey < 25; $nKey++)
      {
        $asData[$nKey] = array('a' => 'X', 'b' => rand(111111, 999999) , 'c' => 'X', 'd' => 'X',
            'e' => 'X', 'f' => 'X', 'g' => uniqid().' '. uniqid(),
            'h' =>uniqid().' '. uniqid(), 'i' =>rand(20, 120), 'j' =>rand(1, 99).'MY',
            'k' => uniqid(), 'l' => 'XX', 'm' => 'XX', 'n' => uniqid()
            );
      }

      //params for the sub-templates when required
      $asParam = array('sub_template' => array('CTemplateList' => array(0 => array('row' => 'CTemplateCtRow'))));

      //initialize the template
      $oTemplate = $this->_oDisplay->getTemplate('CTemplateList', $asParam);



      //set global parameters accessible by all templates
      $oTemplate->setData('is_admin', 1);
      $oTemplate->setData('a string', 'gaaa');
      $oTemplate->setData('a bool', true);

      //if required, set specific params for a template
      $oTemplate->setTemplateParams('CTemplateList', array('tiiii' => 'tuuuu'));

      //get the config object for a specific template (contains default value so it works without config)
      $oConf = $oTemplate->getTemplateConfig('CTemplateList');

      $oConf->setRenderingOption('full', 'full', 'full');

      $oConf->addColumn(' - ', 'a', array('id' => 'aaaaaa', 'width' => '20'));
      $oConf->addColumn('ID', 'b', array('id' => 'bbbbbb', 'width' => '50'));
      $oConf->addColumn('C', 'c', array('id' => '', 'width' => '16'));
      $oConf->addColumn('S', 'd', array('id' => '', 'width' => '16'));
      $oConf->addColumn('G', 'e', array('id' => '', 'width' => '16'));
      $oConf->addColumn('R', 'f', array('id' => '', 'width' => '16'));
      $oConf->addColumn('Lastname / firstname', 'g', array('id' => '', 'width' => '20%'));
      $oConf->addColumn('Company', 'h', array('id' => '', 'width' => '20%'));
      $oConf->addColumn('Age', 'i', array('id' => '', 'width' => '30'));
      $oConf->addColumn('Salary', 'j', array('id' => '', 'width' => '45'));
      $oConf->addColumn('Department', 'k', array('id' => '', 'width' => '15%'));
      $oConf->addColumn('Ch', 'l', array('id' => '', 'width' => '25'));
      $oConf->addColumn('No', 'm', array('id' => '', 'width' => '25'));
      $oConf->addColumn('Title', 'n', array('id' => '', 'width' => '9.5%'));


      $oConf->addBlocMessage('List mixing fixed and % col width<br /> need list to be full size to extend ', array('style' => 'cursor: crossair'), 'title');

      $oConf->setPagerTop(true, 'right', 52021, '#');
      $oConf->setPagerBottom(true, 'right', 52021, '#');


      $sHTML = $oTemplate->getDisplay($asData, 1, 5, 'safdassda');

      return $sHTML;
    }

}