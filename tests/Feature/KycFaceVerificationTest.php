<?php

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Permission;
use App\Models\User;
use App\Models\Verification;
use App\Services\FaceMatchService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    Storage::fake('public');
    Permission::firstOrCreate(['name' => 'loans.create', 'guard_name' => 'web']);
    $this->dealer = Dealer::factory()->create();
    $this->agent = User::factory()->create(['dealer_id' => $this->dealer->id]);
    $this->agent->givePermissionTo('loans.create');
    $this->customer = Customer::factory()->create([
        'registered_by' => $this->agent->id,
        'dealer_id' => $this->dealer->id,
        'first_name' => 'Amina',
        'last_name' => 'Juma',
    ]);
    Sanctum::actingAs($this->agent);
});

// ─── uploadIdPhoto ────────────────────────────────────────────────────────────

it('uploads id front photo and resets face match status to pending', function () {
    $response = $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/id-photo", [
        'id_front_photo' => UploadedFile::fake()->image('id_front.jpg'),
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.customer_id', $this->customer->id)
        ->assertJsonPath('data.face_match_status', 'pending')
        ->assertJsonStructure(['data' => ['customer_id', 'id_front_url', 'face_match_status']]);

    $this->customer->refresh();
    expect($this->customer->id_front_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($this->customer->id_front_photo_path);
});

it('stores id front photo under kyc/id_front directory', function () {
    $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/id-photo", [
        'id_front_photo' => UploadedFile::fake()->image('nida_card.jpg'),
    ])->assertOk();

    $this->customer->refresh();
    expect($this->customer->id_front_photo_path)->toStartWith('kyc/id_front/');
});

it('does not reset face match status when already manual_verified', function () {
    $verification = Verification::factory()->create([
        'customer_id' => $this->customer->id,
        'type' => 'kyc',
        'status' => 'pending',
        'stage' => 1,
        'face_match_status' => 'manual_verified',
    ]);

    $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/id-photo", [
        'id_front_photo' => UploadedFile::fake()->image('id.jpg'),
    ])->assertOk();

    expect($verification->fresh()->face_match_status)->toBe('manual_verified');
});

it('rejects upload without id_front_photo', function () {
    $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/id-photo", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id_front_photo']);
});

it('rejects upload from another agent', function () {
    $otherAgent = User::factory()->create();
    $otherAgent->givePermissionTo('loans.create');
    Sanctum::actingAs($otherAgent);

    $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/id-photo", [
        'id_front_photo' => UploadedFile::fake()->image('id.jpg'),
    ])->assertNotFound();
});

// ─── verifyFace ───────────────────────────────────────────────────────────────

it('verifies face synchronously and returns passed result', function () {
    $this->customer->update([
        'id_front_photo_path' => UploadedFile::fake()->image('id.jpg')->store('kyc/id_front', 'public'),
    ]);

    $this->mock(FaceMatchService::class, function ($mock) {
        $mock->shouldReceive('match')->once()->andReturn([
            'status' => 'passed',
            'score' => 0.94,
            'reason' => null,
        ]);
    });

    $response = $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/verify", [
        'face_frame' => UploadedFile::fake()->image('selfie.jpg'),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.passed', true)
        ->assertJsonPath('data.face_match.status', 'passed')
        ->assertJsonPath('data.face_match.score', 0.94)
        ->assertJsonPath('data.face_match.alert', false)
        ->assertJsonStructure(['data' => ['customer_id', 'passed', 'face_match', 'headshot_url', 'id_front_url']]);

    $this->customer->refresh();
    expect($this->customer->headshot_photo_path)->not->toBeNull()
        ->and($this->customer->headshot_photo_path)->toStartWith('kyc/headshot/');

    $verification = Verification::where('customer_id', $this->customer->id)->where('type', 'kyc')->first();
    expect($verification)->not->toBeNull()
        ->and($verification->face_match_status)->toBe('passed')
        ->and((float) $verification->face_match_score)->toBe(0.94)
        ->and($verification->face_match_ran_at)->not->toBeNull();
});

it('verifies face and returns failed result with alert flag', function () {
    $this->customer->update([
        'id_front_photo_path' => UploadedFile::fake()->image('id.jpg')->store('kyc/id_front', 'public'),
    ]);

    $this->mock(FaceMatchService::class, function ($mock) {
        $mock->shouldReceive('match')->once()->andReturn([
            'status' => 'failed',
            'score' => 0.21,
            'reason' => 'Faces do not match.',
        ]);
    });

    $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/verify", [
        'face_frame' => UploadedFile::fake()->image('selfie.jpg'),
    ])
        ->assertOk()
        ->assertJsonPath('data.passed', false)
        ->assertJsonPath('data.face_match.status', 'failed')
        ->assertJsonPath('data.face_match.alert', true);
});

it('verifies face and returns review result', function () {
    $this->customer->update([
        'id_front_photo_path' => UploadedFile::fake()->image('id.jpg')->store('kyc/id_front', 'public'),
    ]);

    $this->mock(FaceMatchService::class, function ($mock) {
        $mock->shouldReceive('match')->once()->andReturn([
            'status' => 'review',
            'score' => 0.0,
            'reason' => 'Face match service is not configured.',
        ]);
    });

    $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/verify", [
        'face_frame' => UploadedFile::fake()->image('selfie.jpg'),
    ])
        ->assertOk()
        ->assertJsonPath('data.passed', false)
        ->assertJsonPath('data.face_match.status', 'review')
        ->assertJsonPath('data.face_match.alert', true);
});

it('rejects face verify when id front photo is missing', function () {
    $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/verify", [
        'face_frame' => UploadedFile::fake()->image('selfie.jpg'),
    ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('rejects face verify without face_frame', function () {
    $this->customer->update([
        'id_front_photo_path' => UploadedFile::fake()->image('id.jpg')->store('kyc/id_front', 'public'),
    ]);

    $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/verify", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['face_frame']);
});

it('does not overwrite manual_verified status on verify', function () {
    $this->customer->update([
        'id_front_photo_path' => UploadedFile::fake()->image('id.jpg')->store('kyc/id_front', 'public'),
    ]);

    Verification::factory()->create([
        'customer_id' => $this->customer->id,
        'type' => 'kyc',
        'status' => 'pending',
        'stage' => 1,
        'face_match_status' => 'manual_verified',
    ]);

    $this->mock(FaceMatchService::class, function ($mock) {
        $mock->shouldReceive('match')->once()->andReturn([
            'status' => 'failed',
            'score' => 0.1,
            'reason' => 'No match.',
        ]);
    });

    $this->postJson("/api/v1/kyc/application/{$this->customer->id}/face/verify", [
        'face_frame' => UploadedFile::fake()->image('selfie.jpg'),
    ])->assertOk();

    $verification = Verification::where('customer_id', $this->customer->id)->where('type', 'kyc')->first();
    expect($verification->face_match_status)->toBe('manual_verified');
});

// ─── faceStatus ───────────────────────────────────────────────────────────────

it('returns face status with no verification yet', function () {
    $response = $this->getJson("/api/v1/kyc/application/{$this->customer->id}/face/status");

    $response->assertOk()
        ->assertJsonPath('data.customer_id', $this->customer->id)
        ->assertJsonPath('data.has_id_front', false)
        ->assertJsonPath('data.has_headshot', false)
        ->assertJsonPath('data.face_match', null);
});

it('returns face status with existing verification data', function () {
    $this->customer->update([
        'id_front_photo_path' => UploadedFile::fake()->image('id.jpg')->store('kyc/id_front', 'public'),
        'headshot_photo_path' => UploadedFile::fake()->image('selfie.jpg')->store('kyc/headshot', 'public'),
    ]);

    Verification::factory()->create([
        'customer_id' => $this->customer->id,
        'type' => 'kyc',
        'status' => 'pending',
        'stage' => 1,
        'face_match_status' => 'passed',
        'face_match_score' => 0.91,
        'face_match_reason' => null,
    ]);

    $this->getJson("/api/v1/kyc/application/{$this->customer->id}/face/status")
        ->assertOk()
        ->assertJsonPath('data.has_id_front', true)
        ->assertJsonPath('data.has_headshot', true)
        ->assertJsonPath('data.face_match.status', 'passed')
        ->assertJsonPath('data.face_match.score', 0.91)
        ->assertJsonPath('data.face_match.alert', false);
});

it('marks alert true for review and failed statuses', function () {
    Verification::factory()->create([
        'customer_id' => $this->customer->id,
        'type' => 'kyc',
        'status' => 'pending',
        'stage' => 1,
        'face_match_status' => 'review',
    ]);

    $this->getJson("/api/v1/kyc/application/{$this->customer->id}/face/status")
        ->assertOk()
        ->assertJsonPath('data.face_match.alert', true);
});

it('face status is not accessible without authentication', function () {
    $customerId = $this->customer->id;

    $this->refreshApplication();

    $this->getJson("/api/v1/kyc/application/{$customerId}/face/status")
        ->assertUnauthorized();
});
