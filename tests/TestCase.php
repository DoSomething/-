<?php

use Rogue\Jobs\SendReportbackToPhoenix;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    use DatabaseMigrations;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected $reportbackApiUrl = 'api/v1/reportbacks';

    /**
     * The Faker generator, for creating test data.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        // Get a new Faker generator from Laravel.
        $this->faker = app(\Faker\Generator::class);
    }

    /**
     * Mock Container dependencies.
     *
     * @param string $class Class to mock
     *
     * @return void
     */
    public function mock($class)
    {
        $mock = Mockery::mock($class);
        $this->app->instance($class, $mock);
        return $mock;
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Creates a mock file.
     *
     * @return mockfile
     */
    public function mockFile()
    {
        $path = storage_path('images/huskycorgi.jpeg');
        $original_name = 'huskycorgi.jpeg';
        $mime_type = 'image/jpeg';
        $error = null;
        $test = true;
        return new \Illuminate\Http\UploadedFile($path, $original_name, $mime_type, $error, $test);
    }
}
