<?php
/**
 * アプリケーション共通初期処理
 */

include_once realpath(__DIR__.'/../../vendor/autoload.php');

use pimpleFw\Application;
use pimpleFw\Configuration;
use pimpleFw\HttpException;
use pimpleFw\Renderer\PhpRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use pimpleFw\Renderer\PhpTalRenderer;

$app = new Application();

// Service: リクエストオブジェクト
$app->request = function(Application $app) {
    return Request::createFromGlobals();
};

// Service: レンダラ
// $app->renderer = function(Application $app) {
//     $renderer = new PhpRenderer(
//         array('template_dir'=>__DIR__.'/../templates')
//     );
//     // globalなテンプレート変数
//     $renderer->assign('app', $app);
//     $renderer->assign('server', $app->request->server->all());
//     return $renderer;
// };
$app->renderer = function(Application $app) {
    $renderer = new PhpTalRenderer([
        'outputMode'            => \PHPTAL::XHTML,
        'encoding'              => 'UTF-8',
        'templateRepository'    => realpath(__DIR__.'/../www'),
//         'phpCodeDestination'    => sys_get_temp_dir(),
        'phpCodeDestination'    => realpath(__DIR__.'/../../cache'),
        'forceReparse'          => true,
    ]);
    $renderer->assign('app', $app);
    $renderer->assign('server', $app->request->server->all());
    return $renderer;
};

// 設定オブジェクト
$app->config = new Configuration([
    'debug'         => true,
    'app_root'      => __DIR__,
    'web_root'      => realpath(__DIR__.'/../www'),
    'log_dir'       => realpath(__DIR__.'/../../logs'),
    'log_file'      => date('Y-m').'.log',
    'error_log'     => function($config){ return $config['log_dir'].'/'.$config['log_file']; },
    'error_view'    => 'error.html',
]);


// レスポンス作成
$app->render = $app->protect(function($templatePath, array $data=array(), $status=200, $headers=array()) use ($app){
    return new Response($app->renderer->fetch($templatePath, $data), $status, $headers);
});

// HTTPエラーを返す
$app->abort = $app->protect(
    function($statusCode = 500, $message = null, $headers = array()) use ($app) {
        throw new HttpException($statusCode, $headers, $message);
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
    // すべてのエラーを例外にする
    error_reporting(E_ALL);
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    });

    try {
        $method = $app->request->getMethod();
        $handlerName = 'on' . ucfirst(strtolower($method));
        if(!$app->offsetExists($handlerName)) {
            throw new HttpException(405);
        }
        $response = $app->{$handlerName}($app, $method);
    } catch(\Exception $e) {
        var_dump($e->getMessage());
        error_log(sprintf("[%s] %s\n", date('Y-m-d H:i:s'), (string)$e),
            3, $app->config->error_log
        );
        $statusCode = 500;
        $statusMessage = null;
        $message = null;
        $headers = [];

        if($e instanceof  HttpException) {
            $statusCode = $e->getCode();
            $statusMessage = $e->getStatusMessage();
            $message = $e->getMessage();
            $headers = $e->getHeaders();
        }
        $assignVars = [
                'title' => 'エラーが発生しました',
                'statusMessage' => $statusMessage,
                'message'       => $message,
                'exception'     => $e,
                'exception_class'   => get_class($e),
        ];
        $response = $app->render($app->config->error_view,
            $assignVars,
            $statusCode,
            $headers
        );
    }
    $response->send();
});

return $app;
