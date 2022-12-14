<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchController extends Controller
{

   public function index(Request $request)
   {
        $geoResult = [];

        if ($request->query('query')) {
            $geoResult = json_decode(file_get_contents('http://api.positionstack.com/v1/forward?access_key=dcf05f1388a3e91c87f7ae1cc48ac87b&query='. urlencode($request->query('query'))), 1);
            $geoResult = $geoResult['data'];
//            dd($geoResult);
        }

        return view('search', compact('geoResult'));
   }

}
