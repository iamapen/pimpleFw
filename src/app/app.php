<?php
/**
 * アプリケーション共通初期処理
 */

include_once realpath(__DIR__.'/../../vendor/autoload.php');

use pimpleFw\Application;
use pimpleFw\Renderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

$app = new Application();

// Service: リクエストオブジェクト
$app->request = function(Application $app) {
    return Request::createFromGlobals();
};

// Service: レンダラ
$app->renderer = function(Application $app) {
    $renderer = new Renderer(
        array('template_dir'=>__DIR__.'/../templates')
    );
    // globalなテンプレート変数
    $renderer->assign('app', $app);
    $renderer->assign('server', $app->request->server->all());
    return $renderer;
};

// レスポンス作成
$app->render = $app->protect(function($templatePath, array $data=array(), $status=200, $headers=array()) use ($app){
    return new Response($app->renderer->fetch($templatePath, $data), $status, $headers);
});

// リダイレクトレスポンス作成
$app->redirect = $app->protect(function($url, $status=303, $headers=array()) use($app){
    $navigate = $url;
    if(false === strpos($url, '://')) {
        $navigate = $app->request->getSchemeAndHttpHost() . $url;
    }
    return new RedirectResponse($navigate, $status, $headers);
});

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

/*
 * リクエストハンドラ登録
 * 引数で指定されたHTTPメソッドに対応するコールバックを生成、onXXX()として登録。
 * @param string $allowableMethod HTTPメソッドの '|' 区切り
 * @param Closure $function 処理
 */
$app->on = $app->protect(function($allowableMethod, $function) use ($app){
    $allowableMethods = explode('|', $allowableMethod);
    $handler = $app->protect(function(Application $app, $method) use ($function) {
        return $function($app, $method);
    });

    if(in_array('GET', $allowableMethods)) {
        $app->onGet = $handler;
    }
    if(in_array('POST', $allowableMethods)) {
        $app->onPost = $handler;
    }
    if(in_array('PUT', $allowableMethods)) {
        $app->onPut = $handler;
    }
    if(in_array('DELETE', $allowableMethods)) {
        $app->onDelete = $handler;
    }
});

// アプリケーション実行
$app->run = $app->protect(function() use ($app){
   $method = $app->request->getMethod();
   $handlerName = 'on' . ucfirst(strtolower($method));
   if(!$app->offsetExists($handlerName)) {
       $response = new Response('Method Not Allowed', 405);
   } else {
       try {
           $response = $app->{$handlerName}($app, $method);
       } catch(\Exception $e) {
           $response = new Response('Internal Server Error', 500);
       }
   }
   $response->send();
});

return $app;
