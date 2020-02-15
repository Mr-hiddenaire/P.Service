<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (env('APP_DEBUG')) {
            DB::listen(function($query){
                $dbLogger = new Logger('log');
                $dbLogger->pushHandler(new StreamHandler(storage_path('logs/sql-'.date('Y-m-d').'.log')), Logger::INFO);
                
                $sql = str_replace('?', '%s', $query->sql);
                
                if (!empty($query->bindings)) {
                    $sql = vsprintf($sql, $query->bindings);
                }
                
                $sql .= ' time '.$query->time;
                
                $dbLogger->info($sql);
            });
        }
    }
}
