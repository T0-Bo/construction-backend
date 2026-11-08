<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var DatabaseNotification $this->resource */
        return [
            'id' => $this->id,

            'report_id' => $this->data['report_id'] ?? null,

            'message' => $this->data['message'] ?? null,

            'read' => $this->read_at !== null,

            'created_at' => $this->created_at,
        ];
    }
}
