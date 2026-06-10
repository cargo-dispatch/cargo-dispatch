@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{ asset('assets/js/sweetalert.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert-utils.js') }}"></script>
<script src="{{ asset('assets/js/pagination-utils.js') }}"></script>
<script src="{{ asset('assets/js/time-date-format.js') }}"></script>
<script src="{{ asset('assets/js/detail-modal.js') }}?v={{ time() }}"></script>
<script>
    window.SHIPMENT_CONFIG = {
        loadRoute     : "{{ route('shipments.get') }}",
        destroyRoute  : "{{ route('shipments.destroy', ':id') }}",
        bulkDestroyRoute: "{{ route('shipments.bulk-destroy') }}",
    };
</script>
<script src="{{ asset('assets/js/shipments.js') }}?v={{ time() }}"></script>
@endsection
