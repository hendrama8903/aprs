{{-- Exchange rate master: kurs mata uang asing ke IDR per periode, add/edit/delete manual, export Excel. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('exchange-rate._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('exchange-rate._popup-form')
@endsection

@section('scripts')
@include('exchange-rate._scripts')
@endsection
