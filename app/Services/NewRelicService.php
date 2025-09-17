<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NewRelicService
{
    private bool $enabled;
    private string $appName;
    private string $licenseKey;

    public function __construct()
    {
        $this->enabled = config('newrelic.enabled', true);
        $this->appName = config('newrelic.app_name', 'Laravel Application');
        $this->licenseKey = config('newrelic.license_key', '');
    }

    /**
     * New Relicが有効かチェック
     */
    public function isEnabled(): bool
    {
        return $this->enabled && extension_loaded('newrelic');
    }

    /**
     * カスタムイベントを記録
     */
    public function recordCustomEvent(string $name, array $attributes = []): void
    {
        if (!$this->isEnabled()) {
            Log::debug("New Relic custom event (disabled): {$name}", $attributes);
            return;
        }

        try {
            newrelic_record_custom_event($name, $attributes);
            Log::debug("New Relic custom event recorded: {$name}", $attributes);
        } catch (\Exception $e) {
            Log::error("Failed to record New Relic custom event: {$e->getMessage()}");
        }
    }

    /**
     * カスタムメトリクスを記録
     */
    public function recordMetric(string $name, float $value): void
    {
        if (!$this->isEnabled()) {
            Log::debug("New Relic metric (disabled): {$name} = {$value}");
            return;
        }

        try {
            newrelic_custom_metric("Custom/{$name}", $value);
            Log::debug("New Relic metric recorded: {$name} = {$value}");
        } catch (\Exception $e) {
            Log::error("Failed to record New Relic metric: {$e->getMessage()}");
        }
    }

    /**
     * トランザクション名を設定
     */
    public function setTransactionName(string $name): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            newrelic_name_transaction($name);
        } catch (\Exception $e) {
            Log::error("Failed to set New Relic transaction name: {$e->getMessage()}");
        }
    }

    /**
     * カスタムパラメータを追加
     */
    public function addCustomParameter(string $key, $value): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            newrelic_add_custom_parameter($key, $value);
        } catch (\Exception $e) {
            Log::error("Failed to add New Relic custom parameter: {$e->getMessage()}");
        }
    }

    /**
     * バックグラウンドジョブとしてマーク
     */
    public function backgroundJob(bool $flag = true): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            newrelic_background_job($flag);
        } catch (\Exception $e) {
            Log::error("Failed to mark as New Relic background job: {$e->getMessage()}");
        }
    }

    /**
     * エラーを通知
     */
    public function noticeError(string $message, \Throwable $exception = null): void
    {
        if (!$this->isEnabled()) {
            Log::error("New Relic error (disabled): {$message}", [
                'exception' => $exception ? $exception->getMessage() : null
            ]);
            return;
        }

        try {
            // エラーグループ名を生成してカスタム属性として追加
            $errorMessage = $exception ? $exception->getMessage() : $message;
            $exceptionClass = $exception ? get_class($exception) : 'Error';

            // エラーメッセージから動的な値をマスク
            $maskedMessage = $this->maskDynamicValues($errorMessage);

            // エラーグループ識別子を生成
            $groupIdentifier = sprintf(
                '%s',
                $maskedMessage
            );

            // カスタム属性としてerror.group.nameを追加
            newrelic_add_custom_parameter('error.group.name', $exceptionClass);
            newrelic_add_custom_parameter('error.group.message', $groupIdentifier);

            Log::debug('Added error.group.name via noticeError', [
                'original' => $errorMessage,
                'masked' => $maskedMessage,
                'group' => $groupIdentifier
            ]);

            // エラーを通知
            if ($exception) {
                newrelic_notice_error($exception->getMessage(), $exception);
            } else {
                newrelic_notice_error($message);
            }
        } catch (\Exception $e) {
            Log::error("Failed to notice New Relic error: {$e->getMessage()}");
        }
    }

    /**
     * エラーグループコールバックを設定
     *
     * エラーメッセージから動的な値（ID、数値など）をマスクして
     * 同じ種類のエラーをグループ化
     */
    public function setErrorGroupCallback(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            if (function_exists('newrelic_set_error_group_callback')) {
                newrelic_set_error_group_callback([$this, 'errorGroupCallback']);
                Log::debug('New Relic error group callback registered');
            }
        } catch (\Exception $e) {
            Log::error("Failed to set New Relic error group callback: {$e->getMessage()}");
        }
    }

    /**
     * エラーグループコールバック
     *
     * エラーをグループ化するための識別子を生成
     * 数値（ID等）を含むパターンをマスクして同じエラーとして扱う
     *
     * @param array $transaction_data トランザクション情報の配列
     * @param array $error_data エラー情報の配列
     * @return string グループ識別子
     */
    public function errorGroupCallback(array $transaction_data, array $error_data): string
    {
        // エラーデータから必要な情報を取得
        $exceptionClass = $error_data['exception_class'] ?? 'UnknownException';
        $errorMessage = $error_data['message'] ?? '';

        // エラーメッセージから動的な値をマスク
        $maskedMessage = $this->maskDynamicValues($errorMessage);

        // エラーグループ識別子を生成
        // 例外クラス名とマスク済みメッセージを組み合わせる
        $groupIdentifier = sprintf(
            '%s',
            $maskedMessage
        );

        // カスタム属性としてerror.group.nameを追加
        try {
            newrelic_add_custom_parameter('error.group.name', $exceptionClass);
            newrelic_add_custom_parameter('error.group.message', $groupIdentifier);
            Log::debug('Added error.group.name custom parameter', [
                'value' => $groupIdentifier
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to add error.group.name custom parameter: {$e->getMessage()}");
        }

        Log::debug('New Relic error grouped', [
            'original' => $errorMessage,
            'masked' => $maskedMessage,
            'group' => $groupIdentifier
        ]);

        return $groupIdentifier;
    }

    /**
     * エラーメッセージから動的な値をマスク
     *
     * - 連続する数字を [NUMBER] に置換
     * - UUID形式を [UUID] に置換
     * - メールアドレスを [EMAIL] に置換
     * - IPアドレスを [IP] に置換
     * - "スペース数字スペース" パターンを " [ID] " に置換
     *
     * @param string $message 元のエラーメッセージ
     * @return string マスク済みメッセージ
     */
    private function maskDynamicValues(string $message): string
    {
        // パターン定義
        $patterns = [
            // "スペース数字(複数桁)スペース" パターン
            '/\s\d+\s/' => ' [ID] ',

            // "コロン + スペース + 数字" パターン (承認者ID: 15 のような形式)
            '/:\s*\d+/' => ': [ID]',

            // UUID形式
            '/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i' => '[UUID]',

            // メールアドレス
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/' => '[EMAIL]',

            // IPアドレス（IPv4）
            '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/' => '[IP]',

            // タイムスタンプ（Unix timestamp）
            '/\b1[567]\d{8}\b/' => '[TIMESTAMP]',

            // 日付形式（YYYY-MM-DD）
            '/\b\d{4}-\d{2}-\d{2}\b/' => '[DATE]',

            // 時刻形式（HH:MM:SS）
            '/\b\d{2}:\d{2}:\d{2}\b/' => '[TIME]',

            // ファイルパス
            '/\/[a-zA-Z0-9_\-\/]+\.[a-zA-Z]{2,4}/' => '[FILEPATH]',

            // 連続する3桁以上の数字（最後に適用）
            '/\b\d{3,}\b/' => '[NUMBER]',
        ];

        $maskedMessage = $message;
        foreach ($patterns as $pattern => $replacement) {
            $maskedMessage = preg_replace($pattern, $replacement, $maskedMessage);
        }

        return $maskedMessage;
    }


}