<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px 0;
        }
        .message {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #0d6efd;
            margin: 20px 0;
        }
        .action-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .data-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .data-section h3 {
            margin-top: 0;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $title }}</h1>
        </div>
        
        <div class="content">
            <div class="message">
                @php
                    $displayMessage = is_string($messageContent) ? $messageContent : (isset($messageContent->message) ? $messageContent->message : '');
                @endphp
                {!! nl2br(e($displayMessage)) !!}
            </div>
            
            @if($actionUrl)
            <div style="text-align: center;">
                <a href="{{ $actionUrl }}" class="action-button">
                    詳細を確認する
                </a>
            </div>
            @endif
            
            @if($data && is_array($data))
            <div class="data-section">
                <h3>詳細情報</h3>
                @foreach($data as $key => $value)
                    @if(!is_array($value) && !is_object($value))
                    <p><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</p>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
        
        <div class="footer">
            <p>このメールは申請承認ワークフローシステムから自動送信されています。</p>
            <p>返信の必要はありません。</p>
        </div>
    </div>
</body>
</html>