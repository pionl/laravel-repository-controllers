<?php
namespace Pion\Repository\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Pion\Support\Controllers\Traits\URLTrait;

/**
 * Class BaseRepositoryControllerTrait
 *
 * The base trait used in the other repository controllers traits
 *
 * @package Pion\Repository\Traits
 */
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
        $this->createTitle = rpPackageTrans("titles.create");
        $this->editTitle = rpPackageTrans("titles.edit");

        // creates the repository
        $this->repository = $this->createRepository();
    }

    /**
     * Creates a repository we will use
     * @return RepositoryTrait
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
     * @param array $data               the array of data to use and pass to view
     * @param Model|null $object        the model for the current edit mode
     * @param Request $request          the current request
     * @param array $prepareParamters   you can pass aditional data to the prepareForm methods. Will add after normal
     * parameters
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \LogicException
     */
    protected function createFormView(array $data, $object, $request, $prepareParamters = []) {
        if (!is_string($this->formView)) {
            throw new \LogicException("You must subclass this method and return a view for form");
        }

        $this->runPrepareFunction("prepareFormData", $data, $object, $request, $prepareParamters);

        $isEdit = is_object($object);

        // add current object to the view (in edit option)
        if ($isEdit) {
            $data[$this->formObjectIndex] = $object;

            $this->runPrepareFunction("prepareEditFormData", $data, $object, $request, $prepareParamters);
        } else {
            $this->runPrepareFunction("prepareCreateFormData", $data, $object, $request, $prepareParamters);
        }

        // return the data
        return view($this->formView, $data);
    }

    /**
     * Tries to run the given method with all the prepare parameters. Supports custom
     * data to be sent in repositories
     * @param string $name
     * @param array &$data
     * @param Model|null $object
     * @param Request $request
     * @param array $prepareParamters
     */
    private function runPrepareFunction($name, &$data, $object, $request, $prepareParamters) {
        if (method_exists($this, $name)) {
            // merge the parameters
            $parameters = array_merge([
                &$data, $object, $request
            ], $prepareParamters);

            call_user_func_array(array($this, $name), $parameters);
        }
    }

    /**
     * Prepares the form data for all the views
     * @param array $data
     * @param Model|null $object
     * @param Request $request
     */
    protected function prepareFormData(array &$data, $object, $request) {}

    /**
     * Prepares the data only for the
     * @param array $data
     * @param Model $object
     * @param Request $request
     */
    protected function prepareEditFormData(array &$data, $object, $request) {}

    /**
     * Prepares the data for create form
     * @param array $data
     * @param Request $request
     */
    protected function prepareCreateFormData(array &$data, $request) {}
}