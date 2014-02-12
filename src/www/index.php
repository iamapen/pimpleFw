<?php
use pimpleFw\Application;

$app = include realpath(__DIR__.'/../app/app.php');

$app->on('GET|POST', function(Application $app, $method){
    $errors = array();

    $postData = array(
        'name' => $app->findVar('P', 'name'),
        'comment' => $app->findVar('P', 'comment'),
    );

    if($method === 'POST') {
        if(strlen($postData['name']) === 0) {
            $errors['name'] = '名前を入力してください';
        } else if(mb_strlen($postData['name']) > 20) {
            $errors['name'] = '名前は20文字以内で入力してください';
        }
        if(strlen($postData['comment']) === 0) {
            $erros['name'] = 'コメントを入力してください';
        } else if(mb_strlen($postData['comment']) > 50) {
            $errors['name'] = 'コメントは50文字以内で入力してください';
        }
        if(empty($errors)) {
            // $app->addFlashMessage('success', '投稿を受け付けました');
            return $app->redirect('/');
        }
    }

    return $app->render('index.php', array(
        'postData'=>$postData,
        'errors'=>$errors,
    ));
});

$app->run();
