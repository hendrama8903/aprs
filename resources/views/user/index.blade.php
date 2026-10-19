@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('user._search-criteria')
        </div>
        <div id="divGrid">
            @include('user._grid')
        </div>
    </div>
</section>
@include('user._popup-form')
@endsection

@section('scripts')
@include('user._scripts')
@endsection
