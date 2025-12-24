<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>CRM Practical</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
    <div class="container">
      <a class="navbar-brand" href="{{ route('dashboard') }}"> <img src="{{ asset('images/logo.png') }}" alt="Logo" width="100">
</a>
      <div class="ms-auto">
        <a class="btn btn-sm btn-outline-primary" href="{{ route('contacts.index') }}">Manage Contacts</a>
      </div>
      <div class="ms-auto">
        <a class="btn btn-sm btn-outline-primary" href="{{ route('custom-fields.index') }}">Manage Custom Fields</a>
      </div>

            <div class="col-md-1 ms-auto">
                <a class="dropdown-item" href="{{ route('logout') }}"
                   onclick="event.preventDefault();
                                 document.getElementById('logout-form').submit();">
                    {{ __('Logout') }}
                </a>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
    </div>
  </nav>

  <div class="container">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @yield('content')
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>
