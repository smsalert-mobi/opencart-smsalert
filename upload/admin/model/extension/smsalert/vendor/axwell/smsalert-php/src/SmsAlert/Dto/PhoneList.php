<?php

namespace SmsAlert\Dto;

class PhoneList
{

    protected array $list;

    /**
     * @param string $tel
     *
     * @return $this
     */
    public function addPhoneNumber(string $tel): PhoneList
    {
        $this->list[] = trim($tel);

        return $this;
    }

    /**
     * @return string
     */
    public function getCombinedList(): string
    {
        return implode(',', $this->list);
    }
}