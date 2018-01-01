<?php
namespace Module\Searchit
{
    use Poirot\Application\Interfaces\Sapi;

    use Poirot\Loader\Autoloader\LoaderAutoloadAggregate;
    use Poirot\Loader\Autoloader\LoaderAutoloadNamespace;


    class Module implements Sapi\iSapiModule
        , Sapi\Module\Feature\iFeatureModuleAutoload
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



    }
}
