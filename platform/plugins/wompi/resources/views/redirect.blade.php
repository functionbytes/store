<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Redirecting to Wompi...') }}</title>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            text-align: center;
            padding: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .debug {
            background: #fbfbfb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            font-size: 13px;
            text-align: left;
            border: 1px solid #e9ecef;
        }
        .debug strong {
            color: #495057;
        }
        .countdown {
            color: #6c757d;
            font-size: 14px;
            margin-top: 10px;
        }
        .manual-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
            transition: transform 0.2s;
        }
        .manual-submit:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">💳</div>
    <h3>{{ __('Redirecting to Wompi...') }}</h3>
    <div class="loader"></div>
    <p>{{ __('Please wait while we redirect you to complete your payment.') }}</p>

    <div class="countdown" id="countdown">
        {{ __('Redirecting in') }} <span id="timer">{{ $redirectDelay ?? 3 }}</span> {{ __('seconds...') }}
    </div>

    @if(app()->environment('local') && isset($debugInfo))
        <div class="debug">
            <strong>🐛 {{ __('Debug Information') }}:</strong><br>
            <strong>{{ __('Reference') }}:</strong> {{ $debugInfo['reference'] ?? 'N/A' }}<br>
            <strong>{{ __('Amount') }}:</strong> {{ $debugInfo['amount'] ?? 'N/A' }} {{ $debugInfo['currency'] ?? 'COP' }}<br>
            <strong>{{ __('Email') }}:</strong> {{ $debugInfo['email'] ?? 'N/A' }}<br>
            <strong>{{ __('Environment') }}:</strong> {{ app()->environment() }}
        </div>
    @endif

    <form id="wompi-form" action="{{ $checkoutUrl }}" method="POST" style="display: none;">
        @foreach($formData as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @if(app()->environment('local'))
                <!-- {{ $key }}: {{ $value }} -->
            @endif
        @endforeach
    </form>

    <button type="button" class="manual-submit" onclick="submitForm()">
        {{ __('Continue to Payment') }} →
    </button>
</div>

<script>
    let countdown = {{ $redirectDelay ?? 3 }};
    const timerElement = document.getElementById('timer');

    // Actualizar contador


    function submitForm() {
        const form = document.getElementById('wompi-form');
        if (form) {
            // Mostrar loading en el botón
            const button = document.querySelector('.manual-submit');
            if (button) {
                button.innerHTML = '{{ __("Redirecting...") }} ⏳';
                button.disabled = true;
            }

            form.submit();
        }
    }

    // También permitir submit con Enter o click en cualquier parte
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            submitForm();
        }
    });

    // Evitar envío múltiple
    let formSubmitted = false;
    document.getElementById('wompi-form').addEventListener('submit', function() {
        if (formSubmitted) {
            return false;
        }
        formSubmitted = true;
    });
</script>
</body>
</html>
