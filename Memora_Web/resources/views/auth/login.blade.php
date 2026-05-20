<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - The Archive</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:300,400,500,600,700&display=swap" rel="stylesheet" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              lime: '#84CC16',
              dark: '#191A23',
              light: '#F3F3F3',
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
        }
        
        .auth-card {
            background-color: #ffffff;
            border: 2px solid #191A23;
            box-shadow: 0 8px 0 0 #191A23;
            border-radius: 40px;
        }

        .form-input {
            background-color: #F3F3F3;
            border: 2px solid transparent;
            color: #191A23;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            background-color: #ffffff;
            border-color: #191A23;
            outline: none;
            box-shadow: 0 4px 0 0 #84CC16;
            transform: translateY(-2px);
        }
        
        .form-input::placeholder {
            color: #9CA3AF;
        }

        .btn-primary {
            background-color: #191A23;
            color: #ffffff;
            border: 2px solid #191A23;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #84CC16;
            color: #191A23;
            transform: translateY(-2px);
            box-shadow: 0 4px 0 0 #191A23;
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
    </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center relative p-6 selection:bg-lime selection:text-white">
    
    <div class="bg-pattern"></div>

    <a href="{{ url('/') }}" class="absolute top-8 left-8 flex items-center gap-2 text-dark font-bold hover:text-lime transition-colors z-20 bg-white px-4 py-2 border-2 border-dark rounded-xl shadow-[0_3px_0_0_#191A23]">
        <i class='bx bx-left-arrow-alt text-xl'></i>
        <span>Kembali</span>
    </a>

    <div class="w-full max-w-md auth-card p-10 z-10 relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-lime rounded-full opacity-20 blur-2xl"></div>

        <div class="text-center mb-8 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-lime flex items-center justify-center text-dark text-3xl font-bold border-2 border-dark shadow-[0_4px_0_0_#191A23] mx-auto mb-6 rotate-3">
                <i class='bx bx-archive-in'></i>
            </div>
            <h1 class="text-3xl font-bold tracking-tight mb-2">Selamat Datang</h1>
            <p class="text-gray-500 font-medium">Masuk untuk melanjutkan ke The Archive</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border-2 border-red-200 text-red-600 font-medium text-sm shadow-sm">
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
                <label for="email" class="block text-sm font-bold text-dark mb-2">Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <i class='bx bx-envelope text-xl'></i>
                    </div>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@email.com" class="form-input w-full pl-11 pr-4 py-3.5 rounded-xl font-medium">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="password" class="block text-sm font-bold text-dark">Kata Sandi</label>
                    <a href="#" class="text-sm font-bold text-gray-500 hover:text-lime transition-colors">Lupa sandi?</a>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <i class='bx bx-lock-alt text-xl'></i>
                    </div>
                    <input id="password" type="password" name="password" required placeholder="••••••••" class="form-input w-full pl-11 pr-12 py-3.5 rounded-xl font-medium">
                    <button type="button" onclick="togglePassword('password', 'eye-icon')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-dark transition-colors">
                        <i id="eye-icon" class='bx bx-hide text-xl'></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center mt-2">
                <input id="remember_me" type="checkbox" class="w-5 h-5 rounded border-2 border-dark text-lime focus:ring-lime focus:ring-offset-2">
                <label for="remember_me" class="ml-3 block font-medium text-gray-600">Ingat Saya</label>
            </div>

            <button type="submit" class="btn-primary w-full py-4 rounded-xl font-bold text-lg mt-4 flex items-center justify-center gap-2">
                <span>Masuk Sekarang</span>
                <i class='bx bx-right-arrow-alt text-xl'></i>
            </button>
        </form>

        <p class="text-center font-medium text-gray-500 mt-8 relative z-10">
            Belum punya akun? 
            <a href="{{ route('register') }}" class="text-dark font-bold underline decoration-2 decoration-lime underline-offset-4 hover:text-lime transition-colors">Daftar di sini</a>
        </p>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bx-hide');
                icon.classList.add('bx-show');
                icon.classList.add('text-lime');
            } else {
                input.type = 'password';
                icon.classList.remove('bx-show');
                icon.classList.remove('text-lime');
                icon.classList.add('bx-hide');
            }
        }
    </script>
</body>
</html>
