<?php

namespace Infoamin\Installer\Http\Controllers;

use AppController;
use Artisan;
use Infoamin\Installer\Repositories\EnvironmentRepository;
use Exception;
use Illuminate\Http\Request;

class DatabaseController extends AppController
{
    /**
     * Show form.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $host     = env('DB_HOST');
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');

        return view('vendor.installer.database', compact('host', 'database', 'username', 'password'));
    }

    /**
     * Manage form submission.
     *
     * @param  Illuminate\Http\Request                               $request
     * @param  Infoamin\Installer\Repositories\EnvironmentRepository $environmentRepository
     * @return redirection
     */
    public function store(Request $request, EnvironmentRepository $environmentRepository)
    {
        // Set config for migrations and seeds
        $connection = config('database.default');
        config([
            'database.connections.' . $connection . '.host'     => $request->host,
            'database.connections.' . $connection . '.database' => $request->dbname,
            'database.connections.' . $connection . '.password' => $request->password,
            'database.connections.' . $connection . '.username' => $request->username,
        ]);

        // Update .env file
        $environmentRepository->SetDatabaseSetting($request);

        return redirect('install/seedmigrate');
    }

    public function seedMigrate()
    {
        // Migrations and seeds
        try {
            ini_set('max_execution_time', 300);
            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Artisan::call('migrate');
            Artisan::call('db:seed');
            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        catch (Exception $e)
        {
            $data['error'] = $e;
            \Log::error($e);
            return view('vendor.installer.database-error', $data);
        }

        if (config('installer.administrator'))
        {
            return redirect('install/register');
        }

        return redirect('install/finish');
    }

}
