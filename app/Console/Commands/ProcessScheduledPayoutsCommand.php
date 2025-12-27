<?php

namespace App\Console\Commands;

use App\Domains\Payment\Services\PayoutScheduler;
use Illuminate\Console\Command;

class ProcessScheduledPayoutsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payouts:process
                            {--schedule : Schedule new payouts for eligible providers}
                            {--process : Process due scheduled payouts}
                            {--batch= : Process a specific batch ID}
                            {--all : Both schedule and process payouts}';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled provider payouts';

    public function __construct(
        protected PayoutScheduler $payoutScheduler
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $all = $this->option('all');
        $schedule = $this->option('schedule') || $all;
        $process = $this->option('process') || $all;
        $batchId = $this->option('batch');

        if ($batchId) {
            return $this->processBatch($batchId);
        }

        if (! $schedule && ! $process) {
            $this->error('Please specify --schedule, --process, --all, or --batch=<id>');

            return self::FAILURE;
        }

        $scheduledCount = 0;
        $processedCount = 0;

        if ($schedule) {
            $this->info('Scheduling payouts for eligible providers...');
            $scheduledCount = $this->payoutScheduler->schedulePayouts();
            $this->info("Scheduled {$scheduledCount} new payouts.");
        }

        if ($process) {
            $this->info('Processing due payouts...');
            $processedCount = $this->payoutScheduler->processScheduledPayouts();
            $this->info("Processed {$processedCount} payouts.");
        }

        $this->newLine();
        $this->table(
            ['Action', 'Count'],
            [
                ['Scheduled', $scheduledCount],
                ['Processed', $processedCount],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Process a specific batch of payouts.
     */
    protected function processBatch(string $batchId): int
    {
        $this->info("Processing batch: {$batchId}");

        $results = $this->payoutScheduler->processBatch($batchId);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total', $results['total']],
                ['Processed', $results['processed']],
                ['Failed', $results['failed']],
            ]
        );

        if ($results['failed'] > 0) {
            $this->warn("{$results['failed']} payouts failed. Check logs for details.");

            return self::FAILURE;
        }

        $this->info('Batch processed successfully.');

        return self::SUCCESS;
    }
}
