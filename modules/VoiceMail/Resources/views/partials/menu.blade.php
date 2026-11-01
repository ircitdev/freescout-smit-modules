<li class="@if (isset($__selected_menu) && $__selected_menu == 'voicemail')active @endif">
    <a href="{{ route('voicemail.settings') }}">
        <i class="glyphicon glyphicon-earphone"></i> {{ __('VoiceMail') }}
    </a>
</li>
