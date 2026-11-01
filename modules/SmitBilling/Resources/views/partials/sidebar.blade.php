<div class="conv-sidebar-block">
    <div class="panel-group accordion">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href="#cb-billing-panel">
                        <i class="glyphicon glyphicon-stats" style="color:#43b77a;margin-right:5px;font-size:11px"></i>Биллинг
                    </a>
                </h4>
            </div>
            <div id="cb-billing-panel" class="panel-collapse collapse in">
                <div class="panel-body" style="padding: 8px 12px; font-size: 12px;">

                    @if(!empty($billing))

                        @php
                            $statusEnabled = $billing["enabled"];
                            $statusIcon  = $statusEnabled ? "glyphicon-ok-circle" : "glyphicon-ban-circle";
                            $statusIconColor = $statusEnabled ? "#43b77a" : "#d11";
                            $statusLabel = $statusEnabled ? "Активен" : "Заблокирован";
                            if (!$statusEnabled && !empty($billing["disabled_date"])) {
                                $statusLabel .= " с " . $billing["disabled_date"];
                            }
                            $balanceVal = $billing["balance"] !== null ? (float)$billing["balance"] : null;
                            $balanceColor = ($balanceVal !== null && $balanceVal < 0) ? "color:#d11;font-weight:bold" : "color:#43b77a;font-weight:bold";
                            // единый стиль ячейки-иконки: фикс. ширина, приглушённый цвет, выравнивание сверху
                            $icoTd = "width:18px;color:#9aa3ad;vertical-align:top;padding:1px 0;text-align:center";
                            $valTd = "padding:1px 0 1px 4px;vertical-align:top";
                        @endphp

                        <table style="width:100%; border-collapse:collapse; line-height:1.55">
                            <tr>
                                <td style="{{ $icoTd }}"><i class="glyphicon glyphicon-file"></i></td>
                                <td style="{{ $valTd }}">Договор: <strong>{{ $billing["contract"] ?: "—" }}</strong> · Чей: <strong>СМИТ</strong></td>
                            </tr>
                            <tr>
                                <td style="{{ $icoTd }}"><i class="glyphicon {{ $statusIcon }}" style="color:{{ $statusIconColor }}"></i></td>
                                <td style="{{ $valTd }}">{{ $statusLabel }}</td>
                            </tr>
                            @if($balanceVal !== null)
                            <tr>
                                <td style="{{ $icoTd }}"><i class="glyphicon glyphicon-ruble"></i></td>
                                <td style="{{ $valTd }}">Баланс: <span style="{{ $balanceColor }}">{{ $billing["balance"] }} ₽</span></td>
                            </tr>
                            @endif
                            @if($billing["tariff"])
                            <tr>
                                <td style="{{ $icoTd }}"><i class="glyphicon glyphicon-tags"></i></td>
                                <td style="{{ $valTd }}">{{ $billing["tariff"] }}</td>
                            </tr>
                            @endif
                            @if(!empty($billing["notes"]))
                            <tr>
                                <td colspan="2" style="padding-top:6px">
                                    @php
                                        $noteText = $billing["notes"];
                                        $noteDate = '';
                                        if (preg_match('/^\[(.+?)\]\s*/', $noteText, $m)) {
                                            $noteDate = $m[1];
                                            $noteText = trim(substr($noteText, strlen($m[0])));
                                        }
                                    @endphp
                                    <div style="background:linear-gradient(135deg,#34C759,#30B350);color:#fff;border-radius:12px 12px 2px 12px;padding:6px 10px;font-size:11px;line-height:1.4;max-width:95%;display:inline-block;box-shadow:0 1px 2px rgba(0,0,0,.1)">
                                        {{ $noteText }}
                                    </div>
                                    @if($noteDate)
                                    <div style="font-size:9px;color:#aaa;margin-top:1px;text-align:right">{{ $noteDate }}</div>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @if($billing["address"])
                            <tr>
                                <td style="{{ $icoTd }}"><i class="glyphicon glyphicon-home"></i></td>
                                <td style="{{ $valTd }}">{{ $billing["address"] }}</td>
                            </tr>
                            @endif
                            @if($billing["phone"])
                            <tr>
                                <td style="{{ $icoTd }}"><i class="glyphicon glyphicon-earphone"></i></td>
                                <td style="{{ $valTd }}">{{ $billing["phone"] }}</td>
                            </tr>
                            @endif
                        </table>

                        @if(!empty($billing["services"]))
                            <hr style="margin:8px 0">
                            <div style="color:#6c757d;margin-bottom:3px;font-weight:600;display:flex;align-items:center;gap:5px">
                                <i class="glyphicon glyphicon-wrench" style="font-size:11px"></i>Услуги
                            </div>
                            @foreach($billing["services"] as $svc)
                                <div style="padding-left:4px;display:flex;align-items:center;gap:5px">
                                    <i class="glyphicon {{ $svc["enabled"] ? 'glyphicon-ok' : 'glyphicon-remove' }}" style="font-size:10px;color:{{ $svc['enabled'] ? '#43b77a' : '#d11' }}"></i>
                                    <span>{{ $svc["name"] }}</span>
                                </div>
                            @endforeach
                        @endif

                        @if(!empty($billing["payments"]))
                            <hr style="margin:8px 0">
                            {{-- SmIT: Операции — аккордеон, раскрывается по клику --}}
                            <a data-toggle="collapse" href="#cb-ops-{{ $conversation->id }}"
                               class="smit-ops-toggle collapsed"
                               style="display:flex;align-items:center;gap:6px;color:#43b77a;text-decoration:none;font-weight:600;cursor:pointer;margin-bottom:3px">
                                <i class="glyphicon glyphicon-menu-right smit-ops-caret" style="transition:transform .2s;font-size:9px"></i>
                                <i class="glyphicon glyphicon-list-alt" style="font-size:11px"></i>Операции
                                <span style="color:#888;font-weight:normal;font-size:11px">({{ count($billing["payments"]) }})</span>
                            </a>
                            <div id="cb-ops-{{ $conversation->id }}" class="collapse">
                            @foreach($billing["payments"] as $pay)
                                @php
                                    $amt = (float)($pay["amount"] ?? 0);
                                    $amtColor = $amt >= 0 ? "color:#2e7d32" : "color:#a11";
                                    $amtStr = ($amt >= 0 ? "+" : "") . number_format($amt, 0, ".", "") . " ₽";
                                    $dateStr = substr($pay["date"] ?? "", 0, 10);
                                    $descr = $pay["descr"] ?? "";
                                @endphp
                                <div style="padding-left:4px; line-height:1.5">
                                    <span style="{{ $amtColor }}; font-weight:bold">{{ $amtStr }}</span>
                                    <span style="color:#888"> {{ $dateStr }}</span>
                                    @if($descr)
                                        <span style="color:#aaa"> {{ $descr }}</span>
                                    @endif
                                </div>
                            @endforeach
                            </div>
                        @endif

                        {{-- SmIT: круглые иконки-кнопки (как cog/envelope в сайдбаре) + подпись --}}
                        <div style="margin-top:8px; display:flex; gap:14px; align-items:center">
                            @if(!empty($billing["pk"]))
                            <a href="https://billing.example.com/admin/Abonents/{{ $billing["pk"] }}/" target="_blank" rel="noopener"
                               class="smit-bill-icon" title="Открыть карточку абонента в биллинге"
                               style="display:inline-flex;flex-direction:column;align-items:center;gap:3px;text-decoration:none;color:#43b77a;font-size:11px">
                                <span style="width:34px;height:34px;border:1px solid #43b77a;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;transition:all .2s">
                                    <i class="glyphicon glyphicon-share-alt"></i>
                                </span>
                                в биллинг
                            </a>
                            @endif
                            <a href="#" class="cb-refresh-btn smit-bill-icon" data-conv-id="{{ $conversation->id }}"
                               title="Обновить данные из биллинга"
                               style="display:inline-flex;flex-direction:column;align-items:center;gap:3px;text-decoration:none;color:#43b77a;font-size:11px">
                                <span style="width:34px;height:34px;border:1px solid #43b77a;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;transition:all .2s">
                                    <i class="glyphicon glyphicon-refresh"></i>
                                </span>
                                Обновить
                            </a>
                        </div>

                    @else
                        <div style="color:#aaa; font-style:italic">
                            Клиент не найден в биллинге
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
