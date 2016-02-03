<?php
namespace Pion\Repository\Traits;

use Pion\Repository\RepositoryServiceProvider;
use Pion\Support\Controllers\Traits\URLTrait;
use StandardExceptions\OperationExceptions\InvalidOperationException;

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
    protected function bootRepository()
    {
        // setup the default
        $this->createTitle = rpPackageTrans("repository.title.create");
        $this->editTitle = rpPackageTrans("repository.title.edit");

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

    /**
     * Runs the basic settings validation and detects what method we should use. Calls the
     *
     * @param array $data
     * @param $object
     * @param array $prepareParamters
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function createFormView(array $data, $object, $prepareParamters = []) {

        InvalidOperationException::ifFalse(
            is_string($this->formView),
            "You must subclass this method and return a view for form");

        $this->runPrepareFunction("prepareFormData", $data, $object, $prepareParamters);

        $isEdit = is_object($object);

        // add current object to the view (in edit option)
        if ($isEdit) {
            $data[$this->formObjectIndex] = $object;

            $this->runPrepareFunction("prepareEditFormData", $data, $object, $prepareParamters);
        } else {
            $this->runPrepareFunction("prepareCreateFormData", $data, $object, $prepareParamters);
        }

        // return the data
        return view($this->formView, $data);
    }

    /**
     * Tries to run the given method with all the prepare parameters. Supports custom
     * data to be sent in repositories
     * @param $name
     * @param $data
     * @param $object
     * @param $prepareParamters
     */
    private function runPrepareFunction($name, &$data, $object, $prepareParamters) {
        if (method_exists($this, $name)) {
            // merge the parameters
            $parameters = array_merge([
                &$data, $object
            ], $prepareParamters);

            call_user_func_array(array($this, $name), $parameters);
        }
    }

    /**
     * Prepares the form data for all the views
     * @param array $data
     * @param Model|null $object
     */
    protected function prepareFormData(array &$data, $object) {}

    /**
     * Prepares the data only for the
     * @param array $data
     * @param $object
     */
    protected function prepareEditFormData(array &$data, $object) {}

    /**
     * Prepares the data for create form
     * @param array $data
     */
    protected function prepareCreateFormData(array &$data) {}
}