<?php
/**
 * Хелпер для работы со временем.
 *
 * @author Mthps
 */
class HelperTime
{

    /**
     * Возвращает текущие дату-время в формате Y-m-d H:i:s
     * @return string
     */
    public function get()
    {
        return date('Y-m-d H:i:s');
    }

}