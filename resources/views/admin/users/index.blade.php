@extends('layouts.app')
@section('title', 'Users')

@section('content')
    <div class="page-head">
        <div>
            <h1>Users</h1>
            <p>Check a student's COR, make students into org officers, and add faculty accounts.</p>
        </div>
        <a class="btn btn-inline" href="{{ route('admin.users.faculty.create') }}">Add faculty</a>
    </div>

    <nav class="tabs" aria-label="Filter by role">
        <a href="{{ route('admin.users.index', array_filter(['q' => $search, 'org' => $org?->value])) }}" @if (! $role) aria-current="page" @endif>
            All<span class="count">{{ $counts->sum() }}</span>
        </a>
        <a href="{{ route('admin.users.index', array_filter(['role' => 'student', 'q' => $search, 'org' => $org?->value])) }}" @if ($role === 'student') aria-current="page" @endif>
            Students<span class="count">{{ $counts['student'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.users.index', array_filter(['role' => 'officer', 'q' => $search, 'org' => $org?->value])) }}" @if ($role === 'officer') aria-current="page" @endif>
            Officers<span class="count">{{ $counts['officer'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.users.index', array_filter(['role' => 'faculty', 'q' => $search])) }}" @if ($role === 'faculty') aria-current="page" @endif>
            Faculty<span class="count">{{ $counts['faculty'] ?? 0 }}</span>
        </a>
    </nav>

    <form class="toolbar" method="GET" action="{{ route('admin.users.index') }}">
        @if ($role) <input type="hidden" name="role" value="{{ $role }}"> @endif
        <div class="field">
            <label for="q">Search</label>
            <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Name, student number or email">
        </div>
        <div class="field">
            <label for="org">Organization</label>
            <select id="org" name="org">
                <option value="">All organizations</option>
                @foreach ($organizations as $o)
                    <option value="{{ $o->value }}" @selected($org === $o)>{{ $o->value }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn">Filter</button>
    </form>

    @if ($users->isEmpty())
        <div class="empty">No users match.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Name</th><th>Organization</th><th>Role</th><th>COR</th><th>Joined</th><th>Action</th></tr>
                </thead>
                <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td>{{ $u->name }}<span class="sub">{{ $u->student_number ?? $u->email }}</span></td>
                        <td>{{ $u->organization?->value }}</td>
                        <td>
                            @if ($u->isOfficer()) <span class="tag tag-live">Officer</span>
                            @elseif ($u->isFaculty()) <span class="tag tag-joined">Faculty</span>
                            @else <span class="tag">Student</span> @endif
                        </td>
                        <td>
                            @if ($u->isFaculty())
                                <span class="sub">Not needed</span>
                            @elseif ($u->cor_path)
                                <a href="{{ route('admin.users.cor', $u) }}" target="_blank" rel="noopener">View COR</a>
                            @else
                                <span class="sub">None</span>
                            @endif
                        </td>
                        <td>{{ $u->created_at->format('M j, Y') }}</td>
                        <td>
                            <div class="cell-actions">
                                @if ($u->isStudent())
                                    <form class="inline-form" method="POST" action="{{ route('admin.users.role', $u) }}"
                                          onsubmit="return confirm('Make {{ addslashes($u->name) }} a {{ $u->organization?->value }} officer?')">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="role" value="officer">
                                        <button type="submit" class="link-btn">Make officer</button>
                                    </form>
                                @elseif ($u->isOfficer())
                                    <form class="inline-form" method="POST" action="{{ route('admin.users.role', $u) }}"
                                          onsubmit="return confirm('Remove officer access from {{ addslashes($u->name) }}?')">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="role" value="student">
                                        <button type="submit" class="link-btn danger">Remove officer</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">
            @if ($users->onFirstPage()) <span class="off">Previous</span>
            @else <a href="{{ $users->previousPageUrl() }}">Previous</a> @endif
            <span>Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>
            @if ($users->hasMorePages()) <a href="{{ $users->nextPageUrl() }}">Next</a>
            @else <span class="off">Next</span> @endif
        </div>
    @endif
@endsection
