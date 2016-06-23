<?php

/*
$aging = 0 disables the auto remove functionality
*/
$aging = 1 * 3600;

/*
HTTP BaseAuth
name:md5_hash
e.g.
test:098f6bcd4621d373cade4e832627b4f6 (test/test)
*/
$pass = '';

/* authentification + autoremove */
if ($pass) {
  if (empty($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != explode(":", $pass) [0] || md5($_SERVER['PHP_AUTH_PW']) != explode(":", $pass) [1]) {
    header('WWW-Authenticate: Basic realm="Mini File Browser"');
    header('HTTP/1.0 401 Unauthorized');
    die('unauthorized!');
  }
}

if (($aging && time() - filectime(__FILE__) > $aging) || isset($_GET['remove'])) {
  if (unlink(__FILE__)) die('removed!');
  else die('not removed!');
}

main(__DIR__);

function main($dir)
{
  
  echo "<h1>Mini File Browser</h1>";       
  echo "<table border='1' style='border-collapse:collapse'>";
  if (isset($_GET['dir'])) $dir = $_GET['dir'];
  $files = scandir($dir);
  $num = sizeof($files);
  for ($i = 0; $i < $num; $i++) {
    $info = $files[$i];
    $real = realpath($dir . DIRECTORY_SEPARATOR . $files[$i]);
    $size = '';
    $perm = substr(sprintf('%o', fileperms($real)) , -4);
    $my_perm = '';
    if (is_readable($real)) $my_perm.= 'R';
    if (is_writable($real)) $my_perm.= 'W';
    if (is_executable($real)) $my_perm.= 'X';
    echo "<tr><td>";
    if (is_dir($real)) {
      echo '<a href="?dir=' . $real . '">' . $files[$i] . "</a>";
    }
    else {
      echo $files[$i];
      $size = FileSizeConvert(filesize($real));
    }

    echo "</td><td>";
    echo $perm;
    echo "</td><td>";
    echo $my_perm;
    echo "</td><td>";
    echo $size;
    echo "</td></tr>";
  }

  echo "</table>";
  info();
  echo "<p><a href='?remove'>Remove me</a></p>";
  echo "<p>Author: <a href='https://lynt.cz'>Lynt services s.r.o.</a></p>";
}


function info()
{
  echo '<p><b>Current script:</b> ' . __FILE__ . '</p>';
  echo '<p><b>PHP version:</b> ' . phpversion() . '</p>';
  echo '<p><b>PHP extensions:</b> ' . implode(', ', get_loaded_extensions()) . '</p>';
  echo '<p><b>PHP disable functions:</b> ' . ini_get('disable_functions') . '</p>';
  echo '<p><b>Open Basedir:</b> ' . ini_get('open_basedir') . '</p>';
}


function FileSizeConvert($bytes)
{
  $result = '';
  $bytes = floatval($bytes);
  $arBytes = array(
    0 => array(
      "UNIT" => "TB",
      "VALUE" => pow(1024, 4)
    ) ,
    1 => array(
      "UNIT" => "GB",
      "VALUE" => pow(1024, 3)
    ) ,
    2 => array(
      "UNIT" => "MB",
      "VALUE" => pow(1024, 2)
    ) ,
    3 => array(
      "UNIT" => "kB",
      "VALUE" => 1024
    ) ,
    4 => array(
      "UNIT" => "B",
      "VALUE" => 1
    ) ,
  );
  foreach($arBytes as $arItem) {
    if ($bytes >= $arItem["VALUE"]) {
      $result = $bytes / $arItem["VALUE"];
      $result = str_replace(".", ",", strval(round($result, 2))) . " " . $arItem["UNIT"];
      break;
    }
  }

  return $result;
}
