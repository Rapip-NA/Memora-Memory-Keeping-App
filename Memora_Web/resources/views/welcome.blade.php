<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Archive - memora</title>
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

    <!-- Tailwind CDN -->
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
            overflow-x: hidden;
            transition: background-color 0.3s, color 0.3s;
        }

        .service-card {
            border: 2px solid #191A23;
            box-shadow: 0 8px 0 0 #191A23;
            border-radius: 40px;
            transition: all 0.3s ease;
        }
        
        .dark .service-card {
            border: 2px solid #ffffff;
            box-shadow: 0 8px 0 0 #EA580C;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 0 0 #191A23;
        }
        
        .dark .service-card:hover {
            box-shadow: 0 12px 0 0 #EA580C;
        }

        /* Float Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        .animate-float {
            animation: float 5s ease-in-out infinite;
        }
        
        /* Neobrutalism UI Classes */
        .neo-btn {
            border: 2px solid #191A23;
            box-shadow: 0 6px 0 0 #191A23;
            transition: all 0.2s ease;
        }
        .neo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 0 0 #191A23;
        }
        .neo-btn:active {
            transform: translateY(4px);
            box-shadow: 0 0 0 0 #191A23;
        }
        
        .dark .neo-btn {
            border: 2px solid #ffffff;
            box-shadow: 0 6px 0 0 #EA580C;
        }
        .dark .neo-btn:hover {
            box-shadow: 0 8px 0 0 #EA580C;
        }
        .dark .neo-btn:active {
            box-shadow: 0 0 0 0 #EA580C;
        }

        .neo-box {
            border: 2px solid #191A23;
            box-shadow: 0 8px 0 0 #191A23;
        }
        
        .dark .neo-box {
            border: 2px solid #ffffff;
            box-shadow: 0 8px 0 0 #EA580C;
        }
    </style>
</head>
<body class="antialiased bg-white text-dark dark:bg-darkBg dark:text-white selection:bg-lime dark:selection:bg-orange selection:text-white">

    <!-- Navigation -->
    <nav class="w-full bg-white dark:bg-darkBg z-50 pt-8 pb-4 sticky top-0 border-b border-gray-100 dark:border-gray-800 transition-colors">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class='bx bx-archive-in text-3xl text-lime dark:text-orange'></i>
                <span class="text-2xl font-bold tracking-tight text-dark dark:text-white">The Archive</span>
            </div>
            <div class="hidden lg:flex items-center gap-8">
                <a href="#hero" class="text-base font-medium text-dark dark:text-gray-300 hover:text-lime dark:hover:text-orange transition-colors">Beranda</a>
                <a href="#fitur" class="text-base font-medium text-dark dark:text-gray-300 hover:text-lime dark:hover:text-orange transition-colors">Fitur Utama</a>
                <a href="#keunggulan" class="text-base font-medium text-dark dark:text-gray-300 hover:text-lime dark:hover:text-orange transition-colors">Keunggulan</a>
                
                <button onclick="toggleTheme()" class="text-2xl text-dark dark:text-white hover:text-lime dark:hover:text-orange transition-colors">
                    <i class='bx bx-moon dark:hidden'></i>
                    <i class='bx bx-sun hidden dark:block'></i>
                </button>

                @auth
                    <a href="{{ route('desktop.feed') }}" class="neo-btn rounded-xl px-6 py-2.5 font-bold bg-white dark:bg-darkBg text-dark dark:text-white">Dashboard App</a>
                @else
                    <a href="{{ route('login') }}" class="neo-btn rounded-xl px-6 py-2.5 font-bold bg-white dark:bg-darkBg text-dark dark:text-white">Masuk / Daftar</a>
                @endauth
            </div>
            <!-- Mobile Menu Button -->
            <div class="lg:hidden flex items-center gap-4">
                <button onclick="toggleTheme()" class="text-2xl text-dark dark:text-white">
                    <i class='bx bx-moon dark:hidden'></i>
                    <i class='bx bx-sun hidden dark:block'></i>
                </button>
                <button class="text-3xl text-dark dark:text-white">
                    <i class='bx bx-menu'></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <main id="hero" class="max-w-7xl mx-auto px-6 pt-16 pb-24">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            
            <!-- Left Content -->
            <div class="flex flex-col gap-8">
                <h1 class="text-5xl lg:text-[4rem] font-bold leading-tight tracking-tight text-dark dark:text-white">
                    Abadikan Momen,<br>
                    Rawat Kenangan<br>
                    Selamanya
                </h1>
                
                <p class="text-lg text-gray-600 dark:text-gray-400 max-w-md leading-relaxed font-medium">
                    The Archive adalah platform sosial eksklusif untuk komunitas Anda. Bagikan cerita, simpan foto resolusi tinggi, rencanakan acara, dan tetap terhubung tanpa batas ruang dan waktu.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 mt-2">
                    @auth
                        <a href="{{ route('desktop.feed') }}" class="neo-btn bg-dark dark:bg-white text-white dark:text-dark text-center px-8 py-4 rounded-xl text-lg font-bold">
                            Masuk ke Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="neo-btn bg-dark dark:bg-white text-white dark:text-dark text-center px-8 py-4 rounded-xl text-lg font-bold">
                            Buat Akun Gratis
                        </a>
                    @endauth
                    <a href="#fitur" class="neo-btn bg-white dark:bg-darkBg text-dark dark:text-white text-center px-8 py-4 rounded-xl text-lg font-bold">
                        Jelajahi Fitur
                    </a>
                </div>
            </div>

            <!-- Right Visuals -->
            <div class="relative h-[400px] lg:h-[500px] flex items-center justify-center">
                <div class="relative w-full h-full max-w-md mx-auto animate-float">
                    <div class="absolute top-1/4 left-1/4 w-56 h-56 border-2 border-dark dark:border-white rounded-[40px] rotate-12"></div>
                    <div class="absolute top-1/3 left-1/3 w-64 h-64 border-2 border-lime dark:border-orange rounded-[40px] border-dashed -rotate-6"></div>
                    
                    <!-- Main Icon -->
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-9xl text-dark dark:text-white">
                        <i class='bx bxs-widget'></i>
                    </div>

                    <!-- Floating Feature Icons -->
                    <div class="absolute top-[10%] right-[30%] w-14 h-14 bg-dark dark:bg-white rounded-2xl flex items-center justify-center text-white dark:text-dark shadow-lg">
                        <i class='bx bxs-photo-album text-2xl'></i>
                    </div>
                    <div class="absolute top-[15%] right-[5%] w-14 h-14 bg-lime dark:bg-orange rounded-full flex items-center justify-center text-white dark:text-dark shadow-lg">
                        <i class='bx bxs-calendar-event text-2xl'></i>
                    </div>
                    <div class="absolute top-[35%] right-[5%] w-12 h-12 bg-dark dark:bg-white rounded-full flex items-center justify-center text-white dark:text-dark shadow-lg">
                        <i class='bx bx-poll text-xl'></i>
                    </div>
                    <div class="absolute bottom-[30%] right-[15%] w-16 h-16 bg-lime dark:bg-orange rounded-2xl flex items-center justify-center text-white dark:text-dark shadow-lg rotate-12">
                        <i class='bx bxs-message-square-dots text-3xl'></i>
                    </div>
                    
                    <!-- Star accents -->
                    <div class="absolute bottom-[20%] left-[10%] w-10 h-10 bg-dark dark:bg-white text-white dark:text-dark flex items-center justify-center animate-spin" style="animation-duration: 10s; clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);"></div>
                </div>
            </div>
            
        </div>
    </main>

    <!-- App Features Section -->
    <section id="fitur" class="max-w-7xl mx-auto px-6 py-20 border-t border-gray-100 dark:border-gray-800">
        <div class="flex flex-col lg:flex-row items-start lg:items-end justify-between gap-8 mb-16">
            <div class="max-w-2xl">
                <span class="bg-lime dark:bg-orange text-white dark:text-dark px-3 py-1 rounded-md font-bold text-sm uppercase tracking-wider mb-4 inline-block">Ekosistem Aplikasi</span>
                <h2 class="text-4xl md:text-5xl font-bold leading-relaxed mt-2 text-dark dark:text-white pb-2">
                    Semua yang Anda butuhkan dalam <span class="bg-dark dark:bg-white text-white dark:text-dark px-2 py-0.5 rounded-lg inline-block mt-1">satu platform.</span>
                </h2>
            </div>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-md font-medium">
                The Archive tidak hanya sekadar sosial media. Ini adalah arsip digital yang hidup dan interaktif.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-10">
            <!-- Card 1 -->
            <div class="service-card bg-light dark:bg-[#111111] p-10 lg:p-12 flex flex-col justify-between relative overflow-hidden h-[340px] group">
                <div class="z-10">
                    <span class="bg-lime dark:bg-orange text-white dark:text-dark px-3 py-1.5 rounded-lg text-2xl font-bold inline-block mb-1">Galeri Cloud &</span><br>
                    <span class="bg-lime dark:bg-orange text-white dark:text-dark px-3 py-1.5 rounded-lg text-2xl font-bold inline-block mb-4">Penyimpanan Media</span>
                    <p class="text-dark dark:text-gray-300 font-medium leading-relaxed max-w-sm mt-4">Unggah foto momen berharga tanpa batasan kualitas. Terintegrasi dengan Cloudflare R2 untuk menjamin data Anda tidak akan pernah hilang.</p>
                </div>
                <div class="absolute -bottom-6 -right-6 text-[180px] text-gray-200 dark:text-gray-800 group-hover:scale-110 group-hover:-rotate-12 transition-transform duration-500">
                    <i class='bx bx-cloud-upload'></i>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="service-card bg-dark dark:bg-white p-10 lg:p-12 flex flex-col justify-between relative overflow-hidden h-[340px] group">
                <div class="z-10">
                    <span class="bg-white dark:bg-darkBg text-dark dark:text-white px-3 py-1.5 rounded-lg text-2xl font-bold inline-block mb-1">Sosial Feed &</span><br>
                    <span class="bg-white dark:bg-darkBg text-dark dark:text-white px-3 py-1.5 rounded-lg text-2xl font-bold inline-block mb-4">Interaksi Aktif</span>
                    <p class="text-gray-300 dark:text-gray-600 font-medium leading-relaxed max-w-sm mt-4">Bagikan cerita harian, buat polling interaktif, dan berikan komentar atau *like* secara real-time pada postingan pengguna lain.</p>
                </div>
                <div class="absolute -bottom-6 -right-6 text-[180px] text-gray-800 dark:text-gray-200 group-hover:scale-110 group-hover:rotate-12 transition-transform duration-500">
                    <i class='bx bx-news'></i>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="service-card bg-dark dark:bg-white p-10 lg:p-12 flex flex-col justify-between relative overflow-hidden h-[340px] group">
                <div class="z-10">
                    <span class="bg-white dark:bg-darkBg text-dark dark:text-white px-3 py-1.5 rounded-lg text-2xl font-bold inline-block mb-1">Manajemen Acara &</span><br>
                    <span class="bg-white dark:bg-darkBg text-dark dark:text-white px-3 py-1.5 rounded-lg text-2xl font-bold inline-block mb-4">RSVP Sistem</span>
                    <p class="text-gray-300 dark:text-gray-600 font-medium leading-relaxed max-w-sm mt-4">Rencanakan reuni atau pertemuan dengan mudah. Fitur kehadiran (RSVP) bawaan membantu melacak peserta acara secara otomatis.</p>
                </div>
                <div class="absolute -bottom-6 -right-6 text-[180px] text-gray-800 dark:text-gray-200 group-hover:scale-110 group-hover:-rotate-12 transition-transform duration-500">
                    <i class='bx bx-calendar-check'></i>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="service-card bg-light dark:bg-[#111111] p-10 lg:p-12 flex flex-col justify-between relative overflow-hidden h-[340px] group">
                <div class="z-10">
                    <span class="bg-lime dark:bg-orange text-white dark:text-dark px-3 py-1.5 rounded-lg text-2xl font-bold inline-block mb-1">Pesan Instan &</span><br>
                    <span class="bg-lime dark:bg-orange text-white dark:text-dark px-3 py-1.5 rounded-lg text-2xl font-bold inline-block mb-4">Notifikasi Cerdas</span>
                    <p class="text-dark dark:text-gray-300 font-medium leading-relaxed max-w-sm mt-4">Berkomunikasi secara privat (Chat Panel), dan dapatkan pemberitahuan (Notification Badge) seketika ada interaksi baru di akun Anda.</p>
                </div>
                <div class="absolute -bottom-6 -right-6 text-[180px] text-gray-200 dark:text-gray-800 group-hover:scale-110 group-hover:rotate-12 transition-transform duration-500">
                    <i class='bx bx-message-rounded-dots'></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Advantages / Keunggulan Section -->
    <section id="keunggulan" class="max-w-7xl mx-auto px-6 py-20">
        <div class="flex flex-col lg:flex-row items-center gap-6 mb-12">
            <h2 class="text-4xl md:text-5xl font-bold bg-lime dark:bg-orange text-white dark:text-dark px-4 py-2 rounded-xl inline-block">Kelebihan Aplikasi</h2>
            <p class="text-lg text-gray-600 dark:text-gray-400 font-medium mt-4 lg:mt-0">Mengapa komunitas memilih menggunakan sistem kami.</p>
        </div>

        <div class="neo-box bg-dark dark:bg-[#111111] rounded-[40px] p-10 lg:p-16 text-white grid grid-cols-1 md:grid-cols-3 gap-10 relative overflow-hidden">
            <!-- Poin 1 -->
            <div class="flex flex-col gap-4 relative z-10">
                <div class="w-16 h-16 bg-lime dark:bg-orange rounded-2xl flex items-center justify-center text-dark text-3xl mb-2">
                    <i class='bx bx-shield-quarter'></i>
                </div>
                <h3 class="text-2xl font-bold text-white">Privasi & Keamanan</h3>
                <p class="text-gray-400 dark:text-gray-300 font-medium leading-relaxed">
                    Kami melindungi data pengguna dengan sistem keamanan tinggi. Postingan, foto, dan obrolan grup hanya dapat diakses oleh mereka yang memiliki autentikasi akun resmi.
                </p>
            </div>

            <!-- Poin 2 -->
            <div class="flex flex-col gap-4 relative z-10 md:border-l md:border-gray-700 md:pl-10">
                <div class="w-16 h-16 bg-white dark:bg-white rounded-2xl flex items-center justify-center text-dark text-3xl mb-2">
                    <i class='bx bx-rocket'></i>
                </div>
                <h3 class="text-2xl font-bold text-white">Performa Super Cepat</h3>
                <p class="text-gray-400 dark:text-gray-300 font-medium leading-relaxed">
                    Dibangun dengan framework Laravel terbaru dan pemrosesan *background job* (seperti kompresi otomatis foto R2), menjamin performa ngebut tanpa waktu tunggu yang lama.
                </p>
            </div>

            <!-- Poin 3 -->
            <div class="flex flex-col gap-4 relative z-10 md:border-l md:border-gray-700 md:pl-10">
                <div class="w-16 h-16 bg-lime dark:bg-orange rounded-2xl flex items-center justify-center text-dark text-3xl mb-2">
                    <i class='bx bx-adjust'></i>
                </div>
                <h3 class="text-2xl font-bold text-white">Mode Tema Adaptif</h3>
                <p class="text-gray-400 dark:text-gray-300 font-medium leading-relaxed">
                    Sistem dirancang mendukung fungsionalitas UI yang mewah secara penuh. Bebas berganti antara estetika Light Mode (Hijau Putih) atau Dark Mode (Oranye Hitam) kapan saja.
                </p>
            </div>
            
            <!-- Abstract Deco -->
            <div class="absolute -top-32 -right-32 w-96 h-96 bg-white opacity-5 rounded-full blur-3xl"></div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="max-w-7xl mx-auto px-6 py-10 mb-20">
        <div class="neo-box bg-white dark:bg-[#111111] rounded-[40px] p-12 lg:p-16 flex flex-col lg:flex-row items-center justify-between relative overflow-hidden">
            <div class="flex flex-col gap-6 max-w-xl z-10 relative">
                <h2 class="text-4xl font-bold text-dark dark:text-white leading-tight">Siap Membangun Arsip Komunitas Anda?</h2>
                <p class="text-lg text-gray-700 dark:text-gray-300 font-medium">
                    Gabung hari ini dan nikmati platform interaktif bebas iklan yang dirancang khusus untuk menyimpan setiap kenangan berharga dengan sempurna.
                </p>
                <div class="flex gap-4 mt-4">
                    <a href="{{ route('register') }}" class="neo-btn bg-lime dark:bg-orange text-dark px-8 py-4 rounded-xl text-lg font-bold transition-all">
                        Buat Akun Sekarang
                    </a>
                </div>
            </div>

            <!-- Graphic element -->
            <div class="hidden lg:block absolute right-16 top-1/2 transform -translate-y-1/2">
                <div class="relative w-72 h-72 animate-float">
                    <!-- Dashboard Mockup Abstract -->
                    <div class="absolute inset-0 bg-white dark:bg-darkBg rounded-3xl shadow-2xl border-2 border-dark dark:border-white overflow-hidden flex flex-col">
                        <div class="h-12 border-b-2 border-dark dark:border-white flex items-center px-4 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        </div>
                        <div class="flex-1 flex p-4 gap-4">
                            <div class="w-1/3 bg-gray-100 dark:bg-gray-800 rounded-xl"></div>
                            <div class="w-2/3 flex flex-col gap-2">
                                <div class="h-1/2 bg-gray-100 dark:bg-gray-800 rounded-xl w-full"></div>
                                <div class="h-1/2 bg-lime/20 dark:bg-orange/20 rounded-xl w-full"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Decor -->
                    <div class="absolute -bottom-6 -left-6 w-16 h-16 bg-dark dark:bg-white rounded-full flex items-center justify-center text-white dark:text-dark text-2xl border-2 border-dark dark:border-white shadow-[0_4px_0_0_#191A23] dark:shadow-[0_4px_0_0_#EA580C]">
                        <i class='bx bxs-heart'></i>
                    </div>
                    <div class="absolute -top-6 -right-6 w-16 h-16 bg-lime dark:bg-orange rounded-full flex items-center justify-center text-dark text-2xl border-2 border-dark dark:border-white shadow-[0_4px_0_0_#191A23] dark:shadow-[0_4px_0_0_#EA580C]">
                        <i class='bx bxs-star'></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="mt-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="bg-dark dark:bg-[#111111] border-t-2 border-l-2 border-r-2 border-dark dark:border-white rounded-t-[40px] p-12 lg:p-16 text-white flex flex-col gap-12 relative overflow-hidden">
                <div class="flex flex-col md:flex-row justify-between items-center gap-8 relative z-10">
                    <div class="flex items-center gap-2 bg-white dark:bg-darkBg text-dark dark:text-white px-4 py-2 rounded-xl border-2 border-dark dark:border-white">
                        <i class='bx bx-archive-in text-2xl text-lime dark:text-orange'></i>
                        <span class="text-xl font-bold tracking-tight">The Archive</span>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-8 text-base font-medium">
                        <a href="#hero" class="hover:text-lime dark:hover:text-orange transition-colors">Beranda</a>
                        <a href="#fitur" class="hover:text-lime dark:hover:text-orange transition-colors">Fitur Aplikasi</a>
                        <a href="#keunggulan" class="hover:text-lime dark:hover:text-orange transition-colors">Kelebihan</a>
                        <a href="{{ route('login') }}" class="hover:text-lime dark:hover:text-orange transition-colors">Masuk</a>
                    </div>
                    <div class="flex gap-4">
                        <a href="#" class="w-12 h-12 bg-white dark:bg-white rounded-full flex items-center justify-center text-dark hover:bg-lime dark:hover:bg-orange transition-all">
                            <i class='bx bxl-instagram text-2xl'></i>
                        </a>
                        <a href="#" class="w-12 h-12 bg-white dark:bg-white rounded-full flex items-center justify-center text-dark hover:bg-lime dark:hover:bg-orange transition-all">
                            <i class='bx bxl-twitter text-2xl'></i>
                        </a>
                    </div>
                </div>

                <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-500 font-medium relative z-10">
                    <p>&copy; {{ date('Y') }} The Archive. All Rights Reserved. Built with Laravel.</p>
                    <div class="flex gap-6">
                        <a href="#" class="hover:text-white transition-colors">Kebijakan Privasi</a>
                        <a href="#" class="hover:text-white transition-colors">Syarat & Ketentuan</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Theme Toggle Script -->
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                html.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
    </script>
</body>
</html>
