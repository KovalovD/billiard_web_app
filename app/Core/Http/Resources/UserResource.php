<?php

namespace App\Core\Http\Resources;

use App\Core\Models\User;
use App\Leagues\Http\Resources\ClubResource;
use App\User\Http\Resources\CityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'firstname' => $this->firstname,
            'lastname'  => $this->lastname,
            'email'     => $this->email,
            'phone'     => $this->phone,
            'is_admin'  => $this->is_admin,
            'home_city' => $this->whenLoaded('homeCity', fn() => new CityResource($this->homeCity)),
            'home_club' => $this->whenLoaded('homeClub', fn() => new ClubResource($this->homeClub)),
            'sex' => $this->getSexName(),
        ];
    }
}
