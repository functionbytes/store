{{-- resources/views/plugins/wompi/error.blade.php --}}
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Error en el pago') }} - {{ theme_option('site_title') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 500px;
            width: 90%;
        }
        .error-header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .error-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        .error-body {
            padding: 2rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            border-radius: 10px;
            padding: 10px 28px;
            font-weight: 600;
        }
        .btn-outline-secondary:hover {
            background: #6c757d;
            border-color: #6c757d;
        }
    </style>
</head>
<body>
<div class="error-container">
    <div class="error-card">
        <div class="error-header">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="mb-0">{{ __('Error en el Pago') }}</h2>
        </div>

        <div class="error-body text-center">
            <div class="mb-4">
                <p class="text-muted mb-3">{{ __('Ha ocurrido un error al procesar tu pago con Wompi') }}</p>

                @if(isset($message) && $message)
                    <div class="alert alert-danger" role="alert">
                        <strong>{{ __('Detalles del error') }}:</strong><br>
                        {{ $message }}
                    </div>
                @endif

                <p class="small text-muted mb-0">
                    {{ __('Por favor, verifica tu información de pago e inténtalo nuevamente. Si el problema persiste, contacta a nuestro soporte.') }}
                </p>
            </div>

            <div class="d-grid gap-2">
                <button onclick="history.back()" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>
                    {{ __('Volver e Intentar de Nuevo') }}
                </button>

                <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>
                    {{ __('Ir al Inicio') }}
                </a>
            </div>

            <div class="mt-4 pt-3 border-top">
                <small class="text-muted">
                    <i class="fas fa-headset me-1"></i>
                    {{ __('¿Necesitas ayuda?') }}
                    <a href="mailto:{{ theme_option('admin_email') }}" class="text-decoration-none">
                        {{ __('Contacta soporte') }}
                    </a>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Auto-redirect después de 30 segundos si no hay interacción
    let countdown = 30;
    const redirectTimer = setInterval(() => {
        countdown--;
        if (countdown <= 0) {
            window.history.back();
            clearInterval(redirectTimer);
        }
    }, 1000);

    // Detener el contador si el usuario interactúa con la página
    document.addEventListener('click', () => clearInterval(redirectTimer));
    document.addEventListener('keydown', () => clearInterval(redirectTimer));
</script>
</body>
</html>
