<?php
/**
 * Copyright © BoonWeb GmbH. All rights reserved.
 * See LICENSE for license details.
 */

namespace App\Commands;

use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

use function Termwind\render;

class Create extends Command
{
    protected $signature = 'create';
    protected $description = 'Create a new laravel project';
    protected string $laravelVersion;
    protected string $appName;
    protected string $starterKit;
    protected ?string $javascriptFramework = null;
    protected bool $withDarkMode = true;
    protected bool $withSSR = true;
    protected ?string $jetstreamStack = null;
    protected bool $withTeams = false;
    protected string $packageManager;
    protected bool $withAuth = true;
    protected string $uiPreset;
    protected bool $withModules = true;
    protected bool $withImpersonate = false;
    protected bool $withPermissions = true;

    public function handle()
    {
        $this->prepareEnvironment();
        $this->askForInformation();
        $this->runTasks();
        $this->showPostInstallInstructions();
    }

    private function prepareEnvironment()
    {
        File::makeDirectory(config('view.compiled'), 0755, true, true);
    }

    protected function askForInformation()
    {
        $this->appName = $this->ask('[laravel] What should be the name of your new application?', 'new-app');
        $this->laravelVersion = $this->choice(
            '[laravel] Which version should be installed?',
            ['^8.0', '^9.0'],
            '^9.0'
        );

        render(view('starter-kit-information'));
        $this->newLine(2);

        $this->starterKit = $this->choice(
            'Which starterkit to use?',
            ['jetstream', 'breeze', 'ui', 'none'],
            'breeze'
        );

        if ($this->starterKit === 'breeze') {
            $this->withDarkMode = $this->confirm('[breeze] Configure breeze with dark mode support?', true);
            $this->javascriptFramework = $this->choice(
                '[breeze] Which Javascript framework to use for frontend?',
                ['react', 'vue'],
                'vue'
            );
            $this->withSSR = $this->confirm('[breeze] Should SSR be configured as well?', true);
        } elseif ($this->starterKit === 'jetstream') {
            $this->jetstreamStack = $this->choice(
                '[jetstream] Which stack do you prefer?',
                ['inertia', 'livewire'],
                'inertia'
            );
            $this->withTeams = $this->confirm('[jetstream] Do you want "Teams" support to be enabled?', false);
            $this->withSSR = $this->confirm('[jetstream] Should SSR be configured as well?', true);
        } elseif ($this->starterKit === 'ui') {
            $this->withAuth = $this->confirm('[laravel/ui] Should Auth scaffolding be created?', true);
            $this->uiPreset = $this->choice(
                '[laravel/ui] Which Preset to use for frontend?',
                ['react', 'vue', 'bootstrap'],
                'bootstrap'
            );
            /*$this->customUiPreset = $this->ask(
                '[laravel/ui] Additionally you can install a custom preset over the default ones.'
            );*/
            // TODO "custom" preset can be created with laravel/ui but we mostly want to start with an existing
        }

        $this->withModules = $this->confirm('[dependencies] Do you want "Modules" support?', true);
        $this->withPermissions = $this->confirm('[dependencies] Do you need user permissions/roles feature?', true);
        $this->withImpersonate = $this->confirm('[dependencies] Do you need user impersonation feature?', true);

        $this->packageManager = $this->choice(
            'Which package manager to use?',
            ['yarn', 'npm'],
            'yarn'
        );
    }

    protected function runTasks()
    {
        $this->checkForExistingProjectDirectory();
        $this->installLaravel();

        // Ensure all further tasks are executed inside the new laravel project
        chdir($this->appName);

        // StarterKit
        if ($this->starterKit !== 'none') {
            $this->installPackage(sprintf('laravel/%s', $this->starterKit));
        }

        match ($this->starterKit) {
            'breeze' => $this->configureBreeze(),
            'jetstream' => $this->configureJetstream(),
            'ui' => $this->configureLaravelUi(),
        };

        $this->installDependencies();
    }

    /**
     * @return void
     */
    protected function checkForExistingProjectDirectory(): void
    {
        if (file_exists($this->appName)) {
            $question = sprintf(
                'Folder %s already exists. Should this folder be deleted before continuing?',
                $this->appName
            );

            if ($this->confirm($question)) {
                File::deleteDirectory($this->appName);
            }
        }
    }

    /**
     * @return void
     */
    protected function installLaravel(): void
    {
        $this->task('Install Laravel', function () {
            $command = sprintf(
                'composer create-project laravel/laravel %s "%s"',
                $this->appName,
                $this->laravelVersion
            );
            $this->shell($command);
        });
    }

    /**
     * Execute provided command inside the new project and print output to stdout.
     *
     * @param string $command
     *
     * @return void
     */
    protected function shell(string $command): void
    {
        $this->line(shell_exec($command) ?? '');
    }

    protected function installPackage(string $package): void
    {
        $this->task(sprintf('Install %s', $package), function () use ($package) {
            $command = sprintf('composer require %s', $package);
            $this->shell($command);
        });
    }

    protected function configureBreeze(): void
    {
        $this->task('Configure Breeze', function () {
            $command = sprintf(
                'php artisan breeze:install %s',
                $this->jetstreamStack ?: $this->javascriptFramework
            );

            if ($this->withDarkMode) {
                $command .= ' --dark';
            }

            if ($this->withSSR) {
                $command .= ' --ssr';
            }

            $this->shell($command);
        });
    }

    protected function configureJetstream(): void
    {
        $this->task('Configure Jetstream', function () {
            $command = sprintf(
                'php artisan jetstream:install %s',
                $this->jetstreamStack ?: $this->javascriptFramework
            );

            if ($this->withSSR) {
                $command .= ' --ssr';
            }

            if ($this->withTeams) {
                $command .= ' --teams';
            }

            $this->shell($command);
        });
    }

    protected function configureLaravelUi(): void
    {
        $this->task('Configure laravel/ui', function () {
            $command = sprintf('php artisan ui %s', $this->uiPreset);

            if ($this->withAuth) {
                $command .= ' --auth';
            }

            $this->shell($command);
        });
    }

    /**
     * Install additional dependencies as selected during wizard questionnaire
     *
     * @return void
     */
    protected function installDependencies(): void
    {
        if ($this->withModules) {
            $this->task('Setup Modules', function () {
                $this->shell('composer require nwidart/laravel-modules joshbrw/laravel-module-installer');
                $this->shell('php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"');
                File::makeDirectory('Modules');
            });
        }

        if ($this->withPermissions) {
            $this->task('Setup Permissions', function () {
                $this->shell('composer require spatie/laravel-permission');
                $this->shell('php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"');
            });
        }

        if ($this->withImpersonate) {
            $this->task('Setup Impersonate', function () {
                $this->shell('composer require lab404/laravel-impersonate');
            });
        }
    }

    private function showPostInstallInstructions(): void
    {
        $additionalSteps = [];

        if ($this->withImpersonate) {
            $additionalSteps[] = 'Add `Lab404\Impersonate\Models\Impersonate` trait to User model';
        }

        if ($this->withPermissions) {
            $additionalSteps[] = 'Add required HasPermissions/HasRoles traits to User model';
        }

        $data = [
            'packageManager'  => $this->packageManager,
            'appName'         => $this->appName,
            'additionalSteps' => $additionalSteps,
        ];

        render(view('post-install-tasks', $data));
    }
}
