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

// HTMLエスケープ
$app->escape = $app->protect(function($value, $default=''){
    $map = function($filter, $value) use (&$map) {
        if(is_array($value) || $value instanceof \Traversable) {
            $results = array();
            foreach($value as $val) {
                $results[] = $map($filter, $val);
            }
            return $results;
        }
        return $filter($value);
    };
    return $map(function($value) use ($default) {
        $value = (string) $value;
        if(strlen($value) > 0) {
            return htmlspecialchars($value, ENT_QUOTES);
        }
        return $default;
    }, $value);
});

// ?name=<script>alert('hello')</script>]
// ?name[]=foo&name[]=<script>alert('hello')</script>
$name = $app->findVar('G', 'name');
?>
<html>
<body>
<h1>test</h1>
<?php if(is_array($name)): ?>
<?php foreach($name as $_name):?>
<p><?=$app->escape($_name)?></p>
<?php endforeach;?>
<?php else: ?>
<p><?=$app->escape($name)?></p>
<?php endif;?>
</body>
</html>
