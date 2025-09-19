<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $uuid
 * @property string $recipient
 * @property string|null $subject
 * @property string|null $body
 * @property \Carbon\Carbon|null $scheduled_at
 * @property \Carbon\Carbon $scheduled_requested_at
 * @property \Carbon\Carbon|null $sent_at
 * @property string $status
 */
class Message extends Model
{
    use HasFactory, HasUuids;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = [
        'recipient',
        'subject',
        'body',
        'scheduled_at',
        'scheduled_requested_at',
        'sent_at',
        'status',
        'message_provider_id',
        'retry_counter',
    ];

    /**
     * Define casts via method per project conventions.
     *
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'scheduled_requested_at' => 'datetime',
            'sent_at' => 'datetime',
            'retry_counter' => 'integer',
        ];
    }

    protected $hidden = [
        'body'
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'message_tag', 'message_id', 'tag_id', 'id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MessageEvent::class, 'message_id', 'id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(MessageProvider::class, 'message_provider_id');
    }
}
