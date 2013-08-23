<?php
/**
 *
 * Инъекция через конфиг.
 *
 */
class ExClassInjConfig
{

    /**
     *
     * @var ExClassImpl
     */
    protected $impl;

    /**
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->impl->getMessage();
    }

    /**
     *
     * @param ExClassImpl $impl
     */
    public function setImpl($impl)
    {
        $this->impl = $impl;
    }

}