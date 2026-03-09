<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Organize') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0fdf4 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            width: 100%;
            max-width: 480px;
            text-align: center;
        }
        .logo-area {
            margin-bottom: 2.5rem;
        }
        .logo-area h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #0e7490;
            letter-spacing: -0.02em;
        }
        .logo-area p {
            margin-top: 0.5rem;
            color: #64748b;
            font-size: 0.95rem;
        }
        .buttons-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem 1rem;
            border-radius: 0.75rem;
            border: 2px solid transparent;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .btn-company {
            background: #0e7490;
            color: #ffffff;
            border-color: #0e7490;
        }
        .btn-company:hover {
            background: #0c6278;
            border-color: #0c6278;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(14, 116, 144, 0.3);
        }
        .btn-employee {
            background: #ffffff;
            color: #0e7490;
            border-color: #0e7490;
        }
        .btn-employee:hover {
            background: #f0f9ff;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(14, 116, 144, 0.15);
        }
        .btn-icon {
            width: 2.5rem;
            height: 2.5rem;
        }
        .btn-label-ar {
            font-size: 1rem;
            font-weight: 700;
        }
        .btn-label-en {
            font-size: 0.8rem;
            opacity: 0.8;
            font-weight: 400;
        }
        .footer {
            margin-top: 3rem;
            color: #94a3b8;
            font-size: 0.8rem;
        }
        @media (max-width: 400px) {
            .buttons-grid {
                grid-template-columns: 1fr;
            }
            .btn {
                flex-direction: row;
                padding: 1rem 1.25rem;
                gap: 0.75rem;
            }
            .btn-icon {
                width: 1.75rem;
                height: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-area">
            <h1>{{ config('app.name', 'Organize') }}</h1>
            <p>نظام إدارة الموارد البشرية / HR Management System</p>
        </div>

        <div class="buttons-grid">
            {{-- Company Login --}}
            <a href="{{ url('/company/login') }}" class="btn btn-company">
                <svg class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
                <span class="btn-label-ar">تسجيل دخول الشركات</span>
                <span class="btn-label-en">Company Login</span>
            </a>

            {{-- Employee Login --}}
            <a href="{{ url('/employee/login') }}" class="btn btn-employee">
                <svg class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span class="btn-label-ar">تسجيل دخول الموظفين</span>
                <span class="btn-label-en">Employee Login</span>
            </a>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'Organize') }}. جميع الحقوق محفوظة.
        </div>
    </div>
</body>
</html>
