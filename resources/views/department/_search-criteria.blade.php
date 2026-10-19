<div class="box-header dp-toolbar">
    <div class="dp-toolbar-left">
        @if ($add)
            <button type="button" class="dp-btn dp-btn-primary" id="btn_add"><i class="fa fa-plus"></i> Add Department</button>
        @endif
        @if ($edit)
            <button type="button" class="dp-btn dp-btn-outline" id="btn_edit" disabled><i class="fa fa-pencil-square-o"></i> Edit</button>
        @endif
        @if ($delete)
            <button type="button" class="dp-btn dp-btn-outline-danger" id="btn_delete" disabled><i class="fa fa-trash-o"></i> Delete</button>
        @endif
        <button type="button" class="dp-btn dp-btn-outline" id="btn_toggleFilters"><i class="fa fa-filter"></i> Filters</button>
        <button type="button" class="dp-btn dp-btn-outline" id="btn_refresh"><i class="fa fa-refresh"></i> Refresh</button>
    </div>
    <div class="dp-toolbar-right">
        <button type="button" class="dp-btn dp-btn-outline" id="btn_export"><i class="fa fa-download"></i> Export Excel</button>
    </div>
</div>
<div class="dp-filter-bar" id="filterBar">
    <div class="sg-filter">
        <label for="filterStatus">Status</label>
        <select class="form-control" id="filterStatus">
            <option value="">All</option>
            <option value="true">Active</option>
            <option value="false">Inactive</option>
        </select>
    </div>
</div>
