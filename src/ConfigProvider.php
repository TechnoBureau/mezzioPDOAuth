<?php

declare(strict_types=1);

namespace TechnoBureau\mezzioPDOAuth;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Roave\PsrContainerDoctrine\DriverFactory;
use Roave\PsrContainerDoctrine\EntityManagerFactory;
use Doctrine\ORM\EntityManager;

use Mezzio\Application;

use Mezzio\Csrf\CsrfMiddleware;

use TechnoBureau\mezzioPDOAuth\Handler\HomePageHandler;
use TechnoBureau\mezzioPDOAuth\Handler\HomePageHandlerFactory;
use TechnoBureau\mezzioPDOAuth\Handler\AdminPageHandler;
use TechnoBureau\mezzioPDOAuth\Handler\AdminPageHandlerFactory;
use TechnoBureau\mezzioPDOAuth\Handler\LoginPageHandler;
use TechnoBureau\mezzioPDOAuth\Handler\LoginPageHandlerFactory;
use TechnoBureau\mezzioPDOAuth\Handler\LogoutHandler;
use TechnoBureau\mezzioPDOAuth\Middleware\PrgMiddleware;
use TechnoBureau\mezzioPDOAuth\Middleware\UserMiddleware;
use TechnoBureau\mezzioPDOAuth\Middleware\UserMiddlewareFactory;
use TechnoBureau\mezzioPDOAuth\View\Helper\Flash;
use TechnoBureau\mezzioPDOAuth\View\Helper\GetRole;
use TechnoBureau\mezzioPDOAuth\View\Helper\IsGrantedFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\Authentication\AuthenticationMiddleware;
use Mezzio\Authorization\AuthorizationMiddleware;

use Mezzio\Authentication\UserRepositoryInterface;
use Mezzio\Authentication\UserRepository\PdoDatabase;
use Mezzio\Authorization\AuthorizationInterface;
use Mezzio\Authorization\Acl\LaminasAcl;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\Session\PhpSessionFactory;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'doctrine'     => $this->doctrine(),
            'templates'    => $this->getTemplates(),
            'authentication' => $this->getPDOAuthentication(),
            'mezzio-authorization-acl'   => $this->getACL(),
            'view_helpers' => [
                'invokables' => [
                    'flash'   => Flash::class,
                    'getRole' => GetRole::class,
                ],
                'factories'  => [
                    'isGranted' => IsGrantedFactory::class,
                ],
            ],
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'aliases' => [
                EntityManager::class => 'doctrine.entity_manager.orm_default',
                UserRepositoryInterface::class => PdoDatabase::class,
                AuthorizationInterface::class  => LaminasAcl::class,
            ],
            'invokables' => [
                LogoutHandler::class => LogoutHandler::class,
            ],
            'factories' => [
                HomePageHandler::class => HomePageHandlerFactory::class,
                'doctrine.driver.orm_default'         => DriverFactory::class,
                'doctrine.entity_manager.orm_default' => EntityManagerFactory::class,
                AdminPageHandler::class => AdminPageHandlerFactory::class,
                LoginPageHandler::class => LoginPageHandlerFactory::class,
                PrgMiddleware::class    => InvokableFactory::class,
                UserMiddleware::class   => UserMiddlewareFactory::class,
                AuthenticationInterface::class => PhpSessionFactory::class,
            ],
        ];
    }

    /**
     * Get doctrine configuration.
     *
     * @return array<string, mixed>
     */
    private function doctrine(): array
    {
        return [
            'driver' => [
                __NAMESPACE__ . '_driver' => [
                    'class' => AttributeDriver::class,
                    'cache' => 'array',
                    'paths' => [
                        dirname(__DIR__) . '/src/Entity',
                    ],
                ],
                'orm_default' => [
                    'class'   => MappingDriverChain::class,
                    'drivers' => [
                        __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver',
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns the templates configuration
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'user' => [__DIR__ . '/../templates/user'],
            ],
        ];
    }
    public function getPDOAuthentication(): array
    {
        return [
            'pdo' => [
                'dsn' => sprintf(
                    'pgsql:dbname=%s;host=%s',
                    false !== getenv('PGSQL_DB_NAME') ? getenv('PGSQL_DB_NAME') : 'mezzio',
                    false !== getenv('PGSQL_DB_HOST') ? getenv('PGSQL_DB_HOST') : '127.0.0.1'
                ),
                'username' => false !== getenv('PGSQL_DB_USER') ? getenv('PGSQL_DB_USER') : 'postgres',
                'password' => false !== getenv('PGSQL_DB_PASS') ? getenv('PGSQL_DB_PASS') : 'Passw0rd0',
                'table' => 'auth_user',
                'field' => [
                    'identity' => 'email',
                    'password' => 'password',
                ],

                'sql_get_roles' => "SELECT role FROM auth_user WHERE email = :identity",
                //'sql_get_details' => "SELECT first_name FROM auth_user WHERE email = :identity"
            ],
            'redirect' => '/login',
            'username' => 'email',
            'password' => 'password',
            'remember-me-seconds' => 604800,
        ];
    }
    public function getACL(): array
    {
        return include __DIR__ . '/../config/authorization-acl.global.php';
    }

    public function registerRoutes(Application $app, string $basePath = '/user'): void
    {
        $app->get('/', [
            AuthenticationMiddleware::class,
            AuthorizationMiddleware::class,
            HomePageHandler::class,
        ], 'home.view');

        $app->route('/admin', [
            AuthenticationMiddleware::class,
            AuthorizationMiddleware::class,
            AdminPageHandler::class,
        ], ['GET'], 'admin.view');

        $app->route('/login', [
            AuthorizationMiddleware::class,
            //csrf handling
            CsrfMiddleware::class,
            // prg handling
            PrgMiddleware::class,
            // the login page
            LoginPageHandler::class,
            // authentication handling
            AuthenticationMiddleware::class,
        ], ['GET', 'POST'], 'login.form');


        $app->get('/logout', [
            AuthenticationMiddleware::class,
            AuthorizationMiddleware::class,
            LogoutHandler::class,
        ], 'logout.access');
    }
}
