<?php

namespace SimpleDi;

/**
 *
 * Создает и инициализирует сервисы.
 *
 * @author Mthps
 *
 */
class ServiceLocator
{

    /**
     * @var ClassManager
     */
    protected $class_manager;

    /**
     * Настройки сервисов
     *
     * @var array[]
     */
    protected $configs;

    /**
     *
     * @var object[]
     */
    protected $instances;

    /**
     *
     * @var array
     */
    protected $parameters = array();

    public function __construct()
    {
        $this->instances[get_class($this)] = $this;
    }

    /**
     * Создает и возвращает новый экземпляр сервиса.
     *
     * @param string $name
     * @return object
     */
    public function createService($name)
    {
        $cfg = isset($this->configs[$name]) ? $this->configs[$name] : array();

        $instance = isset($cfg['class'])
            ? new $cfg['class']
            : new $name;

        $this->instances[$name] = $instance;

        $this->injectProperties($instance);
        $this->injectCalls(
            $instance,
            isset($cfg['calls']) ? $cfg['calls'] : array()
        );

        return $instance;
    }

    /**
     * Возвращает экземпляр сервиса.
     * Если сервис еще не был инициализирован, создает его.
     *
     * @param string $name Название сервиса
     * @return object
     */
    public function get($name)
    {
        if (!isset($this->instances[$name])) {
            $this->instances[$name] = $this->createService($name);
        }
        return $this->instances[$name];
    }

    /**
     * Возвращает значение, которое будет инъектировано в объект
     * @param mixed $value название сервиса или параметра или значение.
     * @return mixed
     */
    public function getInjectValue($value)
    {
        if (is_string($value) && isset($value[0])) {
            if ($value[0] === '@' && $value[1] !== '@') {
                $value = $this->get(substr($value, 1));
            } elseif ($value[0] === '%') {
                $pn = strstr(substr($value, 1), '%', true);
                $value = $this->parameters[$pn];
            }
        }
        return $value;
    }

    /**
     * @param object $instance
     * @param array[] $replaces Переопределенные вызовы
     */
    public function injectCalls($instance, array $replaces = array())
    {
        $cfg = $this->class_manager->getInfo(get_class($instance));
        $calls = array_merge(
            $cfg['calls'],
            $replaces
        );
        foreach ($calls as $call) {
            $params = array_map(
                array($this, 'getInjectValue'),
                isset($call[1]) ? $call[1] : array()
            );
            call_user_func_array(array($instance, $call[0]), $params);
        }
    }

    /**
     *
     * @param object $instance
     */
    public function injectProperties($instance)
    {
        $cfg = $this->class_manager->getInfo(get_class($instance));
        $props = $cfg['props'];
        foreach ($props as $prop) {
            $ref = new \ReflectionProperty($instance, $prop[0]);
            $ref->setAccessible(true);
            $ref->setValue($instance, $this->getInjectValue($prop[1]));
        }
    }

    /**
     * @param ClassManager $manager
     */
    public function setClassManager($manager)
    {
        $this->class_manager = $manager;
    }

    /**
     *
     * @param array $configs
     */
    public function setConfigs(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

}
