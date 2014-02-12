<?php
/**
 * Pimpleの挙動確認目的
 */
class PimpleTest extends PHPUnit_Framework_TestCase {

    function testBasic() {
        $c = new Pimple();

        // parameter
        $c['hoge.name'] = 'hogehoge';
        $this->assertSame('hogehoge', $c['hoge.name']);

        // service
        // serviceは取得時に第一引数にPimleが渡されることになっている。
        // よってコンテナ上の他のサービス・パラメタにアクセスが可能。
        $c['fuga'] = function($c) {
            $obj = new stdClass();
            $obj->name = $c['hoge.name'];
            return $obj;
        };
        $this->assertSame('stdClass', get_class($c['fuga']));
        $this->assertSame('hogehoge', $c['fuga']->name);
    }


    function testProtect() {
        $c = new Pimple();

        $c['foo'] = function() { return rand(); };
        $this->assertTrue(is_int($c['foo']));

        // protect() でラップするとClosureが登録される。
        // パラメタをClosureで書く場合に使う・・のか・・？
        $c['bar'] = $c->protect(function() { return rand(); });
        $this->assertSame('Closure', get_class($c['bar']));
    }

    function testRaw() {
        $c = new Pimple();

        // 通常は自動的にClosureが実行され、サービスのインスタンスが返る
        $c['foo'] = function($c) {
            $obj = new stdClass();
            $obj->name = 'foo';
            return $obj;
        };
        $this->assertSame('stdClass', get_class($c['foo']));

        // raw() だと、Closure自体が返る
        $c['bar'] = function($c) {
            $obj = new stdClass();
            $obj->name = 'bar';
            return $obj;
        };
        $this->assertSame('Closure', get_class($c->raw('bar')));
    }

}
