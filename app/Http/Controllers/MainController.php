<?php

namespace App\Http\Controllers;

use App\Models\Weather;
use App\Services\Granularizer;
use App\Services\Scale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MainController extends Controller
{

    var $months = [
        '1' => 'jan',
        '2' => 'fev',
        '3' => 'mar',
        '4' => 'abr',
        '5' => 'mai',
        '6' => 'jun',
        '7' => 'jul',
        '8' => 'ago',
        '9' => 'set',
        '10' => 'out',
        '11' => 'nov',
        '12' => 'dez'
    ];

    public function index($lat = '-2.44',$long = '-54.71', $label = false)
    {

        $data = Weather::get($lat, $long, $label);
        $gran = new Granularizer();
        $montly = $gran->execute($data['hourly']);

        $scale = new Scale();

        $datasets = [];
        
        foreach($montly as $metric => $monthData) {
            $datasets[] = [
                'label' => $metric,
                'title' => $metric,
                'data' => $scale->make($monthData, $metric)
            ];
        }

        $vpkData = $this->getVpk($datasets);
        $newDataSet = [];
        foreach ($vpkData as $label => $vpkData) {
            $newDataSet[] = [
                'label' => $label,
                'data' => $vpkData
            ];
        }

        $chartjs = app()->chartjs
        ->name('weatherChart2')
        ->type('radar')
        ->labels(array_values($this->months))
        ->datasets($newDataSet)
        ->options([]);


        $scatter = $this->scatter($montly, ['temperature_2m', 'relativehumidity_2m', 'windspeed_10m']);
        $newDataSet = [];
        $chartjs2 = app()->chartjs
        ->name('weatherChart')
        ->type('bubble')
        ->labels(['a', 'b'])
        ->datasets(array_values($scatter))
        ->options([
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Analise de dados Climaticos em  em VTP'
                ],
                'subtitle' => [
                    'display' => true,
                    'text' => '(x = temp, Y = umid, raio=vento)'
                ]
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => 'string'
                ]
            ]
        ]);

        $infoData = $this->calcData($montly);
        $months = $this->months;
        return view('index', compact('chartjs', 'chartjs2', 'infoData', 'months'));

    }

    public function scatter($dataSet, $metrics)
    {
 
        $return = [];
        $controlMetric = array_values(array_values($dataSet)[0])[0];

        $bubbleDataSet = [];
        foreach($controlMetric as $datasetIndex => $fakeValue) {
            $return[$datasetIndex]['x'] = round($dataSet[$metrics[0]]['avg'][$datasetIndex], 2);
            $return[$datasetIndex]['y'] = round($dataSet[$metrics[1]]['avg'][$datasetIndex],2);
            $return[$datasetIndex]['r'] = round(($dataSet[$metrics[2]]['avg'][$datasetIndex]/max($dataSet[$metrics[2]]['max'])) * 20) ;
            $bubbleDataSet[] = [
                'label' => $this->months[$datasetIndex],
                'title' => $this->months[$datasetIndex],
                'legend' => $this->months[$datasetIndex],
                'data' => [array_values($return)[0]]
            ];
            $return = [];
        }

        return $bubbleDataSet;
    }

    public function getVpk($rawData)
    {

        $returnData =[];
        foreach ($rawData as $metricData) {
            foreach ($metricData['data'] as $month => $monthValue) {

                foreach(['V', 'P', 'K'] as $dosh) {
                    if(!isset($returnData[$dosh][$month]))  $returnData[$dosh][$month] = 0;

                    $returnData[$dosh][$month] += $this->calcDosh($monthValue, $rawData, Weather::getVpk($metricData['label'])[$dosh]);
                }
            }
        }
        return $returnData;
    }
    public function calcDosh($value, $values, $operation)
    {
        switch($operation) {
            case '-' : 
            case '--' :
            case '---' :
            case '----' :    
                if ($value > 0.50) {
                    return strlen($operation) * -1;
                }
                return   strlen($operation) ;
                break;

            case '+' :
            case '++' :
            case '+++' :
            case '++++' : 
                if ($value > 0.5) {
                    return ( strlen($operation));
                }
                return  (strlen($operation) * -1) ;
                break;
            case '+-' :
            case '++--' :
            case '+++---' :
                if ($value < 15 || $value > 85)
                {
                    return (round(strlen($operation)/2));
                }
                return 0;
            case '-+' :
            case '--++' :
            case '---+++' :
                if ($value < 15 )
                {
                    return (round(strlen($operation)/2)) * -1;
                }
                if ($value > 85)
                {
                    return (round(strlen($operation)/2));
                }
                return 0;

            default:
                return 0;
                break;
        }

    }

    public function calcData($dataset)
    {
        $tempSandhi = $this->calcSandhi($dataset['apparent_temperature'], 'avg', 4);
        $umidSandhi = $this->calcSandhi($dataset['relativehumidity_2m'], 'avg', 4);
        $windTop = $this->calcTop($dataset['windspeed_10m'], 'avg', 4);
        return [
            'temp_sandhi' => $tempSandhi,
            'umid_sandhi' => $umidSandhi,
            'wind_top' => $windTop
        ];
    }

    public function calcSandhi($dataset, $metric)
    {
        $oldItem = false;

        foreach ($dataset[$metric] as $datasetIndex => $data)
        {
            if ($oldItem === false) $oldItem = end($dataset[$metric]); 
            $diffs[$datasetIndex] =  $data - $oldItem;
            $oldItem = $data;
        }

        arsort($diffs, SORT_NUMERIC);

        return array_slice($diffs, 1, 2, true) + array_slice($diffs, -2, 2, true);
    }

    public function calcTop($dataset, $metric)
    {
        arsort($dataset[$metric]);
        return  array_slice($dataset[$metric], 1, 4, true);
    }

}
