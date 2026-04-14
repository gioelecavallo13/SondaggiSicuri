@if($paginator->hasPages())
    <nav class="d-flex justify-content-center mt-4 sm-public-surveys-pagination" aria-label="Paginazione sondaggi">
        {{ $paginator->links('pagination::bootstrap-5') }}
    </nav>
@endif
