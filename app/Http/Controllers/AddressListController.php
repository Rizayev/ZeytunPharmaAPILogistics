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
        return  $wialonService->updateAddressList();
    }

}
