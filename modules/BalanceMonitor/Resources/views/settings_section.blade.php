<form method="POST" action="{{ route('settings', ['section' => 'balancemonitor']) }}">
    {{ csrf_field() }}

    <div class="form-group">
        <label>OpenAI - Текущий баланс ($)</label>
        <input type="number" step="0.01" name="settings[balancemonitor.openai_balance]"
               class="form-control"
               value="{{ $settings['balancemonitor.openai_balance'] }}"
               placeholder="9.98">
        <p class="help-block">
            Введите текущий баланс с <a href="https://platform.openai.com/settings/organization/billing/overview" target="_blank">platform.openai.com</a>
        </p>
    </div>

    <div class="form-group margin-top">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
    </div>
</form>

<div class="panel panel-default margin-top">
    <div class="panel-heading">Текущие балансы (обновляются каждые 5 мин)</div>
    <div class="panel-body">
        <ul>
            <li><strong>ServerSpace:</strong> ${{ number_format($template_vars['balances']['serverspace']['balance'], 2) }} ({{ $template_vars['balances']['serverspace']['days'] }} дней)</li>
            <li><strong>OpenAI:</strong> ${{ number_format($template_vars['balances']['openai']['balance'], 2) }}</li>
            <li><strong>Mango:</strong> {{ number_format($template_vars['balances']['mango']['balance'], 1) }} руб.</li>
        </ul>
    </div>
</div>
