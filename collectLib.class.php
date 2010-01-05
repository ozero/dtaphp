<?php


//ファイル収集クラス
class collectLib{

	//設定
	var $conf;
	var $dl;

	//コンストラクタ
	public function collectLib(){
		require('conf.php');
		$this->conf = $conf;
		$this->dl = new downloadLib();
    //$this->fh=fopen("log/collectLib.log".date('-Ymd-His').".txt","w");
	}
	

  //logging
  public function err($str,$enc=''){
    //fwrite($this->fh, $str);
    $enc=($enc=='')?'utf-8':$enc;
    $str=mb_convert_encoding($str,ENC_LOC,$enc);
    print $str;
  }

  //convenc: webから内部エンコードへ
  public function e_web2int(&$str,$enc_web) {
    $str = mb_convert_encoding($str,'utf-8',$enc_web);
    return;
  }

  //convenc: webからローカルファイルシステムへ
  public function e_web2loc(&$str,$enc_web) {
    $str = mb_convert_encoding($str,ENC_LOC,$enc_web);
    return;
  }


	//文字列の中に含まれるURL一覧を作成する
	public function generate_url_list($string){

		$url_list = array();
		$matches = array();

		//データ丸ごと取得
		preg_match_all("/h?ttp(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/",$string,$matches);

		if(count($matches[0])>0){
			//URL置き換え
			if(count($this->conf['url_replace'])>0){
				foreach($this->conf['url_replace'] as $url_replace){
					foreach($matches[0] as $match_key => $match_url){
						$matches[0][$match_key] = preg_replace($url_replace['pattern'],$url_replace['replacement'],$match_url);
					}
				}
			}
		}
		return $url_list;
	}


	public function generate_link_list($base_url,$string){

		$link_list = array();

		//a hrefリンクの中から丸ごとリンクを取り出す
		preg_match_all("/<a[^>]+href=[\"']?([-_.!~*'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)[\"']?[^>]*>(.*?)<\/a>/ims",$string,$matches);
		if(count($matches[1])>0){

			$parse_url = @parse_url($base_url);
			$dir_url = dirname($base_url);

			foreach($matches[1] as $index=> $link){

				//絶対URLへ置き換え
				//http://リンク
				if(preg_match("/^http/i",$link)){
					$url = $link;
				//javascript
				}elseif(preg_match("/^javascript/",$link)){
					continue;
			        }elseif(preg_match("|^/|",$link)){
				//相対パス（スラッシュから始まるもの）
					$url = $parse_url["scheme"]."://".$parse_url["host"].$link;
				}else{
				//相対パス（スラッシュではじまらないもの）
					$url = $dir_url."/".$link;
				}
				

				$title = trim($matches[2][$index]);
				$link_list[] = array($title,$url);

			}

		}
		return $link_list;
	}


}
