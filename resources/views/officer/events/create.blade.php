@extends('layouts.app')
@section('title', 'Request an event')

@section('content')
    <div class="page-head">
        <div>
            <h1>Request an event</h1>
            <p>To be hosted by {{ auth()->user()->organization->value }}. The request goes to an admin and in an official letter.</p>
        </div>
    </div>

    <form class="form-card" method="POST" action="{{ route('officer.events.store') }}" enctype="multipart/form-data" novalidate>
        @csrf

        <div class="field {{ $errors->has('title') ? 'has-error' : '' }}">
            <label for="title">Event title</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required autofocus>
            @error('title') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('description') ? 'has-error' : '' }}">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">{{ old('description') }}</textarea>
            @error('description') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('venue') ? 'has-error' : '' }}">
            <label for="venue">Venue</label>
            <input type="text" id="venue" name="venue" value="{{ old('venue') }}" required>
            @error('venue') <div class="error">{{ $message }}</div> @enderror
        </div>

        <fieldset class="schedule-fieldset">
            <legend>Event schedule</legend>
            @php($scheduleDays = old('schedule'))
            @if (! is_array($scheduleDays) || $scheduleDays === [])
                @php($scheduleDays = [['date' => '', 'start_time' => '', 'end_time' => '']])
            @else
                @php($scheduleDays = array_values($scheduleDays))
            @endif
            <div id="schedule-days" class="schedule-days">
                @foreach ($scheduleDays as $index => $day)
                    @php($day = is_array($day) ? $day : [])
                    <div class="schedule-day" data-schedule-row>
                        <div class="schedule-day-head">
                            <strong>Day {{ $loop->iteration }}</strong>
                            <button type="button" class="link-btn danger" data-remove-day>Remove day</button>
                        </div>
                        <div class="schedule-inputs">
                            <div class="field {{ $errors->has("schedule.$index.date") ? 'has-error' : '' }}">
                                <label for="schedule-{{ $index }}-date">Date</label>
                                <input type="date" id="schedule-{{ $index }}-date" name="schedule[{{ $index }}][date]"
                                       min="{{ today()->toDateString() }}" value="{{ $day['date'] ?? '' }}" required>
                                @error("schedule.$index.date") <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div class="field {{ $errors->has("schedule.$index.start_time") ? 'has-error' : '' }}">
                                <label for="schedule-{{ $index }}-start">Start time</label>
                                <input type="time" id="schedule-{{ $index }}-start" name="schedule[{{ $index }}][start_time]"
                                       value="{{ $day['start_time'] ?? '' }}" required>
                                @error("schedule.$index.start_time") <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div class="field {{ $errors->has("schedule.$index.end_time") ? 'has-error' : '' }}">
                                <label for="schedule-{{ $index }}-end">End time</label>
                                <input type="time" id="schedule-{{ $index }}-end" name="schedule[{{ $index }}][end_time]"
                                       value="{{ $day['end_time'] ?? '' }}" required>
                                @error("schedule.$index.end_time") <div class="error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('schedule') <div class="error">{{ $message }}</div> @enderror
            <button type="button" id="add-schedule-day" class="btn btn-quiet btn-sm">Add another day</button>
        </fieldset>

        <div class="row-2">
            <div class="field {{ $errors->has('visibility') ? 'has-error' : '' }}">
                <label for="visibility">Who can join:</label>
                <select id="visibility" name="visibility" required>
                    @foreach ($visibilities as $v)
                        <option value="{{ $v->value }}" @selected(old('visibility', 'org') === $v->value)>{{ $v->label() }}</option>
                    @endforeach
                </select>
                @error('visibility') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field {{ $errors->has('capacity') ? 'has-error' : '' }}">
                <label for="capacity">Student limit (optional):</label>
                <input type="number" id="capacity" name="capacity" min="1" value="{{ old('capacity') }}">
                @error('capacity') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="field {{ $errors->has('purpose') ? 'has-error' : '' }}">
            <label for="purpose">Purpose of the event:</label>
            <textarea id="purpose" name="purpose" rows="5" required>{{ old('purpose') }}</textarea>
            @error('purpose') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field {{ $errors->has('letter_file') ? 'has-error' : '' }}">
            <label for="letter_file">Signed letter (optional)</label>
            <input type="file" id="letter_file" name="letter_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
            @error('letter_file')
                <div class="error">{{ $message }}</div>
            @else
                <div class="hint">Upload a scan if an admin already signed one. PDF, JPG or PNG, up to 5 MB.</div>
            @enderror
        </div>

        <button type="submit" class="btn">Send request and letter</button>
    </form>

    <template id="schedule-day-template">
        <div class="schedule-day" data-schedule-row>
            <div class="schedule-day-head">
                <strong>Day</strong>
                <button type="button" class="link-btn danger" data-remove-day>Remove day</button>
            </div>
            <div class="schedule-inputs">
                <div class="field">
                    <label for="schedule-__INDEX__-date">Date</label>
                    <input type="date" id="schedule-__INDEX__-date" name="schedule[__INDEX__][date]"
                           min="{{ today()->toDateString() }}" required>
                </div>
                <div class="field">
                    <label for="schedule-__INDEX__-start">Start time</label>
                    <input type="time" id="schedule-__INDEX__-start" name="schedule[__INDEX__][start_time]" required>
                </div>
                <div class="field">
                    <label for="schedule-__INDEX__-end">End time</label>
                    <input type="time" id="schedule-__INDEX__-end" name="schedule[__INDEX__][end_time]" required>
                </div>
            </div>
        </div>
    </template>
    <script>
        (function () {
            const days = document.getElementById('schedule-days');
            const template = document.getElementById('schedule-day-template');

            function updateDays() {
                days.querySelectorAll('[data-schedule-row]').forEach(function (row, index) {
                    row.querySelector('strong').textContent = 'Day ' + (index + 1);
                    row.querySelectorAll('[name], [id], label[for]').forEach(function (element) {
                        ['name', 'id', 'for'].forEach(function (attribute) {
                            const value = element.getAttribute(attribute);
                            if (value) element.setAttribute(attribute, value.replace(/schedule\[\d+\]/, 'schedule[' + index + ']')
                                .replace(/schedule-\d+-/, 'schedule-' + index + '-'));
                        });
                    });
                    row.querySelector('[data-remove-day]').disabled = days.children.length === 1;
                });
            }

            document.getElementById('add-schedule-day').addEventListener('click', function () {
                const index = days.children.length;
                days.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
                updateDays();
            });

            days.addEventListener('click', function (event) {
                const button = event.target.closest('[data-remove-day]');
                if (button && days.children.length > 1) {
                    button.closest('[data-schedule-row]').remove();
                    updateDays();
                }
            });

            updateDays();
        })();
    </script>
@endsection
