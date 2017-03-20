<?php
/*
 A simple file manager with php
 author treemonster
 latest 2017/3/20
 git: https://github.com/treemonster/filemanager.php
 */
$root=realpath(dirname(__FILE__));// 指定文件管理的根目录，所有的文件操作只能在此目录下进行。此目录必须具备全部读写权限

// 校验访问者身份，所有人都可以使用文件下载功能，但其他功能必须校验用户身份
// 请根据实际情况增加校验逻辑
function checkPermission(){
  /*
   // 例如
   session_start();
   if(!$_SESSION['logined']){
	    die('permission denied');
   }
   */
  return true;
}

///////////////////////////////////////////////////////////////
$dir=isset($_REQUEST['dir'])?$_REQUEST['dir']:'.';
$realdir=realpath($root.'/'.$dir);
if(substr($realdir, 0,strlen($root))!=$root)$dir=".";
$realdir=realpath($root.'/'.$dir);

if(isset($_REQUEST['down'])){
  $xp=pathinfo($_REQUEST['down']);
  $bfn=$xp['basename'];
  $filename=realpath($realdir.'/'.$bfn);
  $date=date("Ymd-H:i:m");
  header( "Content-type:  application/octet-stream");
  header( "Accept-Ranges:  bytes ");
  header( "Accept-Length: " .filesize($filename));
  header( "Content-Disposition:  attachment;  filename= ".$bfn);
  if($filename) readfile($filename);
  exit;
}

checkPermission();

if(isset($_REQUEST['delfile']))try{
  @unlink($realdir.'/'.$_REQUEST['delfile']);
  if(file_exists($realdir.'/'.$_REQUEST['delfile']))
  	throw new Exception("permission denied for delete file here");
  header("Location: ?dir=".urldecode($dir)); exit;
}catch(Exception $e){
  echo '<font color="red">Error: '.$e->getMessage().'</font>';
}

function rmdir2($dir){
  $list=dir($dir);
  while($f = $list->read())switch(true){
  	case $f=='.' || $f=='..': continue;
  	case is_dir($dir.'/'.$f):
  	  rmdir2($dir.'/'.$f);
  	  break;
  	default:
  	  unlink($dir.'/'.$f);
      break;
  }
  rmdir($dir);
}
if(isset($_REQUEST['deldir']))try{
  rmdir2($realdir.'/'.$_REQUEST['deldir']);
  if(file_exists($realdir.'/'.$_REQUEST['deldir']))
  	throw new Exception("permission denied for delete directory here");
  header("Location: ?dir=".urldecode($dir)); exit;
}catch(Exception $e){
  echo '<font color="red">Error: '.$e->getMessage().'</font>';
}

if(isset($_FILES['upfile']))try{
  if(!$_FILES['upfile']['tmp_name']) throw new Exception("upload file failed");
  $tmp=$_FILES['upfile']['tmp_name'];
  $px=pathinfo($_FILES['upfile']['name']);
  $newfn=$realdir.'/'.$px['basename'];
  if(file_exists($newfn)) for($i=1;$i<9999;$i++){
  	$pn=pathinfo($newfn);
  	$nn=$pn['dirname'].'/'.$pn['basename'].'_'.$i.'.'.$pn['extension'];
    if(file_exists($nn)) continue;
    $newfn=$nn;
    break;
  }
  @move_uploaded_file($tmp, $newfn);
  if(file_exists($tmp)) throw new Exception("permission denied for upload file here");
  header("Location: ?dir=".urldecode($dir)); exit;
}catch(Exception $e){
  echo '<font color="red">Error: '.$e->getMessage().'</font>';
}

if(isset($_REQUEST['newdir']))try{
  @mkdir($realdir.'/'.$_REQUEST['newdir']);
  if(!file_exists($realdir.'/'.$_REQUEST['newdir']))
  	throw new Exception("permission denied for create directory here");
  header("Location: ?dir=".urldecode($dir)); exit;
}catch(Exception $e){
  echo '<font color="red">Error: '.$e->getMessage().'</font>';
}

echo "<h1>".substr($realdir."/",strlen($root))."</h1>";

$list=dir($realdir);
$dirs=array();
$files=array();
while($f = $list->read()){
  switch(true){
  	case $f=='.': continue;
  	case is_dir($realdir.'/'.$f):
  	  array_push($dirs, $f);
  	  break;
  	default:
  	  array_push($files, $f);
      break;
  }
}

sort($dirs);
sort($files);
echo '<br>';
foreach($dirs as $f)
  if($f=='..')echo '<a href="?dir='.$dir.'/'.$f.'" style="color:#ff0000;">parent folder</a><br><br>';
  else echo '<a href="?dir='.$dir.'/'.$f.'">'.$f.'</a>&nbsp;<a href="?dir='.$dir.'&deldir='.$f.'" onclick=\'return confirm('.json_encode('sure to drop this directory?').')\' style="color:#777777;">delete</a><br>';
echo '<br>';
foreach($files as $f)
  echo $f.'&nbsp;<a href="?dir='.$dir.'&down='.$f.'"><small>download</small></a>&nbsp;<button onclick=\'prompt("",'.json_encode(
  	$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?dir='.$dir.'&down='.$f
  ).')\'>copy</button>&nbsp;<a style="color:#777777;" href="?dir='.$dir.'&delfile='.$f.'" onclick=\'return confirm('.json_encode('sure to delete this file?').')\'>delete</a><br>';
?><meta charset="utf-8">

<div style="position: fixed;
    width: 300px;
    height: 130px;
    background: #fff;
    right: 20px;
    border: 1px solid #ccc;
    padding: 10px;
    top: 60px;">
<form method="POST" enctype="multipart/form-data">
  选择文件：<input type="file" name="upfile">
  <input type="submit" value="上传" />
</form>
<hr>
<form method="POST" enctype="multipart/form-data">
  新建文件夹：<input name="newdir">
  <input type="submit" value="提交" />
</form>
</div>
