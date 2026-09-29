<?php

declare(strict_types=1);

namespace Tests\Feature\LabService;

use App\Domain\Lab\Models\Lab;            // sesuaikan
use App\Domain\LabService\Models\Package;
use App\Domain\LabService\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_returns_all_20_active_services_of_lab(): void
    {
        $lab = Lab::factory()->create();
        Service::factory()->count(20)->create(['lab_id' => $lab->id, 'is_active' => true]);

        $this->getJson("/api/catalog/services?lab_id={$lab->id}")
            ->assertOk()
            ->assertJsonCount(20, 'data');
    }

    public function test_catalog_returns_all_20_active_packages_of_lab(): void
    {
        $lab = Lab::factory()->create();
        Package::factory()->count(20)->create(['lab_id' => $lab->id, 'is_active' => true]);

        $this->getJson("/api/catalog/packages?lab_id={$lab->id}")
            ->assertOk()
            ->assertJsonCount(20, 'data');
    }

    public function test_catalog_excludes_other_lab_and_inactive(): void
    {
        $lab = Lab::factory()->create();
        $other = Lab::factory()->create();

        $keep = Service::factory()->create(['lab_id' => $lab->id, 'is_active' => true]);
        Service::factory()->create(['lab_id' => $lab->id, 'is_active' => false]);
        Service::factory()->create(['lab_id' => $other->id, 'is_active' => true]);

        $keepPkg = Package::factory()->create(['lab_id' => $lab->id, 'is_active' => true]);
        Package::factory()->create(['lab_id' => $lab->id, 'is_active' => false]);
        Package::factory()->create(['lab_id' => $other->id, 'is_active' => true]);

        $this->getJson("/api/catalog/services?lab_id={$lab->id}")
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $keep->uuid);

        $this->getJson("/api/catalog/packages?lab_id={$lab->id}")
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $keepPkg->uuid);
    }

    public function test_catalog_requires_valid_lab_id(): void
    {
        $this->getJson('/api/catalog/services')->assertStatus(422);
    }

    public function test_admin_endpoint_still_paginates(): void
    {
        $lab = Lab::factory()->create();
        Service::factory()->count(20)->create(['lab_id' => $lab->id]);

        $this->getJson("/api/services?lab_id={$lab->id}&per_page=15&page=2")
            ->assertOk()
            ->assertJsonCount(5, 'data.data')
            ->assertJsonPath('data.meta.total', 20)
            ->assertJsonPath('data.meta.last_page', 2);
    }

    public function test_is_active_string_filter_is_parsed_as_boolean(): void
    {
        $lab = Lab::factory()->create();
        Service::factory()->create(['lab_id' => $lab->id, 'is_active' => true]);
        Service::factory()->create(['lab_id' => $lab->id, 'is_active' => false]);

        $this->getJson("/api/services?lab_id={$lab->id}&is_active=true")
            ->assertJsonPath('data.meta.total', 1);
    }

    
}