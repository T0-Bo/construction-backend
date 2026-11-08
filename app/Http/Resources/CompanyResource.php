<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            'projects' => ProjectResource::collection(
                $this->whenLoaded('projects')
            ),
            'role' => $this->whenPivotLoaded(
                'company_user',
                fn () => $this->pivot->role
            ),
            'status' => $this->whenPivotLoaded(
                'company_user',
                fn () => $this->pivot->status
            ),
        ];
    }
}
