<?php

namespace App\Http\Controllers;

use App\Models\Weather;
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
        
        $montly = [];

        foreach ($data['hourly']['time'] as $datetimeIndex => $dateTime) {
            $baseDate = Carbon::parse($dateTime);
            foreach (array_keys($data['hourly']) as $metric) {
                if ($metric == 'time') continue;
                $montly[$metric][$baseDate->month][] = $data['hourly'][$metric][$datetimeIndex];
            }
        }

        foreach ($montly as $metric => $dataMetric)
        {
            foreach ($dataMetric as $month => $monthData) {
                $data['montly'][$metric]['sum'][$month] = array_sum($monthData);
                $data['montly'][$metric]['qtd'][$month] = count($monthData);
                $data['montly'][$metric]['max'][$month] = max($monthData);
                $data['montly'][$metric]['min'][$month] = min($monthData);
                $data['montly'][$metric]['avg'][$month] = $data['montly'][$metric]['sum'][$month]/$data['montly'][$metric]['qtd'][$month];
                $data['montly'][$metric]['sqr'][$month] = sqrt($data['montly'][$metric]['avg'][$month]);
                $data['montly'][$metric]['mid'][$month] = $monthData[ round($data['montly'][$metric]['qtd'][$month]/2) ];
                @$data['montly'][$metric]['var'][$month] = $data['montly'][$metric]['max'][$month] - $data['montly'][$metric]['min'][$month];

                if ($metric == 'temperature_2m') {
                    $data['montly']['temp_variance']['avg'][$month] = max($monthData) - min($monthData);
                }
                
                if (!isset($data['absolute'] )) $data['absolute'] = [];
                
                $premax =  ($data['absolute'][$metric]['max'][$month] ?? 0);
                $premin =  ($data['absolute'][$metric]['min'][$month] ?? 1000);
                $data['absolute'][$metric]['max'] =  max($monthData) > $premax ? max($monthData) : $premax;
                $data['absolute'][$metric]['min'] =  min($monthData) < $premin ? min($monthData) : $premin;
                $data['absolute'][$metric]['raw'] = $monthData;
            }
            
        }

        $datasets = [];
        $statistics = [];
        foreach(['apparent_temperature', 'relativehumidity_2m', 'direct_radiation', 'precipitation', 'windspeed_10m', 'temp_variance', 'et0_fao_evapotranspiration'] as $metric)
        {
            $dataset = [];
        
            for( $x=1;$x<=12;$x++)
            {
                if ($metric == 'xxxxtemp_variance') {
                    $dataset[] =   $data['montly']['temperature_2m']['max'][$x] - max($data['montly']['temperature_2m']['min']) ;
                } else {
                    $dataset[] =   $data['montly'][$metric]['avg'][$x]/ max($data['montly'][$metric]['avg']) ;

                }
            }
            
            $datasets[] = [
                'label' =>$metric,
                'data' => $dataset
            ];
        }

        $chartjs2 = app()->chartjs
        ->name('weatherChart')
        ->type('radar')
        ->labels(array_values($this->months))
        ->datasets($datasets)
        ->options([]);


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

        return view('index', compact('chartjs', 'chartjs2'));

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
            case '--' : 
                if ($value < 0.5) {
                    return ((0.5+$value) * 2);
                }
                return  ((0.5-$value) * 2) ;
                break;
            case '-' :
                if ($value < 0.5) {
                    return ((0.5+$value) * 1) ;
                }
                return  ((0.5-$value) * 1) ;
                break;
            case '+' : 
                if ($value < 0.5) {
                    return ((0.5-$value) * 1);
                }
                return  ((0.5+$value) * 1) ;
                break;
            case '++' : 
                if ($value < 0.5) {
                    return ((0.5-$value) * 2);
                }
                return  ((0.5+$value) * 2) ;
                break;
            case '-+' :
            case '+-' : 
                if ($value < 25)
                {
                    return ((0.5-$value) * 1);
                }
                if ($value > 75)
                {
                    return ((0.5+$value) * 1);
                }
                return 0;   
                break;

            default:
                return 0;
                break;
        }

    }

}
