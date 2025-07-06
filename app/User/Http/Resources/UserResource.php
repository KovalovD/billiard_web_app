<?php

namespace App\User\Http\Resources;

use App\Core\Models\User;
use App\Leagues\Http\Resources\ClubResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'slug'               => $this->slug,
            'firstname'          => $this->firstname,
            'lastname'           => $this->lastname,
            'name'               => $this->full_name, // Assuming there's a full_name accessor
            'email'              => $this->email,
            'phone'              => $this->phone,
            'sex'                => $this->sex,
            'sex_value'          => $this->when($this->sex, fn() => $this->getSexName()),
            'birthdate'          => $this->birthdate?->toISOString(),
            'picture'            => $this->picture,
            'is_admin'           => $this->is_admin,
            'description'        => $this->description,
            'equipment'          => $this->equipment ?? [],
            'tournament_picture' => $this->tournament_picture ?? null,
            'avatar'             => $this->when($this->picture, fn() => asset('storage/'.$this->picture)),
            'home_city'          => $this->whenLoaded('homeCity', fn() => new CityResource($this->homeCity)),
            'home_club'          => $this->whenLoaded('homeClub', fn() => new ClubResource($this->homeClub)),
        ];
    }
}
