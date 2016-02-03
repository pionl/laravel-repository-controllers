<?php
namespace Pion\Repository\Traits;

use Pion\Support\Controllers\Traits\URLTrait;

trait BaseRepositoryControllerTrait
{
    use URLTrait;

    /**
     * @var RepositoryTrait
     */
    protected $repository;

    /**
     * A path for form view to use. Must be used
     * @var string
     */
    protected $formView;

    /**
     * Defines the index for object index in form view
     * @var string
     */
    protected $formObjectIndex = "object";

    /**
     * Defines the create title in navigation
     * @var string
     */
    protected $createTitle = null;

    /**
     * Defines the edit title in navigation
     * @var string
     */
    protected $editTitle = null;

    /**
     * Determines if we should go to edit for redirect
     * @var bool
     */
    protected $redirectToEditOnCreate = false;

    /**
     * BaseRepositoryControllerTrait constructor.
     * Prepares the basic repository
     */
    public function __construct()
    {
        // setup the default
        $this->createTitle = trans("repository.title.create");
        $this->editTitle = trans("repository.title.edit");

        // call the controller construct
        parent::__construct();

        // creates the repository
        $this->repository = $this->createRepository();
    }

    /**
     * Creates a repository we will use
     * @return BaseRepository
     */
    abstract protected function createRepository();

    /**
     * @return RepositoryTrait
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param RepositoryTrait $repository
     *
     * @return $this
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
        return $this;
    }
}