<?php
namespace pimpleFw;

/**
 * Pimple拡張
 *
 * ArrayAccessによる利用を前提としたPimpleに、
 * プロパティアクセスとメソッドコールを実装して、
 * 任意のcallbackの配列をイベントとして実行できるようにしたもの。
 *
 * イベントとそれに対するcallbackを登録・実行できる機能も追加。
 */
class Application extends \Pimple {
    /** @var callable[] */
    private $handlers;

    public function __construct(array $values=array()) {
        parent::__construct($values);
        $this->handlers = array();
    }

    /**
     * for property access
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return parent::offsetGet($name);
    }
    /**
     * for property access
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        return parent::offsetSet($name, $value);
    }
    public function __call($name, $args) {
        if(parent::offsetExists($name)) {
            $value = parent::offsetGet($name);
            if(is_callable($value)) {
                return call_user_func_array($value, $args);
            }
            return $value;
        }

        if(array_key_exists($name, $this->handlers)) {
            switch(count($args)) {
                case 0:
                    return $this->execute($name);
                case 1:
                    return $this->execute($name, $args[0]);
                case 2:
                    return $this->execute($name, $args[0], $args[1]);
                case 3:
                    return $this->execute($name, $args[0], $args[1], $args[2]);
                case 4:
                    return $this->execute($name, $args[0], $args[1], $args[2], $args[3]);
            }
        }
        throw new \BadMethodCallException(
            sprintf('Undefined Method "%s" called.', $name)
        );
    }

    /**
     * イベントを登録
     * @param string $event
     * @param callable[] $handlers (optional)
     * @return Application
     * @throws \InvalidArgumentException
     */
    public function registerEvent($event, $handlers=null) {
        if(array_key_exists($event, $this->handlers)) {
            throw new \InvalidArgumentException(
                sprintf('The event "%s" is already defined.', $event)
            );
        }
        $this->handlers[$event] = array();
        if(isset($handlers)) {
            if(!is_array($handlers)) {
                throw new \InvalidArgumentException(
                    sprintf('The event "%s" handlers is not array. type:%s', $event, gettype($handlers))
                );
            }
            foreach($handlers as $handler) {
                $this->addHandler($event, $handler);
            }
        }
        return $this;
    }

    /**
     * イベントに対するcallbackを登録
     * @param string $event
     * @param callable $handler
     * @return Application
     */
    public function addHandler($event, callable $handler) {
        if(!array_key_exists($event, $this->handlers)) {
            throw new \InvalidArgumentException(
                sprintf('The event "%s" is not defined.', $event)
            );
        }
        $this->handlers[$event][] = $handler;
        return $this;
    }

    public function execute($event) {
        if(!array_key_exists($event, $this->handlers)) {
            throw new \InvalidArgumentException(
                sprintf('The event "%s" is not defined.', $event)
            );
        }
        $args = func_get_args();
        $args[0] = $this;
        foreach($this->handlers[$event] as $handler) {
            $result = call_user_func_array($handler, $args);
        }
        if(isset($result)) {
            return $result;
        }
        return $this;
    }
}
