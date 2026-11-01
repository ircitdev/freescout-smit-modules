<?php

namespace Modules\AidaSessions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AidaSessionsController extends Controller
{
    /**
     * List all conversations with stats, filtered by date range and filters.
     */
    public function index(Request $request)
    {
        // Date range: preset or custom
        $period = $request->input('period', 'week');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($period === 'custom' && $dateFrom && $dateTo) {
            $from = $dateFrom . ' 00:00:00';
            $to = $dateTo . ' 23:59:59';
        } else {
            switch ($period) {
                case 'day':
                    $from = date('Y-m-d 00:00:00');
                    $to = date('Y-m-d 23:59:59');
                    break;
                case 'month':
                    $from = date('Y-m-01 00:00:00');
                    $to = date('Y-m-d 23:59:59');
                    break;
                case 'week':
                default:
                    $from = date('Y-m-d 00:00:00', strtotime('-6 days'));
                    $to = date('Y-m-d 23:59:59');
                    $period = 'week';
                    break;
            }
            $dateFrom = date('Y-m-d', strtotime($from));
            $dateTo = date('Y-m-d', strtotime($to));
        }

        // Filters
        $filterProvider = $request->input('provider', '');
        $filterType = $request->input('type', '');
        $filterLinked = $request->input('linked', '');

        $query = DB::table('aida_conversations')
            ->whereBetween('started_at', [$from, $to]);

        if ($filterProvider === 'smit') {
            $query->where('provider', 'СМИТ');
        } elseif ($filterProvider === 'robor') {
            $query->where('provider', 'РОБОР');
        }

        if ($filterType === 'voice') {
            $query->where('is_voice', 1);
        } elseif ($filterType === 'text') {
            $query->where(function ($q) {
                $q->where('is_voice', 0)->orWhereNull('is_voice');
            });
        }

        if ($filterLinked === 'ticket') {
            $query->where('ticket_created', 1);
        } elseif ($filterLinked === 'lead') {
            $query->where('lead_created', 1);
        } elseif ($filterLinked === 'none') {
            $query->where(function ($q) {
                $q->where('ticket_created', 0)->orWhereNull('ticket_created');
            })->where(function ($q) {
                $q->where('lead_created', 0)->orWhereNull('lead_created');
            });
        }

        $stats = (clone $query)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN lead_created = 1 THEN 1 ELSE 0 END) as leads,
                SUM(CASE WHEN ticket_created = 1 THEN 1 ELSE 0 END) as tickets,
                SUM(CASE WHEN DATE(started_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN has_error = 1 THEN 1 ELSE 0 END) as errors,
                SUM(CASE WHEN branch = 'support' THEN 1 ELSE 0 END) as support,
                SUM(CASE WHEN branch = 'connection' THEN 1 ELSE 0 END) as connection,
                SUM(CASE WHEN is_voice = 1 THEN 1 ELSE 0 END) as voice,
                SUM(CASE WHEN is_voice = 0 OR is_voice IS NULL THEN 1 ELSE 0 END) as text_count,
                SUM(CASE WHEN provider = 'СМИТ' THEN 1 ELSE 0 END) as smit,
                SUM(CASE WHEN provider = 'РОБОР' THEN 1 ELSE 0 END) as robor
            ")
            ->first();

        $conversations = (clone $query)
            ->orderBy('started_at', 'desc')
            ->limit(500)
            ->get();

        return view('aidasessions::index', compact(
            'stats', 'conversations', 'period', 'dateFrom', 'dateTo',
            'filterProvider', 'filterType', 'filterLinked'
        ));
    }

    /**
     * Show conversation detail with messages.
     */
    public function show($id)
    {
        $conversation = DB::table('aida_conversations')->where('id', $id)->first();

        if (!$conversation) {
            \Session::flash('flash_error', 'Диалог не найден');
            return redirect()->route('aidasessions.index');
        }

        $messages = DB::table('aida_messages')
            ->where('conversation_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Get FreeScout customer_id via linked ticket
        $customerId = null;
        if ($conversation->ticket_id) {
            $customerId = DB::table('conversations')
                ->where('id', $conversation->ticket_id)
                ->value('customer_id');
        }

        return view('aidasessions::show', compact('conversation', 'messages', 'customerId'));
    }

    /**
     * Download conversation as .md file.
     */
    public function download($id)
    {
        $conv = DB::table('aida_conversations')->where('id', $id)->first();

        if (!$conv) {
            \Session::flash('flash_error', 'Диалог не найден');
            return redirect()->route('aidasessions.index');
        }

        $messages = DB::table('aida_messages')
            ->where('conversation_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        $branchNames = ['support' => 'Поддержка', 'connection' => 'Подключение'];

        $md = "# Диалог #{$conv->id}\n\n";
        $md .= "**Дата:** {$conv->started_at}\n";
        $md .= "**Клиент:** " . ($conv->client_name ?: 'Аноним') . "\n";
        $md .= "**Телефон:** " . ($conv->client_phone ?: '-') . "\n";
        $md .= "**Ветка:** " . ($branchNames[$conv->branch] ?? '-') . "\n";
        $md .= "**Статус:** {$conv->status}\n";
        $md .= "**Провайдер:** " . ($conv->provider ?: '-') . "\n";
        $md .= "**Тип:** " . ($conv->is_voice ? 'Голосовой' : 'Текстовый') . "\n";
        if ($conv->lead_created) {
            $md .= "**Лид:** #{$conv->lead_id}\n";
        }
        if ($conv->ticket_created) {
            $md .= "**Тикет:** #{$conv->ticket_id}\n";
        }
        if ($conv->has_error) {
            $md .= "**Ошибка:** {$conv->error_message}\n";
        }
        $md .= "\n---\n\n## Сообщения\n\n";

        foreach ($messages as $msg) {
            $role = $msg->role === 'user' ? 'Клиент' : 'Бот';
            $time = date('H:i', strtotime($msg->created_at));
            $md .= "**{$role}** ({$time}):\n{$msg->content}\n\n";
        }

        return response($md, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="dialog_' . $id . '.md"',
        ]);
    }

    /**
     * Delete a conversation (admin only, no linked ticket).
     */
    public function delete($id)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            \Session::flash('flash_error', 'Удаление доступно только администраторам');
            return redirect()->route('aidasessions.index');
        }

        $conv = DB::table('aida_conversations')->where('id', $id)->first();
        if ($conv && $conv->ticket_created && $conv->ticket_id) {
            \Session::flash('flash_error', 'Нельзя удалить диалог #' . $id . ' — к нему привязан тикет #' . $conv->ticket_id);
            return redirect()->route('aidasessions.index');
        }

        DB::table('aida_messages')->where('conversation_id', $id)->delete();
        DB::table('aida_conversations')->where('id', $id)->delete();

        \Session::flash('flash_success', 'Диалог #' . $id . ' удалён');
        return redirect()->route('aidasessions.index');
    }
}
