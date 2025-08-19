{{-- filepath: c:\Users\ASUS\Documents\GitHub\dash_inventory\resources\views\pages\roles.blade.php --}}
@extends('layouts.vertical', ['title' => 'Roles'])

@section('content')
    <div class="container mt-4">
        <h2>Roles Management</h2>

        <!-- Search Form -->
        <div class="mb-3">
            <div class="input-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name or email"
                    onkeyup="filterTable()">
            </div>
        </div>

        <table class="table" id="rolesTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @if (auth()->id() !== $user->id)
                        <!-- Skip the logged-in user's row -->
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <form method="POST" action="{{ route('roles.update', $user->id) }}"
                                    class="d-flex align-items-center">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" class="form-select form-select-sm me-2">
                                        <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin
                                        </option>
                                        <option value="super admin" {{ $user->role === 'super admin' ? 'selected' : '' }}>
                                            Super Admin</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                </form>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('rolesTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) { // Start from 1 to skip the header row
                const cells = rows[i].getElementsByTagName('td');
                let match = false;

                for (let j = 0; j < cells.length; j++) {
                    if (cells[j]) {
                        const text = cells[j].textContent || cells[j].innerText;
                        if (text.toLowerCase().indexOf(filter) > -1) {
                            match = true;
                            break;
                        }
                    }
                }

                rows[i].style.display = match ? '' : 'none';
            }
        }
    </script>
@endsection
