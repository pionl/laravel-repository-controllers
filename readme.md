# Errors

# Usage

For base controller (root table/model) without any parent relation use RepositoryControllerTrait

## RepositoryControllerTrait

In construct of the controller use the bootRepository() to prepare the repository data. This will also use translated
titles and etc.

    /**
     * RepositoryController constructor.
     */
    public function __construct()
    {
        parent:__construct();
        $this->bootRepository();
        $this->editTitle = "Editing this entry"; // call after boot!
    }
    
## ChildRepositoryControllerTrait