<?php

/**
 *
 * Инициализация, то что следует вынести в бутстрап.
 *
 */

require __DIR__ . '/../SimpleDi/CacheStub.php';
require __DIR__ . '/../SimpleDi/ClassManager.php';
require __DIR__ . '/../SimpleDi/ServiceLocator.php';

require __DIR__ . '/HelperTime.php';
require __DIR__ . '/ExClassImpl.php';
require __DIR__ . '/ExClassInjConfig.php';
require __DIR__ . '/ExClassInjProperty.php';
require __DIR__ . '/ExClassInjMethod.php';

$cm = new \SimpleDi\ClassManager;
$cm->setCacher(new \SimpleDi\CacheStub);

$sl = new \SimpleDi\ServiceLocator();
$sl->setClassManager($cm);


/**
 *
 * Пример использования
 *
 */

/* @var $a ExClassInjProperty */
$a = $sl->get('ExClassInjProperty');

/* @var $b ExClassInjMethod */
$b = $sl->get('ExClassInjMethod');

$sl->setConfigs(array(
    'WithConfigs' => array(
        'class' => 'ExClassInjConfig',
        'calls' => array(
            array('setImpl', array('@ExClassImpl'))
        )
    ),
    'UnexistedClass' => array(
        'props' => array(
            'impl' => '@ExClassImpl'
        ),
        'calls' => array(
            array('setImpl', array('@ExClassImpl')),
            array('setParam', array('%some_param%')),
        )
    )
));

/* @var $c ExClassInjConfig */
$c = $sl->get('WithConfigs');

if (isset($_SERVER['REMOTE_ADDR'])) {
    echo '<pre>';
}

var_dump(
    $a->getMessage(),
    $b->getMessage(),
    $c->getMessage(),
    $sl->get('ExClassImpl')->getMessage()
);