<?php
use pimpleFw\Application;

$app = include realpath(__DIR__.'/../app/app.php');

$app->on('GET|POST', function(Application $app, $method){
    $errors = array();

    $postData = array(
        'name' => $app->findVar('P', 'name'),
        'comment' => $app->findVar('P', 'comment'),
    );

    // 設定を動的に切り替える
    $app->config->debug = isset($postData['enable_debug']);

    if(isset($form['move_log_dir'])) {
        $app->config->log_dir = $app->config->web_root;
    }

    if($method === 'POST') {
        if(null !== $app->findVar('P', 'trigger-notice')) {
            trigger_error('[E_USER_NOTICE]PHPエラーのテストです', E_USER_NOTICE);
        }
        if(null !== $app->findVar('P', 'trigger-warning')) {
            trigger_error('[E_USER_WARNING]PHPエラーのテストです', E_USER_WARNING);
        }
        if(null !== $app->findVar('P', 'trigger-error')) {
            trigger_error('[E_USER_WARNING]PHPエラーのテストです', E_USER_ERROR);
        }

        if(null !== $app->findVar('P', 'throw-http-exception-400')) {
            $app->abort(400, 'HttpException[400]のテストです');
        }
        if(null !== $app->findVar('P', 'throw-http-exception-400')) {
            $app->abort(403, 'HttpException[403]のテストです');
        }
        if(null !== $app->findVar('P', 'throw-http-exception-400')) {
            $app->abort(404, 'HttpException[404]のテストです');
        }
        if(null !== $app->findVar('P', 'throw-http-exception-400')) {
            $app->abort(405, 'HttpException[405]のテストです');
        }

        if(null !== $app->findVar('P', 'throw-runtime-exception')) {
            throw new RuntimeException('RuntimeExceptionのテストです');
        }


        if(strlen($postData['name']) === 0) {
            $errors['name'] = '名前を入力してください';
        } else if(mb_strlen($postData['name']) > 20) {
            $errors['name'] = '名前は20文字以内で入力してください';
        }
        if(strlen($postData['comment']) === 0) {
            $errors['comment'] = 'コメントを入力してください';
        } else if(mb_strlen($postData['comment']) > 50) {
            $errors['comment'] = 'コメントは50文字以内で入力してください';
        }
        if(empty($errors)) {
            // $app->addFlashMessage('success', '投稿を受け付けました');
            return $app->redirect('/');
        }
    }

//     $templateName = 'index.php';
    $templateName = 'index.html';
    return $app->render($templateName, array(
        'postData'=>$postData,
        'errors'=>$errors,
    ));
});

$app->run();
