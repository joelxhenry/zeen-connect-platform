<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Error' }} - Zeen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(165deg, #0D1F1B 0%, #0f2922 50%, #0D1F1B 100%);
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 2rem;
        }

        .error-container {
            text-align: center;
            max-width: 480px;
            color: white;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            font-style: italic;
            color: #1ABC9C;
            margin-bottom: 3rem;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.1);
            line-height: 1;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .message {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            background: #106B4F;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn:hover {
            background: #0D5A42;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 5rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .message {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="logo">Zeen</div>
        <div class="error-code">{{ $code ?? 500 }}</div>
        <h1>{{ $title ?? 'Something Went Wrong' }}</h1>
        <p class="message">{{ $message ?? 'We\'re experiencing technical difficulties. Please try again later.' }}</p>
        <a href="/" class="btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9,22 9,12 15,12 15,22"/>
            </svg>
            Return Home
        </a>
    </div>
</body>
</html>
