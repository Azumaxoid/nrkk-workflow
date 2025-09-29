<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{

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
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        //
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