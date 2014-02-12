<?php
$app = include realpath(__DIR__.'/../app/app.php');

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
