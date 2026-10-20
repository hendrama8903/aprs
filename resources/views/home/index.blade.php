@extends('layouts.admin')

@php
    $statuses = ['DRAFT' => 'Draft', 'IN_APPROVAL' => 'In Approval', 'REJECTED' => 'Rejected', 'APPROVED' => 'Approved', 'ISSUED' => 'Issued'];
@endphp

@section('content')
<section class="content">
    <div class="sg-welcome">
        <h2>Welcome, {{ session('FullName') }}</h2>
        <p>{{ session('RoleName') }} &middot; {{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    @if ($canSeeApr)
        <div class="hm-stats">
            @foreach ($statuses as $code => $label)
                <a class="dp-card hm-stat" href="{{ url('/Apr/Index') }}">
                    <span class="apr-badge {{ $code }}">{{ $label }}</span>
                    <b>{{ $statusCounts[$code] ?? 0 }}</b>
                </a>
            @endforeach
        </div>
    @endif

    <div class="hm-row">
        @if ($inbox !== null)
            <div class="dp-card hm-card">
                <div class="hm-title"><i class="fa fa-inbox"></i> Waiting for My Approval <span class="hm-count">{{ count($inbox) }}</span></div>
                @forelse ($inbox as $a)
                    <a class="hm-item" href="{{ url('/Apr/Form?id=' . $a->AprId) }}">
                        <b>{{ $a->DocNo }}</b> &middot; {{ $a->SupplierName }}
                        <span>{{ $a->ItemCount }} item(s) &middot; {{ $a->CurrentStep }}</span>
                    </a>
                @empty
                    <div class="hm-empty">Nothing is waiting for you.</div>
                @endforelse
            </div>
        @endif

        @if ($canSeeApr)
            <div class="dp-card hm-card">
                <div class="hm-title"><i class="fa fa-file-text-o"></i> Recent APR</div>
                @forelse ($recent as $a)
                    <a class="hm-item" href="{{ url('/Apr/Form?id=' . $a->AprId) }}">
                        <b>{{ $a->DocNo }}</b> &middot; {{ $a->SupplierName }}
                        <span class="apr-badge {{ $a->Status }}">{{ $statuses[$a->Status] }}</span>
                    </a>
                @empty
                    <div class="hm-empty">No APR documents yet.</div>
                @endforelse
            </div>
        @endif

        @if ($importMenus !== [])
            <div class="dp-card hm-card">
                <div class="hm-title"><i class="fa fa-cloud-download"></i> OGS Data</div>
                @foreach ($importMenus as $type => $menuUrl)
                    @php($last = $lastImports[$type])
                    <a class="hm-item" href="{{ url($menuUrl) }}">
                        <b>{{ \App\Models\OgsImport::TypeLabels[$type] }}</b>
                        <span>
                            @if ($last)
                                last import {{ $last->CreatedDate->format('d-m-Y H:i') }} ({{ $last->CreatedDate->diffForHumans() }})
                            @else
                                <span class="text-red">never imported</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection

@section('scripts')
@include('apr._status')
<style>
    .hm-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
    .hm-stat { padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; color: inherit; text-decoration: none !important; }
    .hm-stat b { font-size: 26px; color: #111827; }
    .hm-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 12px; margin-top: 12px; }
    .hm-card { padding: 14px 16px; }
    .hm-title { font-weight: 700; margin-bottom: 8px; }
    .hm-count { background: #B42318; color: #fff; border-radius: 10px; padding: 0 8px; font-size: 11px; margin-left: 4px; }
    .hm-item { display: block; padding: 7px 0; border-bottom: 1px dashed #eee; color: inherit; font-size: 13px; }
    .hm-item span { display: block; font-size: 11px; color: #6b7280; margin-top: 2px; }
    .hm-item span.apr-badge { display: inline-block; float: right; margin-top: -2px; }
    .hm-empty { color: #6b7280; font-size: 13px; padding: 6px 0; }
    @media (max-width: 900px) { .hm-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
@endsection
