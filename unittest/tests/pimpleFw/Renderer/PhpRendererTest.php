<?php
namespace pimpleFw\Renderer;

class PhpRendererTest extends \PHPUnit_Framework_TestCase {
    function testTest() {
        $obj = new PhpRenderer();

        $foo = new \stdClass();
        $foo->name = 'foo!!';
        $obj->assign('foo', $foo);

        $templatePath = __DIR__.'/../../../fixtures/template/purePhp.php';
        $expected = '<span>foo!!</span>';
        $result = $obj->fetch($templatePath, []);
        $this->assertSame($expected, $result);
    }
}
