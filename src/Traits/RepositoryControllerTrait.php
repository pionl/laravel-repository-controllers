<?php
namespace Pion\Repository\Traits;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Class RepositoryControllerTrait
 *
 * Uses basic methods for storing, updating and destroying the repository object. This is ideal for
 * using default usage of the repository
 *
 * Use must add the NavigationTrait
 *
 * @package Pion\Repository\Traits
 */
trait RepositoryControllerTrait {
    use BaseRepositoryControllerTrait;

    /**
     * Custom action redirection name for overiding the current context
     * @var string|boolean
     */
    private $storeRedirectionAction = false;

    /**
     * Renders the create page
     *
     * @param Request $request
     *
     * @return View
     */
    public function create(Request $request)
    {
        $this->createNavigationForCreateAction($request);
        return $this->getFormView($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // create the object and redirect to the index
        return $this->repository->createWithRedirect(
            $request, $this->getStoreRedirectActionName()
        );
    }

    /**
     * The edit of the object
     *
     * @param Request $request
     * @param mixed $id
     *
     * @return View
     */
    public function edit(Request $request, $id)
    {
        $object = $this->repository->findOrFail($id);

        $this->createNavigationForEditAction($request, $object);

        return $this->getFormView($request, $object);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        return $this->repository->updateWithRedirect($id, $request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->repository->deleteWithRedirect($id);
    }

    /**
     * Returns the action name (defaulty index page) to redirect on creating
     * Return null if you want to redirect back
     * @return string
     */
    protected function getStoreRedirectActionName()
    {
        if ($this->shouldRedirectToEditOnCreate()) {
            return $this->getCurrentActionForName("edit");
        } else if (is_bool($this->storeRedirectionAction)) {
            return $this->getCurrentActionForName("index");
        } else if (!is_null($this->storeRedirectionAction)) {
            return $this->storeRedirectionAction;
        } else {
            return null;
        }
    }

    /**
     * Sets the store redirection name for redirect on store method. Must be full name that will be used for url
     *
     * @param string $storeRedirectionAction
     * @return $this
     */
    public function setStoreRedirectionAction($storeRedirectionAction)
    {
        $this->storeRedirectionAction = $storeRedirectionAction;
        return $this;
    }

    public function setStoreRedirectionToActionName($name)
    {
        return $this->setStoreRedirectionAction($this->getCurrentActionForName($name));
    }
    /**
     * Should we redirect to edit on create
     * @param boolean $redirectToEditOnCreate
     * @return $this
     */
    public function setRedirectToEditOnCreate($redirectToEditOnCreate)
    {
        $this->redirectToEditOnCreate = $redirectToEditOnCreate;
        return $this;
    }

    /**
     * Determines if we should go to edit for redirect
     * @return boolean
     */
    public function shouldRedirectToEditOnCreate()
    {
        return $this->redirectToEditOnCreate;
    }

    /**
     * Returns the view with required data for every form (create or edit). It will add the correct
     *
     * @param Request $request
     * @param Model|null $object
     * @param array $data
     *
     * @return View
     */
    protected function getFormView($request, $object = null, $data = [])
    {
        return $this->createFormView($data, $object, $request);
    }

    #####
    ### Navigation
    #####

    /**
     * Generates the navigation for create action
     * @param Request $request
     */
    protected function createNavigationForCreateAction(Request $request)
    {
        $this->createNavigation($this->createTitle);
    }

    /**
     * Generates the navigaiton for edit action
     * @param Request $request
     * @param Model $object
     */
    protected function createNavigationForEditAction(Request $request, $object)
    {
        $this->createNavigation($this->editTitle, $object);
    }

    /**
     * Generates the navigation with list support.
     * @param string|null   $title              you can provide model to use as modelForShow. Title will have null value
     * @param Model|null    $modelForShow      indicates if we want to show a model with show url
     */
    abstract protected function createNavigation($title = null, $modelForShow = null);
}