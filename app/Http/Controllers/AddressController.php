<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function getAddressData(Request $request){
        $q = $request->get('q');
        $searchItem = rawurlencode('["'.$q.'"]');
        $curl = curl_init();
//        return $searchItem;

//        return 'https://go.gps.az/gis_many_searchintelli?phrases='.$searchItem.'&flags=1255211008&count=50&uid=2034&sid=6b659e93b2986735565292d3562bafb1';
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://go.gps.az/gis_many_searchintelli?phrases='.$searchItem.'&flags=1255211008&count=50&uid=2034&sid=6b659e93b2986735565292d3562bafb1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return response($response, 200)
            ->header('Content-Type', 'text/json');

    }
}
