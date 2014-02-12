<?php
namespace pimpleFw\Renderer;

class PhpTalRendererTest extends \PHPUnit_Framework_TestCase {
    function testTest() {
        $obj = new PhpTalRenderer();

        $foo = new \stdClass();
        $foo->name = 'foo!!';
        $obj->assign('foo', $foo);

        $templatePath = __DIR__.'/../../../fixtures/template/phpTal.html';
        $expected = '<span>foo!!</span>';
        $result = $obj->fetch($templatePath, []);
        $this->assertSame($expected, $result);
    }
}
