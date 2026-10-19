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
    --primary-blue:#1455D9;
    --dark-blue:#0E3FA5;
    --light-blue:#EAF2FF;
    --text-dark:#0B1B3A;
    --text-secondary:#64748B;
    --field-bg:#F5F8FE;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html, body{height:100%; width:100%;}
  body{
    font-family:'Inter', sans-serif;
    color:var(--text-dark);
    min-height:100vh;
    background:radial-gradient(circle at 15% 20%, #DCE7FB 0, transparent 45%),
               radial-gradient(circle at 85% 80%, #D6F1F8 0, transparent 40%),
               #EEF3FC;
    overflow-x:hidden;
  }
  .page-wrap{
    min-height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:32px 20px;
  }
  .brand-header{
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
    margin-bottom:24px;
    animation:fadeUp .5s cubic-bezier(.2,.7,.3,1) both;
  }
  .brand-header img{width:72px; height:72px; margin-bottom:14px;}
  .brand-header h1{font-family:'Poppins',sans-serif; font-weight:700; font-size:26px; letter-spacing:1px;}
  .brand-header h1 .accent{color:var(--primary-blue);}
  .brand-header p{margin-top:4px; font-size:13px; color:var(--text-secondary);}

  @keyframes fadeUp{ from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);} }

  .card{
    width:100%;
    max-width:360px;
    background:#fff;
    border-radius:12px;
    padding:34px 26px 30px;
    box-shadow:0 30px 70px -20px rgba(11,27,58,.22), 0 2px 8px rgba(11,27,58,.06);
    animation:fadeUp .5s cubic-bezier(.2,.7,.3,1) both .1s;
  }
  .card h2{font-family:'Poppins',sans-serif; font-size:20px; font-weight:700; text-align:center; margin-bottom:4px;}
  .card > p.sub{font-size:12.5px; color:var(--text-secondary); text-align:center; margin-bottom:20px;}
  label{display:block; font-size:12.5px; font-weight:600; margin-bottom:6px;}
  .field{margin-bottom:14px;}
  .input-group{position:relative; display:flex; align-items:center;}
  .input-group svg.leading{position:absolute; left:13px; width:16px; height:16px; color:#A7AEC0;}
  .input-group svg.trailing{position:absolute; right:13px; width:17px; height:17px; color:#A7AEC0; cursor:pointer;}
  .input-group:focus-within svg.leading{color:var(--primary-blue);}
  input[type=text], input[type=password]{
    width:100%;
    padding:12px 14px 12px 42px;
    border:1.5px solid transparent;
    background:var(--field-bg);
    border-radius:12px;
    font-size:13.5px;
    font-family:'Inter',sans-serif;
    color:var(--text-dark);
    outline:none;
    transition:border-color .15s ease, box-shadow .15s ease, background .15s ease;
  }
  input::placeholder{color:#A7AEC0;}
  input:focus{background:#fff; border-color:var(--primary-blue); box-shadow:0 0 0 4px rgba(20,85,217,.12);}
  input#password{padding-right:40px;}
  .btn-login{
    margin-top:8px;
    width:100%;
    padding:13px;
    border:none;
    border-radius:12px;
    background:linear-gradient(135deg, var(--primary-blue), var(--dark-blue) 75%);
    color:#fff;
    font-size:14px;
    font-weight:600;
    font-family:'Inter',sans-serif;
    display:flex; align-items:center; justify-content:center; gap:8px;
    cursor:pointer;
    box-shadow:0 12px 20px -8px rgba(14,63,165,.55);
    transition:transform .15s ease, opacity .15s ease;
  }
  .btn-login:hover{transform:translateY(-2px);}
  .btn-login:disabled{opacity:.7; cursor:wait; transform:none;}
  .btn-login svg{width:15px; height:15px;}
  .footer-note{margin-top:22px; font-size:11.5px; color:var(--text-secondary); text-align:center;}

  .bootstrap-growl{
    font-family:'Inter', sans-serif; font-size:13px; font-weight:500; line-height:1.4;
    padding:12px 34px 12px 16px; border-radius:10px; border:1px solid transparent;
    box-shadow:0 14px 30px -10px rgba(14,63,165,.35);
  }
  .bootstrap-growl.alert-danger{background:#fdecec; border-color:#f5c2c2; color:#a12b2b;}
  .bootstrap-growl.alert-success{background:#e8f8f0; border-color:#b7e6cc; color:#1a7a4c;}
  .bootstrap-growl .close{position:absolute; top:8px; right:10px; font-size:16px; line-height:1; color:inherit; opacity:.55; cursor:pointer; background:none; border:none;}
</style>
</head>
<body>
<div class="page-wrap">
  <div class="brand-header">
    <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }}">
    <h1>{{ config('app.name') }}</h1>
    <p>{{ config('app.description') }}</p>
  </div>

  <div class="card">
    <h2>Welcome Back!</h2>
    <p class="sub">Sign in with your {{ config('app.name') }} account</p>

    <form id="loginForm">
      @csrf
      <div class="field">
        <label for="username">Username</label>
        <div class="input-group">
          <svg class="leading" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
          <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required autofocus>
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-group">
          <svg class="leading" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
          <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
          <svg class="trailing" id="togglePw" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
      </div>

      <button type="submit" class="btn-login" id="btn_login">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
        <span>Sign In</span>
      </button>
    </form>
  </div>

  <div class="footer-note">&copy; {{ date('Y') }} General Affairs Department</div>
</div>

<script src="{{ asset('lib/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('lib/bootstrap-growl/jquery.bootstrap-growl.js') }}"></script>

<script>
  $('#togglePw').on('click', function () {
    var pw = document.getElementById('password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
  });

  $('#loginForm').on('submit', function (e) {
    e.preventDefault();
    validateLogin();
  });

  function growl(message, type, delay) {
    $.bootstrapGrowl($('<div>').text(message).html(), { type: type, allow_dismiss: true, align: 'right', delay: delay || 6000 });
  }

  function setLoading(loading) {
    $('#btn_login').prop('disabled', loading).find('span').text(loading ? 'Signing in...' : 'Sign In');
  }

  function validateLogin() {
    setLoading(true);
    $('.bootstrap-growl').remove();

    $.ajax({
      type: 'POST',
      url: '{{ url("/Auth/Validate") }}',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      data: {
        username: $('#username').val(),
        password: $('#password').val(),
        returnUrl: @json($returnUrl ?? '')
      }
    }).done(function (result) {
      if (result.status === true) {
        growl('Login successful, redirecting...', 'success', 1200);
        setTimeout(function () { window.location.href = result.data; }, 500);
      } else {
        setLoading(false);
        growl(result.message, 'danger');
      }
    }).fail(function (xhr) {
      setLoading(false);
      growl(xhr.status === 419 ? 'Your login page has expired. Please refresh the page.' : 'Login failed. Please try again.', 'danger');
    });
  }
</script>
</body>
</html>
