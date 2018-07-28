<?php
/**
 * NOTICE OF LICENSE
 *
 * UNIT3D is open-sourced software licensed under the GNU General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 * @author     Poppabear
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class gitUpdate extends Command
{
    /**
     * @var SymfonyStyle $io
     */
    protected $io;

    /**
     * The copy command
     */
    protected $copy_command = 'cp -Rfp';

    /**
     * The paths relative to base_path() to backup and restore
     *
     * @var array
     */
    protected $paths = [
        '.env',
        'laravel-echo-server.json',
        'config',
        'public/files',
        'resources/views/emails'
    ];

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'git:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes the commands necessary to update your website using git without loosing changes.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->input = new ArgvInput();
        $this->output = new ConsoleOutput();

        $this->io = new SymfonyStyle($this->input, $this->output);

        $this->info('
        *********************************
        * Git Updater v2.0 by Poppabear *
        *********************************
        ');

        $this->info('
        THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
        
        IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
        SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
        GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) EVEN IF ADVISED OF THE POSSIBILITY 
        OF SUCH DAMAGE.
        
        ');

        $this->warn('
        Press CTRL + C to abort
        ');

        sleep(8);

        $this->backup();

        $this->git();

        $this->restore();

        $this->composer();

        $this->migrations();

        $this->compile();

        $this->clear();

        $this->info('Done ... Please report any errors or issues.');
    }

    private function backup()
    {
        $this->info('Backing up some stuff ...');

        $this->process([
            'rm -rf ' . storage_path('gitupdate'),
            'mkdir ' .storage_path('gitupdate'),
            'mkdir ' .storage_path('gitupdate') . DIRECTORY_SEPARATOR . 'public',
            'mkdir ' .storage_path('gitupdate') . DIRECTORY_SEPARATOR . 'resources',
            'mkdir ' .storage_path('gitupdate') . DIRECTORY_SEPARATOR . 'resources/views',
        ]);

        foreach ($this->paths as $path) {
            $this->process([
                $this->copy_command . ' ' . base_path($path) . ' ' . storage_path('gitupdate') . DIRECTORY_SEPARATOR . $path
            ]);
        }
    }

    private function git()
    {
        $this->info('Updating to be current with remote repository ...');

        $commands = [
            'git stash',
            'git checkout master',
            'git fetch origin',
            'git reset --hard origin/master',
            'git pull origin master'
        ];

        $this->process($commands);
    }

    private function restore()
    {
        $this->info('Restoring backed up stuff ...');

        foreach ($this->paths as $path) {
            $this->process([
                $this->copy_command . ' ' . storage_path('gitupdate') . DIRECTORY_SEPARATOR . $path . ' ' . base_path(dirname($path) . DIRECTORY_SEPARATOR)
            ]);
        }
    }

    private function composer()
    {
        $this->info('Installing Composer packages ...');

        $commands = [
            'composer install',
        ];

        $this->process($commands);
    }

    private function compile()
    {
        $this->info('Compiling Assets ...');

        $commands = [
            'npm install',
            'npm run prod'
        ];

        $this->process($commands);
    }

    private function clear()
    {
        $this->call('clear:all');
    }

    private function migrations()
    {
        $this->info('Running new migrations ...');

        $this->call('migrate');
    }

    private function process(array $commands)
    {
        foreach ($commands as $command) {

            $this->io->writeln("<fg=cyan>$command</>");

            $process = new Process($command);
            $bar = $this->progressStart();
            $process->setTimeout(3600);

            $process->start();

            while($process->isRunning()) {
                $bar->advance();
                sleep(2);

                try {
                    $process->checkTimeout();
                } catch (RuntimeException $e) {
                    $this->error("'{$command}' timed out. Please run manually!");
                }
            }

            if (!$process->isSuccessful()) {
                $this->error($process->getErrorOutput());
            }

            $this->progressStop($bar);
            $process->stop();

            echo "\n\n";
            $this->warn($process->getOutput());
        }
    }

    /**
     * @return ProgressBar
     */
    protected function progressStart()
    {
        $bar = $this->io->createProgressBar();
        $bar->setBarCharacter('<fg=magenta>=</>');
        $bar->setFormat('[%bar%] (<fg=cyan>%message%</>)');
        $bar->setMessage('Please Wait ...');
        //$bar->setRedrawFrequency(20); todo: may be useful for platforms like CentOS
        $bar->start();

        return $bar;
    }

    /**
     * @param $bar
     */
    protected function progressStop(ProgressBar $bar)
    {
        $bar->setMessage("<fg=green>Done!</>");
        $bar->finish();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [

        ];
    }
}
