<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ config('app.name') }} | Login</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
<link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
<style>
  :root{
    --blue-900:#0A2A8A;
    --blue-800:#0E3FA5;
    --blue-700:#1455D9;
    --blue-500:#2F7BF5;
    --sky:#4FC3F7;
    --ink:#0B1B3A;
    --muted:#64748B;
    --field:#F3F6FC;
    --line:#E3E9F4;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html, body{height:100%; overflow-x:hidden;}
  body{
    font-family:'Plus Jakarta Sans','Inter',sans-serif;
    color:var(--ink);
    min-height:100vh;
    background:#EEF3FB;
    overflow-x:hidden;
  }
  .login{display:flex; min-height:100vh;}

  /* ---------- Panel kiri ---------- */
  .hero{
    position:relative; flex:0 0 50%; overflow:hidden; color:#fff;
    background:
      radial-gradient(circle at 20% 15%, rgba(79,195,247,.25) 0, transparent 40%),
      linear-gradient(160deg, #1E63F0 0%, var(--blue-800) 55%, var(--blue-900) 100%);
    padding:52px 64px;
    display:flex; flex-direction:column;
  }
  /* tepi kanan melengkung */
  .hero-curve{position:absolute; top:0; right:-1px; height:100%; width:180px; z-index:3;}
  /* rak gudang samar di belakang */
  .hero-shelves{position:absolute; inset:auto 0 0 0; height:42%; opacity:.07; z-index:0;}
  .hero > *:not(.hero-curve):not(.hero-shelves):not(.hero-art){position:relative; z-index:2;}

  .brand{display:flex; align-items:center; gap:16px;}
  .brand-logo{width:66px; height:66px; border-radius:16px; box-shadow:0 10px 24px rgba(4,20,80,.35);}
  .brand h1{font-size:36px; font-weight:800; letter-spacing:.5px; line-height:1;}
  .brand p{font-size:15px; opacity:.9; margin-top:6px;}

  .headline{margin-top:52px; font-size:40px; font-weight:800; line-height:1.15;}
  .headline span{color:var(--sky); display:block;}
  .tagline{margin-top:14px; font-size:16px; line-height:1.55; opacity:.9; max-width:430px;}

  .features{margin-top:30px; display:flex; flex-direction:column; gap:18px;}
  .feature{display:flex; align-items:center; gap:16px;}
  .feature-ic{width:52px; height:52px; border-radius:50%; flex:none; background:rgba(255,255,255,.16);
    display:flex; align-items:center; justify-content:center; backdrop-filter:blur(2px);}
  .feature-ic svg{width:22px; height:22px;}
  .feature b{display:block; font-size:15px; font-weight:700;}
  .feature small{font-size:13px; opacity:.85;}

  /* menjauh dari lengkungan kanan supaya laptop tidak terpotong */
  .hero-art{position:absolute; right:150px; bottom:0; width:540px; max-width:70%; z-index:1;}

  /* ---------- Panel kanan ---------- */
  .side{
    position:relative; flex:1 1 50%; overflow:hidden;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding:90px 40px 32px;
    background:
      radial-gradient(circle at 85% 80%, rgba(207,226,255,.7) 0, transparent 35%),
      radial-gradient(circle at 90% 10%, rgba(220,232,252,.9) 0, transparent 30%),
      #F2F6FD;
  }
  .side-deco{position:absolute; right:-40px; top:42%; width:150px; opacity:.5;}
  .lang{position:absolute; top:28px; right:36px;}
  .lang-btn{display:flex; align-items:center; gap:10px; padding:9px 16px; border-radius:24px; border:none; cursor:pointer;
    background:#fff; box-shadow:0 6px 18px rgba(11,27,58,.08); font:600 14px 'Plus Jakarta Sans',sans-serif; color:var(--ink);}
  .lang-btn svg{width:18px; height:18px;}
  .lang-menu{position:absolute; right:0; top:46px; background:#fff; border-radius:12px; padding:6px; min-width:150px;
    box-shadow:0 14px 30px rgba(11,27,58,.14); display:none;}
  .lang-menu.open{display:block;}
  .lang-menu button{display:block; width:100%; text-align:left; border:none; background:none; padding:9px 12px; border-radius:8px;
    font:500 13.5px 'Plus Jakarta Sans',sans-serif; color:var(--ink); cursor:pointer;}
  .lang-menu button:hover, .lang-menu button.active{background:#EEF3FC; color:var(--blue-700);}

  .card{
    width:100%; max-width:540px; background:#fff; border-radius:22px;
    padding:38px 46px 42px;
    box-shadow:0 30px 70px -24px rgba(11,27,58,.22), 0 2px 8px rgba(11,27,58,.04);
    animation:fadeUp .5s cubic-bezier(.2,.7,.3,1) both;
  }
  @keyframes fadeUp{from{opacity:0; transform:translateY(12px);} to{opacity:1; transform:none;}}
  .card-brand{text-align:center;}
  .card-brand img{width:86px; height:86px; border-radius:18px; box-shadow:0 12px 26px rgba(20,85,217,.3);}
  .card-brand h2{margin-top:10px; font-size:36px; font-weight:800; letter-spacing:.5px;}
  .card-brand p{color:var(--muted); font-size:17px; margin-top:2px;}
  .card-brand hr{width:42px; height:3px; border:none; border-radius:2px; background:var(--blue-700); margin:18px auto 0;}

  .welcome{margin-top:30px; font-size:30px; font-weight:800;}
  .welcome-sub{color:var(--muted); font-size:15.5px; margin-top:4px; margin-bottom:24px;}

  label.fl{display:block; font-size:14.5px; font-weight:700; margin-bottom:8px;}
  .field{margin-bottom:20px;}
  .input{position:relative;}
  .input svg.lead{position:absolute; left:18px; top:50%; transform:translateY(-50%); width:19px; height:19px; color:#8A94A8;}
  .input input{
    width:100%; height:54px; padding:0 50px 0 52px; border-radius:12px;
    border:1.5px solid var(--line); background:var(--field);
    font:500 15px 'Plus Jakarta Sans',sans-serif; color:var(--ink); outline:none;
    transition:border-color .15s, box-shadow .15s, background .15s;
  }
  .input input::placeholder{color:#97A1B3; font-weight:400;}
  .input input:focus{background:#fff; border-color:var(--blue-700); box-shadow:0 0 0 4px rgba(20,85,217,.12);}
  .input:focus-within svg.lead{color:var(--blue-700);}
  .eye{position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#8A94A8; padding:4px;}
  .eye svg{width:20px; height:20px; display:block;}
  .eye:hover{color:var(--blue-700);}

  .keep{display:flex; align-items:center; gap:10px; font-size:14.5px; color:#334155; cursor:pointer; margin:2px 0 24px; user-select:none;}
  .keep input{appearance:none; width:20px; height:20px; border:1.5px solid #B8C2D3; border-radius:5px; background:#fff; cursor:pointer; position:relative; flex:none;}
  .keep input:checked{background:var(--blue-700); border-color:var(--blue-700);}
  .keep input:checked::after{content:""; position:absolute; left:6px; top:2px; width:5px; height:10px; border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg);}

  .btn{
    width:100%; height:58px; border:none; border-radius:12px; cursor:pointer;
    background:linear-gradient(90deg, #1E7BF2 0%, var(--blue-700) 45%, var(--blue-900) 100%);
    color:#fff; font:700 17px 'Plus Jakarta Sans',sans-serif;
    display:flex; align-items:center; justify-content:center; gap:12px;
    box-shadow:0 16px 26px -12px rgba(14,63,165,.6);
    transition:transform .15s, opacity .15s;
  }
  .btn:hover{transform:translateY(-2px);}
  .btn:disabled{opacity:.75; cursor:wait; transform:none;}
  .btn svg{width:20px; height:20px;}

  .footer{margin-top:40px; display:flex; align-items:center; gap:24px; color:var(--muted); font-size:13px;}
  .footer::before, .footer::after{content:""; width:68px; height:1px; background:#C9D3E3;}

  .bootstrap-growl{
    font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; font-weight:500; line-height:1.4;
    padding:12px 34px 12px 16px; border-radius:10px; border:1px solid transparent;
    box-shadow:0 14px 30px -10px rgba(14,63,165,.35);
  }
  .bootstrap-growl.alert-danger{background:#fdecec; border-color:#f5c2c2; color:#a12b2b;}
  .bootstrap-growl.alert-success{background:#e8f8f0; border-color:#b7e6cc; color:#1a7a4c;}
  .bootstrap-growl .close{position:absolute; top:8px; right:10px; font-size:16px; line-height:1; color:inherit; opacity:.55; cursor:pointer; background:none; border:none;}

  @media (max-width:1280px){
    .hero{padding:44px 44px;}
    .headline{font-size:34px;}
    .hero-art{width:450px; right:140px;}
    .card{padding:32px 34px 36px;}
  }
  /* layar pendek (laptop / browser tidak full screen): semua dirapatkan supaya muat tanpa scroll */
  @media (max-height:860px){
    .hero{padding:34px 52px;}
    .brand-logo{width:54px; height:54px;}
    .brand h1{font-size:30px;}
    .headline{margin-top:30px; font-size:32px;}
    .tagline{font-size:14.5px; margin-top:10px;}
    .features{margin-top:20px; gap:12px;}
    .feature-ic{width:44px; height:44px;}
    .hero-art{width:420px; right:140px;}
    .side{padding:70px 40px 18px;}
    .lang{top:18px;}
    .card{max-width:500px; padding:26px 40px 30px;}
    .card-brand img{width:64px; height:64px; border-radius:15px;}
    .card-brand h2{font-size:28px; margin-top:6px;}
    .card-brand p{font-size:14.5px;}
    .card-brand hr{margin-top:12px;}
    .welcome{margin-top:18px; font-size:24px;}
    .welcome-sub{font-size:14px; margin-bottom:16px;}
    .field{margin-bottom:14px;}
    .input input{height:46px;}
    .keep{margin-bottom:16px;}
    .btn{height:50px;}
    .footer{margin-top:18px;}
  }
  @media (max-height:700px){
    .hero-art{width:330px; right:130px;}
    .features{gap:8px;}
    .feature-ic{width:38px; height:38px;}
    .feature small{font-size:12px;}
    .card-brand img{width:52px; height:52px;}
    .card-brand h2{font-size:24px;}
    .card-brand hr{display:none;}
    .welcome{margin-top:12px; font-size:21px;}
    .welcome-sub{margin-bottom:12px;}
    .field{margin-bottom:10px;}
    .input input{height:42px;}
    .btn{height:46px;}
  }
  /* layar kecil: panel kiri disembunyikan, cukup kartu login */
  @media (max-width:960px){
    .hero{display:none;}
    .side{padding:84px 16px 24px;}
    .card{padding:28px 22px 30px;}
    .card-brand h2{font-size:30px;}
    .welcome{font-size:25px;}
  }
</style>
</head>
<body>
<div class="login">
  <section class="hero" aria-hidden="false">
    {{-- rak gudang samar --}}
    <svg class="hero-shelves" viewBox="0 0 800 420" preserveAspectRatio="none" aria-hidden="true">
      <g fill="none" stroke="#fff" stroke-width="6">
        <path d="M30 40v380M250 40v380M470 40v380M690 40v380"/>
        <path d="M30 140h220M30 260h220M250 140h220M250 260h220M470 140h220M470 260h220"/>
      </g>
      <g fill="#fff">
        <rect x="50" y="90" width="70" height="50" rx="4"/><rect x="130" y="100" width="55" height="40" rx="4"/>
        <rect x="270" y="205" width="80" height="55" rx="4"/><rect x="360" y="215" width="60" height="45" rx="4"/>
        <rect x="490" y="85" width="90" height="55" rx="4"/><rect x="590" y="210" width="70" height="50" rx="4"/>
        <rect x="60" y="320" width="120" height="70" rx="4"/>
      </g>
    </svg>

    <div class="brand">
      <img class="brand-logo" src="{{ asset('images/logo.svg') }}" alt="">
      <div>
        <h1>{{ config('app.name') }}</h1>
        <p>{{ config('app.description') }}</p>
      </div>
    </div>

    <h2 class="headline"><span data-i18n="hero1" style="color:#fff">Efficient Approval</span><span data-i18n="hero2">for Better Control</span></h2>
    <p class="tagline" data-i18n="tagline">A system for fast, accurate and well-documented price approval.</p>

    <div class="features">
      <div class="feature">
        <div class="feature-ic"><svg viewBox="0 0 24 24" fill="#fff"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm2 9v2h8v-2H8zm0 4v2h8v-2H8zm0-8v2h5V7H8z"/></svg></div>
        <div><b data-i18n="f1">Faster Process</b><small data-i18n="f1s">Digital price approval</small></div>
      </div>
      <div class="feature">
        <div class="feature-ic"><svg viewBox="0 0 24 24" fill="#fff"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3zm-1.2 13.6-3.4-3.4 1.4-1.4 2 2 4.6-4.6 1.4 1.4-6 6z"/></svg></div>
        <div><b data-i18n="f2">Safer Data</b><small data-i18n="f2s">Stored securely in one place</small></div>
      </div>
      <div class="feature">
        <div class="feature-ic"><svg viewBox="0 0 24 24" fill="#fff"><path d="M4 20h4V10H4v10zm6 0h4V4h-4v16zm6 0h4v-7h-4v7z"/></svg></div>
        <div><b data-i18n="f3">Real-time Monitoring</b><small data-i18n="f3s">Track approval status anytime</small></div>
      </div>
    </div>

    {{-- ilustrasi: laptop menampilkan daftar approval APR + tanaman --}}
    <svg class="hero-art" viewBox="0 0 560 360" aria-hidden="true">
      <defs>
        <linearGradient id="desk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#EAF1FF"/><stop offset="1" stop-color="#C9D8F5"/></linearGradient>
        <linearGradient id="body" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#C7CFDD"/><stop offset="1" stop-color="#8E98AB"/></linearGradient>
      </defs>
      <ellipse cx="300" cy="345" rx="290" ry="22" fill="url(#desk)" opacity=".9"/>
      {{-- tanaman --}}
      <g transform="translate(70 200)">
        <path d="M40 60c-30-10-45-45-40-70 18 8 40 38 40 70z" fill="#3E9E5F"/>
        <path d="M44 60c-6-35 4-70 26-86 6 30-6 70-26 86z" fill="#2F8A50"/>
        <path d="M48 62c18-28 48-40 70-38-10 26-40 40-70 38z" fill="#47AE6A"/>
        <path d="M18 62h56l-8 70H26z" fill="#F4F6FA"/>
        <path d="M18 62h56v8H18z" fill="#DDE3EE"/>
      </g>
      {{-- laptop --}}
      <g transform="translate(170 40) skewX(-6)">
        <rect x="0" y="0" width="330" height="215" rx="12" fill="#1C2333"/>
        <rect x="9" y="9" width="312" height="197" rx="6" fill="#F7F9FD"/>
        <rect x="9" y="9" width="66" height="197" rx="6" fill="#1D3E9E"/>
        <rect x="18" y="20" width="40" height="8" rx="3" fill="#fff" opacity=".9"/>
        <g fill="#fff" opacity=".45"><rect x="18" y="42" width="44" height="5" rx="2"/><rect x="18" y="56" width="38" height="5" rx="2"/><rect x="18" y="70" width="44" height="5" rx="2"/><rect x="18" y="84" width="34" height="5" rx="2"/></g>
        <rect x="88" y="22" width="80" height="8" rx="3" fill="#0B1B3A" opacity=".75"/>
        <rect x="262" y="20" width="46" height="12" rx="6" fill="#2F6BF0"/>
        <g fill="#C9D2E3"><rect x="88" y="44" width="220" height="6" rx="3"/></g>
        <g fill="#DCE3EF">
          <rect x="88" y="62" width="150" height="5" rx="2"/><rect x="88" y="82" width="140" height="5" rx="2"/><rect x="88" y="102" width="155" height="5" rx="2"/>
          <rect x="88" y="122" width="135" height="5" rx="2"/><rect x="88" y="142" width="150" height="5" rx="2"/><rect x="88" y="162" width="130" height="5" rx="2"/>
        </g>
        <rect x="262" y="59" width="40" height="10" rx="5" fill="#FCD889"/>
        <rect x="262" y="79" width="40" height="10" rx="5" fill="#9BE3B5"/>
        <rect x="262" y="99" width="40" height="10" rx="5" fill="#F5A3A3"/>
        <rect x="262" y="119" width="40" height="10" rx="5" fill="#9BE3B5"/>
        <rect x="262" y="139" width="40" height="10" rx="5" fill="#FCD889"/>
        <rect x="262" y="159" width="40" height="10" rx="5" fill="#9BE3B5"/>
      </g>
      <path d="M140 262h372l28 26H112z" fill="url(#body)"/>
      <path d="M112 288h428v8a6 6 0 0 1-6 6H118a6 6 0 0 1-6-6z" fill="#7C879B"/>
      <rect x="290" y="268" width="80" height="8" rx="4" fill="#A5AEC0"/>
      {{-- pena --}}
      <rect x="20" y="318" width="120" height="7" rx="3.5" fill="#33405A" transform="rotate(-6 80 322)"/>
    </svg>

    {{-- tepi kanan melengkung ke panel putih --}}
    <svg class="hero-curve" viewBox="0 0 180 1000" preserveAspectRatio="none" aria-hidden="true">
      <path d="M180 0H70C150 160 20 330 90 520S170 840 60 1000H180z" fill="#F2F6FD"/>
      <path d="M70 0C150 160 20 330 90 520S170 840 60 1000" fill="none" stroke="#BFD3FA" stroke-width="10" opacity=".8"/>
    </svg>
  </section>

  <section class="side">
    <svg class="side-deco" viewBox="0 0 100 110" aria-hidden="true"><path d="M50 2 98 29v52L50 108 2 81V29z" fill="#DCE7FB"/></svg>

    <div class="lang">
      <button type="button" class="lang-btn" id="langBtn" aria-haspopup="true" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9.5"/><path d="M2.5 12h19M12 2.5c2.8 3 2.8 16 0 19M12 2.5c-2.8 3-2.8 16 0 19"/></svg>
        <span id="langCode">EN</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="lang-menu" id="langMenu" role="menu">
        <button type="button" data-lang="en">English (EN)</button>
        <button type="button" data-lang="id">Bahasa Indonesia (ID)</button>
      </div>
    </div>

    <div class="card">
      <div class="card-brand">
        <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }}">
        <h2>{{ config('app.name') }}</h2>
        <p>{{ config('app.description') }}</p>
        <hr>
      </div>

      <h3 class="welcome" data-i18n="welcome">Welcome Back!</h3>
      <p class="welcome-sub" data-i18n="welcomeSub">Sign in with your {{ config('app.name') }} account to continue</p>

      <form id="loginForm" novalidate>
        @csrf
        <div class="field">
          <label class="fl" for="username" data-i18n="username">Username</label>
          <div class="input">
            <svg class="lead" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
            <input type="text" id="username" name="username" data-i18n-ph="usernamePh" placeholder="Enter your username" autocomplete="username" autofocus>
          </div>
        </div>

        <div class="field">
          <label class="fl" for="password" data-i18n="password">Password</label>
          <div class="input">
            <svg class="lead" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
            <input type="password" id="password" name="password" data-i18n-ph="passwordPh" placeholder="Enter your password" autocomplete="current-password">
            <button type="button" class="eye" id="togglePw" aria-label="Show password">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <label class="keep"><input type="checkbox" id="keepSignedIn"> <span data-i18n="keep">Keep me signed in</span></label>

        <button type="submit" class="btn" id="btn_login">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
          <span id="btnText" data-i18n="signIn">Sign In</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </form>
    </div>

    <div class="footer"><span>&copy; {{ date('Y') }} {{ config('apr.login_footer') }}. <span data-i18n="rights">All rights reserved.</span></span></div>
  </section>
</div>

<script src="{{ asset('lib/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('lib/bootstrap-growl/jquery.bootstrap-growl.js') }}"></script>
<script>
  // ---------- Bahasa halaman login (EN/ID), disimpan di browser ----------
  var I18N = {
    en: {
      hero1: 'Efficient Approval', hero2: 'for Better Control',
      tagline: 'A system for fast, accurate and well-documented price approval.',
      f1: 'Faster Process', f1s: 'Digital price approval',
      f2: 'Safer Data', f2s: 'Stored securely in one place',
      f3: 'Real-time Monitoring', f3s: 'Track approval status anytime',
      welcome: 'Welcome Back!', welcomeSub: 'Sign in with your {{ config('app.name') }} account to continue',
      username: 'Username', usernamePh: 'Enter your username',
      password: 'Password', passwordPh: 'Enter your password',
      keep: 'Keep me signed in', signIn: 'Sign In', signingIn: 'Signing in...',
      rights: 'All rights reserved.',
      required: 'Username and password are required.',
      success: 'Login successful, redirecting...',
      expired: 'Your login page has expired. Please refresh the page.',
      failed: 'Login failed. Please try again.'
    },
    id: {
      hero1: 'Persetujuan Efisien', hero2: 'untuk Kontrol Lebih Baik',
      tagline: 'Sistem untuk proses persetujuan harga secara cepat, akurat, dan terdokumentasi dengan baik.',
      f1: 'Proses Lebih Cepat', f1s: 'Persetujuan harga secara digital',
      f2: 'Data Lebih Aman', f2s: 'Tersimpan dengan aman dan terpusat',
      f3: 'Monitoring Real-time', f3s: 'Status persetujuan dapat dipantau',
      welcome: 'Selamat Datang!', welcomeSub: 'Masuk dengan akun {{ config('app.name') }} Anda untuk melanjutkan',
      username: 'Username', usernamePh: 'Masukkan username',
      password: 'Password', passwordPh: 'Masukkan password',
      keep: 'Tetap masuk', signIn: 'Masuk', signingIn: 'Sedang masuk...',
      rights: 'Hak cipta dilindungi.',
      required: 'Username dan password wajib diisi.',
      success: 'Login berhasil, mengalihkan...',
      expired: 'Halaman login sudah kedaluwarsa. Silakan muat ulang halaman.',
      failed: 'Login gagal. Silakan coba lagi.'
    }
  };
  var lang = 'en';
  try { lang = localStorage.getItem('apr_login_lang') || 'en'; } catch (e) {}
  if (!I18N[lang]) lang = 'en';

  function t(key) { return I18N[lang][key] || I18N.en[key] || key; }

  function applyLang(code) {
    lang = I18N[code] ? code : 'en';
    try { localStorage.setItem('apr_login_lang', lang); } catch (e) {}
    document.documentElement.lang = lang;
    $('#langCode').text(lang.toUpperCase());
    $('[data-i18n]').each(function () { $(this).text(t($(this).data('i18n'))); });
    $('[data-i18n-ph]').each(function () { $(this).attr('placeholder', t($(this).data('i18n-ph'))); });
    $('#langMenu button').removeClass('active').filter('[data-lang="' + lang + '"]').addClass('active');
  }
  applyLang(lang);

  $('#langBtn').on('click', function (e) {
    e.stopPropagation();
    var open = !$('#langMenu').hasClass('open');
    $('#langMenu').toggleClass('open', open);
    $(this).attr('aria-expanded', open);
  });
  $('#langMenu button').on('click', function () {
    applyLang($(this).data('lang'));
    $('#langMenu').removeClass('open');
  });
  $(document).on('click', function () { $('#langMenu').removeClass('open'); });

  // ---------- Login ----------
  $('#togglePw').on('click', function () {
    var pw = document.getElementById('password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
  });

  $('#loginForm').on('submit', function (e) {
    e.preventDefault();
    if (!$.trim($('#username').val()) || !$('#password').val()) {
      growl(t('required'), 'danger');
      return;
    }
    validateLogin();
  });

  function growl(message, type, delay) {
    $('.bootstrap-growl').remove();
    $.bootstrapGrowl($('<div>').text(message).html(), { type: type, allow_dismiss: true, align: 'right', delay: delay || 6000 });
  }

  function setLoading(loading) {
    $('#btn_login').prop('disabled', loading);
    $('#btnText').text(loading ? t('signingIn') : t('signIn'));
  }

  function validateLogin() {
    setLoading(true);

    $.ajax({
      type: 'POST',
      url: '{{ url("/Auth/Validate") }}',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      data: {
        username: $('#username').val(),
        password: $('#password').val(),
        keepSignedIn: $('#keepSignedIn').is(':checked') ? 1 : 0,
        returnUrl: @json($returnUrl ?? '')
      }
    }).done(function (result) {
      if (result.status === true) {
        growl(t('success'), 'success', 1200);
        setTimeout(function () { window.location.href = result.data; }, 500);
      } else {
        setLoading(false);
        growl(result.message, 'danger');
      }
    }).fail(function (xhr) {
      setLoading(false);
      growl(xhr.status === 419 ? t('expired') : t('failed'), 'danger');
    });
  }
</script>
</body>
</html>
