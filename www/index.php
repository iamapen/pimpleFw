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

// ?name=foo
// ?name=<script>alert('hello')</script>
?>
<html>
<body>
<h1>test</h1>
<p><?=$app->findVar('G', 'name')?></p>
</body>
</html>