<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - The Archive</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700&display=swap" rel="stylesheet" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- Theme check before render -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            colors: {
              lime: '#84CC16',
              dark: '#191A23',
              light: '#F3F3F3',
              orange: '#EA580C',
              darkBg: '#050505',
            },
            fontFamily: {
              sans: ['Space Grotesk', 'sans-serif'],
            }
          }
        }
      }
    </script>
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            background-color: #F3F3F3;
            color: #191A23;
            transition: background-color 0.3s, color 0.3s;
        }
        .dark body {
            background-color: #050505;
            color: #ffffff;
        }
        
        .auth-card {
            background-color: #ffffff;
            border: 2px solid #191A23;
            box-shadow: 0 8px 0 0 #191A23;
            border-radius: 40px;
            transition: all 0.3s ease;
        }
        .dark .auth-card {
            background-color: #111111;
            border: 2px solid #ffffff;
            box-shadow: 0 8px 0 0 #EA580C;
        }

        .form-input {
            background-color: #F3F3F3;
            border: 2px solid transparent;
            color: #191A23;
            transition: all 0.3s ease;
        }
        .dark .form-input {
            background-color: #191A23;
            color: #ffffff;
        }

        .form-input:focus {
            background-color: #ffffff;
            border-color: #191A23;
            outline: none;
            box-shadow: 0 4px 0 0 #84CC16;
            transform: translateY(-2px);
        }
        .dark .form-input:focus {
            background-color: #050505;
            border-color: #ffffff;
            box-shadow: 0 4px 0 0 #EA580C;
        }
        
        .form-input::placeholder {
            color: #9CA3AF;
        }
        .dark .form-input::placeholder {
            color: #6B7280;
        }

        .btn-primary {
            background-color: #191A23;
            color: #ffffff;
            border: 2px solid #191A23;
            transition: all 0.3s ease;
        }
        .dark .btn-primary {
            background-color: #ffffff;
            color: #191A23;
            border: 2px solid #ffffff;
        }

        .btn-primary:hover {
            background-color: #84CC16;
            color: #191A23;
            transform: translateY(-2px);
            box-shadow: 0 4px 0 0 #191A23;
        }
        .dark .btn-primary:hover {
            background-color: #EA580C;
            color: #ffffff;
            box-shadow: 0 4px 0 0 #EA580C;
        }
        
        /* Decorative Background */
        .bg-pattern {
            background-image: radial-gradient(#191A23 1px, transparent 1px);
            background-size: 24px 24px;
            opacity: 0.05;
            position: fixed;
            inset: 0;
            z-index: -1;
        }
        .dark .bg-pattern {
            background-image: radial-gradient(#ffffff 1px, transparent 1px);
            opacity: 0.05;
        }
    </style>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/Memora 1.png') }}">
</head>
<body class="antialiased min-h-screen flex items-center justify-center relative p-6 selection:bg-lime dark:selection:bg-orange selection:text-white">
    
    <div class="bg-pattern"></div>

    <a href="{{ url('/') }}" class="absolute top-8 left-8 flex items-center gap-2 text-dark dark:text-white font-bold hover:text-lime dark:hover:text-orange transition-colors z-20 bg-white dark:bg-[#111111] px-4 py-2 border-2 border-dark dark:border-white rounded-xl shadow-[0_3px_0_0_#191A23] dark:shadow-[0_3px_0_0_#EA580C]">
        <i class='bx bx-left-arrow-alt text-xl'></i>
        <span>Kembali</span>
    </a>

    <div class="w-full max-w-md auth-card p-10 z-10 relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-lime dark:bg-orange rounded-full opacity-20 blur-2xl transition-colors"></div>

        <div class="text-center mb-8 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-lime dark:bg-orange flex items-center justify-center text-dark dark:text-white text-3xl font-bold border-2 border-dark dark:border-white shadow-[0_4px_0_0_#191A23] dark:shadow-[0_4px_0_0_#EA580C] mx-auto mb-6 rotate-3 transition-colors">
                <i class='bx bx-archive-in'></i>
            </div>
            <h1 class="text-3xl font-bold tracking-tight mb-2 dark:text-white">Selamat Datang</h1>
            <p class="text-gray-500 dark:text-gray-400 font-medium">Masuk untuk melanjutkan ke The Archive</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border-2 border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 font-medium text-sm shadow-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5 relative z-10">
            @csrf
            <div>
                <label for="email" class="block text-sm font-bold text-dark dark:text-white mb-2">Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                        <i class='bx bx-envelope text-xl'></i>
                    </div>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@email.com" class="form-input w-full pl-11 pr-4 py-3.5 rounded-xl font-medium">
                </div>
            </div>

            <div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                        <i class='bx bx-lock-alt text-xl'></i>
                    </div>
                    <input id="password" type="password" name="password" required placeholder="••••••••" class="form-input w-full pl-11 pr-12 py-3.5 rounded-xl font-medium">
                    <button type="button" onclick="togglePassword('password', 'eye-icon')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 dark:text-gray-500 hover:text-dark dark:hover:text-white transition-colors">
                        <i id="eye-icon" class='bx bx-hide text-xl'></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center mt-2">
                <input id="remember_me" type="checkbox" class="w-5 h-5 rounded border-2 border-dark dark:border-white text-lime dark:text-orange focus:ring-lime dark:focus:ring-orange bg-white dark:bg-darkBg">
                <label for="remember_me" class="ml-3 block font-medium text-gray-600 dark:text-gray-300">Ingat Saya</label>
            </div>

            <button type="submit" class="btn-primary w-full py-4 rounded-xl font-bold text-lg mt-4 flex items-center justify-center gap-2">
                <span>Masuk Sekarang</span>
                <i class='bx bx-right-arrow-alt text-xl'></i>
            </button>
        </form>

        <p class="text-center font-medium text-gray-500 dark:text-gray-400 mt-8 relative z-10">
            Belum punya akun? 
            <a href="{{ route('register') }}" class="text-dark dark:text-white font-bold underline decoration-2 decoration-lime dark:decoration-orange underline-offset-4 hover:text-lime dark:hover:text-orange transition-colors">Daftar di sini</a>
        </p>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            const isDark = document.documentElement.classList.contains('dark');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bx-hide');
                icon.classList.add('bx-show');
                if (isDark) {
                    icon.classList.add('text-orange');
                } else {
                    icon.classList.add('text-lime');
                }
            } else {
                input.type = 'password';
                icon.classList.remove('bx-show');
                icon.classList.remove('text-lime', 'text-orange');
                icon.classList.add('bx-hide');
            }
        }
    </script>
</body>
</html>
