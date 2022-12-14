<?php

namespace App\Services;


class Scale
{

    var $calcMetric = [
        'temperature_2m' => 'var',
        'windspeed_10m' => 'sum',
        'apparent_temperature' => 'var',
    ];
    
    public function make($dataSet, $metric)
    {

        $dataset =[];
        $base_metric = $this->calcMetric[$metric] ?? 'avg';
        for( $x=1;$x<=12;$x++) {
            $dataset[] =   $dataSet[$base_metric][$x]/max($dataSet[$base_metric]) ;
        }
        return $dataset;
    }

    protected function setDinamicMinMax($dataSet)
    {

        foreach($dataSet as $ganularData) {
            
        }
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