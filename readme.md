# Package in development progress

A few lines of code to create a form/create page via the repository way. In default the controller supports all the methods
for a CRUD (missing the index/show page, you can implement it on your end). The controller uses the repository class
that holds all the standart methods for the model, just create a Repository class for your eloquent model, return the class
and thats all.

# Changes

## 0.9.3
- hotfix for the prepareCreateFormData usage not passing the correct request object
- repository trait support createNavigationForCreateAction and createNavigationForEditAction
- updated RepositoryController to use abstract

## 0.9.2
- removed crazycodr/standard-exceptions depedency

## 0.9.1
- the getFormView in both repository controllers has new parameter with current Request
- all the prepareFormData methods has new parameter with current request (last property)
- the ChildRepositoryController trait has request parameter then parent parent object

# Requirements

    Laravel Framework (5.1, 5.2) - 5.0 not tested

# Installation
    
    composer require pion/laravel-repository-controllers
    
Add the service provider to copy the translations to the app.php config

    Pion\Repository\RepositoryServiceProvider::class
    
Run the publish command to copy the translations (Laravel 5.2)

    php artisan publish --provider="Pion\Repository\RepositoryServiceProvider"
    
Run the publish command to copy the translations (Laravel 5.1)

    php artisan vendor:publish --provider="Pion\Repository\RepositoryServiceProvider"

# Usage

- For model (root table/model) without any parent relation use RepositoryControllerTrait
- For model that is owned by different model you can use the ChildRepositoryControllerTrait (you must create the
controller for the parent model too)
- You must include the **CrumbsNavigationTrait** (for custom provider use **NavigationTrait** or 
fork the pion/laravel-support-controllers to implement favorite package ), otherwise the the logic will crash.
- The navigation trait can be added to the base controller.

## RepositoryControllerTrait

In construct of the controller use the bootRepository() to prepare the repository data. This will also use translated
titles and etc. The best practive is to build your own RepositoryController that uses the trait. Then you can extend the
methods fromt trait like a normal class. For default usage you can extend provided `RepositoryController`

    /**
     * RepositoryController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // required!
        $this->bootRepository();
        $this->formView = "admin.season.form"; // the desired form
        
        // optional
        $this->editTitle = "Editing this entry"; // call after boot!
        $this->formObjectIndex = "season";  // the index of the object in form
        $this->detailModelAction = "edit"; // instead of showing the detail action to show, use the edit page
        $this->setRedirectToEditOnCreate(true);
    }
    
or you can use
    
    /**
     * RepositoryController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->bootRepositoryWith("admin.season.form", "Create new!", "Edit this one");
    }
    
Implement the createRepository method. You can/need to return the repository

    /**
     * Creates a AreaRepository repository
     * @return AreaRepository
     */
    protected function createRepository()
    {
        return new AreaRepository(); // this will use the RepositoryTrait
    }
    
You can overide the form data for edit/create and both usages by extending these functions.

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
    
### Customizations for navigation
_Supported since 0.9.3 version_

You can customize the own navigation via methods (you can or not call the parent method with the trait subclass):

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
    
## ChildRepositoryControllerTrait 

Very similar boot usage as repository controller with more options:

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
    
You can overide the form data for edit/create and both usages by extending these functions.

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

## RepositoryTrait

The repository instance that will handle create/update/destroy and all the form validation needed. You can create
own BaseRepository that uses the RepositoryTrait and all the repositories will extend the BaseRepository.

For checkbox validation you can add a list of checkbox indexes that will be validated and will prefill 0 or 1 for the
unchecked/checked state. Unchecked the checkboxes will be added to the request with 0 value.

### Must implement

#### getClass

You must return the model class

    /**
     * @return string
     */
    protected function getClass()
    {
        return Area::class;
    }

#### getValidationRules($isNew, array $data, $model = null)

A set of validation rules. You can return empty array.

    protected function getValidationRules($isNew, array $data, $model = null)
    {
        return [
            "name" => "required|string|max:255",
            "position" => "required|numeric"
        ];
    }

## Extending the resource methods (or traits) with calling "parent" logic

You must use the desired trait and change the traits method you want to extend. Then you
can call the method with your own method.
    
    use ChildRepositoryControllerTrait {
        store as baseStore;
    }
        
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param int $parentId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $parentId)
    {
        // redirect to new form with old data so we can create same properties easier
        return $this->baseStore($request, $parentId)->withInput($request->except([
            "name", "position", "identifier"
        ]));
    }
    
# Todo 

- update readme
- add example
- separate the navigation usage?