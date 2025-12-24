@if ($contacts->hasPages())
  <nav>
    <ul class="pagination">
      {{-- Previous Page Link --}}
      @if ($contacts->onFirstPage())
        <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
      @else
        <li class="page-item"><a class="page-link" href="{{ $contacts->previousPageUrl() }}" onclick="fetchPage(event)">«</a></li>
      @endif

      {{-- Next Page Link --}}
      @if ($contacts->hasMorePages())
        <li class="page-item"><a class="page-link" href="{{ $contacts->nextPageUrl() }}" onclick="fetchPage(event)">»</a></li>
      @else
        <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
      @endif
    </ul>
  </nav>

  <script>
  async function fetchPage(e){
    e.preventDefault();
    const url = e.currentTarget.getAttribute('href');
    // convert query params to POST body and call ajaxList route to keep filtering consistent
    const u = new URL(url, window.location.origin);
    const params = new URLSearchParams();
    if(u.searchParams.get('page')) params.set('page', u.searchParams.get('page'));
    // including current filters
    params.set('name', document.getElementById('filterName').value || '');
    params.set('email', document.getElementById('filterEmail').value || '');
    params.set('gender', document.getElementById('filterGender').value || '');
    const res = await fetch(`{{ route('contacts.ajaxList') }}`, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/x-www-form-urlencoded'},
      body: params.toString()
    });
    const json = await res.json();
    if(json.success){
      document.getElementById('contactsTableBody').innerHTML = json.html;
      document.getElementById('paginationWrapper').innerHTML = json.pagination;
    }
  }
  </script>
@endif
