<div class="modal fade" id="mergeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="mergeForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Merge Contacts</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Master Contact</label>
            <select name="master_id" class="form-control" required>
              <option value="">-- select master --</option>
              @foreach($contacts as $c)
                <option value="{{ $c->id }}">{{ $c->id }} — {{ $c->name }} ({{ $c->email ?? 'no-email' }})</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Secondary Contact</label>
            <select name="secondary_id" class="form-control" required>
              <option value="">-- select secondary --</option>
              @foreach($contacts as $c)
                <option value="{{ $c->id }}">{{ $c->id }} — {{ $c->name }} ({{ $c->email ?? 'no-email' }})</option>
              @endforeach
            </select>
          </div>

          <div class="alert alert-warning small">
            Master contact will remain. Secondary will be marked merged and not deleted.
            If conflicts exist, both values are preserved (master kept, secondary appended).
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-warning" type="submit">Merge</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
    // open merge modal
document.getElementById('openMergeBtn').addEventListener('click', async function(){
  // fetch modal HTML
  const res = await fetch('/merge/modal', { headers: { 'X-CSRF-TOKEN': csrfToken() } });
  const html = await res.text();
  document.getElementById('mergeModalPlaceholder').innerHTML = html;
  const modalEl = document.getElementById('mergeModal');
  const bsModal = new bootstrap.Modal(modalEl);
  bsModal.show();

  document.getElementById('mergeForm').onsubmit = async function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const r = await fetch('/merge', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
      body: fd
    });
    let json;
    try { json = await r.json(); } 
    catch (err) {
      const text = await r.text();
      console.error('Non-JSON response:', text);
      showToast('Server error (see console)', false);
      return;
    }
    if (json.success) {
      showToast(json.message || 'Merged');
      bsModal.hide();
      refreshList(); // re-fetch your contacts list
      // optionally show merged fields summary from json.changes
      console.log('Merge changes:', json.changes);
    } else {
      showToast(json.message || 'Merge failed', false);
    }
  };
});


</script>