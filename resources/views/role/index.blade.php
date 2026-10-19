@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('role._search-criteria')
        </div>
        <div id="divGrid">
            @include('role._grid')
        </div>
    </div>
</section>
@include('role._popup-form')
@include('role._access-form')
@endsection

@section('scripts')
@include('role._scripts')
@endsection
