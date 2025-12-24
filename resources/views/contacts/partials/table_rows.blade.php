<!-- @foreach($contacts as $c)
<tr>
  <td>{{ $c->id }}</td>
  <td>{{ $c->name }}</td>
  <td>{{ $c->email }}</td>
  <td>{{ $c->phone }}</td>
  <td>{{ ucfirst($c->gender) }}</td>
  <td style="min-width:140px">
    <button class="btn btn-sm btn-primary" onclick="openEdit({{ $c->id }})">Edit</button>
    <button class="btn btn-sm btn-danger" onclick="deleteContact({{ $c->id }})">Delete</button>
  </td>
</tr>

@endforeach -->

@foreach($contacts as $c)
<tr class="{{ !$c->is_active ? 'table-warning' : '' }}">
    <td>{{ $c->id }}</td>

    <td>
        {{ $c->name }}

        @if($c->is_active == '1')
            <span class="badge bg-warning ms-1">Merged</span>
        @endif
    </td>

    <td>{{ $c->email }}</td>
    <td>{{ $c->phone }}</td>
    <td>{{ ucfirst($c->gender) }}</td>

    <td style="min-width:180px">
        @if($c->is_active == '0')
            <button class="btn btn-sm btn-warning"
                    onclick="openMergeModal({{ $c->id }})">
                Merge
            </button>
        @endif

        <button class="btn btn-sm btn-primary"
                onclick="openEdit({{ $c->id }})">
            Edit
        </button>

        <!-- <button class="btn btn-sm btn-danger"
                onclick="deleteContact({{ $c->id }})">
            Delete
        </button> -->
    @if($c->is_active == '0')
      <button class="btn btn-sm btn-danger" onclick="deleteContact({{ $c->id }})">
        Delete
      </button>
    @endif
    </td>
</tr>
@endforeach
