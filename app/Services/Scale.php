<?php

namespace App\Services;


class Scale
{

    private $minimunValue;
    private $maximunValue;
    private $method;
    

    public function make($dataSet)
    {
        if (!$this->minimunValue || !$this->maximunValue) {
            $this->setDinamicMinMax($dataSet);
        }

        foreach($dataSet as $ganularData) {

        }
    }

    protected function setDinamicMinMax($dataSet)
    {
        $this->minimunValue = $dataSet['min'];
        $this->maximunValue = $dataSet['max'] - $dataSet['min'];
    }

    /**
     * Set the value of minimunValue
     */
    public function setMinimunValue($minimunValue): self
    {
        $this->minimunValue = $minimunValue;

        return $this;
    }

    /**
     * Set the value of maximunValue
     */
    public function setMaximunValue($maximunValue): self
    {
        $this->maximunValue = $maximunValue;

        return $this;
    }
}