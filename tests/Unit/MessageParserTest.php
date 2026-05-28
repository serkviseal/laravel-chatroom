<?php

use App\Services\MessageParser;

it('converts markdown to html', function () {
    $parser = new MessageParser;
    $html = $parser->toHtml('**bold** and _italic_');
    expect($html)->toContain('<strong>bold</strong>');
    expect($html)->toContain('<em>italic</em>');
});

it('strips unsafe html script tags from input', function () {
    $parser = new MessageParser;
    $html = $parser->toHtml("safe text\n\n<script>alert(\"xss\")</script>");
    expect($html)->not->toContain('<script>');
    expect($html)->toContain('safe text');
});

it('extracts at-mentions from body', function () {
    $parser = new MessageParser;
    $mentions = $parser->extractMentions('Hello @alice and @bob, how are you?');
    expect($mentions)->toContain('alice');
    expect($mentions)->toContain('bob');
    expect($mentions)->toHaveCount(2);
});

it('extracts channel references from body', function () {
    $parser = new MessageParser;
    $refs = $parser->extractChannelRefs('Check #general and #random for updates');
    expect($refs)->toContain('general');
    expect($refs)->toContain('random');
});

it('deduplicates repeated mentions', function () {
    $parser = new MessageParser;
    $mentions = $parser->extractMentions('@alice @alice @bob');
    expect($mentions)->toHaveCount(2);
});
