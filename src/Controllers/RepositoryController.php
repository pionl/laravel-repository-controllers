<?php
namespace Pion\Repository\Controllers;

use Illuminate\Routing\Controller;
use Pion\Repository\Traits\BaseRepository;
use Pion\Repository\Traits\RepositoryControllerTrait;

/**
 * Class RepositoryController
 *
 * Empty repository controller only used for the ChildRepositoryControllerTrait
 * to enable usage of the create/edit and etc.
 *
 * @package Pion\Repository\Controllers
 */
class RepositoryController extends Controller
{
    use RepositoryControllerTrait;

    /**
     * RepositoryController constructor.
     */
    public function __construct()
    {
        $this->bootRepository();
    }


    /**
     * Creates a repository we will use the same from the ChildRepositoryControllerTrait
     * @return BaseRepository
     */
    protected function createRepository()
    {
        return null;
    }


}