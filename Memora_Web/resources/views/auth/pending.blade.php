<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masa Validasi - The Archive</title>
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

        .btn-logout {
            background-color: #F3F3F3;
            color: #FF4757;
            border: 2px solid #191A23;
            box-shadow: 0 4px 0 0 #191A23;
            transition: all 0.3s ease;
        }
        .dark .btn-logout {
            background-color: #191A23;
            color: #FF4757;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 0 0 #EA580C;
        }

        .btn-logout:hover {
            background-color: #FF4757;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 0 #191A23;
        }
        .dark .btn-logout:hover {
            background-color: #FF4757;
            color: #ffffff;
            box-shadow: 0 6px 0 0 #EA580C;
        }
        
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

        .status-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .5; transform: scale(1.1); }
        }
    </style>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/Memora 1.png') }}">
</head>
<body class="antialiased min-h-screen flex items-center justify-center relative p-6 selection:bg-lime dark:selection:bg-orange selection:text-white">
    
    <div class="bg-pattern"></div>

    <div class="w-full max-w-lg auth-card p-10 z-10 relative overflow-hidden text-center">
        <!-- Decoration -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-lime dark:bg-orange rounded-full opacity-20 blur-2xl transition-colors"></div>

        <div class="relative z-10">
            <!-- Icon with pulsing dot -->
            <div class="relative w-20 h-20 bg-lime/10 dark:bg-orange/10 border-2 border-dashed border-lime dark:border-orange rounded-3xl flex items-center justify-center text-4xl text-lime dark:text-orange mx-auto mb-8 transition-all">
                <i class='bx bx-time-five animate-spin' style="animation-duration: 8s;"></i>
                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-[#FFAB00] border-2 border-white dark:border-darkBg rounded-full status-dot"></div>
            </div>

            <h1 class="text-3xl font-bold tracking-tight mb-4 dark:text-white">Akun Sedang Divalidasi</h1>
            
            <p class="text-gray-600 dark:text-gray-400 font-medium leading-relaxed mb-8">
                Halo, <span class="text-dark dark:text-white font-bold">{{ auth()->user()->name }}</span>! Terima kasih telah mendaftar di <span class="text-dark dark:text-white font-bold">The Archive</span>. Pendaftaran Anda saat ini sedang ditinjau oleh administrator angkatan untuk menjaga privasi dan kenyamanan komunitas alumni kita.
            </p>

            <!-- Interactive visual timeline -->
            <div class="bg-light dark:bg-dark p-6 rounded-2xl border-2 border-dark dark:border-white/10 mb-8 text-left space-y-4 shadow-[0_4px_0_0_rgba(0,0,0,0.05)]">
                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center font-bold text-sm">
                        <i class='bx bx-check'></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm dark:text-white">Registrasi Berhasil</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Data Anda telah disimpan di sistem.</p>
                    </div>
                </div>
                <div class="w-0.5 h-6 bg-green-500 ml-4"></div>
                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full bg-[#FFAB00] text-white flex items-center justify-center font-bold text-sm status-dot">
                        <i class='bx bx-loader-alt animate-spin'></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-[#FFAB00]">Validasi Administrator</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Admin sedang mencocokkan data alumni Anda.</p>
                    </div>
                </div>
                <div class="w-0.5 h-6 bg-gray-300 dark:bg-gray-700 ml-4"></div>
                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-800 text-gray-400 dark:text-gray-600 flex items-center justify-center font-bold text-sm">
                        <i class='bx bx-check-shield'></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-gray-400 dark:text-gray-600">Akses Platform Terbuka</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Anda dapat menikmati semua fitur platform.</p>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="btn-logout w-full py-3.5 rounded-xl font-bold flex items-center justify-center gap-2">
                        <i class='bx bx-log-out'></i>
                        <span>Keluar dari Akun</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
