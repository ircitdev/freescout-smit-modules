<?php

return [
    'name' => 'SystemFilter',
    
    /**
     * Автоматическая фильтрация системных bounce-сообщений
     * 
     * Модуль автоматически удаляет тикеты с темами, содержащими
     * ключевые слова bounce-сообщений:
     * - undeliverable
     * - report
     * - failed
     * - delivery
     * - bounce
     * - mailer-daemon
     * и другие
     */
];
