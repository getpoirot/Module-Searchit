<?php
namespace Module\Searchit
{
    use Poirot\Application\Interfaces\Sapi;

    use Poirot\Ioc\Container;
    use Poirot\Ioc\Container\BuildContainer;
    use Poirot\Loader\Autoloader\LoaderAutoloadAggregate;
    use Poirot\Loader\Autoloader\LoaderAutoloadNamespace;


    /**
     * Search Module helps you search through any kind of entity you want,
     * but you should define your searchable entity, by definition I mean
     * telling search service, what searchable fields you have and what kind
     * of search, you need on your entity fields, for example user entity
     * search of type : text on field : username and bio
     *
     *
     */
    class Module implements Sapi\iSapiModule
        , Sapi\Module\Feature\iFeatureModuleAutoload
        , Sapi\Module\Feature\iFeatureModuleNestServices
    {
        /**
         * @inheritdoc
         */
        function initAutoload(LoaderAutoloadAggregate $baseAutoloader)
        {
            /** @var LoaderAutoloadNamespace $nameSpaceLoader */
            $nameSpaceLoader = $baseAutoloader->loader(LoaderAutoloadNamespace::class);
            $nameSpaceLoader->addResource(__NAMESPACE__, __DIR__);

            require_once __DIR__.'/_functions.php';
        }

        /**
         * @inheritdoc
         */
        function getServices(Container $moduleContainer = null)
        {
            $conf    = \Poirot\Config\load(__DIR__ . '/../config/mod-searchit_services');

            $builder = new BuildContainer;
            $builder->with($builder::parseWith($conf));
            return $builder;
        }

    }
}

namespace Module\Searchit
{
    use Elasticsearch\Client;


    /**
     * @method static Client ElasticClient()
     */
    class Services extends \IOC
    { }
}
