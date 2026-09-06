<?php

use App\Services\FailedIndexingDebtRecorder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('search:reconcile-index')
    ->daily()
    ->onFailure(function () {
        app(FailedIndexingDebtRecorder::class)->record(
            'schedule:search:reconcile-index',
            'O comando agendado de reconciliação do índice RAG falhou — ver logs do container pm_scheduler.'
        );
    });
