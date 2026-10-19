@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="sg-welcome">
        <h2>Welcome, {{ session('FullName') }}</h2>
        <p>{{ session('RoleName') }} &middot; {{ session('DepartmentName') ?: '-' }} &middot; {{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    <div class="dp-card" style="margin-top: 16px; padding: 24px;">
        <p style="color:#667085; font-size: 13px; margin: 0;">
            Gunakan menu di sidebar untuk mengelola Master Data, User, Role &amp; Access, dan Menu Master.
        </p>
    </div>
</section>
@endsection
