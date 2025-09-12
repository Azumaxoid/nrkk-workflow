<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'application_type',
        'conditions',
        'step_count',
        'flow_config',
        'is_active',
        'organization_id',
    ];

    protected $casts = [
        'conditions' => 'array',
        'flow_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('application_type', $type);
    }

    public function matchesConditions(Application $application)
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field || !isset($application->{$field})) {
                continue;
            }

            $applicationValue = $application->{$field};

            $matches = match($operator) {
                '=' => $applicationValue == $value,
                '!=' => $applicationValue != $value,
                '>' => $applicationValue > $value,
                '>=' => $applicationValue >= $value,
                '<' => $applicationValue < $value,
                '<=' => $applicationValue <= $value,
                'in' => in_array($applicationValue, (array)$value),
                'not_in' => !in_array($applicationValue, (array)$value),
                default => false,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    public function createApprovals(Application $application)
    {
        $approvals = [];
        
        foreach ($this->flow_config as $step => $config) {
            $stepNumber = $step + 1;
            $approvers = $config['approvers'] ?? [];
            $stepType = $config['type'] ?? 'approve';

            foreach ($approvers as $approverId) {
                $approvals[] = [
                    'application_id' => $application->id,
                    'approval_flow_id' => $this->id,
                    'approver_id' => $approverId,
                    'step_number' => $stepNumber,
                    'step_type' => $stepType,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Approval::insert($approvals);
        return $approvals;
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public static function findBestMatch(Application $application)
    {
        return static::active()
            ->byType($application->type)
            ->get()
            ->first(function ($flow) use ($application) {
                return $flow->matchesConditions($application);
            });
    }
}