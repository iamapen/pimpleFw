<?php
include_once realpath(__DIR__.'/../vendor/autoload.php');

use pimpleFw\Application;

$app = new Application();

// リクエスト変数を登録
$app->findVar = $app->protect(function($key, $name, $default=null){
    $value = null;
    switch($key) {
        case 'G':
            $value = isset($_GET[$name]) ? $_GET[$name] : null;
            break;
        case 'P':
            $value = isset($_POST[$name]) ? $_POST[$name] : null;
            break;
        case 'C':
            $value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
            break;
        case 'S':
            $value = isset($_SERVER[$name]) ? $_SERVER[$name] : null;
            break;
    }
    if(!isset($value) ||
        (is_string($value) && strlen($value) === 0) ||
        (is_array($value) && count($value) === 0)
    ) {
        $value = $default;
    }
    return $value;
});

// リクエスト変数の正規化
$app->normalize = $app->protect(function($value) use($app) {
    $filters = array(
        // HT,LF,CR,SP以外の制御コード(00-08,11,12,14-31,127,128-159)を除去
        function($val) {
            return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\xC2[\x80-\x9F]/S', '', $val);
        },
        // 改行コードを統一
        function($val) {
            return str_replace("\r", "\n", str_replace("\r\n", "\n", $val));
        },
    );
    foreach($filters as $filter) {
        $value = $app->map($filter, $value);
        return $value;
    }
});

// HTMLエスケープ
$app->escape = $app->protect(function($value, $default='') use ($app){
    return $app->map(function($value) use ($default) {
        $value = (string) $value;
        if(strlen($value) > 0) {
            return htmlspecialchars($value, ENT_QUOTES);
        }
        return $default;
    }, $value);
});

// 全要素に再帰処理
$app->map = $app->protect(function($filter, $value) use ($app){
    if(is_array($value) || $value instanceof \Traversable) {
        $results = array();
        foreach($value as $val) {
            $results[] = $map($filter, $val);
        }
        return $results;
    }
    return $filter($value);
});


$postData = array(
    'name' => $app->findVar('P', 'name'),
    'comment' => $app->findVar('P', 'comment'),
);

// ?name=<script>alert('hello')</script>]
// ?name[]=foo&name[]=<script>alert('hello')</script>
$name = $app->findVar('G', 'name');
?>
<html>
<body>
<h1>test</h1>

<form method="post" action="<?=$app->escape($app->findVar('S', 'REQUEST_URI'))?>">
<dl>
<dt>名前</dt>
<dd><input type="text" name="name" value="<?=$app->escape($postData['name'])?>">
<dt>コメント</dt>
<dd><textarea name="comment"><?=$app->escape($postData['comment'])?></textarea>
</dl>
<input type="submit" value="送信"/>
</form>
<hr>

<dl>
<dt>名前</dt>
<dd><?=$app->escape($postData['name'])?></dd>
<dt>コメント</dt>
<dd><pre><?=$app->escape($postData['comment'])?></pre></dd>
</dl>
</body>
</html>
