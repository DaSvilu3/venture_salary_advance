<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Carbon\CarbonImmutable;
use Database\Factories\EmployerDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A company document uploaded by an employer, stored on the private disk and reviewed by the platform.
 *
 * @property int $id
 * @property int $employer_id
 * @property DocumentType $type
 * @property string $original_name
 * @property string $path
 * @property string $mime
 * @property int $size
 * @property DocumentStatus $status
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $review_note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Employer $employer
 * @property-read User|null $reviewer
 */
#[Fillable(['employer_id', 'type', 'original_name', 'path', 'mime', 'size', 'status', 'reviewed_by', 'reviewed_at', 'review_note'])]
class EmployerDocument extends Model
{
    /** @use HasFactory<EmployerDocumentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Employer, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The document as the employer and platform portals display it.
     *
     * @return array{id: int, type: string, original_name: string, status: string, review_note: string|null, size: int, uploaded_at: string|null, reviewed_at: string|null}
     */
    public function toPortalArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'original_name' => $this->original_name,
            'status' => $this->status->value,
            'review_note' => $this->review_note,
            'size' => $this->size,
            'uploaded_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'reviewed_at' => 'immutable_datetime',
        ];
    }
}
