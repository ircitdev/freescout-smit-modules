@extends('layouts.app')

@section('title_full', 'Balance Monitor - Настройки')

@section('body_class', 'sidebar-no-height')

@section('content')
<div class="section-heading">Balance Monitor</div>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Настройки модуля</div>
                <div class="panel-body">
                    <form method="POST" action="{{ route('balancemonitor.settings.save') }}">
                        {{ csrf_field() }}
                        
                        <div class="form-group">
                            <label>OpenAI - Текущий баланс ($)</label>
                            <input type="number" step="0.01" name="openai_balance" 
                                   class="form-control" 
                                   value="{{ $settings['openai_balance'] }}"
                                   placeholder="9.98">
                            <p class="help-block">
                                Введите текущий баланс с <a href="https://platform.openai.com/settings/organization/billing/overview" target="_blank">platform.openai.com</a>
                            </p>
                        </div>
                        
                        <div class="form-group margin-top">
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                            <a href="{{ route('modules') }}" class="btn btn-link">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
