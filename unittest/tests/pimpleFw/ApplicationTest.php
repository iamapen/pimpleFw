<?php
namespace pimpleFw;

class ApplicationTest extends \PHPUnit_Framework_TestCase {

    /**
     * 通常のPimpleのプロパティアクセスのテスト
     */
    function testPimplePropertyAccess() {
        $app = new Application();

        $app['parent'] = function(Application $app) {
            $parent = new \stdClass();
            $parent->name = 'parent';
            return $parent;
        };

        $app['child'] = function(Application $app) {
            $child = new \stdClass();
            $child->name = 'child';
            $child->parent = $app['parent'];
            return $child;
        };

        $this->assertSame('stdClass', get_class($app['parent']));
        $this->assertSame('parent', $app['parent']->name);

        $this->assertSame('stdClass', get_class($app['child']));
        $this->assertSame('child', $app['child']->name);

        $this->assertSame('stdClass', get_class($app['child']->parent));
        $this->assertSame('parent', $app['child']->parent->name);
    }

    /**
     * 拡張したプロパティアクセスのテスト
     */
    function testPropertyAccess() {
        $app = new Application();

        $app->parent = function(Application $app) {
            $parent = new \stdClass();
            $parent->name = 'parent';
            return $parent;
        };

        $app->child = function(Application $app) {
            $child = new \stdClass();
            $child->name = 'child';
            $child->parent = $app->parent;
            return $child;
        };

        $this->assertSame('stdClass', get_class($app->parent));
        $this->assertSame('parent', $app->parent->name);

        $this->assertSame('stdClass', get_class($app->child));
        $this->assertSame('child', $app->child->name);

        $this->assertSame('stdClass', get_class($app->child->parent));
        $this->assertSame('parent', $app->child->parent->name);
    }

    /**
     * 通常のPimpleのメソッドコールのテスト
     *
     * __invoke() を実装したオブジェクトにプロパティアクセスすると実行。
     */
    function testPimpleMethodCall() {
        $app = new Application();
        $app['invoker'] = new Invokable();

        $this->assertSame('Invokable', $app['invoker']);
    }

    /**
     * 拡張したメソッドコールのテスト。
     */
    function testMethodCall() {
        $app = new Application();
        $app->invoker = new Invokable();

        $this->assertSame('Invokable', $app->invoker);
        $this->assertSame('Invokable', $app->invoker());
    }

    /**
     * 通常のPimpleのClosure実行
     */
    function testPimpleClosure() {
        $counter = 0;
        $app = new Application();
        $app['count'] = $app->protect(function() use (&$counter){
            $counter++;
            return $counter;
        });

        $this->assertSame(1, $app['count']());
        $this->assertSame(2, $app['count']());
    }

    /**
     * 拡張したClosure実行
     */
    function testClosure() {
        $counter = 0;
        $app = new Application();
        $app->count = $app->protect(function() use (&$counter) {
            $counter++;
            return $counter;
        });

        $this->assertSame(1, $app->count());
        $this->assertSame(2, $app->count());
    }

    /**
     * イベント登録のテスト
     */
    function testExecute() {
        $app = new Application();

        $app->registerEvent('init');
        $app->addHandler('init', function(Application $app) {
            $app->counter = 0;
            return $app->counter;
        });

        $app->registerEvent('count');
        $app->addHandler('count', function(Application $app){
            $app->counter++;
            return $app->counter;
        });

        $this->assertSame(0, $app->execute('init'));
        $this->assertSame(1, $app->execute('count'));
        $this->assertSame(2, $app->execute('count'));
        $this->assertSame(3, $app->count());
        $this->assertSame(4, $app->execute('count'));
        $this->assertSame(0, $app->init());
    }

    /**
     * 連鎖的にイベントを起こすことができる
     */
    function testChainEvent() {
        $app = new Application();
        $app->counter = 0;

        $app->registerEvent('three');
        $app->addHandler('three', function(Application $app) {
            $app->counter++;
            return $app->execute('two');
        });
        $app->registerEvent('two');
        $app->addHandler('two', function(Application $app){
            $app->counter++;
            return $app->execute('one');
        });
        $app->registerEvent('one');
        $app->addHandler('one', function(Application $app){
            $app->counter++;
            return $app->counter;
        });

        $this->assertSame(3, $app->three());
        $this->assertSame(5, $app->two());
        $this->assertSame(6, $app->one());
        $this->assertSame(9, $app->three());
        $this->assertSame(11, $app->two());
        $this->assertSame(12, $app->one());
    }
}

class Invokable {
    public function __invoke() {
        return 'Invokable';
    }
}
