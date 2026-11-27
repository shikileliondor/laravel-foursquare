<?php

it('serves the public privacy policy and legal notice without login', function (string $page, string $heading) {
    $this->get("/{$page}")
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=3600, public')
        ->assertSee($heading, false)
        ->assertDontSee('<script', false);
})->with([
    ['privacy', 'Politique de confidentialité'],
    ['legal', 'Mentions légales'],
]);
