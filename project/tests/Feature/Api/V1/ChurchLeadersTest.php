<?php

use App\Models\Church;
use App\Models\ChurchLeader;
use App\Models\District;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $district = District::create(['name' => 'District de la Côtière']);
    $this->niangon = Church::create(['district_id' => $district->id, 'name' => 'Niangon Révélation']);
    $this->other = Church::create(['district_id' => $district->id, 'name' => 'Ananeraie']);
});

function leadersAdmin(string $role, array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => $role, 'status' => 'ACTIVE'], $attributes));
}

it('lists only published leaders, in display order, with the donation link', function () {
    $this->niangon->update(['donation_url' => 'https://pay.wave.com/m/M_ci_XF7Fts2YKKpD/c/ci/']);
    ChurchLeader::create(['church_id' => $this->niangon->id, 'name' => 'Second', 'display_order' => 2, 'is_published' => true]);
    ChurchLeader::create(['church_id' => $this->niangon->id, 'name' => 'Premier', 'position' => 'Pasteur principal', 'description' => 'Berger de l’assemblée.', 'display_order' => 1, 'is_published' => true]);
    ChurchLeader::create(['church_id' => $this->niangon->id, 'name' => 'Brouillon', 'display_order' => 0]);

    $this->getJson('/api/v1/churches/'.$this->niangon->id)
        ->assertOk()
        ->assertJsonPath('data.donation_url', 'https://pay.wave.com/m/M_ci_XF7Fts2YKKpD/c/ci/')
        ->assertJsonCount(2, 'data.leaders')
        ->assertJsonPath('data.leaders.0.name', 'Premier')
        ->assertJsonPath('data.leaders.0.position', 'Pasteur principal')
        ->assertJsonPath('data.leaders.0.description', 'Berger de l’assemblée.')
        ->assertJsonPath('data.leaders.0.photo', null)
        ->assertJsonPath('data.leaders.1.name', 'Second')
        ->assertJsonMissingPath('data.leaders.0.is_published');

    $this->getJson('/api/v1/churches?search=Ananeraie')
        ->assertOk()
        ->assertJsonPath('data.0.donation_url', null)
        ->assertJsonPath('data.0.leaders', []);
});

it('accepts only an https link from an allowed payment provider', function (?string $url, bool $valid) {
    Sanctum::actingAs(leadersAdmin('SUPER_ADMIN'));

    $response = $this->putJson('/api/v1/admin/churches/'.$this->niangon->id, ['donation_url' => $url]);

    $valid ? $response->assertOk()->assertJsonPath('data.donation_url', $url) : $response->assertUnprocessable();
})->with([
    'wave' => ['https://pay.wave.com/m/M_ci_XF7Fts2YKKpD/c/ci/', true],
    'removed' => [null, true],
    'http' => ['http://pay.wave.com/m/abc', false],
    'other host' => ['https://pay.wave.com.evil.test/m/abc', false],
    'unknown provider' => ['https://example.com/don', false],
]);

it('lets a church admin manage only the leaders of their own church', function () {
    Sanctum::actingAs(leadersAdmin('CHURCH_ADMIN', ['church_id' => $this->niangon->id]));
    $foreign = ChurchLeader::create(['church_id' => $this->other->id, 'name' => 'Autre pasteur']);

    $id = $this->postJson('/api/v1/admin/church-leaders', [
        'church_id' => $this->niangon->id, 'name' => 'Pasteur Kouadio', 'position' => 'Pasteur principal', 'is_published' => true,
    ])->assertCreated()->json('data.id');

    $this->postJson('/api/v1/admin/church-leaders', ['church_id' => $this->other->id, 'name' => 'Intrus'])->assertForbidden();
    $this->putJson('/api/v1/admin/church-leaders/'.$foreign->id, ['name' => 'Modifié'])->assertForbidden();
    $this->deleteJson('/api/v1/admin/church-leaders/'.$foreign->id)->assertForbidden();
    $this->putJson('/api/v1/admin/church-leaders/'.$id, ['church_id' => $this->other->id])->assertForbidden();

    $this->getJson('/api/v1/admin/church-leaders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Pasteur Kouadio');

    expect($foreign->fresh()->name)->toBe('Autre pasteur');
});
