<?php
namespace pimpleFw;

/**
 * 設定オブジェクト
 *
 * Pimpleでは設定値を書き換えられないので導入。
 * マジックメソッドでプロパティアクセスした配列は参照を返さないため。
 */
class Configuration implements \ArrayAccess, \IteratorAggregate {
    /**
     * 設定値 name=>value
     * @var mixed[]
     */
    private $attributes;

    /**
     *
     * @param 設定(name=>value) mixed[] $attributes
     * @throws \InvalidArgumentException
     */
    public function __construct($attributes=[]) {
        if((!is_array($attributes)) && (!($attributes instanceof \Traversable))) {
            throw new \InvalidArgumentException(
                sprintf('The attributes is not Array and not Traversable. type:"%s"',
                    (is_object($attributes)? get_class($attributes) : gettype($attributes))
                )
            );
        }

        if(!empty($attributes)) {
            $this->attributes = $this->import($attributes);
        } else {
            $this->attributes = [];
        }
    }

    /** @var string $offset */
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->attributes);
    }
    /**
     * @var string $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        if(!array_key_exists($offset, $this->attributes)) {
            throw new \InvalidArgumentException(
                sprintf('The attribute "%s" does not exists.', $offset)
            );
        }
        return (is_callable($this->attributes[$offset]))
            ? $this->attributes[$offset]($this)
            : $this->attributes[$offset];
    }
    /**
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        if(!array_key_exists($offset, $this->attributes)) {
            throw new \InvalidArgumentException(
               sprintf('The attribute "%s" does not exists', $offset)
            );
        }
        $this->attributes[$offset] = $value;
    }
    /**
     * @param string $offset
     */
    public function offsetUnset($offset) {
        if(array_key_exists($offset, $this->attributes)) {
            unset($this->attributes[$offset]);
        }
    }

    /**
     * magic setter
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->offsetSet($name, $value);
    }
    /**
     * magic getter
     * @param string $name
     */
    public function __get($name) {
        return $this->offsetGet($name);
    }
    /**
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args) {
        if(array_key_exists($name, $this->attributes)) {
            $value = $this->attributes[$name];
            // Closureなら実行
            if(is_callable($value)) {
                return call_user_func($value, $args);
            }
            // valueならreturn
            return $value;
        }
        throw new \BadMethodCallException(
           sprintf('Undefined Method "%s" called.', $name)
        );
    }
    /**
     * @return \ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * 属性値を配列から再帰的に設定
     *
     * 要素が配列またはTraversableの場合は自身でラッピングして
     * 配列アクセスとプロパティアクセスを提供する
     * @param array|Traversable $attributes
     */
    private function import($attributes) {
        foreach($attributes as $name=>$value) {
            $attributes[$name] = (is_array($value) || $value instanceof \Traversable)
            ? new static($value)
            : $value;
        }
        return $attributes;
    }
}
