<?php
namespace pimpleFw\Renderer;

trait BasicTrait {
    /**
     * $name=>$value
     * @var mixed[]
     */
    private $data;

    public function assign($name, $value) {
        $this->data[$name] = $value;
    }

    public function render($templatePath, array $data) {
        echo $this->fetch($view, $data);
    }

    abstract public function fetch($templatePath, array $data);
}
