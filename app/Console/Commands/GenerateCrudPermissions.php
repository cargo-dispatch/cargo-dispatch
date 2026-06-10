<?php

namespace App\Console\Commands;

use Spatie\Permission\Models\Permission;
use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'make:crud-permissions', description: 'Generate CRUD permissions for a given controller')]
class GenerateCrudPermissions extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('controller', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'The name of the controller (e.g., DriverController)');
    }

    public function handle(): void
    {
        $controller = $this->argument('controller');
        $name = strtolower(str_replace('Controller', '', $controller));
        $actions = ['view', 'create',   'update', 'delete'];

        foreach ($actions as $action) {
            $permission = "{$name}.{$action}";

            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
                $this->info("✅ Permission created: {$permission}");
            } else {
                $this->warn("⚠️ Permission already exists: {$permission}");
            }
        }
    }
}
