@extends('layouts.admin')

@section('content')
<div class="box-header dp-toolbar">
    <div class="dp-toolbar-left">
        @if ($add)
            <button type="button" class="dp-btn dp-btn-primary" id="btn_add" title="Add under the selected row"><i class="fa fa-plus"></i> <span id="btnAddLabel">Add Menu</span></button>
        @endif
        @if ($edit)
            <button type="button" class="dp-btn dp-btn-outline" id="btn_edit" disabled><i class="fa fa-pencil-square-o"></i> Edit</button>
        @endif
        @if ($delete)
            <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_delete" disabled><i class="fa fa-trash-o"></i> Delete</button>
        @endif
        <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
    </div>
    <div class="dp-toolbar-right">
        <button type="button" class="dp-btn dp-btn-outline" id="btn_expand"><i class="fa fa-plus-square-o"></i> Expand All</button>
        <button type="button" class="dp-btn dp-btn-outline" id="btn_collapse"><i class="fa fa-minus-square-o"></i> Collapse All</button>
        <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Export Excel</button>
    </div>
</div>
<section class="content">
    <div class="dp-card">
        <div id="divGrid">
            @include('menu._grid')
        </div>
        <div class="mn-note">
            <i class="fa fa-info-circle"></i>
            <b>Menu</b> = group in the sidebar, <b>Screen</b> = page with a URL, <b>Button</b> = action permission inside a screen (e.g. ITEM-ADD).
            New menus/buttons are granted to the Administrator role automatically; grant them to other roles in Role &amp; Access.
            Changes appear after users log in again.
        </div>
    </div>
</section>
@include('menu._popup-form')
@endsection

@section('scripts')
@include('menu._scripts')
@endsection
