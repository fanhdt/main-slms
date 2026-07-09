<?php

use App\Domain\Portfolio\Models\PhotographerPortfolio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $groups = PhotographerPortfolio::query()
            ->whereNull('photographer_id')
            ->get()
            ->groupBy(fn ($row) => $row->lab_id . '|' . $row->photographer_name);

        foreach ($groups as $key => $rows) {
            [$labId, $name] = explode('|', $key, 2);

            $photographerId = DB::table('photographers')->insertGetId([
                'uuid'       => (string) Str::uuid(),
                'lab_id'     => $labId,
                'name'       => $name,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            PhotographerPortfolio::whereIn('id', $rows->pluck('id'))
                ->update(['photographer_id' => $photographerId]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank — data migration, tidak perlu rollback destruktif
    }
};