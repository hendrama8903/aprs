{{-- Price master: hasil upload OGS + add/edit/delete manual. Status dihitung dari Time Control terhadap hari ini. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('item-price._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('item-price._popup-form')
@include('partials.ogs-upload', ['title' => 'Price Master', 'baseUrl' => url('/ItemPrice'), 'hint' => 'The file replaces ALL prices, including prices added or edited manually. Suppliers in the file are added or renamed.'])
@endsection

@section('scripts')
@include('item-price._scripts')
@stack('ogs-upload-scripts')
@endsection
