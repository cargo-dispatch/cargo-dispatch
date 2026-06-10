@props(['route', 'id', 'useAjax' => false])

<form action="{{ $route }}" method="POST" class="delete-form {{ $useAjax ? 'ajax-delete' : '' }} button-delete-form" data-id="{{ $id }}">
    @csrf
    @method('DELETE')
    <button type="button" class="btn btn-link text-danger p-0 delete-button" title="Delete" data-id="{{ $id }}">
        <i class="bi bi-trash"></i>
    </button>
</form>