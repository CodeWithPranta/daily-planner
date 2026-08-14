<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StorageLinkCommand extends Command
{
    protected $signature = 'storage:link-force';

    protected $description = 'Create the storage symlink (or directory fallback for cPanel/shared hosting).';

    public function handle(): int
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! file_exists($target)) {
            File::makeDirectory($target, 0775, true);
        }

        if (is_link($link)) {
            $this->info('The [public/storage] link already exists.');

            return self::SUCCESS;
        }

        if (is_dir($link)) {
            if (is_dir($target) && realpath($link) === realpath($target)) {
                $this->info('The [public/storage] link already exists.');

                return self::SUCCESS;
            }
            File::deleteDirectory($link);
        } elseif (file_exists($link)) {
            File::delete($link);
        }

        if (@symlink($target, $link)) {
            $this->info('The [public/storage] directory has been linked.');

            return self::SUCCESS;
        }

        $this->warn('Symlink not supported (likely cPanel/shared hosting). Using directory copy fallback.');

        File::copyDirectory($target, $link);

        $this->info('The [public/storage] directory has been created with copied files.');
        $this->warn('Note: New uploads will need a copy step. The app handles this automatically for avatars.');
        $this->warn('For other files, run: cp -r storage/app/public/* public/storage/');

        return self::SUCCESS;
    }
}
