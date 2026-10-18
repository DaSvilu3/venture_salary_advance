<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DisclosureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A versioned statement of terms the employee accepts with each advance.
 *
 * @property int $id
 * @property string $version
 * @property string $body_en
 * @property string $body_ar
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['version', 'body_en', 'body_ar', 'published_at'])]
class Disclosure extends Model
{
    /** @use HasFactory<DisclosureFactory> */
    use HasFactory;

    /**
     * The most recently published disclosure, which new advances must accept.
     */
    public static function current(): ?self
    {
        return static::query()->whereNotNull('published_at')->latest('published_at')->latest('id')->first();
    }

    public function bodyFor(string $locale): string
    {
        return $locale === 'ar' ? $this->body_ar : $this->body_en;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
        ];
    }
}
