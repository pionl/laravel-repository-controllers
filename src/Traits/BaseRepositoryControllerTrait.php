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
     * Boots the repository with the default values
     *
     * @return $this
     */
    protected function bootRepository()
    {
        return $this->bootRepositoryWith(null);
    }

    /**
     * Boots the repository with given parameters
     *
     * @param string      $formView
     * @param string|null $createTitle
     * @param string|null $editTitle
     *
     * @return $this
     */
    protected function bootRepositoryWith($formView, $createTitle = null, $editTitle = null)
    {
        // setup the default
        $this->createTitle = $this->titleWithTranslationFallback($createTitle, "titles.create");
        $this->editTitle = $this->titleWithTranslationFallback($editTitle, "titles.edit");
        $this->formView = $formView;

        // creates the repository
        $this->repository = $this->createRepository();

        return $this;
    }

    /**
     * A helper function to check if the provided value can be used for a title or
     * uses the package translation
     *
     * @param string $value
     * @param string $key
     *
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    private function titleWithTranslationFallback($value, $key)
    {
        if (is_string($value)) {
            return $value;
        }

        return rpPackageTrans($key);
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
     * @param array      $data             the array of data to use and pass to view
     * @param Model|null $object           the model for the current edit mode
     * @param Request    $request          the current request
     * @param array      $prepareParamters you can pass aditional data to the prepareForm methods. Will add after normal
     *                                     parameters
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \LogicException
     */
    protected function createFormView(array $data, $object, $request, $prepareParamters = [])
    {
        if (!is_string($this->formView)) {
            throw new \LogicException("Your form view is not set. Please setup your form view");
        }

        // build the base parameters that we will send to the prepare methods
        $parameters = array_merge([
            &$data, $object, $request
        ], $prepareParamters);

        // run the base prepare form data
        $this->runPrepareFunction("prepareFormData", $parameters);

        $isEdit = is_object($object);

        // add current object to the view (in edit option)
        if ($isEdit) {
            $data[$this->formObjectIndex] = $object;

            $this->runPrepareFunction("prepareEditFormData", $parameters);
        } else {
            // dont pass the object parameter that is null
            $this->runPrepareFunction("prepareCreateFormData", array_filter($parameters, function ($val) {
                return !is_null($val);
            }));
        }

        // return the data
        return view($this->formView, $data);
    }

    /**
     * Tries to run the given method with all the prepare parameters. Supports custom
     * data to be sent in repositories
     *
     * @param string     $name the method name
     * @param array      $parameters the parameters that will be sent to
     */
    private function runPrepareFunction($name, $parameters)
    {
        // a special fallback if the method exists. Wit
        if (method_exists($this, $name)) {
            call_user_func_array(array($this, $name), $parameters);
        }
    }

    /**
     * Prepares the form data for all the views
     *
     * @param array      $data
     * @param Model|null $object
     * @param Request    $request
     */
    protected function prepareFormData(array &$data, $object, $request) {}


    /**
     * Prepares the data only for the
     *
     * @param array   $data
     * @param Model   $object
     * @param Request $request
     */
    protected function prepareEditFormData(array &$data, $object, $request) {}


    /**
     * Prepares the data for create form
     *
     * @param array   $data
     * @param Request $request
     */
    protected function prepareCreateFormData(array &$data, $request) {}

}