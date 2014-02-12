<?php
namespace pimpleFw\Renderer;

class PhpRenderer implements RendererInterface {
    use BasicTrait;

    /** @var array */
    private $config;

    public function __construct($config=array()) {
        $this->initialize($config);
    }

    /**
     * 初期化
     * @param array $config
     */
    public function initialize($config=array()) {
        $this->data = array();
        $this->config = array(
            'template_dir'=>null,
        );
        if(!empty($config)) {
            foreach($config as $name=>$value) {
                $this->config($name, $value);
            }
        }
        return $this;
    }

    /**
     * 設定の取得/設定
     *
     * 引数が1つ($name)の場合は設定値を返す。
     * 引数が2つ($name,$value)の場合は設定して$thisを返す。
     * @param string $name
     * @param mixed $value
     * @throws \InvalidArgumentException
     * @return mixed|\pimpleFw\Renderer
     */
    public function config($name) {
        switch (func_num_args()) {
        case 1:
            return $this->config[$name];
        case 2:
            $value = func_get_arg(1);
            if(isset($value)) {
                switch ($name) {
                case 'template_dir':
                    if(!is_string($value)) {
                        throw new \InvalidArgumentException(
                            sprintf('The config parameter "%s" only accepts string.', $name)
                        );
                    }
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf('The config parameter "%s" is not defined.', $name)
                    );
                }
                $this->config[$name] = $value;
            }
            return $this;
        }
        throw new \InvalidArgumentException('Invalid argument count.');
    }

    /**
     *
     * @param string $templatePath path to template file
     * @param array $data assign variables.
     * @return string 処理済テンプレート
     */
    public function fetch($templatePath, array $data) {
        $dir = $this->config('template_dir');
        if(isset($dir)) {
            $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        }
        $template = isset($dir) ? $dir.DIRECTORY_SEPARATOR.$templatePath : $templatePath;
        if('\\' === DIRECTORY_SEPARATOR) {
            $template = str_replace('\\', '/', $template);
        }
        if(false !== realpath($template)) {
            ob_start();
            $data = array_merge($this->data, $data);
            extract($data);
            include $template;
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }

        throw new \InvalidArgumentException(
            printf('The template "%s" is not exists.', $templatePath)
        );
    }
}
