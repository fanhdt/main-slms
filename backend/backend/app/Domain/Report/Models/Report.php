<?php

declare(strict_types=1);

namespace App\Domain\Report\Models;

use App\Core\Traits\BelongsToLab;
use App\Core\Traits\HasImageUrl; // dipakai ulang: generate presigned URL, bukan cuma untuk gambar
use App\Core\Traits\HasUuid;
use App\Domain\Report\Enums\ReportType;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use BelongsToLab;
    use HasImageUrl;
    use HasUuid;

    protected $fillable = [
        'uuid', 'lab_id', 'type', 'period_start', 'period_end',
        'total_bookings', 'total_revenue', 'summary',
        'excel_path', 'pdf_path', 'disk',
    ];

    protected function casts(): array
    {
        return [
            'type'           => ReportType::class,
            'period_start'   => 'date',
            'period_end'     => 'date',
            'total_revenue'  => 'decimal:2',
            'summary'        => 'array',
        ];
    }

    public function getExcelUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->excel_path);
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->getImageUrlFrom($this->pdf_path);
    }
}