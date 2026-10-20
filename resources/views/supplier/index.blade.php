{{-- Supplier master: add/edit/delete manual, export Excel. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('supplier._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('supplier._popup-form')
@endsection

@section('scripts')
@include('supplier._scripts')
@endsection
