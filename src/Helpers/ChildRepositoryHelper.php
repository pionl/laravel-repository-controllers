<?php
namespace Pion\Repository\Helpers;

use Illuminate\Database\Eloquent\Model;
use Pion\Repository\Traits\RepositoryControllerTrait;
use Pion\Repository\Traits\RepositoryTrait;

/**
 * Class ChildRepositoryHelper
 *
 * A container that helps to create the child repository with desired functions of normal repository
 *
 * @package Pion\Repository\Helpers
 */
class ChildRepositoryHelper
{
    use RepositoryControllerTrait;

    /**
     * ChildRepositoryHelper constructor.
     */
    public function __construct()
    {
        $this->bootRepository();
    }

    /**
     * Creates a repository we will use
     * @return RepositoryTrait
     */
    protected function createRepository()
    {
        return null; // we dont need to create the repository
    }

    /**
     * Generates the navigation with list support.
     *
     * @param string|null $title        you can provide model to use as modelForShow. Title will have null value
     * @param Model|null  $modelForShow indicates if we want to show a model with show url
     */
    protected function createNavigation($title = null, $modelForShow = null)
    {
        // do nothing
    }


}