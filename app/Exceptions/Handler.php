<?php

namespace App\Exceptions;

use App\Services\NewRelicService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
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
        logger()->notice('Authorization failed', [
            'exception' => $e->getMessage(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'ip' => request()->ip(),
        ]);
    }

    /**
     * New Relicへの例外報告
     */
    protected function reportToNewRelic(Throwable $e): void
    {
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

        } catch (\Exception $newRelicException) {
            // New Relic への報告が失敗しても元の例外処理は継続
            logger()->error('Failed to report to New Relic', [
                'original_exception' => $e->getMessage(),
                'newrelic_exception' => $newRelicException->getMessage()
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
        // AuthorizationExceptionの場合、ユーザーフレンドリーなメッセージに変換
        if ($e instanceof AuthorizationException) {
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

        return parent::render($request, $e);
    }
}