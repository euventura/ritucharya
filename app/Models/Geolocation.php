<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Geolocation extends Model
{

    protected $table = false;

    static public function get($query)
    {

        return Cache::rememberForever($query, function() use ($query){
            $response = file_get_contents(self::makeUrl($query));
            return json_decode($response, true);
        }); 
    }

    static protected function makeUrl($query) {
        return "http://api.positionstack.com/v1/forward?access_key=dcf05f1388a3e91c87f7ae1cc48ac87b&query=".urlencode($query);
    }


}
