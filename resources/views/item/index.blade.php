{{-- Item master: add/edit/delete manual, upload file export OGS, export Excel. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('item._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('item._popup-form')
@include('partials.ogs-upload', ['title' => 'Item Master', 'baseUrl' => url('/Item'), 'hint' => 'Items are matched by Item Code: existing codes are updated, new codes are added. Items not in the file are kept.'])
@endsection

@section('scripts')
@include('item._scripts')
@stack('ogs-upload-scripts')
@endsection
