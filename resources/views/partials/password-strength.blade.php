{{-- Needs $for: the id of the password input to watch. Styled by css/password.css, driven by js/password.js. --}}
<div class="pw-strength" data-strength data-input="{{ $for }}" data-level="0">
    <div class="pw-head">
        <span>Password strength</span>
        <span class="pw-status" data-status aria-live="polite">Not entered</span>
    </div>

    <div class="pw-bars" aria-hidden="true"><i></i><i></i><i></i></div>

    <ul class="pw-rules">
        <li data-rule="length"><span class="pw-dot" aria-hidden="true"></span>Must be at least 8 characters<span class="sr-only" data-sr> (not yet)</span></li>
        <li data-rule="upper"><span class="pw-dot" aria-hidden="true"></span>At least one uppercase letter<span class="sr-only" data-sr> (not yet)</span></li>
        <li data-rule="number"><span class="pw-dot" aria-hidden="true"></span>At least one number<span class="sr-only" data-sr> (not yet)</span></li>
    </ul>
</div>
