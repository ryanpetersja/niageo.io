<?php

namespace App\Services;

use App\Models\ClientServer;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class SshActivityService
{
    /**
     * Commands to filter out (common noise).
     */
    private const NOISE_COMMANDS = [
        'ls', 'ls -la', 'ls -l', 'ls -al', 'ls -lah', 'ls -a',
        'cd', 'pwd', 'clear', 'exit', 'logout', 'history',
        'cat', 'less', 'more', 'man', 'which', 'whoami', 'who',
        'echo', 'date', 'uptime', 'top', 'htop', 'free', 'df',
        'w', 'id', 'hostname', 'uname', 'env', 'set', 'alias',
    ];

    /**
     * Command prefixes that indicate meaningful operations.
     */
    private const MEANINGFUL_PREFIXES = [
        'artisan', 'php artisan', 'composer', 'npm', 'yarn', 'pnpm',
        'git', 'systemctl', 'service', 'certbot', 'mysql', 'pg_dump',
        'psql', 'mongodump', 'redis-cli', 'cp', 'mv', 'chmod', 'chown',
        'apt', 'apt-get', 'yum', 'dnf', 'docker', 'docker-compose',
        'forge', 'supervisor', 'supervisorctl', 'nginx', 'apache',
        'a2ensite', 'a2dissite', 'a2enmod', 'certbot', 'crontab',
        'tar', 'zip', 'unzip', 'rsync', 'scp', 'wget', 'curl',
        'pip', 'python', 'node', 'pm2', 'forever', 'screen', 'tmux',
        'iptables', 'ufw', 'fail2ban', 'openssl',
        'vim', 'nano', 'vi', 'sed', 'awk', 'grep',
        'kill', 'pkill', 'reboot', 'shutdown',
        'mkdir', 'rm', 'ln',
    ];

    /**
     * @return array{success: bool, error: string|null}
     */
    public function testConnection(ClientServer $server): array
    {
        try {
            $ssh = $this->connect($server);
            $ssh->disconnect();
            return ['success' => true, 'error' => null];
        } catch (\Exception $e) {
            Log::warning('SSH connection test failed', [
                'server' => $server->display_name,
                'host' => $server->host,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function fetchBashHistory(ClientServer $server, ?string $since = null, ?string $until = null): array
    {
        $ssh = $this->connect($server);

        // Try to read bash history with timestamps
        $historyContent = $ssh->exec('cat ~/.bash_history 2>/dev/null');
        $ssh->disconnect();

        if (empty(trim($historyContent))) {
            return [];
        }

        $commands = $this->parseHistory($historyContent, $server->label);

        // Filter by date range if provided
        if ($since || $until) {
            $sinceTs = $since ? strtotime($since) : null;
            $untilTs = $until ? strtotime($until) : null;

            $commands = array_filter($commands, function ($cmd) use ($sinceTs, $untilTs) {
                // If no timestamp on command, include it (can't filter)
                if (!$cmd['timestamp']) {
                    return true;
                }
                $ts = strtotime($cmd['timestamp']);
                if ($sinceTs && $ts < $sinceTs) return false;
                if ($untilTs && $ts > $untilTs) return false;
                return true;
            });
        }

        // Filter out noise commands
        $commands = array_filter($commands, function ($cmd) {
            return $this->isMeaningfulCommand($cmd['command']);
        });

        return array_values($commands);
    }

    public function fetchActivityForClient(int $clientId, string $since, string $until): array
    {
        $servers = ClientServer::where('client_id', $clientId)
            ->where('is_active', true)
            ->get();

        $allCommands = [];

        foreach ($servers as $server) {
            try {
                $commands = $this->fetchBashHistory($server, $since, $until);
                $allCommands = array_merge($allCommands, $commands);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch SSH history', [
                    'server' => $server->display_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Sort by timestamp descending (nulls at end)
        usort($allCommands, function ($a, $b) {
            if (!$a['timestamp'] && !$b['timestamp']) return 0;
            if (!$a['timestamp']) return 1;
            if (!$b['timestamp']) return -1;
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return $allCommands;
    }

    private function connect(ClientServer $server): SSH2
    {
        $ssh = new SSH2($server->host, $server->port, 10);

        if ($server->auth_type === 'key') {
            $keyPath = $server->private_key_path;

            if (!$keyPath || !file_exists($keyPath)) {
                throw new \RuntimeException("SSH key not found at: {$keyPath}");
            }

            // Guard against accidentally using the .pub file
            if (str_ends_with(strtolower($keyPath), '.pub')) {
                throw new \RuntimeException("You provided a public key (.pub). Please provide the private key path instead (without .pub).");
            }

            $keyContents = file_get_contents($keyPath);

            if (empty(trim($keyContents))) {
                throw new \RuntimeException("SSH key file is empty: {$keyPath}");
            }

            try {
                $key = PublicKeyLoader::load($keyContents);
            } catch (\Exception $e) {
                throw new \RuntimeException("Unable to read key at {$keyPath}: " . $e->getMessage());
            }

            // Ensure it's a private key, not a public key
            if ($key instanceof \phpseclib3\Crypt\Common\PublicKey) {
                throw new \RuntimeException("The file at {$keyPath} is a public key. Please provide the private key path (without .pub extension).");
            }

            if (!$ssh->login($server->username, $key)) {
                throw new \RuntimeException("SSH key authentication failed for {$server->username}@{$server->host}. Verify the public key is in the server's authorized_keys.");
            }
        } else {
            $password = $server->decrypted_password;

            if (!$password) {
                throw new \RuntimeException('No password configured for this server.');
            }

            if (!$ssh->login($server->username, $password)) {
                throw new \RuntimeException("SSH password authentication failed for {$server->username}@{$server->host}");
            }
        }

        return $ssh;
    }

    private function parseHistory(string $content, string $serverLabel): array
    {
        $lines = explode("\n", $content);
        $commands = [];
        $currentTimestamp = null;
        $hasTimestamps = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Check for timestamp line: #1706000000
            if (preg_match('/^#(\d{10,})$/', $line, $matches)) {
                $currentTimestamp = date('Y-m-d H:i:s', (int) $matches[1]);
                $hasTimestamps = true;
                continue;
            }

            $commands[] = [
                'command' => $line,
                'timestamp' => $currentTimestamp,
                'server_label' => $serverLabel,
            ];

            // Reset timestamp after consuming it
            $currentTimestamp = null;
        }

        // If no timestamps found, only return last 200 commands
        if (!$hasTimestamps && count($commands) > 200) {
            $commands = array_slice($commands, -200);
        }

        return $commands;
    }

    private function isMeaningfulCommand(string $command): bool
    {
        $command = trim($command);

        // Skip empty or very short commands
        if (strlen($command) < 2) return false;

        // Skip pure comments
        if (str_starts_with($command, '#')) return false;

        // Check exact noise matches
        $baseCommand = strtolower(trim(explode(' ', $command)[0]));
        $normalized = strtolower(trim($command));

        if (in_array($normalized, self::NOISE_COMMANDS)) return false;

        // "cd /some/path" is noise
        if (str_starts_with($normalized, 'cd ')) return false;

        // "ls" with flags but no useful context
        if ($baseCommand === 'ls') return false;

        // "cat" alone or "cat somefile" — noise in isolation
        if ($baseCommand === 'cat' && substr_count($command, ' ') <= 1) return false;

        return true;
    }
}
