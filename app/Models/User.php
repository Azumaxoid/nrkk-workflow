<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'position',
        'is_active',
        'slack_webhook_url',
        'notification_preferences',
        'organization_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'notification_preferences' => 'array',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class, 'applicant_id');
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class, 'approver_id');
    }

    public function notificationSettings()
    {
        return $this->hasMany(NotificationSetting::class);
    }

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function unreadNotifications()
    {
        return $this->hasMany(NotificationLog::class)->unread();
    }

    public function isApplicant()
    {
        return $this->role === 'applicant';
    }

    public function isReviewer()
    {
        return in_array($this->role, ['reviewer', 'approver', 'admin']);
    }

    public function isApprover()
    {
        return in_array($this->role, ['approver', 'admin']);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function canApprove($application)
    {
        return $this->isReviewer() && $this->is_active;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}