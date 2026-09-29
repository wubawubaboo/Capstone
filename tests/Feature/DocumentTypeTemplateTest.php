<?php

namespace Tests\Feature;

use App\Enums\DocumentTemplateType;
use App\Models\Barangay;
use App\Models\DocumentType;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTypeTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $barangay = Barangay::create(['name' => 'San Nicolas']);
        $this->secretary = User::create([
            'full_name' => 'Secretary User',
            'phone_number' => '09000000001',
            'password' => 'password',
            'role' => 'secretary',
            'barangay_id' => $barangay->id,
            'address' => 'Hall',
            'date_of_birth' => '1990-01-01',
            'start_of_residency' => 2000,
            'is_verified' => true,
        ]);
    }

    public function test_secretary_can_remove_a_template(): void
    {
        Storage::fake();
        Storage::put('document_templates/clearance.png', 'x');

        $type = DocumentType::create([
            'name' => 'Barangay Clearance',
            'template_type' => DocumentTemplateType::Image,
            'template_path' => 'document_templates/clearance.png',
            'field_positions_json' => [['field' => 'full_name', 'x' => 1, 'y' => 1]],
            'template_image_width' => 800,
            'template_image_height' => 1131,
        ]);

        $this->actingAs($this->secretary)
            ->delete(route('secretary.document-types.destroy-template', $type))
            ->assertSessionHas('success', 'Template removed.');

        $type->refresh();
        $this->assertNull($type->template_type);
        $this->assertNull($type->template_path);
        $this->assertNull($type->field_positions_json);
        $this->assertNull($type->template_image_width);
        Storage::assertMissing('document_templates/clearance.png');
        $this->assertTrue(SystemLog::where('description', 'like', "%Removed the auto-generation template from document type 'Barangay Clearance'%")->exists());
    }

    public function test_removing_when_there_is_no_template_is_rejected(): void
    {
        $type = DocumentType::create(['name' => 'Certificate of Residency']);

        $this->actingAs($this->secretary)
            ->delete(route('secretary.document-types.destroy-template', $type))
            ->assertStatus(422);
    }
}
