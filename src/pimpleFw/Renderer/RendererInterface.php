<?php
namespace pimpleFw\Renderer;

interface RendererInterface {
    /**
     * テンプレートに変数を割り当てる
     * @param string $name
     * @param mixed $value
     */
    public function assign($name, $value);

    /**
     * 出力
     * @param string $templatePath
     * @param array $data
     */
    public function render($templatePath, array $data);

    /**
     * 変数を処理して返す
     * @param string $templatePath
     * @param array $data
     * @return string
     */
    public function fetch($templatePath, array $data);
}
