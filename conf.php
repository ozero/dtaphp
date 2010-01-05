<?php

//スクリプトの最大実行時間（0で無限）
set_time_limit(0);

$conf=array();

//使用OSがWindowsか win/unix
$conf['os'] = (isset($_ENV['windir']))?'win':'unix';

//directory sepaleator string
$conf['dirspl']['win'] = "\\";
$conf['dirspl']['unix'] = "/";
define('DS',$conf['dirspl'][$conf['os']]);

//画像データを保存するディレクトリの指定(win)
$conf['img_dir'] = "img".DS.date("Y-m-d").DS;

//文字コード設定
$conf['cs']['_fs']['win'] = "sjis-win";
$conf['cs']['_fs']['unix'] = "utf-8";
define('ENC_LOC',$conf['cs']['_fs'][$conf['os']]);


//URL置換用の正規表現を記述する.半角英数のみ
//置き換え前(pattern)
$conf['url_replace'][0]["pattern"] = "/(ttp|http)/";
//置き換え後(replacement)
$conf['url_replace'][0]["replacement"] = "http";


//取得する画像のファイルタイプのMIME Typeを指定
$conf['file_type']['gif'] = 'image/gif';
$conf['file_type']['png'] = 'image/png';
$conf['file_type']['_.png'] = 'image/x-png';
$conf['file_type']['jpg'] = 'image/jpeg';
$conf['file_type']['jpeg'] = 'image/jpeg';
$conf['file_type']['zip'] = 'application/zip';
$conf['file_type']['jpe'] = 'application/octet-stream';

//画像判定関数(mime_content_typeまたはfinfo_file(※PECLの拡張モジュールFileinfoが必要))
$conf['func_mime_type'] = "mime_content_type";

//再試行回数
$conf['retry_count'] = 1;

//リトライ時に待つ秒
$conf['retry_wait'] = 2;

//タイムアウト(秒）
$conf['timeout'] = 120;

//UserAgentリスト。UserAgentはランダムで使用される
$conf['UserAgent'][0] = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
$conf['UserAgent'][1] = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727) Sleipnir/2.6.1";
$conf['UserAgent'][2] = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)";
$conf['UserAgent'][3] = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X; ja-jp) AppleWebKit/523.12.2 (KHTML, like Gecko) Version/3.0.4 Safari/523.12.2";
$conf['UserAgent'][4] = "Mozilla/5.0 (Windows; U; Windows NT 5.1; ja-JP; rv:1.9.0.11) Gecko/2009060215 Firefox/3.0.11";
$conf['UserAgent'][5] = "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)";



