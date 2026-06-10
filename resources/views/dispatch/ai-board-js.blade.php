@section('scripts')
<script>
    window.AI_BOARD_CONFIG = {
        base  : "{{ config('app.url') }}/admin/api/ai-board",
        csrf  : "{{ csrf_token() }}",
        routes: {
            loadboardLoads  : "{{ route('dashboard.loadboard.loads') }}",
            loadboardDetail : "{{ route('dashboard.loadboard.detail') }}",
            rateIntelligence: "{{ route('dashboard.rate-intelligence') }}"
        }
    };
</script>
<script src="{{ asset('assets/js/ai-board.js') }}?v={{ filemtime(public_path('assets/js/ai-board.js')) }}"></script>
@endsection
