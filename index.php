<?php
ob_start();
//画像へのリンクをダウンロード
require('collectLib.class.php');
require('downloadLib.class.php');

if($_GET['url'] != ''){
  $arg=array(
    'url'=>$_GET['url'],'enc'=>$_GET['enc'],
  );
  $ret = urlencode(main($arg));
  //DL画面へリダイレクト
  ob_end_clean();
  print "<html><head><META HTTP-EQUIV='Refresh' 
  CONTENT='0; URL=http://nanndemo.ddo.jp/dtaphp/index.php?dl={$ret}'
  ></head></html>";
  exit;
}

ob_end_clean();

?>
<html>
<head>
  <META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=UTF-8">
  <title>画像を集める</title>
</head>
<body>
<p><a href="./">画像を集める</a></p>
<hr>

<?php  ?>
<?php if($_GET['dl'] == ''){ ?>
<form method='get' action="index.php">
<p><input type="test" name="url" value="paste url here.."
  onclick="this.value='';"
  ><input type="submit" value="get"></p>
</form>
<hr>
<form method='get' action='./'>
  <select size='1' name='dl'>
  <option value=''>[select caches]</option>
<?php 
  print showcaches();
?>
  </select><input type="submit" value="get">
</form>

<?php 
}else{
  print ($_GET['dl'] != '')?showdllink($_GET['dl']):'';
} ?>

</body>
</html>


<?php
//

function showdllink($path){
  //print mb_convert_encoding($path,'utf-8','auto');
  if(!file_exists('./'.$path)){return;}
  $path = urlencode($path);
  return "<a href='./{$path}'>画像アーカイブをダウンロード</a>";
}

function showcaches(){
  $zips=dirList('./');
  foreach($zips as $k0=>$v0){
    $val=htmlspecialchars($v0);
    //$val=urlencode($val);
    print "<option value='{$val}'>{$v0}</option>";
  }
  return;
}

function main($arg){
  //recv
  $arr['baseurl']=$arg['url'];
  $arr['scrapesrc']="";
  $arr['urllist']="";
  $arr['enc_local']="utf-8";
  $arr['appdir']="/home/iwkrpubw/deploy/_root/dtaphp";
  
  if($arr['baseurl'] == ""){return;}
  
  
  $clib=new collectlib();
  
  //get page
  $arr['scrapesrc'] = $clib->dl->curl_download($arr['baseurl']);
  $arr['urllist'] = $clib->generate_link_list($arr['baseurl'],$arr['scrapesrc']);
  
  //get charset, title
  $titlesrc = preg_replace(array('/\n/','/\r/'),'',$arr['scrapesrc']);
  preg_match("#\<meta.*?http\-equiv.*?Content\-Type.*?charset\=(.*?)\".*?\>#is",$titlesrc,$tmp);
  //print_r($tmp);
  $arr['meta_charset'] = $tmp[1];
  preg_match("/\<title\>(.*?)\<\/title\>/is",$titlesrc,$tmp);
  $reparr=array("/\//","/\:/","/\\\/","/\*/","/\</","/\>/","/\|/","/\?/","/\"/","/ /");
  $arr['scrapetitle'] = preg_replace($reparr,'_',urldecode(trim($tmp[1])));
  //print_r($tmp);
  if(preg_match("/utf.8/",$arr['meta_charset']) == 0){
    $arr['scrapetitle'] = mb_convert_encoding(
      $arr['scrapetitle'],$arr['enc_local'], "eucjp-win, sjis-win, utf-8");
  }
  //print $arr['meta_charset'].$arr['scrapetitle'];return;
  
  //storage
  $sesskey='i_'.sha1($arr['scrapetitle']);
  $arr['saveto']=$arr['appdir'].'/'.$sesskey;
  mkdir($arr['saveto']);
  $files=array();
  
  //text content
  $pagestr = preg_replace('/<script.*?script>/is','',$arr['scrapesrc']);
  $pagestr = preg_replace('/<.*?>/s','',$pagestr);
  $pagestr = preg_replace('/\r/',"\n",$pagestr);
  $pagestr = preg_replace('/\n\n/',"\n",$pagestr);
  $pagestr = preg_replace('/\n\n/',"\n",$pagestr);
  $pagestr = preg_replace('/\n\n/',"\n",$pagestr);
  $pagestr = preg_replace('/\t/','',$pagestr);
  $fh_ptxt=fopen($arr['saveto']."/content.txt","w");
  fwrite($fh_ptxt,$pagestr);
  fclose($fh_ptxt);
  
  //begin logging
  $fh_log=fopen($arr['saveto'].'/index.txt','w');
  fwrite($fh_log,$arr['scrapetitle']."\n");
  $files[]='index.txt';
  
  //scan urls;
  foreach((array) $arr['urllist'] as $k0 => $v0){
    $content = null;
    //filter url
    if(
      preg_match("/jpg$/i",$v0[1])
      +preg_match("/gif$/i",$v0[1])
      +preg_match("/png$/i",$v0[1])
       < 1
    ){
      continue;
    }
    //get filename
    preg_match("/\/([^\/]+$)/",$v0[1],$tmp);
    $fname = $tmp[1];
    
    //dl
    $content = $clib->dl->download($v0[1]);
    if(!$content){continue;}
    $fh_bin = fopen($arr['saveto']."/".$fname,"w");
    fwrite($fh_bin,$content."\n");
    fclose($fh_bin);
    //logging
    fwrite($fh_log,$v0[1]."\n");
    $files[]=$fname;
    
  }
  fclose($fh_log);
  
  //archive
//  $cmd="rar a -df {$sesskey}.rar {$arr['saveto']}";
//  system($cmd);print $cmd;
//  rename("{$sesskey}.rar","{$arr['scrapetitle']}.rar");

  $cmd="zip -rmj0 {$sesskey}.zip {$arr['saveto']}";
  system($cmd);
  //print $cmd;
  rename("{$sesskey}.zip","{$arr['scrapetitle']}.zip");
  $cmd="rm -rf {$arr['saveto']}";
  system($cmd);
  //print $cmd;
  
  return "{$arr['scrapetitle']}.zip";
}


//指定されたpathのファイル一覧
function dirList($path){
	//pline($path);
	$dh = opendir($path);
	$fnameList=array();
	while ($fname =readdir($dh)) {
		if(preg_match("/zip$/i",$fname)){
      $ftime= filemtime($path."/".$fname);
			$fnameList[$ftime] = $fname;
		}
	}
	closedir ($dh);
	if(count($fnameList)>1){krsort($fnameList);}
	return $fnameList;
}