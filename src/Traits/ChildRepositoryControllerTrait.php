<?php
namespace Pion\Repository\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Pion\Repository\Controllers\RepositoryController;
use StandardExceptions\OperationExceptions\InvalidOperationException;
use StandardExceptions\ValidationExceptions\InvalidNumberException;

/**
 * Class ChildRepositoryControllerTrait
 *
 *
 * Dont use ChildRepositoryControllerTrait and RepositoryControllerTrait
 * A chield repository controllers enables connecting two controllers and with repository controllers
 * functions that will be defaulty used. Also provides genereting the navigation of the parent object.
 *
 * The routes must pass the parents id and then standart resource parameters:
 * Like: object.types (object/{parent}/...)
 *
 * You must overide the createRepository method and you must setup formView (optionaly you can provide custom
 * indexes for object and parent object in the view). getFormView is used in create and edit. Based on passing
 * the object you can detect if you are creating or updating.
 *
 * It's good to set the parents object index to same prefix for the child object relation connecting with id.
 * Like: object (key in object: object_id). This will enable automatic parent key retreive and etc.
 *
 * @package Pion\Repository\Traits
 */
trait ChildRepositoryControllerTrait
{
    use BaseRepositoryControllerTrait;

    /**
     * Defines the index for the parent object in the form view. Must be in the
     * data of the model (witout the _id)
     * @var string
     */
    protected $formParentObjectIndex = "parent";

    /**
     * Defines a list of collumns we want to select from the parent. If empty
     * the repository will use the name collumn and the key
     * @var array
     */
    protected $parentObjectSelect = [];

    /**
     * Defines the collumn for the parent object name
     * @var string
     */
    protected $parentObjectNameCollumn = "name";

    /**
     * A repository controller for using the repository function of the parent controller.
     *
     * @var RepositoryControllerTrait
     */
    protected $repositoryController;

    /**
     * ChildRepositoryControllerTrait constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if (empty($this->parentObjectSelect)) {
            $this->parentObjectSelect = [
                $this->repository->getObjectKeyCollumn(null),
                $this->parentObjectNameCollumn
            ];
        }

        /**
         * Becouse we want to reuse function from base repository
         * we need to pass our repository and store it to new property.
         * The methods can be overided becouse of the incorrect parameters
         */
        $this->repositoryController = new RepositoryController();
        $this->repositoryController->setRepository($this->repository);
    }

    /**
     * Creates a parent controller for connection. Must use the
     * RepositoryControllerTrait trait to create the correct navigation.
     *
     * @return RepositoryControllerTrait
     */
    abstract protected function createParentController();

    /**
     * Show the form for creating a new resource for the parent object
     *
     * @param int $parentId
     *
     * @return \Illuminate\Http\Response
     */
    public function create($parentId)
    {
        // check if valide int
        InvalidNumberException::throwIf($parentId);

        // get the parent object
        $parentObject = $this->createNavigationWithParentId($parentId, $this->createTitle);

        return $this->getFormView($parentObject);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param int $parentId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $parentId)
    {
        // check if the section has not changed
        return $this->checkParentIdWithRequest($request, $parentId)
            ->prepareRepositoryStoreActionName()
            ->repositoryController->store($request);
    }

    /**
     * Show the form for editing the specified resource for the parent object
     *
     * @param  int  $parentId
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($parentId, $id)
    {
        /** @var Model $object */
        $object = $this->repository->findOrFail($id);
        $sectionId = $this->getObjectsParentKey($object);

        if (!is_null($sectionId)) {
            $this->checkParentIds($parentId, $sectionId);
        }

        $section = $this->createNavigationWithParentId($parentId, $this->getEditTitleForObject($object));

        return $this->getFormView($section, $object);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @param  int $parentId
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $parentId, $id)
    {
        return $this->checkParentIdWithRequest($request, $parentId)
            ->repositoryController->update($request, $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $paretnId
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($paretnId, $id)
    {
        return $this->repositoryController->destroy($id);
    }


    ########
    ### Helpers methods
    ########

    /**
     * Tries to get the parents key from the object. Uses formParentObjectIndex and adds _id
     * @param Model $object
     * @return int
     */
    protected function getObjectsParentKey($object)
    {
        $index =  $this->getObjectsParentKeyIndex();
        return $object->$index;
    }

    /**
     * Returns a key used in the object for storing parent key. Adds id to the
     * formParentObjectIndex
     * @return string
     */
    protected function getObjectsParentKeyIndex()
    {
        return $this->formParentObjectIndex . "_id";
    }

    /**
     * Tires to get objects name (uses getName)
     * @param Model $object
     * @return string
     */
    protected function getObjectsName($object)
    {
        return $object->{$this->parentObjectNameCollumn};
    }

    /**
     * Builds the current edit title and the objects name. uses getObjectsName
     * @param Model $object
     * @return string
     */
    protected function getEditTitleForObject($object)
    {
        return $this->editTitle . " " . $this->getObjectsName($object);
    }

    /**
     * Removes the redirection action name form index to back option. The method
     * would try to get to the index. In this scenario we dont have index.
     *
     * @return $this
     */
    protected function prepareRepositoryStoreActionName()
    {
        // if we want to redirect the chield controller to edit after store
        if ($this->shouldRedirectToEditOnCreate()) {
            $this->repositoryController->setStoreRedirectionAction(
                $this->getCurrentActionForName("edit")
            );
        } else {
            $this->repositoryController->setStoreRedirectionAction(null);
        }
        return $this;
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
     * Checks if the parent id (uses formParentObjectIndex)
     * @param Request $request
     * @param $parentId
     * @return $this
     */
    protected function checkParentIdWithRequest(Request $request, $parentId)
    {
        return $this->checkParentIds($parentId, $request->get($this->getObjectsParentKeyIndex()));
    }

    /**
     * Checks if the parent ids are same
     *
     * @param int $parentId
     * @param int $nextParentId
     * @return $this
     */
    protected function checkParentIds($parentId, $nextParentId)
    {
        InvalidOperationException::ifFalse($parentId == $nextParentId, "Invalide parent id change.");
        return $this;
    }

    /**
     * Creates the parent controller navigation and then the current navigation. Returns the parent object
     *
     * @param int               $parentId
     * @param string|null       $title
     * @param Model|null        $modelForShow
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function createNavigationWithParentId($parentId, $title = null, $modelForShow = null)
    {
        // create the section controller for navigation and repository usage
        $parentController = $this->createParentController();

        // get parent object
        $parentObject = $parentController->getRepository()
            ->findOrFail($parentId, $this->parentObjectSelect);

        // create the navigation
        $parentController->createNavigation(null, $parentObject);

        $this->createNavigation($title, $modelForShow);
        return $parentObject;
    }

    /**
     * Returns the view with required data for every form (create or edit). It will add the correct
     *
     * @param Model $parentObject
     * @param Model|null $object
     * @param array $data
     *
     * @return View
     */
    protected function getFormView($parentObject, $object = null, $data = [])
    {
        InvalidOperationException::ifFalse(
            is_string($this->formView),
            "You must subclass this method and return a view for form");

        // add parent object to the view
        $data[$this->formParentObjectIndex] = $parentObject;

        // add current object to the view (in edit option)
        if (is_object($object)) {
            $data[$this->formObjectIndex] = $object;
        }

        // return the data
        return view($this->formView, $data);
    }
}