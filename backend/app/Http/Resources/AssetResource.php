<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plant_id' => $this->plant_id,
            'name' => $this->name,
            'asset_type' => $this->asset_type,
            'serial_number' => $this->serial_number,
            'status' => $this->status,
            'location' => $this->location,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}