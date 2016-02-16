<?php
namespace Pion\Repository\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Pion\Repository\Controllers\RepositoryController;

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
    /**
     * Extend the base repository controller and add alias to bootRepository
     * for extending usage
     */
    use BaseRepositoryControllerTrait {
        bootRepository as bootBaseRepository;
    }

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
     * Boots the basic attributes
     */
    protected function bootRepository()
    {
        $this->bootBaseRepository();

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
     * @param Request $request
     * @param int $parentId
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $parentId)
    {
        // check if valide int
        if (!is_numeric($parentId)) {
            throw new \InvalidArgumentException("Invalide parent number");
        }

        // get the parent object
        $parentObject = $this->createNavigationWithParentId($parentId, $this->createTitle);

        return $this->getFormView($parentObject, $request);
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
     * @param Request $request
     * @param  int $parentId
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $parentId, $id)
    {
        /** @var Model $object */
        $object = $this->repository->findOrFail($id);
        $sectionId = $this->getObjectsParentKey($object);

        if (!is_null($sectionId)) {
            $this->checkParentIds($parentId, $sectionId);
        }

        $section = $this->createNavigationWithParentId($parentId, $this->getEditTitleForObject($object));

        return $this->getFormView($section, $request, $object);
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
     * @param  int $parentId
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($parentId, $id)
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
     * @throws \LogicException
     */
    protected function checkParentIds($parentId, $nextParentId)
    {
        if ($parentId != $nextParentId) {
            throw new \LogicException("Invalide parent id change.");
        }

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
     * @param Model $parentObject the parent object that owns the current object (or will own)
     * @param Request $request
     * @param Model|null $object
     * @param array $data
     * @return View
     */
    protected function getFormView($parentObject, $request, $object = null, $data = [])
    {
        // add parent object to the view
        $data[$this->formParentObjectIndex] = $parentObject;

        // the child repository need to use the parent object and will be sent as third option
        $parameters = [$parentObject];
        return $this->createFormView($data, $object, $request, $parameters);
    }

    /**
     * Prepares the form data for all the views
     * @param array $data
     * @param Model|null $object
     * @param Model $parentObject
     */
    protected function prepareFormData(array &$data, $object, $parentObject) {}

    /**
     * Prepares the data only for the
     * @param array $data
     * @param Model $object
     * @param Request $request
     * @param Model $parentObject
     */
    protected function prepareEditFormData(array &$data, $object, $request, $parentObject) {}

    /**
     * Prepares the data for create form
     * @param array $data
     * @param Request $request
     * @param Model $parentObject
     */
    protected function prepareCreateFormData(array &$data, $request, $parentObject) {}
}