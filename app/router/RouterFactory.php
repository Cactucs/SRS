<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{
    /**
     * @var \App\Model\CMS\PageRepository
     */
    private $pageRepository;

    public function __construct(\App\Model\CMS\PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * @return Nette\Application\IRouter
     */
    public function createRouter()
    {
        $router = new RouteList;

        $router[] = new Route('index.php', 'Web:Page:default', Route::ONE_WAY);

        $router[] = new Route('api/<action>[/<id>][/<area>]', [
            'module' => 'Api',
            'presenter' => 'Api',
            'action' => 'default',
            'id' => null,
            'area' => null
        ]);

        $router[] = new Route('admin/cms/<presenter>/<action>[/<id>][/<area>]', [
            'module' => 'Admin:CMS',
            'presenter' => 'Page',
            'action' => 'default',
            'id' => null,
            'area' => null
        ]);

        $router[] = new Route('admin/program/<presenter>/<action>[/<id>][/<area>]', [
            'module' => 'Admin:Program',
            'presenter' => 'Block',
            'action' => 'list',
            'id' => null,
            'area' => null
        ]);

        $router[] = new Route('admin/<presenter>/<action>[/<id>][/<area>]', [
            'module' => 'Admin',
            'presenter' => 'Dashboard',
            'action' => 'default',
            'id' => null,
            'area' => null
        ]);

        $router[] = new Route('install/<action>/<id>/', [
            'module' => 'Install',
            'presenter' => 'Install',
            'action' => 'default',
            'id' => null
        ]);

        $router[] = new Route('login/', 'Auth:login');
        $router[] = new Route('logout/', 'Auth:logout');

        try {
            $router[] = new Route('[page/<slug>]', [
                'module' => 'Web',
                'presenter' => 'Page',
                'action' => 'default',
                'page' => [
                    Route::FILTER_IN => function ($page) {
                        return $this->pageRepository->findBySlug($page);
                    },
                    Route::FILTER_OUT => function ($page) {
                        return $page->getSlug();
                    }
                ]
            ]);
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex) { }

        $router[] = new Route('<presenter>/<action>[/<id>]', [
            'module' => 'Web',
            'presenter' => 'Page',
            'action' => 'default',
            'id' => null,
            'area' => null
        ]);

        return $router;
    }
}
