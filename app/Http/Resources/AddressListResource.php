<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AddressList */
class AddressListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guid' => $this->guid,
            'description' => $this->description,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
//            'data' => $this->data
        ];
    }


    // extract guid from description
    public function getGuidAttribute()
    {
        $description = $this->description;
        $guid = null;
        if (preg_match('/\[#\w+]/', $description, $matches)) {
            $guid = $matches[1];
        }
        return $guid;
    }
}
