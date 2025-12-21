<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xavfsizlik tekshiruvi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .status {
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-weight: 500;
        }

        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .close-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
            display: none;
        }

        .close-btn:hover {
            background: #5568d3;
        }

        .ip-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 14px;
            color: #495057;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔐</div>
        <h1>Xavfsizlik tekshiruvi</h1>
        <p>Botimiz spam va soxta ovozlardan himoyalangan. Sizning haqiqiy IP manzilingiz tekshirilmoqda...</p>

        <div class="loader" id="loader"></div>

        <div class="status" id="status" style="display: none;"></div>
        <div class="ip-info" id="ipInfo"></div>

        <button class="close-btn" id="closeBtn" onclick="closeWindow()">Telegram botga qaytish</button>
    </div>

    <script>
        const pollId = '{{ $poll_id }}';
        const chatId = '{{ $chat_id }}';
        const token = '{{ $token }}';

        // Auto-collect IP on page load
        window.addEventListener('DOMContentLoaded', function() {
            collectIp();
        });

        async function collectIp() {
            try {
                const response = await fetch('/api/collect-ip', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        poll_id: pollId,
                        chat_id: chatId,
                        token: token
                    })
                });

                const data = await response.json();

                // Hide loader
                document.getElementById('loader').style.display = 'none';

                const statusEl = document.getElementById('status');
                const ipInfoEl = document.getElementById('ipInfo');
                const closeBtnEl = document.getElementById('closeBtn');

                statusEl.style.display = 'block';

                if (data.success) {
                    statusEl.className = 'status success';
                    statusEl.innerHTML = '✅ Xavfsizlik tekshiruvi muvaffaqiyatli o\'tdi!<br>Iltimos, Telegram botga qayting.';

                    ipInfoEl.style.display = 'block';
                    ipInfoEl.textContent = 'Sizning IP: ' + data.ip;

                    closeBtnEl.style.display = 'inline-block';

                    // Auto-close after 3 seconds (if opened in Telegram WebApp)
                    setTimeout(() => {
                        if (window.Telegram && window.Telegram.WebApp) {
                            window.Telegram.WebApp.close();
                        }
                    }, 3000);

                } else {
                    statusEl.className = 'status error';
                    statusEl.textContent = '❌ Xato: ' + data.message;
                    closeBtnEl.style.display = 'inline-block';
                }

            } catch (error) {
                document.getElementById('loader').style.display = 'none';
                const statusEl = document.getElementById('status');
                statusEl.style.display = 'block';
                statusEl.className = 'status error';
                statusEl.textContent = '❌ Xatolik yuz berdi. Iltimos, qaytadan urinib ko\'ring.';
                document.getElementById('closeBtn').style.display = 'inline-block';
            }
        }

        function closeWindow() {
            // Try Telegram WebApp close
            if (window.Telegram && window.Telegram.WebApp) {
                window.Telegram.WebApp.close();
            } else {
                // Fallback: close window or go back
                window.close();
                if (!window.closed) {
                    window.history.back();
                }
            }
        }
    </script>
</body>
</html>
