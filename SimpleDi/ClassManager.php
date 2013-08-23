<?php

namespace SimpleDi;

/**
 * Предоставляет информацию о классах.
 * Информация содержит
 *      время модификации файла;
 *      путь до файла, содержащего класс;
 *      вызовы, описанные аннотациями.
 *
 * @author Mthps
 */
class ClassManager
{

    /**
     *
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    protected $cacher;

    /**
     * Информация по классам
     * @var array[]
     */
    protected $classes = array();

    /**
     * Рефлексии классов
     * @var \ReflectionClass[]
     */
    protected $reflections = array();

    /**
     * Возвращает массив вызовов для класса
     * @param \ReflectionClass $ref
     * @return array
     */
    public function getClassCalls(\ReflectionClass $ref)
    {
        $calls = array();
        /* @var $method \ReflectionMethod[] */
        $methods = $ref->getMethods();
        $ns = $ref->getNamespaceName();
        $m = null;
        foreach ($methods as $method) {
            $doc = $method->getDocComment();
            $m = null;
            if (!preg_match('#@Inject(\([^\)]+?\))?#', $doc, $m)) {
                continue;
            }
            $params = isset($m[1]) ? trim($m[1], '() ') : null;

            if (!$params) {
                // @Inject пустой, берем из @param
                preg_match('#@param\s+([^\s]+?)?\s#', $doc, $m);
                if (empty($m[1])) {
                    throw new \Exception(
                        'no inject param description in ' .
                        $ref->getName() . '::' . $method->getName() . '()'
                    );
                }

                // имя класса
                $params = $m[1];
                if ($params[0] === '\\') {
                    $params = substr($params, 1);
                } elseif ($ns) {
                    $params = $ns . '\\' . $params;
                }
            }

            $argumets = array_map(
                array($this, 'getInjectValue'),
                explode(',', $params)
            );

            $calls[] = array($method->getName(), $argumets);
        }
        return $calls;
    }

    /**
     * Возвращает сохраненный в кэше путь до файла
     * @param string $class
     * @return string|null
     */
    public function getClassFile($class)
    {
        $info = $this->getInfoSimple($class);
        return $info ? $info['file'] : null;
    }

    /**
     * Возвращает список свойств, которые необходимо инъектировать.
     * @param \ReflectionClass $ref
     */
    public function getClassProperties($ref)
    {
        $props = array();

        /* @var $properties \ReflectionProperty[] */
        $properties = $ref->getProperties();
        $ns = $ref->getNamespaceName();
        $m = null;
        foreach ($properties as $property) {
            $doc = $property->getDocComment();
            if (!preg_match('#@Inject(\([^\)]+?\))?#', $doc, $m)) {
                continue;
            }
            $params = isset($m[1]) ? trim($m[1], '() ') : null;

            if (!$params) {
                // @Inject пустой, берем из @var
                preg_match('#@var\s+([^\s]+?)?\s#', $doc, $m);
                if (empty($m[1])) {
                    throw new \Exception(
                        'no inject var description in ' .
                        $ref->getName() . '::' . $property->getName()
                    );
                }

                // имя класса
                $params = $m[1];
                if ($params[0] === '\\') {
                    $params = substr($params, 1);
                } elseif ($ns) {
                    $params = $ns . '\\' . $params;
                }
            }

            $props[] = array(
                $property->getName(),
                $this->getInjectValue($params)
            );
        }

        return $props;
    }

    /**
     * Возвращает всю информацию о классе.
     * @param string $class
     * @return array
     */
    public function getInfo($class)
    {
        // в локальном массиве
        if (isset($this->classes[$class])) {
            $info = $this->classes[$class];
            if (isset($info['calls'])) {
                return $info;
            }
        }

        // в кэше
        $info = $this->cacher->fetch($class);
        if ($info) {
            $info = json_decode($info, true);
            if (
                isset($info['calls']) &&
                filemtime($info['file']) == $info['mtime']
            ) {
                return $this->classes[$class] = $info;
            }
        }

        // информации нет, загружаем
        $ref = $this->getReflection($class);

        $newinfo = array(
            'file' => $ref->getFileName(),
            'mtime' => filemtime($ref->getFileName()),
            'calls' => $this->getClassCalls($ref),
            'props' => $this->getClassProperties($ref),
        );

        $this->cacher->save($class, json_encode($newinfo));
        return $this->classes[$class] = $newinfo;
    }

    /**
     * Возвращает имеющуюся информацию о классе
     * @param string $class
     * @return array|null
     */
    public function getInfoSimple($class)
    {
        if (isset($this->classes[$class])) {
            return $this->classes[$class];
        }

        $cache = $this->cacher->fetch($class);
        if (!$cache) {
            return null;
        }

        $info = json_decode($cache, true);
        $mtime = filemtime($info['file']);
        if ($mtime != $info['mtime']) {
            // файл обновлен
            return null;
        }

        return $this->classes[$class] = $info;
    }

    /**
     * Возвращает инъектируемое значение, как оно должно быть описано в конфиге.
     * Для сервисов добалвяется префикс "@".
     * @param string $value
     */
    public function getInjectValue($value)
    {
        $trimed = trim($value, '" ');
        return $trimed[0] === '%'
            ? $trimed   // название параметра
            : '@' . $trimed;
    }

    /**
     *
     * @param type $class
     * @return \ReflectionClass
     */
    public function getReflection($class)
    {
        if (empty($this->reflections[$class])) {
            $this->reflections[$class] = new \ReflectionClass($class);
        }
        return $this->reflections[$class];
    }

    /**
     * Обновление информации о классе
     * @param string $class
     */
    public function saveLoadInfo($class)
    {
        $ref = $this->getReflection($class);
        $info = array(
            'file' => $ref->getFileName(),
            'mtime' => filemtime($ref->getFileName()),
        );
        $this->cacher->save($class, json_encode($info));
    }

    /**
     *
     * @param \Doctine\Common\Cache\CacheProvider $cacher
     */
    public function setCacher($cacher)
    {
        $this->cacher = $cacher;
    }

}
