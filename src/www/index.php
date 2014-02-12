<?php
$app = include realpath(__DIR__.'/../app/app.php');

$postData = array(
    'name' => $app->findVar('P', 'name'),
    'comment' => $app->findVar('P', 'comment'),
);

$app->render('index.php', array('postData'=>$postData));

