<?php

namespace App\Services;

use App\Models\Approval;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ApprovalService
{
    /**
     * 申請書を取得（汎用メソッド）
     *
     * @param array $filters フィルター条件
     *   - ids: array IDリスト
     *   - user_id: int ユーザーID
     *   - approver_id: int 承認者ID
     *   - application_id: int アプリケーションID
     *   - status: string ステータス (pending, approved, rejected)
     *   - with: array リレーション
     * @return Collection
     */
    public function getApprovals(array $filters = []): Collection
    {
        Log::debug('Getting approvals', ['filters' => $filters]);

        $query = Approval::query();

        // ID指定
        if (isset($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        // ユーザーID指定
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // 承認者ID指定
        if (isset($filters['approver_id'])) {
            $query->where('approver_id', $filters['approver_id']);
        }

        // アプリケーションID指定
        if (isset($filters['application_id'])) {
            $query->where('application_id', $filters['application_id']);
        }

        // ステータス指定
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // リレーション指定
        if (isset($filters['with']) && is_array($filters['with'])) {
            $query->with($filters['with']);
        }

        // ソート指定
        if (isset($filters['order_by'])) {
            $direction = $filters['order_direction'] ?? 'asc';
            $query->orderBy($filters['order_by'], $direction);
        }

        return $query->get();
    }

    /**
     * ペンディング状態の申請書を取得
     *
     * @param array $filters 追加のフィルター条件
     * @return Collection
     */
    public function getPendingApprovals(array $filters = []): Collection
    {
        $filters['status'] = 'pending';
        return $this->getApprovals($filters);
    }

    /**
     * 承認済みの申請書を取得
     *
     * @param array $filters 追加のフィルター条件
     * @return Collection
     */
    public function getApprovedApprovals(array $filters = []): Collection
    {
        $filters['status'] = 'approved';
        return $this->getApprovals($filters);
    }

    /**
     * 却下された申請書を取得
     *
     * @param array $filters 追加のフィルター条件
     * @return Collection
     */
    public function getRejectedApprovals(array $filters = []): Collection
    {
        $filters['status'] = 'rejected';
        return $this->getApprovals($filters);
    }

    /**
     * ページネーション付きで申請書を取得
     *
     * @param array $filters フィルター条件
     * @param int $perPage ページあたりの表示数
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getApprovalsPaginated(array $filters = [], int $perPage = 15)
    {
        Log::debug('Getting paginated approvals', [
            'filters' => $filters,
            'per_page' => $perPage
        ]);

        $query = Approval::query();

        // ID指定
        if (isset($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        // ユーザーID指定
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // 承認者ID指定
        if (isset($filters['approver_id'])) {
            $query->where('approver_id', $filters['approver_id']);
        }

        // アプリケーションID指定
        if (isset($filters['application_id'])) {
            $query->where('application_id', $filters['application_id']);
        }

        // ステータス指定
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // リレーション指定
        if (isset($filters['with']) && is_array($filters['with'])) {
            $query->with($filters['with']);
        }

        // ソート指定
        if (isset($filters['order_by'])) {
            $direction = $filters['order_direction'] ?? 'desc';
            $query->orderBy($filters['order_by'], $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * 単一の申請書を取得
     *
     * @param int $id
     * @param array $with リレーション
     * @return Approval|null
     */
    public function findApproval(int $id, array $with = []): ?Approval
    {
        Log::debug('Finding approval', ['id' => $id, 'with' => $with]);

        $query = Approval::query();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    /**
     * 申請書の総数を取得
     *
     * @param array $filters フィルター条件
     * @return int
     */
    public function countApprovals(array $filters = []): int
    {
        Log::debug('Counting approvals', ['filters' => $filters]);

        $query = Approval::query();

        // ユーザーID指定
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // 承認者ID指定
        if (isset($filters['approver_id'])) {
            $query->where('approver_id', $filters['approver_id']);
        }

        // ステータス指定
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->count();
    }

    // 後方互換性のためのメソッド（段階的に削除予定）

    /**
     * @deprecated Use getApprovals() instead
     */
    public function getAllPendingApprovals(): Collection
    {
        return $this->getPendingApprovals();
    }

    /**
     * @deprecated Use getApprovals(['ids' => $ids]) instead
     */
    public function getApprovalsByIds(array $ids): Collection
    {
        return $this->getApprovals(['ids' => $ids]);
    }
}