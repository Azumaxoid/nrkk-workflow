<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'event_type',
        'title',
        'message',
        'data',
        'status',
        'error_message',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public const TYPE_EMAIL = 'email';
    public const TYPE_SLACK = 'slack';
    public const TYPE_DATABASE = 'database';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsSent()
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isRead()
    {
        return $this->read_at !== null;
    }

    public function isUnread()
    {
        return $this->read_at === null;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSent()
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => '送信待ち',
            self::STATUS_SENT => '送信済み',
            self::STATUS_FAILED => '送信失敗',
            default => '不明',
        };
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            self::TYPE_EMAIL => 'メール',
            self::TYPE_SLACK => 'Slack',
            self::TYPE_DATABASE => 'アプリ内',
            default => '不明',
        };
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }
}