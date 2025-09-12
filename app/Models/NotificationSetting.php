<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'channels',
        'is_enabled',
        'config',
    ];

    protected $casts = [
        'channels' => 'array',
        'config' => 'array',
        'is_enabled' => 'boolean',
    ];

    public const EVENT_TYPES = [
        'application_submitted' => '申請が提出されました',
        'approval_requested' => '承認依頼が送られました',
        'application_approved' => '申請が承認されました',
        'application_rejected' => '申請が却下されました',
        'application_cancelled' => '申請がキャンセルされました',
        'step_approved' => 'ステップが承認されました',
        'step_rejected' => 'ステップが却下されました',
        'step_skipped' => 'ステップがスキップされました',
    ];

    public const AVAILABLE_CHANNELS = [
        'email' => 'メール通知',
        'slack' => 'Slack通知',
        'database' => 'アプリ内通知',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hasChannel($channel)
    {
        return in_array($channel, $this->channels ?? []);
    }

    public function enableChannel($channel)
    {
        $channels = $this->channels ?? [];
        if (!in_array($channel, $channels)) {
            $channels[] = $channel;
            $this->update(['channels' => $channels]);
        }
    }

    public function disableChannel($channel)
    {
        $channels = $this->channels ?? [];
        $channels = array_filter($channels, fn($c) => $c !== $channel);
        $this->update(['channels' => array_values($channels)]);
    }

    public function getEventTypeLabelAttribute()
    {
        return self::EVENT_TYPES[$this->event_type] ?? $this->event_type;
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForEvent($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeWithChannel($query, $channel)
    {
        return $query->whereJsonContains('channels', $channel);
    }
}