@extends('layouts.app')

@section('title', 'Claude AI — Статистика')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title" style="display:flex;align-items:center;gap:8px;justify-content:space-between">
                        <span><span style="font-size:20px">📊</span> Claude AI — Статистика</span>
                        <a href="{{ route('claudeai.settings') }}" class="btn btn-default btn-sm">⬅ Настройки</a>
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        Статистика использования будет доступна после начала работы модуля.
                        Данные собираются из логов Laravel (<code>storage/logs/laravel.log</code>).
                    </div>

                    <p>Для просмотра текущего потребления:</p>
                    <pre>grep "[ClaudeAI] Usage:" storage/logs/laravel.log | tail -20</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
