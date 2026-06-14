<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de contraseña — PipeCell</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing: antialiased; }
        .wrapper { max-width: 600px; margin: 40px auto; padding: 0 16px 40px; }
        .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); padding: 40px 48px; text-align: center; }
        .header-logo { font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; }
        .header-logo span { color: #3b82f6; }
        .header-tagline { color: #94a3b8; font-size: 13px; margin-top: 4px; }
        .body { padding: 48px; }
        .icon-wrap { width: 64px; height: 64px; background: #eff6ff; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 28px; }
        .title { font-size: 22px; font-weight: 700; color: #0f172a; text-align: center; margin-bottom: 12px; }
        .subtitle { font-size: 15px; color: #64748b; text-align: center; line-height: 1.6; margin-bottom: 36px; }
        .btn-wrap { text-align: center; margin-bottom: 36px; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 36px; border-radius: 10px; letter-spacing: 0.2px; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 32px 0; }
        .url-label { font-size: 12px; color: #94a3b8; text-align: center; margin-bottom: 8px; }
        .url-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; word-break: break-all; font-size: 12px; color: #475569; text-align: center; }
        .expiry-box { background: #fefce8; border: 1px solid #fde047; border-radius: 10px; padding: 14px 18px; display: flex; align-items: flex-start; gap: 12px; margin-top: 28px; }
        .expiry-icon { font-size: 18px; flex-shrink: 0; line-height: 1; }
        .expiry-text { font-size: 13px; color: #713f12; line-height: 1.5; }
        .expiry-text strong { font-weight: 600; }
        .security-box { background: #f8fafc; border-radius: 10px; padding: 14px 18px; margin-top: 14px; }
        .security-text { font-size: 13px; color: #64748b; line-height: 1.5; }
        .footer { padding: 24px 48px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; }
        .footer-text { font-size: 12px; color: #94a3b8; line-height: 1.6; }
        .footer-text a { color: #64748b; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">

        <div class="card">

            {{-- Cabecera con marca --}}
            <div class="header">
                <div class="header-logo">Pipe<span>Cell</span></div>
                <div class="header-tagline">Panel de gestión de reparaciones</div>
            </div>

            {{-- Cuerpo del correo --}}
            <div class="body">

                {{-- Ícono central --}}
                <div class="icon-wrap">
                    <svg width="32" height="32" fill="none" stroke="#2563eb" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>

                <h1 class="title">Recupera tu contraseña</h1>
                <p class="subtitle">
                    Recibimos una solicitud para restablecer la contraseña de tu cuenta.<br>
                    Haz clic en el botón para continuar.
                </p>

                {{-- Botón principal --}}
                <div class="btn-wrap">
                    <a href="{{ $resetUrl }}" class="btn">
                        Restablecer contraseña
                    </a>
                </div>

                <hr class="divider">

                {{-- URL alternativa --}}
                <p class="url-label">Si el botón no funciona, copia este enlace en tu navegador:</p>
                <div class="url-box">{{ $resetUrl }}</div>

                {{-- Aviso de expiración --}}
                <div class="expiry-box">
                    <span class="expiry-icon">⏱️</span>
                    <p class="expiry-text">
                        <strong>Este enlace expira en 60 minutos.</strong>
                        Si no lo usas antes, tendrás que solicitar uno nuevo desde la pantalla de inicio de sesión.
                    </p>
                </div>

                {{-- Aviso de seguridad --}}
                <div class="security-box">
                    <p class="security-text">
                        🔒 Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña seguirá siendo la misma y nadie más puede acceder a tu cuenta.
                    </p>
                </div>
            </div>

            {{-- Pie del correo --}}
            <div class="footer">
                <p class="footer-text">
                    Este correo fue enviado a <strong>{{ $email }}</strong><br>
                    &copy; {{ date('Y') }} PipeCell — Todos los derechos reservados
                </p>
            </div>
        </div>

    </div>
</body>
</html>
