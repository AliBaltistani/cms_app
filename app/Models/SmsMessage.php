<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SMS Message Model
 * 
 * Tracks SMS communications between trainers and clients
 * Stores message history, delivery status, and metadata
 * 
 * @package     Laravel CMS App
 * @subpackage  Models
 * @category    SMS Communication
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class SmsMessage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'sms_messages';

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'sender_phone',
        'recipient_phone',
        'message_content',
        'message_sid',
        'status',
        'direction',
        'message_type',
        'error_code',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * SMS message status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED = 'queued';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_UNDELIVERED = 'undelivered';

    /**
     * SMS message direction constants
     */
    const DIRECTION_OUTBOUND = 'outbound';
    const DIRECTION_INBOUND = 'inbound';

    /**
     * SMS message type constants
     */
    const TYPE_NOTIFICATION = 'notification';
    const TYPE_CONVERSATION = 'conversation';
    const TYPE_REMINDER = 'reminder';
    const TYPE_ALERT = 'alert';

    /**
     * Get the sender user relationship
     * 
     * @return BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the recipient user relationship
     * 
     * @return BelongsTo
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Scope for messages between specific users
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId1
     * @param  int  $userId2
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenUsers($query, int $userId1, int $userId2)
    {
        return $query->where(function ($q) use ($userId1, $userId2) {
            $q->where(function ($subQ) use ($userId1, $userId2) {
                $subQ->where('sender_id', $userId1)
                     ->where('recipient_id', $userId2);
            })->orWhere(function ($subQ) use ($userId1, $userId2) {
                $subQ->where('sender_id', $userId2)
                     ->where('recipient_id', $userId1);
            });
        });
    }

    /**
     * Scope for messages sent by a specific user
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSentBy($query, int $userId)
    {
        return $query->where('sender_id', $userId);
    }

    /**
     * Scope for messages received by a specific user
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReceivedBy($query, int $userId)
    {
        return $query->where('recipient_id', $userId);
    }

    /**
     * Scope for messages with specific status
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for failed messages
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_UNDELIVERED]);
    }

    /**
     * Scope for successful messages
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    /**
     * Scope for recent messages
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if message was successfully delivered
     * 
     * @return bool
     */
    public function isDelivered(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    /**
     * Check if message failed to send
     * 
     * @return bool
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_UNDELIVERED]);
    }

    /**
     * Check if message is pending
     * 
     * @return bool
     */
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_QUEUED, self::STATUS_SENDING]);
    }

    /**
     * Mark message as read
     * 
     * @return bool
     */
    public function markAsRead(): bool
    {
        if (!$this->read_at) {
            $this->read_at = now();
            return $this->save();
        }
        
        return true;
    }

    /**
     * Update message status
     * 
     * @param  string  $status
     * @param  array  $additionalData
     * @return bool
     */
    public function updateStatus(string $status, array $additionalData = []): bool
    {
        $this->status = $status;

        // Update timestamps based on status
        switch ($status) {
            case self::STATUS_SENT:
                if (!$this->sent_at) {
                    $this->sent_at = now();
                }
                break;
            case self::STATUS_DELIVERED:
                if (!$this->delivered_at) {
                    $this->delivered_at = now();
                }
                break;
            case self::STATUS_FAILED:
            case self::STATUS_UNDELIVERED:
                if (isset($additionalData['error_code'])) {
                    $this->error_code = $additionalData['error_code'];
                }
                if (isset($additionalData['error_message'])) {
                    $this->error_message = $additionalData['error_message'];
                }
                break;
        }

        // Update metadata if provided
        if (!empty($additionalData['metadata'])) {
            $currentMetadata = $this->metadata ?? [];
            $this->metadata = array_merge($currentMetadata, $additionalData['metadata']);
        }

        return $this->save();
    }

    /**
     * Get conversation thread between two users
     * 
     * @param  int  $userId1
     * @param  int  $userId2
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getConversation(int $userId1, int $userId2, int $limit = 50)
    {
        return self::betweenUsers($userId1, $userId2)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Get message statistics for a user
     * 
     * @param  int  $userId
     * @param  int  $days
     * @return array
     */
    public static function getUserStats(int $userId, int $days = 30): array
    {
        $baseQuery = self::where(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                  ->orWhere('recipient_id', $userId);
        })->where('created_at', '>=', now()->subDays($days));

        return [
            'total_messages' => $baseQuery->count(),
            'sent_messages' => self::sentBy($userId)->recent($days)->count(),
            'received_messages' => self::receivedBy($userId)->recent($days)->count(),
            'successful_messages' => $baseQuery->successful()->count(),
            'failed_messages' => $baseQuery->failed()->count(),
            'unread_messages' => self::receivedBy($userId)->whereNull('read_at')->count()
        ];
    }
}