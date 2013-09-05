<?php namespace Barryvdh\TwigBridge;


use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\SecurityExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Form\TwigRenderer;

/**
 * Twig integration for Laravel 4
 *
 * Based on Twig integration for Silex by Fabien Potencier <fabien@symfony.com>
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $app = $this->app;

        $extension = $app['config']->get('laravel-twigbridge::config.extension', 'twig');

        $app['twig.options'] = array();
        $app['twig.form.templates'] = array();
        $app['twig.path'] = $app['view']->getFinder()->getPaths();
        $app['twig.templates'] = array();

        $app['twig'] = $app->share(function ($app) {
                $app['twig.options'] = array_replace(
                    $app['config']->get('laravel-twigbridge::config.options', array(
                            'debug' => $app['config']['app.debug'],
                            'cache' =>  $app['path.storage'].'/views/twig',
                            'base_template_class' => 'Barryvdh\TwigBridge\TwigTemplate',
                        )), $app['twig.options']
                );

                $twig = new \Twig_Environment($app['twig.loader'], $app['twig.options']);
                $twig->addGlobal('app', $app);

                if ( $app['twig.options']['debug']) {
                    $twig->addExtension(new \Twig_Extension_Debug());
                }

                $twig->addExtension(new Extension\LaravelHelperExtension());

                //Test if Symfony TwigBridge is available
                if (class_exists('Symfony\Bridge\Twig\Extension\TranslationExtension')) {
                    if (isset($app['translator'])) {
                        $twig->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($app['translator']));
                    }
                }
                return $twig;
            });

        $app['twig.loader.path'] = $app->share(function () {
                return new Loader\PathLoader();
            });

        $app['twig.loader.viewfinder'] = $app->share(function ($app) use($extension) {
                return new Loader\ViewfinderLoader($app['view']->getFinder(), $extension);
            });

        $app['twig.loader.filesystem'] = $app->share(function ($app) {
                return new \Twig_Loader_Filesystem($app['twig.path']);
            });

        $app['twig.loader.array'] = $app->share(function ($app) {
                return new \Twig_Loader_Array($app['twig.templates']);
            });

        $app['twig.loader'] = $app->share(function ($app) {
                return new \Twig_Loader_Chain(array(
                    $app['twig.loader.path'],
                    $app['twig.loader.array'],
                    $app['twig.loader.viewfinder'],
                    $app['twig.loader.filesystem'],
                ));
            });


        // Register the view engine:
        $app['view']->addExtension($extension, 'twig', function () use ($app)
            {
                return new TwigEngine($app['twig']);
            });
	}

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(){
        $this->package('barryvdh/laravel-twigbridge');
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}