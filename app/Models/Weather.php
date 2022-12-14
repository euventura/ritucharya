<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Weather extends Model
{
   
    protected $table = false;

    var $metrics = [
        'temp' => 'temperature_2m',
        'relativehumidity_2m',
        ''

    ];

    static public function getVpk($metric = false)
    {
        $vpk =[
            'relativehumidity_2m' => [ // quanto mais
                'V' => '--', // diminui vata
                'P' => '-+', // em extremos perturba
                'K' => '++' //agrava kapha
            ],
            'direct_radiation' => [ // quanto mais
                'V' => '-', // em extremos perturba
                'P' => '++', // relacao direta
                'K' => '-' // relacao direta inversa
            ],
            'apparent_temperature' => [
                'V' => '--', 
                'P' => '++',
                'K' => '-' 
            ],
            'windspeed_10m' => [
                'V' => '++',
                'P' => '+',
                'K' => '--' 
            ],
            'et0_fao_evapotranspiration' => [
                'V' => '--',
                'P' => '-+',
                'K' => '++' 
            ],
            'precipitation' => [
                'V' => '--', // diminui vata
                'P' => '-', 
                'K' => '++' //agrava kapha
            ],
            'temp_variance' => [
                'V' => '++', // diminui vata
                'P' => '+', // em extremos perturba
                'K' => '-+' // inversa leve
            ]
        ];

        if ($metric) return $vpk[$metric];

        return $vpk;
    }

    static public function get($lat, $long)
    {
        $response = file_get_contents(self::makeUrl($lat, $long));
        return json_decode($response, true);
    }
   
    static protected function makeUrl($lat, $long)
    {
        return "https://archive-api.open-meteo.com/v1/era5?latitude={$lat}&longitude={$long}1&start_date=2019-01-01&end_date=2021-12-31&hourly=temperature_2m,apparent_temperature,relativehumidity_2m,precipitation,direct_radiation,windspeed_10m,et0_fao_evapotranspiration&timezone=America%2FSao_Paulo";
    }


}
