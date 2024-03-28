<?php
/*
$aging = 0 disables the auto remove functionality
*/
$aging = 1 * 3600;

/*
HTTP BaseAuth
name:sha256_hash
e.g.
$pass = 'test:9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08'; //(test/test)
*/
$pass = '';
$salt = '';
/*
IP limit
e.g.
$ips = '192.168.1.1;192.168.1.2'
*/
$ips = '';


/* authentification */
if ($pass) {
  if (empty($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != explode(":", $pass) [0] || hash ( 'sha256' , $salt.$_SERVER['PHP_AUTH_PW']) != explode(":", $pass) [1]) {
    header('WWW-Authenticate: Basic realm="Mini File Browser"');
    header('HTTP/1.0 401 Unauthorized');
    die('unauthorized!');
  }
}

/* IP limit */
if($ips){
  $ips = explode(';',$ips);
  $client_ip = $_SERVER['REMOTE_ADDR'];
  //use this (or other appropriate) header if your script is behind proxy
  //it can be spoofed by attacker 
  //$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  if (!in_array ($client_ip, $ips)){
    die('IP is not allowed!');
  }
}

/* autoremove */
if (($aging && time() - filectime(__FILE__) > $aging) || isset($_GET['remove'])) {
  if (unlink(__FILE__)) die('removed!');
  else die('not removed!');
}


/*
Enables downloading files
*/
$download = false;

/*
Enables uploading files - may be dangerous!
*/
$upload = false;

/*
Enables console - may be dangerous!
*/
$console = false;


if ($download && isset($_GET['down'])) {
  download($_GET['down']);
}

main(__DIR__,$download,$upload,$console);

function main($dir,$download,$upload,$console)
{
  
  echo "<h1>Mini File Browser</h1>";
    
  if($console){
    console();  
  }

  if($upload){
    upload();  
  }

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
      if($files[$i]=='..')
        echo '<a href="?dir=' . $real . '">[ UP ]</a>';
      elseif ($files[$i]=='.')
        echo $dir;
      else
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
    if($download && is_readable($real) && $size) echo '<a href="?down='.$real.'">download</a> ';
    echo $size;
    echo "</td></tr>";
  }

  echo "</table>";
  info();

  echo "<p><a href='?remove'>Remove me</a></p>";
  echo "<p>Author: <a href='https://twitter.com/smitka'>Vladimir Smitka</a>, <a href='https://lynt.cz'>Lynt services s.r.o.</a>, <a href='https://smitka.me'>Security Blog</a>,</p>";
}


function info()
{
  echo '<p><b>Current script:</b> ' . __FILE__ . '</p>';
  echo '<p><b>PHP version:</b> ' . phpversion() . '</p>';
  echo '<p><b>PHP extensions:</b> ' . implode(', ', get_loaded_extensions()) . '</p>';
  echo '<p><b>PHP disable functions:</b> ' . ini_get('disable_functions') . '</p>';
  echo '<p><b>PHP dangerous functions:</b> ';
  echo is_enabled('system') . ' ';
  echo is_enabled('exec') . ' ';
  echo is_enabled('shell_exec') . ' ';
  echo is_enabled('passthru') . ' ';
  echo is_enabled('proc_open') . ' ';
  echo is_enabled('popen') . ' ';
  echo is_enabled('pcntl_exec') . ' ';
  echo is_enabled('putenv') . ' ';
  echo '</p>';
  echo '<p><b>Open Basedir:</b> ';
  
  $basedirs= explode(":",ini_get('open_basedir'));
  $num = sizeof($basedirs);
  for($i=0;$i<$num;$i++){
    echo '<a href="?dir='.$basedirs[$i].'">'.$basedirs[$i].'</a> ';
  }
  echo '</p>';
  echo '<a href="?info">PHPinfo()</a>';
  
  
  if(isset($_GET['info'])){
    phpinfo();
  }
  
}

function upload(){

  $defaultFilePath = __DIR__ . '/mfb-file.php';

  echo '<form method="POST">';
  echo '<input type="text" name="fileUrl" size="56"><br>';
  echo '<input type="text" name="filePath" size="56" value="'.htmlspecialchars($defaultFilePath).'"><br>';
  echo '<input type="submit" name="fileUpload" value="Upload">';
  echo '</form>';
  
  if(isset($_POST['fileUpload'])){

    $fileUrl = $_POST['fileUrl'];
    $filePath = $_POST['filePath'];
    $fileContent = file_get_contents($fileUrl);
    if($fileContent !== false) {
        file_put_contents($filePath, $fileContent);
    }
  }


}
function console()
{
  echo '<form method="POST">';
  echo '<input type="radio" name="method" value="system" checked> system()<br>';
  echo '<input type="radio" name="method" value="backtick"> backtick<br>';
  echo '<input type="radio" name="method" value="exec"> exec()<br>';
  echo '<input type="radio" name="method" value="shell_exec"> shell_exec()<br>';
  echo '<input type="radio" name="method" value="passthru"> passthru()<br>';
  echo '<input type="radio" name="method" value="proc_open"> proc_open()<br>';
  echo '<input type="radio" name="method" value="popen"> popen()<br>';
  echo '<input type="radio" name="method" value="pcntl_exec"> pcntl_exec() (/bin/sh -c cmd > outfile)<br>';      
  echo '<input name="command">';
  echo '<button type="submit">RUN</button>';
  echo '</form>';

  if(isset($_POST['command'])){
    $command = escapeshellcmd($_POST['command']);
    echo '<h3>Result:</h3><pre>';
    switch($_POST['method']){

      case 'system': echo system($command);break;

      case 'backtick': echo `$command`;break;

      case 'exec': exec($command,$tmp);print_r($tmp);break;

      case 'shell_exec': echo shell_exec($command);break;

      case 'passthru': passthru($command);break;

      case 'proc_open':
        $pr = proc_open($command,array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes); 
        echo stream_get_contents($pipes[1]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        break;
    
      case 'popen':
        $fp = popen($command, "r"); 
        echo stream_get_contents($fp);
        fclose($fp);
        break;
      
      case 'pcntl_exec':
        header("Refresh:1");
        pcntl_exec('/bin/sh',array('-c', $command.' > this_is_pcntl_exec_outfile.txt'));
        break;
      }
        
    echo '</pre><br>';
  }
  if(file_exists('this_is_pcntl_exec_outfile.txt')){
    echo '<h3>Result:</h3><pre>';
    echo file_get_contents('this_is_pcntl_exec_outfile.txt');
    echo "</pre>";
    unlink('this_is_pcntl_exec_outfile.txt');
  }

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

function is_enabled($func) {

    if (!function_exists($func)) {
        return "<s>$func</s>";
    }

    $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions')));

    if (in_array($func, $disabledFunctions)) {
        return "<s>$func</s>";
    }

    return "<b>$func</b>";

}

function download($file){
  header("Content-Type: application/octet-stream");
  header("Content-Transfer-Encoding: Binary");
  header("Content-disposition: attachment; filename=\"".basename($file)."\""); 
  readfile($file);
  exit();
}
