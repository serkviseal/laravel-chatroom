<?php

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class MessageParser
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $env = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);
        $env->addExtension(new CommonMarkCoreExtension);
        $env->addExtension(new GithubFlavoredMarkdownExtension);
        $env->addExtension(new AutolinkExtension);

        $this->converter = new MarkdownConverter($env);
    }

    public function toHtml(string $body): string
    {
        return trim((string) $this->converter->convert($body));
    }

    /** @return string[] */
    public function extractMentions(string $body): array
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $body, $matches);

        return array_unique($matches[1]);
    }

    /** @return string[] */
    public function extractChannelRefs(string $body): array
    {
        preg_match_all('/#([a-zA-Z0-9_-]+)/', $body, $matches);

        return array_unique($matches[1]);
    }
}
