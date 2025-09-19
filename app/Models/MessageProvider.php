<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageProvider extends Model
{
    use HasFactory;

    protected $table = 'message_providers';

    protected $fillable = ['slug', 'name', 'provider', 'config', 'is_default', 'messages_per_minute'];

    protected $casts = [
        'config' => 'array',
        'is_default' => 'boolean',
        'messages_per_minute' => 'integer',
    ];

    public static function default(): ?self
    {
        return self::where('is_default', true)->first();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'message_provider_id');
    }

    /**
     * Get scheduled messages count for a specific time range.
     *
     * @param \Carbon\Carbon $from
     * @param \Carbon\Carbon $to
     * @return int
     */
    public function getScheduledMessagesCount(\Carbon\Carbon $from, \Carbon\Carbon $to): int
    {
        return $this->messages()
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereNotIn('status', ['sent', 'failed'])
            ->count();
    }

    /**
     * Check if this provider has rate limiting enabled.
     *
     * @return bool
     */
    public function hasRateLimit(): bool
    {
        return $this->messages_per_minute > 0;
    }
}
