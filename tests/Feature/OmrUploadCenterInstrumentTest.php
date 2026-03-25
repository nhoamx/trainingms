<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OmrUploadCenterInstrumentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_omr_center_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('evaluations.omr-center'));

        $response->assertStatus(200);
    }

    public function test_store_accepts_clima_laboral_instrument(): void
    {
        Bus::fake();
        Storage::fake('public');

        $file = UploadedFile::fake()->create('clima-laboral.pdf', 128, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->post(route('evaluations.store'), [
                'files' => [$file],
                'instrument' => 'clima-laboral',
            ]);

        $response->assertStatus(302);
        $response->assertSessionDoesntHaveErrors(['instrument']);
    }
}
