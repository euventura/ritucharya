<?php

namespace App\Services;

use Carbon\Carbon;

class Granularizer
{

    var $granularity = 'n';
    var $timeInformationType = 'metric'; // metric | index
    var $timeInformationValue = 'time';
    
    public function setGranularity(string $dateTimeFormat)
    {
        $this->granularity = $dateTimeFormat;
    }

    public function setTimeInformationType($timeInformationType)
    {
        $this->timeInformationType = $timeInformationType;
    }

    public function execute($dataSet)
    {
        switch($this->timeInformationType) {
            case 'metric': return $this->executeMetric($dataSet);
            case 'index': return 'ainda nao ta pronto';
        }
    }

    protected function executeMetric($dataSet)
    {
        $granularDataSet = [];
        foreach ($dataSet[$this->timeInformationValue] as $datetimeIndex => $dateTime) {
            $baseDate = Carbon::parse($dateTime);
            foreach ($dataSet as $metric => $metricData) {
                if ($metric == $this->timeInformationValue) continue;
                if (!isset($granularDataSet[$metric])) $granularDataSet[$metric] = [];
                if (!isset($granularDataSet[$metric][$baseDate->format($this->granularity)])) $granularDataSet[$metric][$baseDate->format($this->granularity)] = [];
                $granularDataSet[$metric][$baseDate->format($this->granularity)][] = $metricData[$datetimeIndex];
            }
        }

        $calculedDataSet = [];
        foreach($granularDataSet as $metric => $metricData) {
            foreach($metricData as $granularity => $granularData) {
                $calcs = $this->makeCalculations(($granularData));
                foreach ($calcs as $calType => $resut) {
                    $calculedDataSet[$metric][$calType][$granularity] = $resut;
                }
            }
        }
        return $calculedDataSet;
    }

    protected function makeCalculations($metricDataSet)
    {
        $sum = array_sum($metricDataSet);
        $qtd = count($metricDataSet);
        sort($metricDataSet);
        return [
            'sum' => $sum,
            'qtd' => $qtd,
            'max' => max($metricDataSet),
            'min' => min($metricDataSet),
            'avg' => $sum / $qtd,
            'mid' => $metricDataSet[ round($qtd/2) ],
            'var' => max($metricDataSet) - min($metricDataSet)
        ];
    }

}