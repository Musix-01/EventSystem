@extends('layouts.app')
@section('title', 'Letters')

@section('content')
    <div class="page-head">
        <div>
            <h1>Letters</h1>
            <p>Request letters sent by org officers. Open one to read and print it.</p>
        </div>
    </div>

    @if ($letters->isEmpty())
        <div class="empty">No letters yet. They arrive with every event request.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Received</th><th>Event</th><th>From</th><th>Request status</th><th>Action</th></tr>
                </thead>
                <tbody>
                @foreach ($letters as $letter)
                    <tr>
                        <td>{{ $letter->created_at->format('M j, Y') }}<span class="sub">{{ $letter->created_at->format('g:i A') }}</span></td>
                        <td>
                            <a href="{{ route('letters.show', $letter) }}">{{ $letter->event->title }}</a>
                            @if ($letter->isUnread()) <span class="mark-new">NEW</span> @endif
                            <span class="sub">{{ $letter->event->organization->value }}, {{ $letter->event->starts_at->format('M j, g:i A') }}</span>
                        </td>
                        <td>{{ $letter->sender->name }}<span class="sub">{{ $letter->sender->student_number }}</span></td>
                        <td><span class="status status-{{ $letter->event->status->value }}">{{ $letter->event->status->label() }}</span></td>
                        <td>
                            <div class="cell-actions">
                                <a href="{{ route('letters.show', $letter) }}">Open and print</a>
                                @if ($letter->attachment_path)
                                    <a href="{{ route('letters.attachment', $letter) }}" target="_blank" rel="noopener">Signed copy</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
