<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xavfsizlik tekshiruvi</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--tg-theme-bg-color, #ffffff);
            color: var(--tg-theme-text-color, #000000);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 400px;
            margin: 0 auto;
        }

        .icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }

        .question {
            background: var(--tg-theme-secondary-bg-color, #f0f0f0);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
        }

        .question-text {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .option-btn {
            background: var(--tg-theme-button-color, #3390ec);
            color: var(--tg-theme-button-text-color, #ffffff);
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .option-btn:active {
            opacity: 0.7;
        }

        .option-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .status {
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
            font-weight: 500;
            display: none;
        }

        .status.success {
            background: #d4edda;
            color: #155724;
        }

        .status.error {
            background: #f8d7da;
            color: #721c24;
        }

        .loader {
            text-align: center;
            padding: 20px;
            display: none;
        }

        .loader-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--tg-theme-hint-color, #999);
            border-top: 4px solid var(--tg-theme-button-color, #3390ec);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hint {
            text-align: center;
            color: var(--tg-theme-hint-color, #999);
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🤖</div>
        <h1>Inson ekanligingizni tasdiqlang</h1>

        <div class="question" id="questionBlock">
            <div class="question-text" id="questionText">Hisobni yeching:</div>
            <div class="options" id="optionsBlock"></div>
        </div>

        <div class="loader" id="loader">
            <div class="loader-spinner"></div>
            <p style="margin-top: 10px;">Tekshirilmoqda...</p>
        </div>

        <div class="status" id="status"></div>
        <div class="hint">IP manzilingiz xavfsizlik uchun avtomatik saqlanadi</div>
    </div>

    <script>
        const tg = window.Telegram.WebApp;
        tg.expand();
        tg.ready();

        const pollId = '{{ $poll_id }}';
        const chatId = '{{ $chat_id }}';
        const token = '{{ $token }}';
        const candidateId = '{{ $candidate_id ?? "" }}';

        // Generate simple math question
        const num1 = Math.floor(Math.random() * 10) + 1;
        const num2 = Math.floor(Math.random() * 10) + 1;
        const correctAnswer = num1 + num2;

        // Generate 4 options (including correct answer)
        const options = [correctAnswer];
        while (options.length < 4) {
            const wrong = Math.floor(Math.random() * 20) + 1;
            if (!options.includes(wrong)) {
                options.push(wrong);
            }
        }

        // Shuffle options
        options.sort(() => Math.random() - 0.5);

        // Display question
        document.getElementById('questionText').textContent = `${num1} + ${num2} = ?`;

        // Display options
        const optionsBlock = document.getElementById('optionsBlock');
        options.forEach(option => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.textContent = option;
            btn.onclick = () => checkAnswer(option);
            optionsBlock.appendChild(btn);
        });

        async function checkAnswer(userAnswer) {
            // Disable all buttons
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.disabled = true;
            });

            const isCorrect = userAnswer === correctAnswer;

            if (!isCorrect) {
                showStatus('error', '❌ Noto\'g\'ri javob! Qaytadan urinib ko\'ring.');
                setTimeout(() => {
                    location.reload();
                }, 2000);
                return;
            }

            // Show loader
            document.getElementById('questionBlock').style.display = 'none';
            document.getElementById('loader').style.display = 'block';

            try {
                // Submit answer and collect IP
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
                        token: token,
                        candidate_id: candidateId,
                        captcha_answer: userAnswer,
                        captcha_correct: correctAnswer
                    })
                });

                const data = await response.json();

                document.getElementById('loader').style.display = 'none';

                if (data.success) {
                    showStatus('success', '✅ Tasdiqlandi! Telegram botga qaytilmoqda...');

                    // Send data back to bot and close WebApp
                    setTimeout(() => {
                        // Send verification complete data to bot
                        const responseData = JSON.stringify({
                            action: 'verification_complete',
                            poll_id: pollId,
                            candidate_id: candidateId,
                            verified: true
                        });

                        tg.sendData(responseData);
                    }, 500);
                } else {
                    showStatus('error', '❌ Xato: ' + data.message);
                }

            } catch (error) {
                document.getElementById('loader').style.display = 'none';
                showStatus('error', '❌ Xatolik yuz berdi. Iltimos, qaytadan urinib ko\'ring.');
            }
        }

        function showStatus(type, message) {
            const statusEl = document.getElementById('status');
            statusEl.className = 'status ' + type;
            statusEl.textContent = message;
            statusEl.style.display = 'block';
        }

        // Set theme colors
        if (tg.themeParams) {
            document.body.style.backgroundColor = tg.themeParams.bg_color || '#ffffff';
            document.body.style.color = tg.themeParams.text_color || '#000000';
        }
    </script>
</body>
</html>
