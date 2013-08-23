<?php
/**
 *
 * Пример инъекции в поле
 *
 */
class ExClassInjProperty
{

    /**
     * @Inject
     * @var ExClassImpl
     */
    protected $impl;

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->impl->getMessage();
    }

}