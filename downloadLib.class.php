<?php

//ダウンロードクラス
class downloadLib{

  //設定
  var $conf;
  
  //costructor
  public function downloadLib($cs_web='sjis-win'){
    require('conf.php');
    $this->conf = $conf;
    //$this->fh=fopen("log/downloadLib.log".date('-Ymd-His').".txt","w");
    $this->cs_web = $cs_web;
    $this->service = '_undef';
    $this->file_dir=array();
  }

  //ロギング
  public function err($str,$enc=''){
    //fwrite($this->fh, $str);
    $enc=($enc=='')?$this->cs_web:$enc;
    $str=mb_convert_encoding($str,ENC_LOC,$enc);
    print $str;
  }

  //indexOf(マルチバイト文字列検索）
  public function indexOf($haystack, $needle, $offset=0 , $encoding="auto"){
    return mb_strpos($haystack, $needle, $offset,$encoding);
  }

  //マルチバイト文字列置き換え
  public function mb_str_replace( $search,$replace,$haystack, $offset=0,$encoding='auto'){
      $len_sch=mb_strlen($search,$encoding);
      $len_rep=mb_strlen($replace,$encoding);
      
      while (($offset=mb_strpos($haystack,$search,$offset,$encoding))!==false){
          $haystack=mb_substr($haystack,0,$offset,$encoding)
              .$replace
              .mb_substr($haystack,$offset+$len_sch,1000,$encoding);
          $offset=$offset+$len_rep;
          if ($offset>mb_strlen($haystack,$encoding))break;
      }
      return $haystack;
  }

  //Windowsのフォルダ名に使えない文字を全角にエスケープ
  public function dir_name_escape($subject,$encoding='auto'){

    if($this->conf['os']!='win'){return $subject;}
    
    $subject = $this->mb_str_replace('"','”',$subject,0,$encoding);
    $subject = $this->mb_str_replace('*','＊',$subject,0,$encoding);
    $subject = $this->mb_str_replace('/','／',$subject,0,$encoding);
    $subject = $this->mb_str_replace(':','：',$subject,0,$encoding);
    $subject = $this->mb_str_replace('<','＜',$subject,0,$encoding);
    $subject = $this->mb_str_replace('>','＞',$subject,0,$encoding);
    $subject = $this->mb_str_replace('?','？',$subject,0,$encoding);
    $subject = $this->mb_str_replace("\\",'¥',$subject,0,$encoding);
    $subject = $this->mb_str_replace('|','｜',$subject,0,$encoding);
    return $subject;
  }




  //ダウンロード関数
  //$url = ダウンロードするファイルのあるURL
  //$method = GET/POST
  //$param = array("key"=>"キー","data"=>"データ")または、「key=キー&data=データ」的なパラメータを渡すことができる。
  public function download($url,$method="GET",$param=""){
    //if(preg_match($this->conf['ignoreurl_preg'],$url) > 0){return false;}
    $url=preg_replace("/[\"\']/","",$url);
    $this->err("D:dl[m:{$method}][u:{$url}][p:{$param}]\n");
    
    $parse_url = @parse_url($url);
    if($parse_url == false){
      return false;
    }
    $url = $parse_url['scheme']."://".$parse_url['host'].$parse_url['path'];

    if(strlen($param)<=0){
      $param = $parse_url['query'];
    }
    
    //
    for($i = 0; $i < $this->conf['retry_count'] ; $i++){
      $ret = $this->curl_download($url,$method,$param);
      if($ret != false){
        break;
      }
      $this->err("D:failed[{$url}]:retrying:\n");
    }
    if($ret === false){
      return false;
    }
    return $ret;

  }


  //ダウンロードに使うcURLを用いた内部関数
  //$url = ダウンロードするファイルのあるURL
  //$method = GET/POST
  //$param = array("key"=>"キー","data"=>"データ")または、「key=キー&data=データ」的なパラメータを渡す
  public function curl_download($url,$method='GET',$param=""){

    //パラメータを整理
    $param_string = "";
    if(count($param)>0 && is_array($param)){
      foreach($param as $key => $value){
        $param_string .= $key.'='.urlencode($value).'&';
      }
      rtrim($param_string,'&');
    }else{
      $param_string = $param;
    }

    //curl接続開始
    $ch = curl_init(); 

    //リザルトを受け取る
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

    //オプション設定
    //URL
    curl_setopt($ch,CURLOPT_URL,$url);


    //POST
    if(preg_match("/POST/i",$method)){
      curl_setopt($ch,CURLOPT_POST,1);
      //パラメータ
      if($param_string != ""){
        curl_setopt($ch,CURLOPT_POSTFIELDS,$param_string); 
      }
    //GET
    }else if(preg_match("/GET/i",$method)){
      curl_setopt($ch,CURLOPT_GET,1);
      //パラメータ
      if($param_string != ""){
        curl_setopt($ch,CURLOPT_GETFIELDS,$param_string); 
      }
    }else{
      $this->err("D:f:curl_download: invalid method was passed.\n");
      return false;
    }

    //タイムアウト
    curl_setopt($ch,CURLOPT_TIMEOUT,$this->conf['timeout']);
    //UserAgentはランダムで使用する
    $tmp=array_rand($this->conf['UserAgent'],1);
    $UA=$this->conf['UserAgent'][$tmp];
    curl_setopt($ch,CURLOPT_USERAGENT, $UA);
    //$this->err("D:UA: [".$UA."]\n");

    //その他のパラメータ
    //Loactionがあれば再帰的にたどり続ける
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    //Locationがあれば自動的にリファラをつける
    curl_setopt($ch,CURLOPT_AUTOREFERER,1);

    //GET/POSTリクエストを送信
    $result = curl_exec($ch);
    //$this->err("D:f:curl_download:result\n".print_r(curl_getinfo($ch),true)."\n");
    $info = curl_getinfo($ch);

    //中身が空、またはタイムアウト等
    if(empty($result)){
      curl_close($ch);
      $this->err("D:f:curl_download: null content or timeout.\n");
      return false;
    }

    //HTTPコード取得(302,303,307も追いかけるようにしたいね）
    if(empty($info['http_code'])){
      $this->err("D:f:curl_download: null http code\n");
      return false;
    }
    if(intval($info['http_code'])!=200){
      $this->err("D:f:curl_download: http code isn't 200[{$info['http_code']}]\n");
      return false;
    }

    //接続を閉じる
    curl_close($ch);


    //結果に「metaタグのrefresh」があるか調べる
    //<META HTTP-EQUIV="refresh" CONTENT="0; URL=hoge.html">のようなリダイレクトがあれば再帰的に辿る
    $meta_refresh_url = $this->parse_meta_refresh($result);


    //リフレッシュタグが存在した場合は再帰的にリダイレクト
    if($meta_refresh_url!==false){
      //URL解析
      $meta_parse_url = parse_url($meta_refresh_url);
      //scheme指定があった場合は絶対URLと見なす
      if(strlen($meta_parse_url['scheme'])){
        $url = $parse_url;
      //その他は相対URLと見なす
      }else{
        $parse_url = parse_url($url);
        rtrim("/",$meta_refresh_url);
        $url = $parse_url['scheme']."://".$parse_url['host']."/".$meta_refresh_url;
      }
      return $this->curl_download($url,$method,$param);
    }

    //データ内容を返す
    return $result;
  }


  //refreshタグがあれば解析して、その１番目を返す
  public function parse_meta_refresh(&$string){

    preg_match_all('/<[\s]*meta[\s]*http-equiv="?REFRESH"?' . '[\s]*content="?[0-9]*;[\s]*URL[\s]*=[\s]*([^>"]*)"?' . '[\s]*[\/]?[\s]*>/si', $string,$matches);
    if(count($matches[1])>0){
      return $matches[1][0];
    }
    return false;
  }

  //MIME-typeの判別
  public function get_mime_type(&$string){

    //テンポラリファイルにデータ書き込み
    $tmpfname = tempnam(getcwd()."tmp", "tmp");
    $fp = fopen($tmpfname,"w");
    fwrite($fp,$string);
    fclose($fp);

    //mime-typeの算出
    if($this->conf['func_mime_type']=="mime_content_type"){
      $mime_content_type = mime_content_type($tmpfname);
    }else if($this->conf['func_mime_type']=="finfo_file"){
      if(function_exists("finfo_file")){
        $finfo = finfo_open(FILEINFO_MIME);
        $mime_content_type = finfo_file($finfo, $filename);
      }
    }

    //テンポラリファイルの削除
    unlink($tmpfname);
    return $mime_content_type;
  }

  //URLからファイル名を生成する
  //$url = URL
  //$ext = 拡張子
  public function get_file_name($url,$ext){

    $parse_url = parse_url($url);
    $filename = basename($parse_url['path']);;
    $filename = rtrim($filename,".".$ext);
    $filename = $filename . "." . $ext;

    return $filename;
  }


  //同名の何ぞなファイルが有るか
  public function save_path_detect($url,$subject,$file_ext){
    //ファイル名はpathから適当につける
    $this->file_name = $this->get_file_name($url,$file_ext);
    
    //ディレクトリ名に使えない文字のエスケープ
    $subject = $this->dir_name_escape($subject);
    //パス指定
    $file_dir_str = $this->conf['img_dir'].DS.$this->service.DS.$subject.DS;
    $this->file_dir_str = mb_convert_encoding($file_dir_str,ENC_LOC,'auto');
    $this->file_dir=array(
      'a_imgdir'=>$this->conf['img_dir'],
      'b_service'=>$this->service,
      'c_subject'=>$subject,
    );
    $this->file_path = $this->file_dir_str.$this->file_name;
    
    if(is_file($this->file_path)){
      if(filesize($this->file_path) > 0){
        $this->err("D:already gotten:[{$url}]\n");
        return false;
      }
    }
    return true;
  }
  
  
  //画像を保存する
  public function save_file($url,$subject,&$url_data,$file_ext){
    
    //ファイル名、保存先を生成する
    $this->save_path_detect($url,$subject,$file_ext);
    $file_path = $this->file_path;
    $file_name = $this->file_name;
    $file_dir_str = $this->file_dir_str;
    //フォルダ生成
    $this->err("D:writepath: {$file_path}[{$file_ext}][{$url}]\n",ENC_LOC);
    if(!file_exists($file_dir_str)){
      mkdir($file_dir_str, 0755, true);
      $this->err("D:MKDIR: {$file_dir}\n");
    }
    
    //まったく同じファイルがあった場合はmd5比較して差がないか確認
    if(is_file($file_path)){
      $url_data_md5 = md5($url_data);
      $file_data_md5 = md5(file_get_contents($file_path));
      //比較結果が異なっていた場合
      if($url_data_md5 != $file_data_md5){
        //ファイル名を変更
        $file_path_new = $file_path."_bak".date('Hmd-His').".".$file_ext;
        rename($file_path, $file_path_new);
      //比較結果が一致していた場合
      }else{
        $this->err("D:already gotten, same md5.[{$file_path}]\n");
        return false;
      }
    }

    //ファイル書き込み
    $ret = @file_put_contents($file_path ,$url_data);
    if($ret != false){
      $this->err("D:wrote: {$file_path}\n",ENC_LOC);
      //wait
      sleep($this->conf['retry_wait']);
      return $file_name;
    }else{
      //なんらかの原因で保存失敗した場合はテンポラリファイル名を使って再試行
      $file_path = tempnam($file_dir_str,"temp");
      $ret = @file_put_contents($file_path ,$url_data);
      if($ret != false){
        $this->err("D:wrote: {$file_path}\n",ENC_LOC);
        //wait
        sleep($this->conf['retry_wait']);
        return basename($file_path);
      }
    }

    return false;

  }



}


