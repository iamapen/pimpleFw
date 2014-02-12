<?php
namespace pimpleFw\Renderer;

class PhpTalRenderer implements RendererInterface {
    use BasicTrait;

    /** @var array */
    private $config;

    /** @var \PHPTAL */
    public $phptal;

    /**
     * PHPTAL用オプション
     * @var string[]
     */
    static private $phptalOptions= array(
        'outputMode', 'encoding', 'templateRepository',
        'phpCodeDestination', 'phpCodeExtension',
        'cacheLifetime', 'forceReparse',
    );

    public function __construct($config=array()) {
        $this->initialize($config);
    }

    /**
     * 初期化
     * @param array $config
     */
    public function initialize($config=array()) {
        $this->data = array();
        $this->config = array_fill_keys(static::$phptalOptions, null);
        if(!empty($config)) {
            foreach($config as $name=>$value) {
                $this->config($name, $value);
            }
        }
        $this->phptal = new \PHPTAL();
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
                case 'templateRepository':
                case 'phpCodeDestination':
                    if(!is_string($value) && !is_array($value)) {
                        throw new \InvalidArgumentException(
                            sprintf('The config parameter "%s" only accepts string.', $name)
                        );
                    }
                    break;
                case 'encoding':
                case 'phpCodeExtension':
                    if(!is_string($value)) {
                        throw new \InvalidArgumentException(
                            sprintf('The config parameter "%s" only accepts string.', $name)
                        );
                    }
                    break;
                case 'outputMode':
                case 'cacheLifetime':
                    if(!is_int($value) && !ctype_digit($value)) {
                        throw new \InvalidArgumentException(
                            sprintf('The config parameter "%s" only accepts int.', $name)
                        );
                    }
                    $value = (int)$value;
                    break;
                case 'forceReparse':
                    if(!is_bool($value) && !is_int($value) && !ctype_digit($value)) {
                        throw new \InvalidArgumentException(
                            sprintf('The config parameter "%s" only accepts bool.', $name)
                        );
                    }
                    $value = (bool)$value;
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
        foreach($this->config as $name=>$value) {
            if(isset($value) && in_array($name, static::$phptalOptions)) {
                $methodName = 'set'.ucfirst($name);
                if(!method_exists($this->phptal, $methodName)) {
                    $msg = sprintf('The accessor method to "%s" is not defined.', $name);
                    throw new \InvalidArgumentException($msg);
                }
            }
            switch($name) {
            case 'phpCodeDestination':
            case 'templateRepository':
                if('\\' === DIRECTORY_SEPARATOR) {
                    if(is_array($value)) {
                        $value = array_map(function($v) {
                            return str_replace('\\', '/', $v);
                        }, $value);
                    } else {
                        $value = str_replace('\\', '/', $value);
                    }
                    break;
                }
                $this->phptal->{$methodName}($value);
            }
        }
        if(strpos($templatePath, '/') === 0) {
            $templatePath = substr($templatePath, 1);
        }
        $data = array_merge($this->data, $data);
        foreach($data as $name=>$value) {
            $this->phptal->set($name, $value);
        }
        return $this->phptal->setTemplate($templatePath)->execute();
    }
}
