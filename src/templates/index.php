<html>
<body>
<h1>俺のフレームワーク@<?=$app->escape($server['HTTP_HOST'])?></h1>

<form method="post" action="<?=$app->escape($server['REQUEST_URI'])?>">
<?php if(count($errors) > 0):?>
<ul>
<?php foreach($errors as $error):?>
<li><?=$app->escape($error)?></li>
<?php endforeach;?>
</ul>
<?php endif?>

<dl>
<dt>名前</dt>
<dd><input type="text" name="name" value="<?=$app->escape($postData['name'])?>"></dd>
<dt>コメント</dt>
<dd><textarea name="comment"><?=$app->escape($postData['comment'])?></textarea></dd>
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