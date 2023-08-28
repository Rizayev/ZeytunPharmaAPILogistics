<?php

namespace App\Http\Controllers;

use App\Http\Resources\AddressListResource;
use App\Models\AddressList;
use App\Service\WialonService;
use Illuminate\Http\Request;

class AddressListController extends Controller
{
    public function index()
    {
        return AddressListResource::collection(AddressList::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'description' => ['nullable'],
            'longitude' => ['required'],
            'latitude' => ['required'],
        ]);

        return new AddressListResource(AddressList::create($request->validated()));
    }

    public function show(AddressList $addressList)
    {
        return new AddressListResource($addressList);
    }

    public function update(Request $request, AddressList $addressList)
    {
        $request->validate([
            'name' => ['required'],
            'description' => ['nullable'],
            'longitude' => ['required'],
            'latitude' => ['required'],
        ]);

        $addressList->update($request->validated());

        return new AddressListResource($addressList);
    }

    public function destroy(AddressList $addressList)
    {
        $addressList->delete();

        return response()->json();
    }


    public function updateAddressList(Request $request)
    {
        $wialonService = new WialonService($request);
        $result = $wialonService->getAddresList();

        foreach ($result as $item) {
            AddressList::updateOrCreate([
                'name' => $item['name'],
            ], [
                'name' => $item['name'],
                'description' => $item['description'],
                'guid' => $this->getUIDFromDescription($item['description']),
                'longitude' => $item['longitude'],
                'latitude' => $item['latitude'],
                'data' => json_encode($item['data']),
            ]);
        }
        return [
            'status' => true,
            'message' => 'Updated',
        ];
    }

    public function getUIDFromDescription($text){
        // if it has [#ID] in description
        if (preg_match('/\[#ID(\d+)\]/', $text, $matches)) {
            return $matches[1];
        }
    }
}
