<!DOCTYPE html><html lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Masuk Portal Manajemen - {{ config('app.name', 'TokoKu') }} Back-Office &amp; ERP Operasional</title>
<!-- Google Fonts: Plus Jakarta Sans -->
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
<!-- Tailwind CSS CDN with forms and container queries -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<!-- Tailwind Configuration -->
<script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          },
          colors: {
            brand: {
              50: '#fdf4f6',
              100: '#fbe8ec',
              200: '#f8d4dc',
              300: '#f1b1c0',
              400: '#e5829c',
              500: '#be5b72', // Primary Brand Rose Tone
              600: '#a8475e',
              700: '#8c354a',
              800: '#752d3f',
              900: '#642938',
            },
            warm: {
              50: '#fbf9f7',
              100: '#f5f1eb',
              200: '#eae2d6',
              300: '#dacdbb',
              400: '#c5b098',
            }
          },
          boxShadow: {
            'glow': '0 12px 30px -8px rgba(190, 91, 114, 0.28)',
            'card-soft': '0 20px 40px -15px rgba(27, 24, 25, 0.07)',
          }
        }
      }
    }
  </script>
<!-- BEGIN: Custom Micro-Styles -->
<style data-purpose="custom-styling">
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Ambient noise & soft organic blur effect */
    .bg-gradient-mesh {
      background: radial-gradient(circle at 18% 22%, rgba(248, 212, 220, 0.65) 0%, transparent 48%),
                  radial-gradient(circle at 82% 78%, rgba(254, 237, 222, 0.75) 0%, transparent 52%),
                  linear-gradient(145deg, #faf7f5 0%, #f4eee9 100%);
    }

    /* Smooth focus rings matching TokoKu brand */
    .input-brand-focus:focus {
      outline: none;
      border-color: #be5b72;
      box-shadow: 0 0 0 3px rgba(190, 91, 114, 0.16);
    }
  </style>
<!-- END: Custom Micro-Styles -->
</head>
<body class="min-h-screen bg-stone-50 text-stone-800 antialiased selection:bg-brand-100 selection:text-brand-800">
<!-- BEGIN: MainLayout -->
<main class="min-h-screen w-full flex flex-col lg:flex-row" data-purpose="auth-page-container">
<!-- BEGIN: LeftBrandShowcasePanel -->
<section class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-mesh p-12 xl:p-16 flex-col justify-between border-r border-stone-200/80" data-purpose="brand-showcase">
<!-- Decorative Backdrop Circles -->
<div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-brand-200/40 blur-3xl pointer-events-none"></div>
<div class="absolute bottom-10 right-0 w-[420px] h-[420px] rounded-full bg-amber-100/60 blur-3xl pointer-events-none"></div>
<!-- Top Showcase Header -->
<div class="relative z-10 flex items-center justify-between">
<div class="flex items-center gap-2.5">
<div class="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center text-white shadow-md shadow-brand-500/30">
<!-- Modern Store Front / ERP Building Icon -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
<path d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009 9.35c.664 0 1.282-.217 1.782-.587.5.37 1.118.587 1.782.587.664 0 1.282-.217 1.782-.587.5.37 1.118.587 1.782.587a3.001 3.001 0 003.75.615m-16.5 0l1.455-4.364A3 3 0 015.682 3h12.636a3 3 0 012.837 2.054L22.61 9.35" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<div>
<span class="text-2xl font-extrabold tracking-tight text-stone-900">Toko<span class="text-brand-500">Ku</span></span>
<span class="ml-2 text-xs font-semibold px-2.5 py-0.5 rounded-full bg-stone-900/5 text-stone-700 border border-stone-200">Internal Portal &amp; Back-Office</span>
</div>
</div>
</div>
<!-- Mid Showcase Hero Visual (Internal Operations & ERP Control) -->
<div class="relative z-10 my-auto py-10">
<!-- Floating Value Tag -->
<div class="inline-flex items-center gap-2 text-xs font-bold tracking-wide uppercase text-brand-700 bg-brand-100/90 px-3 py-1.5 rounded-full mb-4">
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
<path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
          SISTEM MANAJEMEN INTERNAL &amp; ERP OPERASIONAL
        </div>
<h1 class="text-3xl xl:text-4xl font-extrabold text-stone-900 tracking-tight leading-tight max-w-xl mb-4">
          Pusat Kendali Operasional, Inventori &amp; Manajemen Toko
        </h1>
<p class="text-stone-600 text-base max-w-lg leading-relaxed mb-8">
          Portal terpusat untuk branch admin, tim gudang, store manager, dan kantor pusat. Kelola mutasi stok, audit kasir harian, logistik antar-cabang, dan laporan konsolidasi internal.
        </p>
<!-- Interactive Simulated ERP & Back-Office Previews -->
<div class="relative max-w-lg" data-purpose="retail-analytics-preview-widget">
<!-- Main Stat Card (Internal Logistics & Branch Operations) -->
<div class="bg-white/95 backdrop-blur-xl border border-stone-200/80 rounded-2xl p-5 shadow-card-soft">
<div class="flex items-center justify-between border-b border-stone-100 pb-3 mb-4">
<div class="flex items-center gap-3">
<div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-sm">
<!-- Inventory / Layers Icon -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
<path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<div>
<p class="text-xs text-stone-500 font-medium">Logistik &amp; Mutasi Stok Real-Time</p>
<p class="text-lg font-bold text-stone-900">1.420 SKU Termonitor</p>
</div>
</div>
<span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-100/90 px-2.5 py-1 rounded-full">
<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Semua Node Sinkron
              </span>
</div>
<div class="grid grid-cols-2 gap-3 text-xs">
<div class="bg-stone-50/90 rounded-xl p-3 border border-stone-200/70">
<span class="text-stone-500 block mb-0.5 font-medium">Transfer Cabang</span>
<span class="text-sm font-bold text-stone-800">14 Permintaan Aktif</span>
</div>
<div class="bg-stone-50/90 rounded-xl p-3 border border-stone-200/70">
<span class="text-stone-500 block mb-0.5 font-medium">Rekonsiliasi Kasir</span>
<span class="text-sm font-bold text-emerald-600">8 / 8 Cabang Selesai</span>
</div>
</div>
</div>
<!-- Floating Notification Widget 1 (Warehouse & Branch Status) -->
<div class="absolute -top-7 -right-4 bg-white/95 backdrop-blur-md rounded-xl p-3 shadow-lg border border-stone-200/80 flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
<path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<div>
<p class="text-[11px] font-semibold text-stone-800">Multi-Cabang &amp; Gudang Terintegrasi</p>
<p class="text-[10px] text-stone-500">8 Cabang Aktif • Hub Jakarta &amp; SBY</p>
</div>
</div>
<!-- Floating Notification Widget 2 (Security & Role Level) -->
<div class="absolute -bottom-6 -left-4 bg-white/95 backdrop-blur-md rounded-xl p-3 shadow-lg border border-stone-200/80 flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center shrink-0">
<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
<path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<div>
<p class="text-[11px] font-semibold text-stone-800">Akses Level Terproteksi</p>
<p class="text-[10px] text-stone-500">Store Manager, Audit, &amp; Admin Gudang</p>
</div>
</div>
</div>
</div>
<!-- Bottom Merchant Proof & Internal Operations Info -->
<div class="relative z-10 pt-6 border-t border-stone-200/70 flex items-center justify-between text-xs text-stone-600">
<div class="flex items-center gap-3">
<!-- Staff / Division Badges -->
<div class="flex -space-x-2">
<span class="w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-[10px] ring-2 ring-white">HQ</span>
<span class="w-7 h-7 rounded-full bg-stone-700 text-white flex items-center justify-center font-bold text-[10px] ring-2 ring-white">WH</span>
<span class="w-7 h-7 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold text-[10px] ring-2 ring-white">SM</span>
</div>
<div>
<p class="font-bold text-stone-900 leading-none">Divisi Operasional &amp; Manajemen</p>
<p class="text-[11px] text-stone-500">Terhubung ke 8 gerai ritel &amp; 2 pusat distribusi</p>
</div>
</div>
<div class="flex items-center gap-1.5 font-semibold text-stone-700 bg-stone-100/90 px-3 py-1 rounded-full border border-stone-200">
<span class="w-2 h-2 rounded-full bg-brand-500"></span>
<span class="text-[11px]">ERP v4.2 Internal</span>
</div>
</div>
</section>
<!-- END: LeftBrandShowcasePanel -->
<!-- BEGIN: RightAuthFormPanel -->
<section class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10 xl:p-16 bg-white" data-purpose="login-form-wrapper">
<div class="w-full max-w-md mx-auto space-y-7">
<!-- Mobile Brand Logo Header (Visible on Mobile & Tablet only) -->
<div class="lg:hidden flex items-center justify-between pb-4 border-b border-stone-100">
<div class="flex items-center gap-2">
<div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center text-white font-bold text-sm shadow-sm">
              TK
            </div>
<span class="text-xl font-extrabold text-stone-900 tracking-tight">Toko<span class="text-brand-500">Ku</span></span>
</div>
<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-stone-100 text-stone-600">Back-Office ERP</span>
</div>
<!-- Form Intro Title & Subtitle -->
<header class="space-y-2">
<!-- Logo & Internal Badge for Desktop view -->
<div class="hidden lg:flex items-center gap-2.5 mb-2">
<span class="text-2xl font-extrabold tracking-tight text-stone-900">Toko<span class="text-brand-500">Ku</span></span>
<span class="text-[11px] font-semibold tracking-wide uppercase px-2 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-200/60">ERP &amp; Management</span>
</div>
<h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-stone-900">
            Masuk Portal Manajemen
          </h2>
<p class="text-sm text-stone-500 leading-relaxed">
            Masukkan kredensial akun staf/admin untuk mengakses back-office dan pusat kontrol operasional {{ config('app.name', 'TokoKu') }}.
          </p>
</header>

@if (session('status'))
    <div class="text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
        {{ session('status') }}
    </div>
@endif

<!-- BEGIN: LoginForm -->
<form action="{{ route('login') }}" class="space-y-4" data-purpose="credential-form" method="POST">
@csrf
<!-- Employee ID / Internal Email Input -->
<div class="space-y-1.5">
<label class="block text-xs font-semibold text-stone-700" for="email">
              ID Karyawan / Email Internal
            </label>
<div class="relative rounded-xl shadow-sm">
<div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<input autocomplete="username" autofocus class="input-brand-focus block w-full rounded-xl border border-stone-300/80 bg-stone-50/40 pl-10 pr-3.5 py-2.5 text-sm text-stone-900 placeholder:text-stone-400 transition" id="email" name="email" placeholder="staff@internal.tokoku.id" required="" type="text" value="{{ old('email') }}">
</div>
@error('email')
<p class="mt-1.5 text-xs font-medium text-brand-600">{{ $message }}</p>
@enderror
</div>
<!-- Password Input with Show/Hide toggle -->
<div class="space-y-1.5">
<div class="flex items-center justify-between">
<label class="block text-xs font-semibold text-stone-700" for="password">
                Kata Sandi
              </label>
<a class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition" href="{{ Route::has('password.request') ? route('password.request') : '#' }}">
                Lupa kata sandi?
              </a>
</div>
<div class="relative rounded-xl shadow-sm">
<div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
<path d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</div>
<input autocomplete="current-password" class="input-brand-focus block w-full rounded-xl border border-stone-300/80 bg-stone-50/40 pl-10 pr-10 py-2.5 text-sm text-stone-900 placeholder:text-stone-400 transition" id="password" name="password" placeholder="••••••••" required="" type="password">
<!-- Password reveal button toggle -->
<button aria-label="Tampilkan atau sembunyikan kata sandi" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-stone-400 hover:text-stone-600" id="togglePasswordBtn" type="button">
<svg class="h-4 w-4" fill="none" id="eyeIcon" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
<path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</button>
</div>
@error('password')
<p class="mt-1.5 text-xs font-medium text-brand-600">{{ $message }}</p>
@enderror
</div>
<!-- Remember Me & Help Links -->
<div class="flex items-center justify-between pt-1">
<label class="flex items-center gap-2 cursor-pointer">
<input class="w-4 h-4 rounded border-stone-300 text-brand-500 focus:ring-brand-500" id="rememberMe" name="remember" type="checkbox">
<span class="text-xs text-stone-600 select-none">Ingat sesi di perangkat ini</span>
</label>
<a class="text-xs text-stone-500 hover:text-stone-800 flex items-center gap-1 transition" href="#">
<span class="">IT Helpdesk</span>
<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
<path d="M8.25 4.5l7.5 7.5-7.5 7.5" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</a>
</div>
<!-- Submit Button (High-End TokoKu Mauve/Rose Action) -->
<div class="pt-2">
<button class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white bg-brand-500 hover:bg-brand-600 active:scale-[0.99] transition shadow-glow hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-brand-200 flex items-center justify-center gap-2" type="submit">
<span class="">Masuk ke Sistem Manajemen →</span>
</button>
</div>
</form>
<!-- END: LoginForm -->
<!-- Bottom IT / Superadmin Contact Link (Replaces Consumer Signup) -->
<div class="text-center pt-2">
<p class="text-xs text-stone-500">
            Kendala akses atau belum memiliki hak otorisasi?
            <br>
<a class="font-bold text-brand-600 hover:text-brand-700 hover:underline transition inline-flex items-center gap-1 mt-1" href="#">
              Hubungi IT Helpdesk / Superadmin TokoKu
            </a>
</p>
</div>
<!-- Security & Trust Badges -->
<div class="pt-6 border-t border-stone-100 flex flex-col sm:flex-row items-center justify-between text-[11px] text-stone-400 gap-3">
<div class="flex items-center gap-1.5">
<svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
<path clip-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" fill-rule="evenodd"></path>
</svg>
<span class="">Audit Logging Aktif &amp; Terenkripsi SSL</span>
</div>
<div>
<span class="">© {{ date('Y') }} PT Toko Retail Nusantara.</span>
</div>
</div>
</div>
</section>
<!-- END: RightAuthFormPanel -->
</main>
<!-- END: MainLayout -->
<!-- BEGIN: InteractiveScripts -->
<script data-purpose="password-visibility-toggle">
    // Toggle password display between text and password type
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');

    if (togglePasswordBtn && passwordInput) {
      togglePasswordBtn.addEventListener('click', () => {
        const isPassword = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
        togglePasswordBtn.classList.toggle('text-brand-600', isPassword);
      });
    }
  </script>
<!-- END: InteractiveScripts -->


</body></html>