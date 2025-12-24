@extends('layouts.app')

@section('content')
<!-- Create Contact Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="createForm" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Create Contact</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="createModalBody">
          <!-- Form fields will be loaded via JS -->
        </div>

      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="mergeModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Merge Contact</h5>
      </div>

      <div class="modal-body">
        <input type="hidden" id="secondary_id">

        <label>Select Master Contact</label>
        <select id="master_id" class="form-control">
          @foreach($contacts as $c)
            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
          @endforeach
        </select>

        <div id="mergePreview" class="mt-3"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger" onclick="confirmMerge()">Confirm Merge</button>
      </div>
    </div>
  </div>
</div>

  <div class="col-md-8">
    <h5>Contacts</h5>
<button class="btn btn-success mb-2" onclick="openCreateModal()">Create Contact</button>

    <div class="row g-2 mb-2">
      <div class="col-md-4">
        <input id="filterName" class="form-control filter-input" placeholder="Filter by name">
      </div>
      <div class="col-md-4">
        <input id="filterEmail" class="form-control filter-input" placeholder="Filter by email">
      </div>
      <div class="col-md-3">
        <select id="filterGender" class="form-control filter-input">
          <option value="">All genders</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>
      </div>
    </div>

    <table class="table table-bordered">
      <thead>
        <tr>
          <th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Actions</th>
        </tr>
      </thead>
      <tbody id="contactsTableBody">
        
        @include('contacts.partials.table_rows', ['contacts' => $contacts])
      </tbody>
    </table>

    <div id="paginationWrapper">
      @include('contacts.partials.pagination', ['contacts' => $contacts])
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="editForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Contact</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="editModalBody">

        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
          <button class="btn btn-primary" type="submit">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection


@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
  refreshList();
});
const csrfToken = () => document.querySelector('meta[name="csrf-token"]').content;

// ---------------------------------------------------------
// AJAX helper
// ---------------------------------------------------------
async function postFormData(url, formData) {
  const res = await fetch(url, {
    method: "POST",
    headers: { 'X-CSRF-TOKEN': csrfToken() },
    body: formData
  });
  return res.json();
}

// ---------------------------------------------------------
// Simple toast message
// ---------------------------------------------------------
function showToast(msg, ok = true) {
  const el = document.createElement('div');
  el.className = `toast text-bg-${ok ? 'success':'danger'} border-0 position-fixed top-0 end-0 m-3`;
  el.innerHTML = `
      <div class="d-flex">
         <div class="toast-body">${msg}</div>
         <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
  `;
  document.body.appendChild(el);
  new bootstrap.Toast(el).show();
  setTimeout(() => el.remove(), 4000);
}

// ---------------------------------------------------------
function debounce(fn, ms=300){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }

// ---------------------------------------------------------
// Refresh contact list
// ---------------------------------------------------------
async function refreshList(page = 1) {
  const params = new URLSearchParams({
    name: document.getElementById('filterName').value || '',
    email: document.getElementById('filterEmail').value || '',
    gender: document.getElementById('filterGender').value || '',
    page: page
  });

  const res = await fetch(`{{ route('contacts.ajaxList') }}`, {
    method: "POST",
    headers: { 
      'X-CSRF-TOKEN': csrfToken(),
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: params.toString()
  });

  const json = await res.json();
  if(json.success){
    document.getElementById('contactsTableBody').innerHTML = json.html;
    document.getElementById('paginationWrapper').innerHTML = json.pagination;
  }
}

// Attach filters
document.querySelectorAll('.filter-input').forEach(el =>
  el.addEventListener('input', debounce(refreshList, 400))
);

// ---------------------------------------------------------
// Create Contact
// ---------------------------------------------------------
document.getElementById('contactForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  const res = await postFormData(`{{ route('contacts.store') }}`, formData);
  
  if (res.success) {
    showToast("Contact Saved");
    this.reset();
    refreshList();
  } else {
    showToast("Error saving", false);
  }
});

// Reset button
document.getElementById('resetBtn').onclick = () => {
  document.getElementById('contactForm').reset();
};

// ---------------------------------------------------------
// Delete Contact
// ---------------------------------------------------------
async function deleteContact(id){
  if (!confirm("Delete this contact?")) return;

  const res = await fetch(`/contacts/${id}`, {
    method: "DELETE",
    headers: { 'X-CSRF-TOKEN': csrfToken() }
  });

  const json = await res.json();
  if (json.success){
    showToast(json.message);
    refreshList();
  } else {
    showToast("Delete failed", false);
  }
}
function openCreateModal() {
  fetch(`{{ route('contacts.create') }}`)
    .then(res => res.json())
    .then(json => {
      if (!json.success) {
        showToast("Failed to load create form", false);
        return;
      }

      const { fields } = json.data;

      let html = `
        <form id="createForm" enctype="multipart/form-data">
          <div class="mb-2">
            <label class="form-label">Name*</label>
            <input name="name" class="form-control" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Email</label>
            <input name="email" class="form-control">
          </div>

          <div class="mb-2">
            <label class="form-label">Phone</label>
            <input name="phone" class="form-control">
          </div>

          <div class="mb-2">
            <label class="form-label">Gender</label><br>
            <input type="radio" name="gender" value="male"> Male
            <input type="radio" name="gender" value="female"> Female
            <input type="radio" name="gender" value="other"> Other
          </div>

          <div class="mb-2">
            <label class="form-label">Profile Image</label>
            <input type="file" name="profile_image" class="form-control">
          </div>

          <div class="mb-2">
            <label class="form-label">Additional File</label>
            <input type="file" name="additional_file" class="form-control">
          </div>

          <hr>
          <h6>Custom Fields</h6>
      `;

      fields.forEach(field => {
        html += `<div class="mb-2">
          <label class="form-label">${field.name}</label>`;

        if (field.type === 'text')
          html += `<input class="form-control" name="custom[${field.id}]">`;

        else if (field.type === 'date')
          html += `<input type="date" class="form-control" name="custom[${field.id}]">`;

        else if (field.type === 'file')
          html += `<input type="file" class="form-control" name="custom_files[${field.id}]">`;

        html += `</div>`;
      });

      html += `
          <button class="btn btn-secondary mt-2" data-bs-dismiss="modal" type="button">Close</button>
          <button class="btn btn-primary mt-2" type="submit">Save</button>
        </form>
      `;

      document.getElementById('createModalBody').innerHTML = html;

      const modal = new bootstrap.Modal(document.getElementById('createModal'));
      modal.show();

      // ✅ Bind submit AFTER form exists
      document.getElementById('createForm').addEventListener('submit', submitCreateForm);
    });
}

async function submitCreateForm(e) {
  e.preventDefault();

  const fd = new FormData(e.target);

  const res = await fetch(`{{ route('contacts.store') }}`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken()
    },
    body: fd
  });

  const json = await res.json();

  if (json.success) {
    showToast("Contact created successfully");
    bootstrap.Modal.getInstance(document.getElementById('createModal')).hide();
    refreshList();
  } else {
    showToast("Failed to create contact", false);
  }
}


// ---------------------------------------------------------
// Open Edit Modal
// ---------------------------------------------------------
async function openEdit(id){
  const res = await fetch(`{{ route('contacts.edit', ':id') }}`.replace(':id', id))
  const json = await res.json();

  if(!json.success){
    showToast("Failed to load", false);
    return;
  }

  const { contact, custom } = json.data;

  // Build edit form body
  let html = `
    <input type="hidden" name="contact_id" value="${contact.id}">

    <div class="mb-2"><label class="form-label">Name*</label>
      <input name="name" class="form-control" value="${escapeHtml(contact.name)}">
    </div>

    <div class="mb-2"><label class="form-label">Email</label>
      <input name="email" class="form-control" value="${escapeHtml(contact.email ?? '')}">
    </div>

    <div class="mb-2"><label class="form-label">Phone</label>
      <input name="phone" class="form-control" value="${escapeHtml(contact.phone ?? '')}">
    </div>

    <div class="mb-2"><label class="form-label">Gender</label><br>
      <label class="form-check form-check-inline">
        <input type="radio" name="gender" class="form-check-input" value="male" ${contact.gender==='male'?'checked':''}> Male
      </label>
      <label class="form-check form-check-inline">
        <input type="radio" name="gender" class="form-check-input" value="female" ${contact.gender==='female'?'checked':''}> Female
      </label>
      <label class="form-check form-check-inline">
        <input type="radio" name="gender" class="form-check-input" value="other" ${contact.gender==='other'?'checked':''}> Other
      </label>
    </div>

    <div class="mb-2"><label>Profile Image (replace)</label>
      <input type="file" name="profile_image" class="form-control">
    </div>

    <div class="mb-2"><label>Additional File (replace)</label>
      <input type="file" name="additional_file" class="form-control">
    </div>

    <hr>
    <h6>Custom Fields</h6>
  `;

  @foreach($fields as $field)
    html += `<div class="mb-2">
      <label class="form-label">{{ $field->name }}</label>`;

    @if($field->type === 'text')
      html += `<input class="form-control" name="custom[{{ $field->id }}]" value="${escapeHtml(custom['{{ $field->id }}'] ?? '')}">`;

    @elseif($field->type === 'textarea')
      html += `<textarea class="form-control" name="custom[{{ $field->id }}]">${escapeHtml(custom['{{ $field->id }}'] ?? '')}</textarea>`;

@elseif($field->type === 'date')
  html += `<input
    type="date"
    class="form-control"
    name="custom[{{ $field->id }}]"
    value="${custom['{{ $field->id }}']
      ? custom['{{ $field->id }}'].substring(0,10)
      : ''}"
  >`;


    @elseif($field->type === 'select')
      html += `<select class="form-control" name="custom[{{ $field->id }}]">
                 <option value="">-- select --</option>
                 @foreach($field->options ?? [] as $opt)
                    <option value="{{ $opt }}" ${(custom['{{ $field->id }}'] ?? '') == '{{ $opt }}' ? 'selected' : ''}>{{ $opt }}</option>
                 @endforeach
               </select>`;

    @elseif($field->type === 'file')
      html += `<input type="file" class="form-control" name="custom_files[{{ $field->id }}]">`;

    @endif

    html += `</div>`;
  @endforeach

  document.getElementById('editModalBody').innerHTML = html;

  // Show modal
  new bootstrap.Modal(document.getElementById('editModal')).show();

  // Submit update
  document.getElementById('editForm').onsubmit = async function(e){
    e.preventDefault();

    const fd = new FormData(this);
    fd.append('_method', 'PUT');

    const res = await fetch(`/contacts/${contact.id}`, {
      method: "POST",
      headers: { 'X-CSRF-TOKEN': csrfToken() },
      body: fd
    });

    const json = await res.json();
    if(json.success){
      showToast(json.message);
      refreshList();
      bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
    } else {
      showToast("Update failed", false);
    }
  };
}

// HTML escaping
// function escapeHtml(text="") {
//   return text.replace(/[&<>"']/g, c => ({ "&":"&amp;", "<":"&lt;", ">":"&gt;", '"':"&quot;", "'":"&#39;" }[c]));
// }

function escapeHtml(text = "") {
  if (text === null || text === undefined) return "";
  return String(text).replace(/[&<>"']/g, c => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  }[c]));
}

// function openMergeModal(secondaryId) {
//   document.getElementById('secondary_id').value = secondaryId;
//   new bootstrap.Modal(document.getElementById('mergeModal')).show();
// }
function openMergeModal(secondaryId) {
    document.getElementById('secondary_id').value = secondaryId;

    fetch('/contacts/list-json') // create this route to return all contacts as JSON
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('master_id');
            select.innerHTML = ''; // clear existing options
            data.contacts.forEach(c => {
              console.log(c);
                // if (c.id != secondaryId) { // exclude the contact being merged
                    const option = document.createElement('option');
                    option.value = c.id;
                    option.textContent = `${c.name} (${c.email})`;
                    select.appendChild(option);
                // }
            });

            // show the modal
            const mergeModalEl = document.getElementById('mergeModal');
            const mergeModal = new bootstrap.Modal(mergeModalEl);
            mergeModal.show();
        });
}

</script>
<script>
async function confirmMerge() {
  const masterId = document.getElementById('master_id').value;
  const secondaryId = document.getElementById('secondary_id').value;

  if (masterId == secondaryId) {
    alert("Master and secondary contact cannot be same");
    return;
  }

  const res = await fetch(`/contacts/merge`, {
    method: "POST",
    headers: {
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      master_id: masterId,
      secondary_id: secondaryId
    })
  });

  const json = await res.json();

  if (json.success) {
    // Create modal instance
    const mergeModalEl = document.getElementById('mergeModal');
    const mergeModal = bootstrap.Modal.getInstance(mergeModalEl) || new bootstrap.Modal(mergeModalEl);

    mergeModal.hide(); // ✅ properly hide modal
    showToast(json.message);

    refreshList(); // refresh table to show merged tag
  } else {
    alert(json.message);
  }
}

</script>

@endpush
