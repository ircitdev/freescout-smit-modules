<?php

namespace Modules\SmitBilling\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SmitBillingService
{
    protected $apiUrl;
    protected $apiKey;
    protected $cacheTtl;

    public function __construct()
    {
        $this->apiUrl   = config('smitbilling.api_url', 'https://billing.example.com/rest_api/v2');
        $this->apiKey   = config('smitbilling.api_key', env('AIDA_API_KEY', ''));
        $this->cacheTtl = config('smitbilling.cache_ttl', 60);
    }

    /**
     * Получить данные абонента по номеру телефона из FreeScout Customer
     */
    public function getAbonentByConversation($conversation)
    {
        try {
            $customerId = $conversation->customer_id;
            if (!$customerId) {
                return null;
            }

            // Получаем данные клиента напрямую из БД
            $customerRow = \DB::table('customers')->where('id', $customerId)->first();
            if (!$customerRow) {
                return null;
            }

            // 1. Ищем по телефону (JSON-поле phones)
            $phones = json_decode($customerRow->phones ?? '[]', true) ?: [];
            foreach ($phones as $phoneItem) {
                $phone = $phoneItem['value'] ?? '';
                if ($phone) {
                    $data = $this->getAbonentByPhone($phone);
                    if ($data) {
                        return $data;
                    }
                }
            }

            // 2. Ищем по договору (customer_field_id=1)
            $contract = \DB::table('customer_customer_field')
                ->where('customer_id', $customerId)
                ->where('customer_field_id', 1)
                ->value('value');
            if ($contract) {
                $data = $this->getAbonentByContract($contract);
                if ($data) {
                    return $data;
                }
            }

            // 3. Ищем по email (JSON-поле emails, исключаем .local)
            $emails = json_decode($customerRow->emails ?? '[]', true) ?: [];
            foreach ($emails as $emailItem) {
                $email = $emailItem['value'] ?? '';
                if ($email && strpos($email, '.local') === false) {
                    $data = $this->getAbonentByEmail($email);
                    if ($data) {
                        return $data;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('[SmitBilling] getAbonentByConversation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Получить абонента по телефону (10 цифр без +7)
     */
    public function getAbonentByPhone($phone)
    {
        $phone10 = $this->normalizePhone($phone);
        if (!$phone10) {
            return null;
        }

        $cacheKey = 'smitbilling_phone_' . $phone10;

        return \Cache::remember($cacheKey, $this->cacheTtl, function () use ($phone10) {
            return $this->fetchAbonent(['sms__contains' => $phone10]);
        });
    }

    /**
     * Получить абонента по номеру договора
     */
    public function getAbonentByContract($contract)
    {
        $contract = trim($contract);
        if (!$contract) {
            return null;
        }

        $cacheKey = 'smitbilling_contract_' . $contract;

        return \Cache::remember($cacheKey, $this->cacheTtl, function () use ($contract) {
            return $this->fetchAbonent(['contract_number' => $contract]);
        });
    }

    /**
     * Получить абонента по email
     */
    public function getAbonentByEmail($email)
    {
        $cacheKey = 'smitbilling_email_' . md5($email);

        return \Cache::remember($cacheKey, $this->cacheTtl, function () use ($email) {
            return $this->fetchAbonent(['email' => $email]);
        });
    }

    /**
     * Основной метод: запрос к SMIT Billing API (4 запроса)
     */
    protected function fetchAbonent(array $filter)
    {
        try {
            $client = new Client(['timeout' => 10.0, 'headers' => ['Authorization' => 'Bearer ' . $this->apiKey], 'verify' => (bool) env('SMITBILLING_VERIFY_SSL', true)]);

            // 1. Abonents: основные данные
            $abon_fields = json_encode([
                'name', 'contract_number', 'tarif', 'account_id', 'sms', 'email',
                'enabled', 'disabled_date', 'home', 'a_home_number', 'home_entrance'
            ]);
            $resp1 = $client->post($this->apiUrl . 'Abonents/', [
                'form_params' => [
                    'method1' => 'objects.filter',
                    'arg1'    => json_encode($filter),
                    'fields'  => $abon_fields,
                ]
            ]);

            $data1   = json_decode($resp1->getBody()->getContents(), true);
            $results = $data1['result'] ?? [];
            if (empty($results)) {
                return null;
            }

            $abon   = $results[0];
            $fields = $abon['fields'] ?? [];
            $pk     = $abon['pk'] ?? null;

            // ФИО
            $fullname = trim($fields['name'] ?? '');
            $nameParts = explode(' ', $fullname);
            $surname   = $nameParts[0] ?? '';
            $firstname = $nameParts[1] ?? '';

            // Тариф
            $tariff = '';
            if (!empty($fields['tarif']) && is_array($fields['tarif'])) {
                $tariff = $fields['tarif']['fields']['name'] ?? '';
            }

            // Адрес
            $address = '';
            if (!empty($fields['home']) && is_array($fields['home'])) {
                $homeFields = $fields['home']['fields'] ?? [];
                $parts = array_filter([
                    $homeFields['city']   ?? '',
                    $homeFields['street'] ?? '',
                    $homeFields['s_number'] ? 'д. ' . $homeFields['s_number'] : '',
                ]);
                $address = implode(', ', $parts);
            }
            if (!empty($fields['home_entrance'])) {
                $address .= ', кор. ' . $fields['home_entrance'];
            }
            if (!empty($fields['a_home_number'])) {
                $address .= ', кв. ' . $fields['a_home_number'];
            }

            // Статус
            $enabled      = $fields['enabled'] ?? true;
            $disabledDate = $fields['disabled_date'] ?? null;

            $accountId = $fields['account_id'] ?? null;

            // 2. AdminAccounts: баланс
            $balance = null;
            $limit   = null;
            if ($accountId) {
                try {
                    $resp2 = $client->post($this->apiUrl . 'AdminAccounts/', [
                        'form_params' => [
                            'method1' => 'objects.get',
                            'arg1'    => json_encode(['id' => $accountId]),
                            'fields'  => json_encode(['balance', 'limit', 'limit_warning']),
                        ]
                    ]);
                    $data2   = json_decode($resp2->getBody()->getContents(), true);
                    $acc     = $data2['result'][0] ?? null;
                    if ($acc) {
                        $balance = $acc['fields']['balance'] ?? null;
                        $limit   = $acc['fields']['limit'] ?? null;
                    }
                } catch (\Exception $e) {
                    \Log::error('[SmitBilling] AdminAccounts error: ' . $e->getMessage());
                }
            }

            // 3. UsersUsluga: услуги
            $services = [];
            if ($pk) {
                try {
                    $resp3 = $client->post($this->apiUrl . 'UsersUsluga/', [
                        'form_params' => [
                            'method1' => 'objects.filter',
                            'arg1'    => json_encode(['abonent' => $pk]),
                            'fields'  => json_encode(['usluga__name', 'enabled', 'next_date']),
                        ]
                    ]);
                    $data3 = json_decode($resp3->getBody()->getContents(), true);
                    foreach ($data3['result'] ?? [] as $svc) {
                        $svcFields = $svc['fields'] ?? [];
                        $name = $svcFields['usluga__name'] ?? '';
                        if ($name) {
                            $services[] = [
                                'name'      => $name,
                                'enabled'   => (bool)($svcFields['enabled'] ?? false),
                                'next_date' => $svcFields['next_date'] ?? null,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('[SmitBilling] UsersUsluga error: ' . $e->getMessage());
                }
            }

            // 4. FinanceOperations: последние 5 операций
            $payments = [];
            if ($pk) {
                try {
                    $resp4 = $client->post($this->apiUrl . 'FinanceOperations/', [
                        'form_params' => [
                            'method1' => 'objects.filter',
                            'arg1'    => json_encode(['abonent_id' => $pk]),
                            'fields'  => json_encode(['op_summa', 'op_date', 'descr', 'op_type_id']),
                            'order_by' => '-op_date',
                            'limit'    => 5,
                        ]
                    ]);
                    $data4 = json_decode($resp4->getBody()->getContents(), true);
                    foreach ($data4['result'] ?? [] as $op) {
                        $opFields = $op['fields'] ?? [];
                        $payments[] = [
                            'amount' => $opFields['op_summa'] ?? 0,
                            'date'   => $opFields['op_date'] ?? '',
                            'descr'  => $opFields['descr'] ?? '',
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::error('[SmitBilling] FinanceOperations error: ' . $e->getMessage());
                }
            }

            return [
                'pk'           => $pk,
                'fullname'     => $fullname,
                'surname'      => $surname,
                'firstname'    => $firstname,
                'contract'     => $fields['contract_number'] ?? '',
                'phone'        => $fields['sms'] ?? '',
                'email'        => $fields['email'] ?? '',
                'tariff'       => $tariff,
                'address'      => $address,
                'balance'      => $balance !== null ? number_format((float)$balance, 2, '.', '') : null,
                'limit'        => $limit,
                'enabled'      => $enabled,
                'disabled_date'=> $disabledDate,
                'services'     => $services,
                'payments'     => $payments,
            ];

        } catch (\Exception $e) {
            \Log::error('[SmitBilling] fetchAbonent error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Нормализация телефона → 10 цифр (без +7)
     */

    protected function getCustomerNotes($email, $phone)
    {
        try {
            if ($email) {
                $customer = \DB::table('customers')
                    ->join('emails', 'customers.id', '=', 'emails.customer_id')
                    ->where('emails.email', $email)
                    ->select('customers.notes')
                    ->first();
                if ($customer && $customer->notes) {
                    return $customer->notes;
                }
            }
        } catch (\Exception $e) {
            \Log::error('[SmitBilling] getCustomerNotes: ' . $e->getMessage());
        }
        return '';
    }
    protected function normalizePhone($phone)
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) == 11 && ($clean[0] == '7' || $clean[0] == '8')) {
            return substr($clean, 1);
        }
        if (strlen($clean) == 10) {
            return $clean;
        }
        return null;
    }
}
