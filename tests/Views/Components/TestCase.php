<?php

namespace Tests\Views\Components;

use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Testing\TestView;
use Tests\TestCase as BaseTestCase;
use function file_put_contents;
use function in_array;
use function method_exists;
use function pathinfo;
use function sys_get_temp_dir;
use function tempnam;
use function view;

abstract class TestCase extends BaseTestCase
{
    protected function render(string $blade): TestView
    {
        if (method_exists($this, 'blade')) {
            return $this->blade($blade);
        }

        $tempDirectory = sys_get_temp_dir();

        if (! in_array($tempDirectory, ViewFacade::getFinder()->getPaths())) {
            ViewFacade::addLocation(sys_get_temp_dir());
        }

        $tempFileInfo = pathinfo(tempnam($tempDirectory, 'laravel-blade'));

        $tempFile = $tempFileInfo['dirname'].'/'.$tempFileInfo['filename'].'.blade.php';

        file_put_contents($tempFile, $blade);

        return new TestView(view($tempFileInfo['filename'], []));
    }
}
