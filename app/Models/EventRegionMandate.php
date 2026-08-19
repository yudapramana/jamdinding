<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventRegionMandate extends Model
{
    use HasFactory;

    protected $table = 'event_region_mandates';

    protected $fillable = [
        'event_id',
        'region_type',
        'region_id',
        'mandate_file_url',
        'status',
        'uploaded_by',
        'uploaded_at',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /* =========================
     |  CONSTANTS (REGION TYPE)
     ========================= */
    public const REGION_PROVINCE = 'province';
    public const REGION_REGENCY  = 'regency';
    public const REGION_DISTRICT = 'district';
    public const REGION_VILLAGE  = 'village';

    /* =========================
     |  CONSTANTS (STATUS)
     ========================= */
    public const STATUS_NOT_UPLOADED = 'not_uploaded';
    public const STATUS_UPLOADED     = 'uploaded';
    public const STATUS_APPROVED     = 'approved';
    public const STATUS_REJECTED     = 'rejected';

    /* =========================
     |  RELATIONSHIPS
     ========================= */

    protected function secureUrl($value)
    {
        if (!$value) return null;

        // Jika sudah URL absolut (http / https), return langsung
        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        // Jika path lokal, bungkus dengan /secure/
        return '/secure/' . ltrim($value, '/');
    }

    public function getMandateFileUrlAttribute($value)
    {
        return $this->secureUrl($value);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Polymorphic region (province / regency / district / village)
     */
    public function region()
    {
        return $this->morphTo(null, 'region_type', 'region_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* =========================
     |  ACCESSORS
     ========================= */

    public function getRegionNameAttribute(): string
    {
        return match ($this->region_type) {
            self::REGION_PROVINCE =>
                \DB::table('provinces')->where('id', $this->region_id)->value('name'),

            self::REGION_REGENCY =>
                \DB::table('regencies')->where('id', $this->region_id)->value('name'),

            self::REGION_DISTRICT =>
                \DB::table('districts')->where('id', $this->region_id)->value('name'),

            self::REGION_VILLAGE =>
                \DB::table('villages')->where('id', $this->region_id)->value('name'),

            default => '-',
        } ?? '-';
    }

    /* =========================
     |  SCOPES
     ========================= */

    public function scopeByEvent(Builder $query, int $eventId): Builder
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeForRegion(Builder $query, string $regionType, int $regionId): Builder
    {
        return $query->where('region_type', $regionType)->where('region_id', $regionId);
    }

    public function scopeUploaded(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_UPLOADED, self::STATUS_APPROVED]);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeNotUploaded(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NOT_UPLOADED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /* =========================
     |  HELPERS
     ========================= */

    public function isUploaded(): bool
    {
        return in_array($this->status, [self::STATUS_UPLOADED, self::STATUS_APPROVED]);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function markUploaded(string $fileUrl, ?int $uploadedByUserId = null): bool
    {
        return $this->update([
            'mandate_file_url' => $fileUrl,
            'status'           => self::STATUS_UPLOADED,
            'uploaded_by'      => $uploadedByUserId,
            'uploaded_at'      => now(),
            'approved_by'      => null,
            'approved_at'      => null,
        ]);
    }

    public function markApproved(?int $approvedByUserId = null, ?string $notes = null): bool
    {
        return $this->update([
            'status'       => self::STATUS_APPROVED,
            'approved_by'  => $approvedByUserId,
            'approved_at'  => now(),
            'notes'        => $notes ?? $this->notes,
        ]);
    }

    public function markRejected(?int $approvedByUserId = null, ?string $notes = null): bool
    {
        return $this->update([
            'status'       => self::STATUS_REJECTED,
            'approved_by'  => $approvedByUserId,
            'approved_at'  => now(),
            'notes'        => $notes ?? $this->notes,
        ]);
    }

    /**
     * Cek apakah region tertentu sudah disetujui (approved)
     * untuk event tertentu. Aman walau row belum pernah dibuat.
     */
    public static function isRegionMandateApproved(int $eventId, string $regionType, int $regionId): bool
    {
        return static::query()
            ->byEvent($eventId)
            ->forRegion($regionType, $regionId)
            ->approved()
            ->exists();
    }

    /**
     * Cek apakah region tertentu sudah upload mandat (uploaded/approved)
     * untuk event tertentu. Aman walau row belum pernah dibuat.
     */
    public static function isRegionMandateUploaded(int $eventId, string $regionType, int $regionId): bool
    {
        return static::query()
            ->byEvent($eventId)
            ->forRegion($regionType, $regionId)
            ->uploaded()
            ->exists();
    }

    /**
     * Ambil atau buat record mandat (default not_uploaded)
     * untuk kombinasi event + region tertentu.
     */
    public static function firstOrCreateFor(int $eventId, string $regionType, int $regionId): self
    {
        return static::firstOrCreate(
            ['event_id' => $eventId, 'region_type' => $regionType, 'region_id' => $regionId],
            ['status' => self::STATUS_NOT_UPLOADED]
        );
    }

    /**
     * Ambil status mandat aktual untuk region tertentu pada event tertentu.
     * Jika belum pernah ada row sama sekali, dianggap 'not_uploaded'.
     */
    public static function getRegionMandateStatus(int $eventId, string $regionType, int $regionId): string
    {
        $mandate = static::query()
            ->byEvent($eventId)
            ->forRegion($regionType, $regionId)
            ->first();

        return $mandate->status ?? 'not_uploaded';
    }
}