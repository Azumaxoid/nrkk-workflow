<?php

namespace App\Services;

use App\Models\Approval;
use App\Services\NewRelicService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ApprovalService
{
    protected $newRelicService;

    public function __construct(NewRelicService $newRelicService)
    {
        $this->newRelicService = $newRelicService;
    }
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

        // New Relicメトリクス記録
        $this->recordApprovalQueryMetrics('get_approvals', $filters);

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

        $results = $query->get();

        // 結果をNew Relicに記録
        $this->recordApprovalQueryResult(count($results), $filters);

        return $results;
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

        // New Relicメトリクス記録
        $this->recordApprovalQueryMetrics('get_approvals_paginated', $filters, $perPage);

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

        $results = $query->paginate($perPage);

        // 結果をNew Relicに記録
        $this->recordApprovalPaginationResult($results, $filters);

        return $results;
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

        // New Relicメトリクス記録
        $this->newRelicService->addCustomParameter('approval.action', 'find');
        $this->newRelicService->addCustomParameter('approval.id', $id);
        $this->newRelicService->addCustomParameter('approval.with_relations', !empty($with));
        if (!empty($with)) {
            $this->newRelicService->addCustomParameter('approval.relations', implode(',', $with));
        }

        $query = Approval::query();

        if (!empty($with)) {
            $query->with($with);
        }

        $result = $query->find($id);

        // 結果をNew Relicに記録
        $this->newRelicService->addCustomParameter('approval.found', $result !== null);
        if ($result) {
            $this->newRelicService->addCustomParameter('approval.status', $result->status);
        }

        return $result;
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

    /**
     * 承認クエリのメトリクスをNew Relicに記録
     */
    protected function recordApprovalQueryMetrics(string $method, array $filters, ?int $perPage = null): void
    {
        $this->newRelicService->addCustomParameter('approval.action', $method);
        $this->newRelicService->addCustomParameter('approval.filters.count', count($filters));

        // フィルター詳細
        if (isset($filters['status'])) {
            $this->newRelicService->addCustomParameter('approval.filter.status', $filters['status']);
        }
        if (isset($filters['user_id'])) {
            $this->newRelicService->addCustomParameter('approval.filter.user_id', $filters['user_id']);
        }
        if (isset($filters['approver_id'])) {
            $this->newRelicService->addCustomParameter('approval.filter.approver_id', $filters['approver_id']);
        }
        if (isset($filters['application_id'])) {
            $this->newRelicService->addCustomParameter('approval.filter.application_id', $filters['application_id']);
        }
        if (isset($filters['ids'])) {
            $this->newRelicService->addCustomParameter('approval.filter.ids_count', count($filters['ids']));
        }
        if (isset($filters['with'])) {
            $this->newRelicService->addCustomParameter('approval.with_relations', implode(',', $filters['with']));
        }

        // ページネーション情報
        if ($perPage) {
            $this->newRelicService->addCustomParameter('approval.per_page', $perPage);
        }
    }

    /**
     * 承認クエリ結果をNew Relicに記録
     */
    protected function recordApprovalQueryResult(int $count, array $filters): void
    {
        $this->newRelicService->addCustomParameter('approval.result.count', $count);
        $this->newRelicService->recordMetric('ApprovalQueryResultCount', $count);

        // ステータス別のメトリクス
        if (isset($filters['status'])) {
            $this->newRelicService->recordMetric('ApprovalQuery_' . ucfirst($filters['status']), $count);
        }
    }

    /**
     * 承認ページネーション結果をNew Relicに記録
     */
    protected function recordApprovalPaginationResult($results, array $filters): void
    {
        $this->newRelicService->addCustomParameter('approval.pagination.total', $results->total());
        $this->newRelicService->addCustomParameter('approval.pagination.per_page', $results->perPage());
        $this->newRelicService->addCustomParameter('approval.pagination.current_page', $results->currentPage());
        $this->newRelicService->addCustomParameter('approval.pagination.last_page', $results->lastPage());
        $this->newRelicService->addCustomParameter('approval.pagination.count', $results->count());

        // メトリクス記録
        $this->newRelicService->recordMetric('ApprovalPaginationTotal', $results->total());
        $this->newRelicService->recordMetric('ApprovalPaginationCount', $results->count());

        // カスタムイベント記録
        $this->newRelicService->recordCustomEvent('ApprovalQuery', [
            'method' => 'paginated',
            'total_count' => $results->total(),
            'page_count' => $results->count(),
            'current_page' => $results->currentPage(),
            'filters' => json_encode(array_keys($filters))
        ]);
    }
}