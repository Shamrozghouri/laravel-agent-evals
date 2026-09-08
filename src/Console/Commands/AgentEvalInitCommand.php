<?php

namespace Ali\LaravelAgentEvals\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class AgentEvalInitCommand extends Command
{
    protected $signature = 'agent:eval:init';

    protected $description = 'Initialize Laravel Agent Evals in the application';

    public function handle(Filesystem $files): int
    {
        $this->components->info('Initializing Laravel Agent Evals...');

        $this->publishConfig($files);
        $this->createTestDirectory($files);
        $this->createExampleTest($files);

        $this->newLine();

        $this->components->info(
            'Laravel Agent Evals initialized successfully.'
        );

        $this->newLine();

        $this->line('Next steps:');

        $this->line(
            '  1. Configure AGENT_EVALS_AGENT_CLASS in your .env file.'
        );

        $this->line(
            '  2. Configure AGENT_EVALS_AGENT_METHOD if your agent method is not "respond".'
        );

        $this->line(
            '  3. Run: php artisan agent:eval'
        );

        return self::SUCCESS;
    }

    private function publishConfig(Filesystem $files): void
    {
        $source = __DIR__.'/../../../config/agent-evals.php';
        $destination = config_path('agent-evals.php');

        if ($files->exists($destination)) {
            $this->components->warn(
                'Config file already exists. Skipping config creation.'
            );

            return;
        }

        if (! $files->exists($source)) {
            $this->components->warn(
                'Package config file could not be found.'
            );

            return;
        }

        $files->ensureDirectoryExists(dirname($destination));

        $files->copy($source, $destination);

        $this->components->info(
            'Created config/agent-evals.php'
        );
    }

    private function createTestDirectory(Filesystem $files): void
    {
        $directory = base_path('tests/AgentEvals');

        if (! $files->isDirectory($directory)) {
            $files->makeDirectory(
                $directory,
                0755,
                true
            );

            $this->components->info(
                'Created tests/AgentEvals/'
            );

            return;
        }

        $this->components->warn(
            'tests/AgentEvals/ already exists. Skipping directory creation.'
        );
    }

    private function createExampleTest(Filesystem $files): void
    {
        $path = base_path(
            'tests/AgentEvals/ExampleAgentEval.php'
        );

        if ($files->exists($path)) {
            $this->components->warn(
                'ExampleAgentEval.php already exists. Skipping example creation.'
            );

            return;
        }

        $contents = <<<'PHP'
<?php

use Ali\LaravelAgentEvals\TestCase;

return TestCase::make(
    name: 'agent responds to a basic greeting',
    input: 'Hello, how can you help me?',
    expect: [
        'contains' => 'help',
    ],
);
PHP;

        $files->ensureDirectoryExists(dirname($path));

        $files->put($path, $contents);

        $this->components->info(
            'Created tests/AgentEvals/ExampleAgentEval.php'
        );
    }
}
