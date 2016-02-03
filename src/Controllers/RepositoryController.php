<?php
namespace Pion\Repository\Controllers;

use Illuminate\Routing\Controller;
use Pion\Repository\Traits\BaseRepository;
use Pion\Repository\Traits\RepositoryControllerTrait;

/**
 * Class RepositoryController
 *
 * Empty repository controller
 *
 * @package Pion\Repository\Controllers
 */
class RepositoryController extends Controller
{
    use RepositoryControllerTrait;

    /**
     * Creates a repository we will use
     * @return BaseRepository
     */
    protected function createRepository()
    {
        return null;
    }


}