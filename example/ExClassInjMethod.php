<?php
/**
 *
 * Пример инъекции через метод
 *
 */
class ExClassInjMethod
{

    /**
     *
     * @var ExClassImpl
     */
    protected $impl;

    /**
     *
     * @return ExClassImpl
     */
    public function getMessage()
    {
        return $this->impl->getMessage();
    }

    /**
     * @Inject("ExClassImpl")
     * @param ExClassImpl $impl
     */
    public function setImpl($impl)
    {
        $this->impl = $impl;
    }

}