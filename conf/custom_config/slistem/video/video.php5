<!DOCTYPE html>

<html>
  <head>
    <title>Video tutorial</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
  </head>
  <body>


<?php

if(!isset($_GET['video_no']) ||  $_GET['video_no'] == 1)
{
?>

  <video width="800" height="600" controls >
    <source src="/conf/custom_config/slistem/video/video_1.webm" type="video/webm">
    Your browser does not support the video tag.
  </video>

<?php
}
elseif(isset($_GET['video_no']) && $_GET['video_no'] == 2)
{
?>
  <video width="800" height="600" controls >
    <source src="/conf/custom_config/slistem/video/video_<?php echo $_GET['video_no']; ?>.webm" type="video/webm">
    Your browser does not support the video tag.
  </video>

<?php
}
?>

  </body>
</html>
