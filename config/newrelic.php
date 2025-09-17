<?php

return [
    /**
     * New Relic APM 有効化フラグ
     *
     * true: New Relic への通知を有効化
     * false: New Relic への通知を無効化（ログに記録のみ）
     */
    'enabled' => env('NEW_RELIC_ENABLED', true),

    /**
     * New Relic アプリケーション名
     *
     * New Relic ダッシュボードで表示されるアプリケーション名
     */
    'app_name' => env('NEW_RELIC_APP_NAME', 'Laravel Approval Workflow'),

    /**
     * New Relic ライセンスキー
     *
     * New Relic アカウントのライセンスキー
     * https://one.newrelic.com/admin-portal/api-keys/home から取得
     */
    'license_key' => env('NEW_RELIC_LICENSE_KEY', ''),

    /**
     * New Relic API キー
     *
     * New Relic API を使用する場合のキー
     */
    'api_key' => env('NEW_RELIC_API_KEY', ''),

    /**
     * New Relic アカウント ID
     */
    'account_id' => env('NEW_RELIC_ACCOUNT_ID', ''),

    /**
     * カスタムメトリクスのプレフィックス
     */
    'metric_prefix' => 'Custom/ApprovalWorkflow/',

    /**
     * デバッグモード
     *
     * true: New Relic への送信内容を詳細にログ出力
     */
    'debug' => env('NEWRELIC_DEBUG', false),

    /**
     * トランザクション名のマッピング
     *
     * コントローラアクションごとのカスタムトランザクション名
     */
    'transaction_names' => [
        'ApplicationController@index' => 'Application/List',
        'ApplicationController@store' => 'Application/Create',
        'ApprovalController@approve' => 'Approval/Approve',
        'ApprovalController@reject' => 'Approval/Reject',
        'ApprovalController@bulkApprove' => 'Approval/BulkApprove',
        'ApprovalController@bulkReject' => 'Approval/BulkReject',
    ],

    /**
     * エラー通知の設定
     */
    'error_notification' => [
        /**
         * 通知する最小エラーレベル
         *
         * error, warning, notice, info, debug
         */
        'min_level' => 'error',

        /**
         * 除外するエラーコード
         */
        'exclude_codes' => [404, 403],

        /**
         * 除外する例外クラス
         */
        'exclude_exceptions' => [
            \Illuminate\Validation\ValidationException::class,
        ],
    ],

    /**
     * パフォーマンスモニタリング設定
     */
    'performance' => [
        /**
         * 遅いトランザクションのしきい値（ミリ秒）
         */
        'slow_transaction_threshold' => 3000,

        /**
         * データベースクエリの記録
         */
        'record_sql' => true,

        /**
         * 外部APIコールの記録
         */
        'record_external_calls' => true,
    ],

    /**
     * バックグラウンドジョブの設定
     */
    'background_jobs' => [
        /**
         * ジョブをバックグラウンドとしてマーク
         */
        'mark_as_background' => true,

        /**
         * ジョブ実行時間のしきい値（秒）
         */
        'timeout_threshold' => 300,
    ],
];