{{-- Info upload OGS terakhir di toolbar kanan. Parameter: $lastImport (object|null, dari HandlesOgsUpload::lastOgsImport). --}}
<span class="ogs-last" title="Last upload from OGS">
    <i class="fa fa-clock-o"></i> Last upload:
    @if ($lastImport)
        <b>{{ $lastImport->CreatedDate->format('d-m-Y H:i') }}</b> by {{ $lastImport->CreatedBy }}
    @else
        <b class="text-red">never</b>
    @endif
</span>
