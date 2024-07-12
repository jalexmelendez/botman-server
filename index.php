<?php

use App\Services\ChatHandlerService;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\SymfonyCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Attribute\Route;

require __DIR__.'/vendor/autoload.php';

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private $botConfig = [
        // Cache
        'botman' => [
            'conversation_cache_time' => 30
        ],

        // Web driver
        'web' => [
            'matchingData' => [
                'driver' => 'web',
            ],
        ],
    ];

    public function registerBundles(): array
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // PHP equivalent of config/packages/framework.yaml
        $container->extension('framework', [
            'secret' => 'S0ME_SECRET'
        ]);
    }

    #[Route('/', name: 'chat_frame')]
    public function chatFrame()
    {
        return new Response(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>BotMan Widget</title>
                    <meta charset="UTF-8">
                    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/botman-web-widget@0/build/assets/css/chat.min.css">
                </head>
                <body>
                    <script id="botmanWidget" src='https://cdn.jsdelivr.net/npm/botman-web-widget@0/build/js/chat.js'></script>
                </body>
            </html>
            HTML
        );
    }

    #[Route('/chat', name: 'bot')]
    public function bot()
    {
        DriverManager::loadDriver(WebDriver::class);

        $adapter = new FilesystemAdapter();

        // Create an instance
        $botman = BotManFactory::create($this->botConfig, new SymfonyCache($adapter));

        // Give the bot something to listen for.
        $botman->hears('{message}', fn ($botman, $message) => 
            (new ChatHandlerService(botman: $botman, message: $message))->handle()
        );

        // Start listening

        /**
         * @var mixed $response
         */
        $response = $botman->listen();

        return new Response($response);
    }
}

$kernel = new Kernel('dev', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);