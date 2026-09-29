<?php

declare(strict_types=1);

namespace App\Domain\Report\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'type'           => ['value' => $this->type->value, 'label' => $this->type->label()],
            'period_start'   => $this->period_start->toDateString(),
            'period_end'     => $this->period_end->toDateString(),
            'total_bookings' => $this->total_bookings,
            'total_revenue'  => $this->total_revenue,
            'summary'        => $this->summary,
            'excel_url'      => $this->excel_url,
            'pdf_url'        => $this->pdf_url,
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}