<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateSupplyAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:generate-alerts
                            {--silent : Run silently without output}
                            {--skip-email : Skip sending email notifications}
                            {--force : Force regeneration even if alerts exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate inventory alerts for low stock, out of stock, and expiring supplies';

    /**
     * Execute the console command.
     */
    public function handle(AlertService $alertService): int
    {
        $startTime = microtime(true);
        
        if (!$this->option('silent')) {
            $this->info('Starting inventory alert generation...');
        }
        
        $results = [
            'low_stock' => 0,
            'out_of_stock' => 0,
            'expiry' => 0,
            'total' => 0,
        ];
        
        try {
            // Generate low stock alerts
            $lowStockAlerts = $alertService->generateLowStockAlerts();
            $results['low_stock'] = count($lowStockAlerts);
            
            if (!$this->option('silent')) {
                $this->info("Generated {$results['low_stock']} low stock alerts");
            }
            
            // Generate out of stock alerts
            $outOfStockAlerts = $alertService->generateOutOfStockAlerts();
            $results['out_of_stock'] = count($outOfStockAlerts);
            
            if (!$this->option('silent')) {
                $this->info("Generated {$results['out_of_stock']} out of stock alerts");
            }
            
            // Generate expiry alerts
            $expiryAlerts = $alertService->generateExpiryAlerts();
            $results['expiry'] = count($expiryAlerts);
            
            if (!$this->option('silent')) {
                $this->info("Generated {$results['expiry']} expiry alerts");
            }
            
            $results['total'] = $results['low_stock'] + $results['out_of_stock'] + $results['expiry'];
            
            $processingTime = microtime(true) - $startTime;
            
            if (!$this->option('silent')) {
                $this->displayResults($results, $processingTime);
            }
            
            // Log the results
            Log::info('Inventory alerts generated', [
                'low_stock' => $results['low_stock'],
                'out_of_stock' => $results['out_of_stock'],
                'expiry' => $results['expiry'],
                'total' => $results['total'],
                'processing_time' => round($processingTime, 2),
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $errorMessage = "Failed to generate inventory alerts: " . $e->getMessage();
            
            if (!$this->option('silent')) {
                $this->error($errorMessage);
            }
            
            Log::error($errorMessage, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
    
    /**
     * Display results in a formatted table
     */
    private function displayResults(array $results, float $processingTime): void
    {
        $this->line('');
        $this->line(str_repeat('=', 60));
        $this->line('INVENTORY ALERT GENERATION RESULTS');
        $this->line(str_repeat('=', 60));
        $this->line('');
        
        $this->table(
            ['Alert Type', 'Count', 'Status'],
            [
                ['Low Stock', $results['low_stock'], $results['low_stock'] > 0 ? '⚠️ Generated' : '✅ None'],
                ['Out of Stock', $results['out_of_stock'], $results['out_of_stock'] > 0 ? '🔴 Generated' : '✅ None'],
                ['Expiry', $results['expiry'], $results['expiry'] > 0 ? '⚠️ Generated' : '✅ None'],
                ['Total', $results['total'], $results['total'] > 0 ? '📊 Generated' : '✅ Clean'],
            ]
        );
        
        $this->line("Processing Time: " . round($processingTime, 2) . " seconds");
        $this->line("Timestamp: " . now()->format('Y-m-d H:i:s'));
        $this->line('');
        $this->line(str_repeat('=', 60));
    }
    
    /**
     * Schedule the command
     */
    public function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        // Run daily at 8:00 AM
        $schedule->command('inventory:generate-alerts --silent')
                 ->dailyAt('08:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->environments(['production', 'staging']);
                 
        // Run every 6 hours in development for testing
        $schedule->command('inventory:generate-alerts --silent')
                 ->everySixHours()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->environments(['local', 'development']);
    }
}