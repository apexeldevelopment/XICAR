<?php

namespace App\Jobs;

use App\Traits\LocationTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\Api\DriverController;

class ProcessLocationApi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LocationTrait;

    protected $request_data;
    protected $driver;
    protected $string_file;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request, $driver, $string_file)
    {
        //
        $this->request_data = $request->all();
        $this->driver = $driver;
        $this->string_file = $string_file;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{
            $this->saveLocation($this->request_data,$this->driver, $this->string_file);
        }
        catch (\Exception $exception){
            \Log::channel('location_queue')->info(['error_message_from_job'=>$exception->getMessage()]);
        }

    }
}
