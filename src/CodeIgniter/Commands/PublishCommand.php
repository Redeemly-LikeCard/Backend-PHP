<?php

namespace LuckyCode\IntegrationHelper\CodeIgniter\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use LuckyCode\IntegrationHelper\CodeIgniter\Installer;

class PublishCommand extends BaseCommand
{
    protected $group = 'LuckyCode';
    protected $name = 'luckycode:publish';
    protected $description = 'Publish LuckyCode CodeIgniter configuration, controller, routes, and environment variables';

    public function run(array $params): void
    {
        CLI::write('Publishing LuckyCode package...', 'green');

        try {
            Installer::publish(ROOTPATH, static function (string $message): void {
                if ($message === '') {
                    CLI::newLine();
                    return;
                }

                $color = str_contains($message, 'already exists') ? 'yellow' : 'green';
                CLI::write($message, $color);
            });
        } catch (\Throwable $e) {
            CLI::error('[LuckyCode] ' . $e->getMessage());
            return;
        }

        CLI::newLine();
        CLI::write('Next steps:', 'yellow');
        CLI::write('1. Update LUCKYCODE_BASE_URL, LUCKYCODE_API_KEY, and LUCKYCODE_CLIENT_ID in .env');
        CLI::write('2. Run: php spark routes');
        CLI::write('3. API endpoints are available at /luckycode/*');
    }
}
