<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StripPhpBomCommand extends Command
{
    protected $signature = 'app:strip-php-bom {path=app : Directory to scan}';

    protected $description = 'Remove UTF-8 BOM from PHP files (fixes namespace fatal errors after bad uploads)';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");

            return self::FAILURE;
        }

        $fixed = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);

            if ($content === false) {
                continue;
            }

            $original = $content;
            $content = preg_replace('/^\xEF\xBB\xBF+/', '', $content);
            $content = preg_replace('/^\x{FEFF}+/u', '', $content);
            // Collapse "<?php" + blank line + "namespace" (BOM often hides on the blank line).
            $content = preg_replace(
                '/^<\?php[ \t]*\R+\s*namespace\s+/',
                '<?php namespace ',
                $content
            );

            if ($content !== $original) {
                file_put_contents($filePath, $content);
                $this->line("Fixed: {$filePath}");
                $fixed++;
            }
        }

        $this->info($fixed > 0 ? "Fixed {$fixed} file(s)." : 'No BOM or header issues found.');

        return self::SUCCESS;
    }
}
