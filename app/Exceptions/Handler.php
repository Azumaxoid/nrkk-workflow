<?php

namespace App\Exceptions;

use App\Services\NewRelicService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $newRelicService;

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function __construct()
    {
        parent::__construct(app());
        $this->newRelicService = app(NewRelicService::class);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // 全ての例外をNew Relicに報告
            $this->reportToNewRelic($e);
        });
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        // 例外報告開始ログ
        Log::info(sprintf(
            '例外報告開始 | 例外クラス: %s | メッセージ: %s | ファイル: %s | 行: %d | ユーザーID: %s | URL: %s',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            auth()->id() ?? 'guest',
            request()->fullUrl() ?? 'N/A'
        ));

        // AuthorizationExceptionは特別扱い（notice レベル）
        if ($e instanceof AuthorizationException) {
            $this->reportAuthorizationException($e);
        } else {
            // その他の例外は通常のレポート
            parent::report($e);
        }

        // New Relicに報告
        $this->reportToNewRelic($e);
    }

    /**
     * AuthorizationExceptionの特別な処理
     */
    protected function reportAuthorizationException(AuthorizationException $e): void
    {
        // ログレベルを notice に下げる
        Log::notice('認証エラー発生', [
            'exception' => $e->getMessage(),
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? 'unknown',
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }

    /**
     * New Relicへの例外報告
     */
    protected function reportToNewRelic(Throwable $e): void
    {
        Log::debug(sprintf(
            'New Relic報告開始 | 例外クラス: %s | ユーザーID: %s',
            get_class($e),
            auth()->id() ?? 'guest'
        ));

        try {
            $errorMessage = sprintf(
                '%s: %s (File: %s, Line: %d)',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            // AuthorizationExceptionは notice レベル、その他は error レベル
            if ($e instanceof AuthorizationException) {
                $this->newRelicService->noticeError($errorMessage, $e);
            } else {
                $this->newRelicService->noticeError($errorMessage, $e);
            }

            // 追加のコンテキスト情報をNew Relicに送信
            $this->addNewRelicContext($e);

            Log::info(sprintf(
                'New Relic報告完了 | 例外クラス: %s | ユーザーID: %s',
                get_class($e),
                auth()->id() ?? 'guest'
            ));

        } catch (\Exception $newRelicException) {
            // New Relic への報告が失敗しても元の例外処理は継続
            Log::error('New Relic報告失敗', [
                'original_exception' => $e->getMessage(),
                'original_exception_class' => get_class($e),
                'newrelic_exception' => $newRelicException->getMessage(),
                'newrelic_exception_class' => get_class($newRelicException),
                'user_id' => auth()->id()
            ]);
        }
    }

    /**
     * New Relicにコンテキスト情報を追加
     */
    protected function addNewRelicContext(Throwable $e): void
    {
        // リクエスト情報
        if (app()->bound('request') && request()) {
            newrelic_add_custom_parameter('request.url', request()->fullUrl());
            newrelic_add_custom_parameter('request.method', request()->method());
            newrelic_add_custom_parameter('request.ip', request()->ip());
            newrelic_add_custom_parameter('request.user_agent', request()->userAgent());
        }

        // ユーザー情報
        if (auth()->check()) {
            newrelic_add_custom_parameter('user.id', auth()->id());
            newrelic_add_custom_parameter('user.email', auth()->user()->email ?? 'unknown');
        }

        // 例外情報
        newrelic_add_custom_parameter('exception.class', get_class($e));
        newrelic_add_custom_parameter('exception.file', $e->getFile());
        newrelic_add_custom_parameter('exception.line', $e->getLine());

        // スタックトレース（最初の5行のみ）
        $trace = explode("\n", $e->getTraceAsString());
        for ($i = 0; $i < min(5, count($trace)); $i++) {
            newrelic_add_custom_parameter("stack.{$i}", $trace[$i]);
        }
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        Log::info(sprintf(
            '例外レンダリング開始 | 例外クラス: %s | メッセージ: %s | ユーザーID: %s | URL: %s | JSON期待: %s',
            get_class($e),
            $e->getMessage(),
            auth()->id() ?? 'guest',
            $request->fullUrl(),
            $request->expectsJson() ? 'true' : 'false'
        ));

        // renderでエラーが発生した場合もNew Relicに通知
        try {
            $this->newRelicService->noticeError("Rendering exception: " . $e->getMessage(), $e);
        } catch (\Exception $noticeException) {
            // New Relic通知が失敗しても処理は継続
            Log::error('New Relicレンダリング通知失敗', [
                'original_exception' => $e->getMessage(),
                'original_exception_class' => get_class($e),
                'notice_exception' => $noticeException->getMessage(),
                'user_id' => auth()->id()
            ]);
        }

        // AuthorizationExceptionの場合、ユーザーフレンドリーなメッセージに変換
        if ($e instanceof AuthorizationException) {
            Log::info(sprintf(
                '認証エラーレスポンス生成 | ユーザーID: %s | JSON期待: %s',
                auth()->id() ?? 'guest',
                $request->expectsJson() ? 'true' : 'false'
            ));

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'この操作を実行する権限がありません。',
                    'message' => $e->getMessage()
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'この操作を実行する権限がありません。')
                ->withInput();
        }

        // BulkApprovalExceptionの場合
        if ($e instanceof BulkApprovalException) {
            Log::warning('一括承認エラーレスポンス生成', [
                'approval_id' => $e->getApprovalId(),
                'user_id' => $e->getUserId(),
                'message' => $e->getMessage(),
                'expects_json' => $request->expectsJson()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Bulk approval failed',
                    'message' => $e->getMessage(),
                    'approval_id' => $e->getApprovalId(),
                    'user_id' => $e->getUserId()
                ], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        Log::info(sprintf(
            '標準例外レンダリング実行 | 例外クラス: %s | ユーザーID: %s',
            get_class($e),
            auth()->id() ?? 'guest'
        ));

        return parent::render($request, $e);
    }
}