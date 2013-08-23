<?php
/**
 * Подключемый сервис
 */
class ExClassImpl
{

    /**
     *
     * @var HelperTime
     */
    protected $helper_time;

    protected $msg = 'It\'s work!';

    /**
     * @return string
     */
    public function getMessage()
    {
        $time = $this->helper_time->get();
        return $time . ' ' . $this->msg . ' obj: ' . spl_object_hash($this);
    }

    /**
     * @Inject
     * @param HelperTime $helper
     */
    public function setHelperTime($helper)
    {
        $this->helper_time = $helper;
    }

}