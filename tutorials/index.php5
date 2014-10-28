<?php


$sFolder = '/tutorials/videos/';
$sDomain = 'https://slistem.devserv.com/'.$sFolder;
$sFolderPath = $_SERVER['DOCUMENT_ROOT'].$sFolder;
$oFs = dir($sFolderPath);

if(!$oFs)
  exit('Not available.');


$asVideo = array();
while(($sFilename = $oFs->read()) !== false)
{
  if($sFilename != '.' && $sFilename != '..')
  {
    $sLabel = ucfirst(trim(str_ireplace(array('_', '.webm'), ' ', $sFilename)));
    $asVideo[] = array('filename' => $sFilename,
        'filepath' => $sFolderPath.$sFilename,
        'url' => $sDomain.$sFilename,
        'label' => $sLabel
            );
  }
}
$oFs->close();
?>

<html>
  <head>
    <title>Slistem tutorials</title>
    <style>
      body{ font-family: 'Verdana'; }
      .video_list{ width: 250px; float: left; border-right: 1px solid #ccc; padding: 10px; min-height: 700px; }
      .video_container{ width: 800px; float: left; border-right: 1px solid #ccc; padding: 10px; min-height: 700px; }

      .video_list ul { width: 100%; list-style: none; padding: 0; margin: 0; }
      .video_list ul li { width: 100%; float: left; font-size: 12px; line-height: 32px; border-bottom: 1px solid #ccc; cursor: pointer;}
      .video_list ul li:hover {background-color: #e6e6e6; }
    </style>
    <script src="https://slistem3.slate.co.jp/common/js/jquery.js" type="text/javascript"></script>
  </head>
  <body>

    <div class="video_list" style="">
      <ul>
<?php

foreach($asVideo as $asVideoData)
{
  echo '<li onclick="
    var oVideo = $(\'#the_video\').get(0);

    oVideo.pause();
    $(\'#the_video\').animate({opacity: \'0.3\'}, 650, function()
    {

      $(\'#the_video source\').attr(\'src\', \''.$asVideoData['url'].'\');
      oVideo.load();
      oVideo.play();

      $(\'#the_video\').animate({opacity: \'1\'}, 650);

    });
    ">'.$asVideoData['label'].'</li>';
}

?>
      </ul>
    </div>

    <div class="video_container">
      <video id="the_video" width="800" height="640" controls>
        <source src="<?php echo $asVideo[0]['url']; ?>" type="video/webm">
        Your browser does not support the video tag.
      </video>

    </div>

  </body>
</html>

