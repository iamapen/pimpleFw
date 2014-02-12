<?php
/**
 * アプリケーション共通初期処理
 */

include_once realpath(__DIR__.'/../../vendor/autoload.php');

use pimpleFw\Application;
use pimpleFw\Renderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Application();

// レンダラ・globalなテンプレート変数
$app->renderer = function(Application $app) {
    $renderer = new Renderer(
        array('template_dir'=>__DIR__.'/../templates')
    );
    $renderer->assign('app', $app);
    $renderer->assign('server', $app->request->server->all());
    return $renderer;
};

// レスポンスオブジェクトでレンダラから出力
$app->render = $app->protect(function($templatePath, array $data=array(), $status=200, $headers=array()) use ($app){
    $objRes = new Response($app->renderer->fetch($templatePath, $data), $status, $headers);
    $objRes->send();
});

// リクエストオブジェクト
$app->request = function(Application $app) {
    return Request::createFromGlobals();
};

// リクエスト変数を取得
$app->findVar = $app->protect(function($key, $name, $default=null) use($app){
    $value = null;
    /* @var $objReq Request */
    $objReq = $app->request;
    switch ($key) {
        case 'G':   // $_GET
            $value = $objReq->query->get($name);
            break;
        case 'P':   // $_POST
            $value = $objReq->request->get($name);
            break;
        case 'C':   // $_COOKIE
            $value = $objReq->cookies->get($name);
            break;
        case 'S':   // $_SERVER
            $value = $objReq->server->get($name);
            break;
    }
    if(!isset($value) ||
        (is_string($value) && strlen($value) === 0) ||
        (is_array($value) && count($value) == 0)
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

return $app;
