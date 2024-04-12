<?php
/*
$aging = 0 disables the auto remove functionality
*/
$aging = 1 * 3600;
$aging = 0;
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
  if (empty($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != explode(":", $pass)[0] || hash('sha256', $salt . $_SERVER['PHP_AUTH_PW']) != explode(":", $pass)[1]) {
    header('WWW-Authenticate: Basic realm="Mini File Browser"');
    header('HTTP/1.0 401 Unauthorized');
    die('unauthorized!');
  }
}

/* IP limit */
if ($ips) {
  $ips = explode(';', $ips);
  $client_ip = $_SERVER['REMOTE_ADDR'];
  //use this (or other appropriate) header if your script is behind proxy
  //it can be spoofed by attacker 
  //$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  if (!in_array($client_ip, $ips)) {
    die('IP is not allowed!');
  }
}

/* autoremove */
if (($aging && time() - filectime(__FILE__) > $aging) || isset($_GET['remove'])) {
  if (unlink(__FILE__))
    die('removed!');
  else
    die('not removed!');
}


/* Enables downloading files */
$download = false;

/* Enables readinging files */
$read = false;

/* Enables uploading files - may be dangerous! */
$upload = false;

/* Enables console - may be dangerous! */
$console = false;


date_default_timezone_set('Europe/Prague');

$method = isset($_GET['m']) ? $_GET['m'] : 'php';

if ($download && isset($_GET['down'])) {
  download($_GET['down'], $method);
}

main(__DIR__, $download, $upload, $read, $console, $method);

function main($dir, $download, $upload, $read, $console, $method)
{

  echo "<h1>Mini File Browser</h1>";

  if ($console) {
    echo "<h2>Console</h2>";
    console();
  }

  if ($upload) {
    echo "<h2>File uploader</h2>";
    upload();
  }


  if ($read && isset($_GET['read'])) {
    echo "<h2>File reader</h2>";
    read($_GET['read'], $method);
  }


  $dir = isset($_GET['dir']) ? $_GET['dir'] : $dir;

  echo "<h2>File browser</h2>";

  switch ($method) {
    case 'shell_exec':
      $files = list_directory_shell($dir);
      echo "<p>File browsing: <a href='" . modify_url('m', 'php') . "'>switch to php</a></p>";
      break;
    default:
      $files = list_directory_php($dir);
      if (is_enabled('shell_exec')[1] === 'b')
        echo "<p>File browsing: <a href='" . modify_url('m', 'shell_exec') . "'>switch to shell_exec</a></p>";
      break;
  }

  echo "<table border='1' style='border-collapse:collapse'>";

  foreach ($files as $file) {
    echo "<tr>";
    echo "<td>";
    if ($file['isDir']) {
      if ($file['name'] === '..')
        echo "<a href='?m=$method&dir={$file["path"]}'>[ UP ]</a>";
      elseif ($file['name'] === '.')
        echo $file['path'];
      else
        echo "<a href='?m=$method&dir={$file["path"]}'>{$file["name"]}</a>";
    } else {
      echo $file["name"];
    }

    echo "</td>";
    echo "<td>{$file['perm']} {$file['owner']}</td>";
    echo "<td>{$file['my_perm']}</td>";
    echo "<td>{$file['time']}</td>";
    echo "<td>";
    if ($download && $file['base64'])
      echo "<a href='" . modify_url('down', $file['base64']) . "'>download</a> ";
    if ($read && $file['base64'])
      echo "<a href='" . modify_url('read', $file['base64']) . "'>read</a> ";
    echo $file['size'];
    echo "</tr>";
  }
  echo "</table>";

  echo "<h2>Information</h2>";

  echo '<p><b>Current script:</b> <a href="?dir=' . __DIR__ . '">' . __FILE__ . '</a></p>';
  echo '<p><b>PHP version:</b> ' . phpversion() . ' @ ' . php_uname() . ' [' . php_sapi_name() . ']</p>';
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

  $basedirs = explode(":", ini_get('open_basedir'));
  $num = sizeof($basedirs);
  for ($i = 0; $i < $num; $i++) {
    echo '<a href="?dir=' . $basedirs[$i] . '">' . $basedirs[$i] . '</a> ';
  }
  echo '</p>';
  if ($read)
    echo '<p><a href="?read=predefined1">Try to read passwd</a></p>';
  echo '<p><a href="?info">PHPinfo()</a></p>';


  if (isset($_GET['info'])) {
    phpinfo();
  }

  echo "<p><a href='?remove'>Remove me</a></p>";
  echo "<p>Author: <a href='https://twitter.com/smitka'>Vladimir Smitka</a>, <a href='https://lynt.cz'>Lynt services s.r.o.</a>, <a href='https://smitka.me'>Security Blog</a>, <a href='https://github.com/lynt-smitka/PHP-Mini-File-Browser'>GitHub</a></p>";
}



function list_directory_php($dir)
{
  $files = scandir($dir);
  $fileList = [];
  foreach ($files as $file) {

    $real = @realpath($dir . DIRECTORY_SEPARATOR . $file);
    if ($real === false)
      continue;


    $isDir = is_dir($real);

    $perm = get_perms($real);

    $my_perm = '';
    if (is_readable($real))
      $my_perm .= 'R';
    if (is_writable($real))
      $my_perm .= 'W';
    if (is_executable($real))
      $my_perm .= 'X';

    $owner = '';
    if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
      $owner = posix_getpwuid(fileowner($real))['name'] . ":" . posix_getgrgid(fileowner($real))['name'];
    }

    $time = date('Y-m-d H:i:s', filemtime($real));
    $size = is_dir($real) ? '' : FileSizeConvert(filesize($real));
    $base64 = is_readable($real) && !$isDir ? base64_encode($real) : '';

    $fileList[] = [
      'name' => $file,
      'path' => $real,
      'isDir' => $isDir,
      'perm' => $perm,
      'my_perm' => $my_perm,
      'owner' => $owner,
      'time' => $time,
      'size' => $size,
      'base64' => $base64
    ];
  }
  return $fileList;
}

function list_directory_shell($dir)
{
  $output = shell_exec("ls -la --time-style=full-iso " . escapeshellarg($dir));
  $lines = explode("\n", trim($output));
  $fileList = [];
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, 'total ') === 0)
      continue;
    $parts = preg_split('/\s+/', $line, 9);
    if (count($parts) < 9)
      continue;
    $file = $parts[8];
    $real = emul_realpath($dir . DIRECTORY_SEPARATOR . $file);
    $isDir = $parts[0][0] === 'd';
    $perm = substr($parts[0], 1);
    $my_perm = '';
    if (strpos($perm, 'r') !== false)
      $my_perm .= 'R';
    if (strpos($perm, 'w') !== false)
      $my_perm .= 'W';
    if (strpos($perm, 'x') !== false)
      $my_perm .= 'X';
    $owner = $parts[2] . ':' . $parts[3];
    $time = date('Y-m-d H:i:s', strtotime($parts[5] . ' ' . explode(".", $parts[6])[0]));
    $size = $isDir ? '' : FileSizeConvert($parts[4]);
    $base64 = strpos($perm, 'r') !== false && !$isDir ? base64_encode($real) : '';

    $fileList[] = [
      'name' => $file,
      'path' => $real,
      'isDir' => $isDir,
      'perm' => $perm,
      'my_perm' => $my_perm,
      'owner' => $owner,
      'time' => $time,
      'size' => $size,
      'base64' => $base64
    ];
  }
  return $fileList;
}


function get_perms($file)
{

  $perms = fileperms($file);

  switch ($perms & 0xF000) {
    case 0xC000: // socket
      $info = 's';
      break;
    case 0xA000: // symbolic link
      $info = 'l';
      break;
    case 0x8000: // regular
      $info = '-';
      break;
    case 0x6000: // block special
      $info = 'b';
      break;
    case 0x4000: // directory
      $info = 'd';
      break;
    case 0x2000: // character special
      $info = 'c';
      break;
    case 0x1000: // FIFO pipe
      $info = 'p';
      break;
    default: // unknown
      $info = 'u';
  }
  // Owner
  $info .= (($perms & 0x0100) ? 'r' : '-');
  $info .= (($perms & 0x0080) ? 'w' : '-');
  $info .= (($perms & 0x0040) ?
    (($perms & 0x0800) ? 's' : 'x') :
    (($perms & 0x0800) ? 'S' : '-'));
  // Group
  $info .= (($perms & 0x0020) ? 'r' : '-');
  $info .= (($perms & 0x0010) ? 'w' : '-');
  $info .= (($perms & 0x0008) ?
    (($perms & 0x0400) ? 's' : 'x') :
    (($perms & 0x0400) ? 'S' : '-'));
  // World
  $info .= (($perms & 0x0004) ? 'r' : '-');
  $info .= (($perms & 0x0002) ? 'w' : '-');
  $info .= (($perms & 0x0001) ?
    (($perms & 0x0200) ? 't' : 'x') :
    (($perms & 0x0200) ? 'T' : '-'));

  return $info;


}

function upload()
{

  $defaultFilePath = __DIR__ . '/mfb-file.php';

  echo '<form method="POST">';
  echo '<input type="text" name="fileUrl" size="56" placeholder="URL"><br>';
  echo '<input type="text" name="filePath" size="56" value="' . htmlspecialchars($defaultFilePath) . '"><br>';
  echo '<input type="submit" name="fileUpload" value="Upload from URL">';
  echo '</form>';

  if (isset($_POST['fileUpload'])) {

    $fileUrl = $_POST['fileUrl'];
    $filePath = $_POST['filePath'];
    $fileContent = file_get_contents($fileUrl);
    if ($fileContent !== false) {
      file_put_contents($filePath, $fileContent);
    }
  }


}
function console()
{

  $method = isset($_POST['method']) ? $_POST['method'] : 'system';

  echo '<form method="POST">';
  echo '<input type="radio" name="method" value="system" ' . ($method == "system" ? 'checked' : '') . '> system()<br>';
  echo '<input type="radio" name="method" value="backtick" ' . ($method == "backtick" ? 'checked' : '') . '> backtick<br>';
  echo '<input type="radio" name="method" value="exec" ' . ($method == "exec" ? 'checked' : '') . '> exec()<br>';
  echo '<input type="radio" name="method" value="shell_exec" ' . ($method == "shell_exec" ? 'checked' : '') . '> shell_exec()<br>';
  echo '<input type="radio" name="method" value="passthru" ' . ($method == "passthru" ? 'checked' : '') . '> passthru()<br>';
  echo '<input type="radio" name="method" value="proc_open" ' . ($method == "proc_open" ? 'checked' : '') . '> proc_open()<br>';
  echo '<input type="radio" name="method" value="popen" ' . ($method == "popen" ? 'checked' : '') . '> popen()<br>';
  echo '<input type="radio" name="method" value="pcntl_exec" ' . ($method == "pcntl_exec" ? 'checked' : '') . '> pcntl_exec() (/bin/sh -c cmd > outfile)<br>';
  echo '<input type="radio" name="method" value="eval" ' . ($method == "eval" ? 'checked' : '') . '> eval()<br>';
  echo '<input name="command" value="' . (isset($_POST['command']) ? htmlspecialchars($_POST['command']) : '') . '">';
  echo '<button type="submit">RUN</button>';
  echo '</form>';

  if (isset($_POST['command'])) {
    $command = escapeshellcmd($_POST['command']);
    echo '<h3>Result:</h3><pre>';
    switch ($_POST['method']) {

      case 'system':
        echo system($command);
        break;

      case 'backtick':
        echo `$command`;
        break;

      case 'exec':
        exec($command, $tmp);
        print_r($tmp);
        break;

      case 'shell_exec':
        echo shell_exec($command);
        break;

      case 'passthru':
        passthru($command);
        break;

      case 'eval':
        echo eval_code($_POST['command']);
        break;

      case 'proc_open':
        $pr = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
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
        pcntl_exec('/bin/sh', array('-c', $command . ' > this_is_pcntl_exec_outfile.txt'));
        break;
    }

    echo '</pre><br>';
  }
  if (file_exists('this_is_pcntl_exec_outfile.txt')) {
    echo '<h3>Result:</h3><pre>';
    echo file_get_contents('this_is_pcntl_exec_outfile.txt');
    echo "</pre>";
    unlink('this_is_pcntl_exec_outfile.txt');
  }

}


function eval_code($code)
{
  if (!preg_match('/\breturn\b/', $code)) {
    $code = 'return ' . $code;
  }
  if (substr(trim($code), -1) !== ';') {
    $code .= ';';
  }
  try {
    $result = eval ($code);
  } catch (ParseError $e) {
    return 'Parse error: ' . $e->getMessage();
  }
  return $result;
}

function emul_realpath($path)
{
  $folders = explode('/', $path);
  $stack = [];
  foreach ($folders as $folder) {
    if ($folder === '..') {
      array_pop($stack);
    } elseif ($folder !== '' && $folder !== '.') {
      array_push($stack, $folder);
    }
  }
  $result = '/' . implode('/', $stack);
  if (substr($path, -1) === '/') {
    $result .= '/';
  }
  return $result;
}

function modify_url($key, $value)
{
  $parts = parse_url($_SERVER['REQUEST_URI']);
  parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
  $query[$key] = $value;
  $parts['query'] = http_build_query($query);
  return $parts['path'] . '?' . $parts['query'];
}

function FileSizeConvert($bytes)
{
  $units = ["TB" => pow(1024, 4), "GB" => pow(1024, 3), "MB" => pow(1024, 2), "kB" => 1024, "B" => 1];
  foreach ($units as $unit => $value) {
    if ($bytes >= $value) {
      return str_replace(".", ",", round($bytes / $value, 2)) . " " . $unit;
    }
  }
  return '0 B';
}


function is_enabled($func)
{
  if (!function_exists($func)) {
    return "<s>$func</s>";
  }

  $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions')));

  if (in_array($func, $disabledFunctions)) {
    return "<s>$func</s>";
  }

  return "<b>$func</b>";

}

function download($file, $method)
{
  $file = base64_decode($file);
  header("Content-Type: application/octet-stream");
  header("Content-Transfer-Encoding: Binary");
  header("Content-disposition: attachment; filename=\"" . basename($file) . "\"");
  switch ($method) {
    case "php":
      readfile($file);
      break;
    case "shell_exec":
      echo shell_exec("cat " . escapeshellarg($file) . " 2>&1");
      break;
  }

  exit();
}

function read($file, $method)
{
  switch ($file) {
    case 'predefined1':
      $file = "/etc/passwd";
      break;
    default:
      $file = base64_decode($file);
  }

  echo "<p>File: $file</p>";

  $ext = pathinfo($file, PATHINFO_EXTENSION);
  $file_name = pathinfo($file, PATHINFO_BASENAME);
  echo "<pre>";
  ob_start();

  switch ($method) {
    case "php":
      readfile($file);
      break;
    case "shell_exec":
      echo shell_exec("cat " . escapeshellarg($file) . " 2>&1");
      break;
  }

  $content = ob_get_clean();
  $is_img = false;
  $is_archive = false;
  $mime = 'text/plain';

  switch ($ext) {
    case "jpg":
      $is_img = true;
      $mime = 'image/jpeg';
      break;
    case "png":
      $is_img = true;
      $mime = 'image/png';
      break;
    case "gif":
      $is_img = true;
      $mime = 'image/gif';
      break;
    case "webp":
      $is_img = true;
      $mime = 'image/webp';
      break;
    case "zip":
      $is_archive = true;
      break;

    case "tgz":
      $is_archive = true;
      break;

    case "tar":
      $is_archive = true;
      break;

    case "gz":
      $is_archive = true;
      break;

    default:
      $is_img = false;
  }

  if ($is_img) {
    echo '<img src="' . 'data:' . $mime . ';base64,' . base64_encode($content) . '">';
  } elseif ($is_archive) {
    read_archive($content, $file_name);
  } else {
    echo htmlspecialchars($content);
  }
  echo "</pre>";

}


function read_archive($content, $file_name)
{
  if (class_exists("PharData")) {
    $temp = sys_get_temp_dir() . '/mfb-archive-' . $file_name;
    file_put_contents($temp, $content);
    $phar = new PharData($temp);
    echo "Archive files:<br>";
    foreach (new RecursiveIteratorIterator($phar) as $file) {
      echo $file->getFilename() . "<br>";
    }
    unlink($temp);
  }
}