<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark light">

        <title>@yield('title')</title>

        <style>
            @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');

            :root {
                --bc0-solid: #1a7de0; --bc0-light: #70c8ff; --bc0-dark: #0c4fa0; --bc0-glow: rgba(26,125,224,0.55);
                --bc1-solid: #7b3fd0; --bc1-light: #c890ff; --bc1-dark: #4a1898; --bc1-glow: rgba(123,63,208,0.55);
                --bc3-solid: #c89000; --bc3-light: #ffe870; --bc3-dark: #886000; --bc3-glow: rgba(200,144,0,0.50);
                --bc4-solid: #d42020; --bc4-light: #ff8080; --bc4-dark: #880e0e; --bc4-glow: rgba(212,32,32,0.55);

                --ds-bg: #060324;
                --ds-surface: rgba(255,255,255,.04);
                --ds-surface-2: rgba(255,255,255,.07);
                --ds-border: rgba(255,255,255,.09);
                --ds-border-2: rgba(255,255,255,.13);
                --ds-text: #e0eaff;
                --ds-text-2: #8a9bb0;
                --ds-text-muted: #4a6080;
                --ds-card-shadow: 0 6px 20px rgba(0,0,0,.35);
            }

            @media (prefers-color-scheme: light) {
                :root {
                    --ds-bg: #e8eaf4;
                    --ds-surface: #ffffff;
                    --ds-surface-2: #f4f5fb;
                    --ds-border: #d8dde8;
                    --ds-border-2: #c0c8d8;
                    --ds-text: #1a202c;
                    --ds-text-2: #4a5568;
                    --ds-text-muted: #8a94a6;
                    --ds-card-shadow: 0 6px 20px rgba(0,0,0,.12);
                }
            }

            * { box-sizing: border-box; }

            html, body {
                margin: 0;
                padding: 0;
                background: var(--ds-bg);
            }

            body {
                font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                position: relative;
                overflow-x: hidden;
            }

            .error-page__glow {
                position: fixed;
                inset: 0;
                pointer-events: none;
                background:
                    radial-gradient(circle at 20% 20%, rgba(123, 63, 208, 0.16), transparent 45%),
                    radial-gradient(circle at 80% 75%, rgba(26, 125, 224, 0.14), transparent 45%);
            }

            .error-card {
                position: relative;
                z-index: 1;
                width: 100%;
                max-width: 460px;
                text-align: center;
                background: var(--ds-surface);
                border: 1px solid var(--ds-border);
                border-radius: 24px;
                padding: 3rem 2rem 2.5rem;
                box-shadow: var(--ds-card-shadow);
            }

            .error-ball-wrapper {
                position: relative;
                width: 140px;
                height: 140px;
                margin: 0 auto 1.75rem;
            }

            .error-ball {
                width: 100%;
                height: 100%;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                background: radial-gradient(circle at 32% 28%, var(--ball-light), var(--ball-solid) 55%, var(--ball-dark) 100%);
                box-shadow:
                    0 0 0 6px var(--ds-surface-2),
                    0 0 32px 6px var(--ball-glow),
                    inset 0 -6px 14px rgba(0, 0, 0, 0.25),
                    inset 0 6px 10px rgba(255, 255, 255, 0.25);
                animation: error-ball-float 3.2s ease-in-out infinite;
            }

            .error-ball__code {
                font-weight: 800;
                font-size: 2.75rem;
                line-height: 1;
                color: #fff;
                text-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
            }

            .error-ball__emoji {
                position: absolute;
                top: -6px;
                right: -6px;
                font-size: 1.85rem;
                background: var(--ds-surface-2);
                border: 1px solid var(--ds-border-2);
                border-radius: 50%;
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            }

            @keyframes error-ball-float {
                0%, 100% { transform: translateY(0) rotate(-2deg); }
                50%      { transform: translateY(-8px) rotate(2deg); }
            }

            .error-title {
                font-weight: 700;
                font-size: 1.5rem;
                color: var(--ds-text);
                margin: 0 0 0.5rem;
            }

            .error-description {
                font-size: 0.95rem;
                line-height: 1.5;
                color: var(--ds-text-2);
                margin: 0 0 1.75rem;
            }

            .error-actions {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                justify-content: center;
            }

            @media (min-width: 480px) {
                .error-actions {
                    flex-direction: row;
                }
            }

            .error-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                flex: 1;
                padding: 0.7rem 1.25rem;
                border-radius: 12px;
                font-weight: 600;
                font-size: 0.95rem;
                text-decoration: none;
                cursor: pointer;
                border: 1px solid transparent;
                transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
            }

            .error-btn:hover { transform: translateY(-2px); }

            .error-btn--primary {
                background: linear-gradient(135deg, var(--bc0-solid), var(--bc1-solid));
                color: #fff;
                box-shadow: 0 6px 18px rgba(26, 125, 224, 0.35);
            }

            .error-btn--ghost {
                background: var(--ds-surface-2);
                border-color: var(--ds-border-2);
                color: var(--ds-text);
            }

            @media (prefers-reduced-motion: reduce) {
                .error-ball { animation: none; }
            }
        </style>
    </head>
    <body>
        <div class="error-page__glow"></div>

        <div class="error-card">
            <div class="error-ball-wrapper">
                <div class="error-ball" style="--ball-solid: var(--@yield('accent', 'bc0')-solid); --ball-light: var(--@yield('accent', 'bc0')-light); --ball-dark: var(--@yield('accent', 'bc0')-dark); --ball-glow: var(--@yield('accent', 'bc0')-glow);">
                    <span class="error-ball__code">@yield('code')</span>
                </div>
                <span class="error-ball__emoji">@yield('emoji', '🎲')</span>
            </div>

            <h1 class="error-title">@yield('title')</h1>
            <p class="error-description">@yield('message')</p>

            <div class="error-actions">
                <a href="/" class="error-btn error-btn--primary">Ir al inicio</a>
                <a href="javascript:history.back()" class="error-btn error-btn--ghost">Volver atrás</a>
            </div>
        </div>
    </body>
</html>
