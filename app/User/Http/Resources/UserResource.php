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
            'picture'            => $this->getPicture($this->picture),
            'is_admin'           => $this->is_admin,
            'is_active'          => $this->is_active,
            'created_at'         => $this->created_at,
            'email_verified_at'  => $this->email_verified_at,
            'description'        => $this->description,
            'equipment'          => $this->equipment ?? [],
            'tournament_picture' => $this->getPicture($this->tournament_picture),
            'avatar'             => $this->getPicture($this->picture),
            'home_city'          => $this->whenLoaded('homeCity', fn() => new CityResource($this->homeCity)),
            'home_club'          => $this->whenLoaded('homeClub', fn() => new ClubResource($this->homeClub)),
        ];
    }
}
