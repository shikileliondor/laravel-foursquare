<?php

it('serves every public legal page without login', function (string $page, string $heading) {
    $this->get("/{$page}")
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=3600, public')
        ->assertSee($heading, false)
        ->assertDontSee('<script', false);
})->with([
    ['privacy', 'Politique de confidentialité'],
    ['legal', 'Mentions légales'],
    ['terms', 'Conditions d’utilisation'],
    ['cookies', 'Politique relative aux cookies et au stockage local'],
    ['consent', 'Politique de consentement'],
]);

it('does not publish drafting placeholders on legal pages', function (string $page) {
    $this->get("/{$page}")
        ->assertOk()
        ->assertDontSee('[nom', false)
        ->assertDontSee('[adresse', false)
        ->assertDontSee('[durée', false)
        ->assertDontSee('décrire ici', false);
})->with(['privacy', 'legal', 'terms', 'cookies', 'consent']);
