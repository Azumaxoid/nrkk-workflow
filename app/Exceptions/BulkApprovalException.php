<?php

namespace App\Exceptions;

use Exception;

class BulkApprovalException extends Exception
{
    protected $approvalId;
    protected $userId;

    public function __construct($message = "一括承認処理でエラーが発生しました", $approvalId = null, $userId = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->approvalId = $approvalId;
        $this->userId = $userId;
    }

    public function getApprovalId()
    {
        return $this->approvalId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * デバッグ情報を含む詳細なエラーメッセージを取得
     */
    public function getDetailedMessage()
    {
        $details = [
            'message' => $this->getMessage(),
            'approval_id' => $this->approvalId,
            'user_id' => $this->userId,
            'timestamp' => now()->toDateTimeString(),
            'trace' => $this->getTraceAsString()
        ];

        return json_encode($details, JSON_UNESCAPED_UNICODE);
    }

    /**
     * ログに記録するための情報を取得
     */
    public function getLogContext()
    {
        return [
            'approval_id' => $this->approvalId,
            'user_id' => $this->userId,
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}