{{-- CONTOH MASTER STANDAR — salin folder department/ sebagai titik awal master baru. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('department._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('department._popup-form')
@endsection

@section('scripts')
@include('department._scripts')
@endsection
