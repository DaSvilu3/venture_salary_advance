<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

class ArabicTranslationsTest extends TestCase
{
    /**
     * Matches t('…'), tKey('…') and __('…') with single- or double-quoted keys, across line breaks.
     */
    private const KEY_PATTERN = '/(?<![\w.])(?:t|tKey|__)\(\s*([\'"])((?:(?!\1)[^\\\\]|\\\\.)+)\1/';

    public function test_every_interface_string_has_an_arabic_translation(): void
    {
        $arabic = json_decode(File::get(lang_path('ar.json')), true, flags: JSON_THROW_ON_ERROR);

        $missing = $this->usedKeys()
            ->reject(fn (string $key): bool => array_key_exists($key, $arabic))
            ->values()
            ->all();

        $this->assertSame([], $missing, 'Add these keys to lang/ar.json.');
    }

    public function test_arabic_translations_keep_every_placeholder(): void
    {
        $arabic = json_decode(File::get(lang_path('ar.json')), true, flags: JSON_THROW_ON_ERROR);

        $broken = collect($arabic)
            ->filter(fn (string $line, string $key): bool => $this->placeholders($key) !== $this->placeholders($line))
            ->keys()
            ->all();

        $this->assertSame([], $broken);
    }

    /**
     * @return Collection<int, string>
     */
    private function usedKeys(): Collection
    {
        $files = collect([
            ...File::allFiles(resource_path('js/pages')),
            ...File::allFiles(resource_path('js/layouts')),
            ...File::allFiles(resource_path('js/components')),
            ...File::allFiles(resource_path('js/lib')),
            ...File::allFiles(app_path()),
        ])->reject(fn (SplFileInfo $file): bool => str_contains($file->getPathname(), '/components/ui/'));

        return $files
            ->flatMap(function (SplFileInfo $file): array {
                preg_match_all(self::KEY_PATTERN, $file->getContents(), $matches);

                return array_map(stripslashes(...), $matches[2]);
            })
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $line): array
    {
        preg_match_all('/:([a-z_]+)/i', $line, $matches);
        $names = $matches[1];
        sort($names);

        return $names;
    }
}
