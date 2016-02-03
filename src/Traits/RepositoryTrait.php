<?php
namespace Pion\Repository\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use StandardExceptions\OperationExceptions\InvalidOperationException;

/**
 * Class RepositoryTrait
 * @package Pion\Repository\Traits
 */
trait RepositoryTrait
{
    use ValidatesRequests;

    /**
     * A list of checkboxes that can be send in save/update request
     * @var array
     */
    protected $validateCheckboxes = [];

    /**
     * The object name collumn
     * @var string
     */
    protected $objectNameCollumn = "name";

    /**
     * The default model key collumn. Fill be prefiled from the object
     * @var string
     */
    protected $objectKeyCollumn = null;

    /**
     * The index used for success message on redirect
     * @var string
     */
    protected $redirectSuccessIndex = "success";

    #######
    ### Object helpers
    #######

    /**
     * Must be subclassed. Should return  class that represents the object for the repository
     *
     * @return string
     */
    abstract protected function getClass();

    /**
     * Returns the new object instance from the class.
     * @return Model
     */
    public function getObjectInstance()
    {
        // get the current class
        $class = $this->getClass();

        return (new $class());
    }

    /**
     * Returns the object name collumn. If the provided collumn is null
     * the default object name collumn is returned $this->objectNameCollumn
     * @param string $collumn
     * @return string
     */
    public function getObjectNameCollumn($collumn)
    {
        return is_null($collumn) ? $this->objectNameCollumn : $collumn;
    }

    /**
     * Returns the object key collumn. If the provided collumn is null
     * the default object key collumn is returned $this->objectKeyCollumn
     * @param string $collumn
     * @return string
     */
    public function getObjectKeyCollumn($collumn)
    {
        // prefil the default value
        if (is_null($this->objectKeyCollumn)) {
            $this->objectKeyCollumn = $this->getObjectInstance()->getKeyName();
        }

        return is_null($collumn) ? $this->objectKeyCollumn : $collumn;
    }

    /**
     * Calls the save method
     * @param Model $model
     * @return bool|null
     */
    public function save(Model $model)
    {
        return $model->save();
    }

    /**
     * Delets the model
     * @param Model $model
     * @return bool|null
     * @throws \Exception
     */
    public function delete(Model $model)
    {
        return $model->delete();
    }

    #######
    ### Object queries
    #######

    /**
     * Retunrs new query for current model.
     * @param string|null $name when you provide name of the method, the class
     * can have an extendNameQuery($query,..) function that can change the query
     * @param array $parametersForExtend    the list of parameters that will be called to method or callback
     * @param \Closure   $callback       a callback with query function
     *
     * @return Builder
     */
    public function getNewQuery($name = null, $parametersForExtend = [], $callback = null)
    {
        $object = $this->getObjectInstance();

        // enable custom extending of quieries
        return $this->extendQuery($name, $object->newQuery(), $parametersForExtend, $callback);
    }

    /**
     * Enables the query overide by the name of the "method". Can pass aditional parameters. Uses extendNameQuery
     * Example: lists has extendListsQuery($query, ....)
     *
     * @param string|null $name     when you provide name of the method, the class
     * can have an extendNameQuery($query,..) function that can change the query
     *
     * @param Builder $query
     * @param array $parametersForExtend    the list of parameters that will be called to method or callback
     * @param \Closure   $callback       a callback with query function
     *
     * @return Builder
     */
    protected function extendQuery($name, $query, $parametersForExtend = [], $callback = null)
    {
        // check if the parameters is callback instead
        if (is_callable($parametersForExtend)) {
            $callback = $parametersForExtend;
            $parametersForExtend = [];
        }

        // check if we have name for extend
        if (is_string($name)) {
            $method = "extend".ucfirst($name)."Query";

            // check if the method exits
            if (method_exists($this, $method)) {
                return call_user_func_array([$this, $method], [
                        $query
                    ] + $parametersForExtend);
            }
        }

        // call callback for query
        if (is_callable($callback)) {
            return $callback($query);
        }

        return $query;
    }

    #######
    #### Quick queries
    #######

    /**
     * Returns a lists of name grouped by the id
     *
     * @param string        $nameCollumn
     * @param string        $keyCollumn
     * @param \Closure|null  $callback       a callback with query function
     *
     * @return \Illuminate\Support\Collection
     */
    public function lists($nameCollumn = null, $keyCollumn = null, $callback = null)
    {
        $name = $this->getObjectNameCollumn($nameCollumn);

        return $this->getNewQuery("lists", [], $callback)
            ->orderBy($name)
            ->lists(
                $name,
                $this->getObjectKeyCollumn($keyCollumn)
            );
    }

    /**
     * Returns a lists of name grouped by the id with where condition
     *
     * @param string        $collumn
     * @param mixed         $value
     * @param string        $nameCollumn
     * @param string        $keyCollumn
     * @param \Closure|null  $callback       a callback with query function
     * @return Collection
     */
    public function listsWhere($collumn, $value, $nameCollumn = null, $keyCollumn = null, $callback = null)
    {
        return $this->lists($nameCollumn, $keyCollumn,
            function ($query) use ($collumn, $value, $callback) {

                /** @var Builder $query */
                $query->where($collumn, $value);

                if (is_callable($callback)) {
                    return $callback($query);
                }
                return $query;
            });
    }

    /**
     * Returns a object by id if exists in database. If not throws an exception
     *
     * @param int $id
     * @param array $select
     *
     * @return Model
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($id, $select = ["*"])
    {
        return $this->getNewQuery("findOrFail")->findOrFail($id, $select);
    }

    #######
    ### Requests
    #######

    /**
     * Checks if the request has all valide data
     *
     * @param Request $request
     * @param Model|null $model
     *
     * @return array
     */
    public function checkRequest(Request $request, Model $model = null)
    {
        // get the data
        $data = $this->getDataFromRequest($request);

        $hasValidateCheckboxes = count($this->validateCheckboxes);

        $rules = $this->getValidationRules(is_null($model->getKey()), $data, $model);

        if ($hasValidateCheckboxes) {
            $rules += $this->getValidationRulesForCheckboxes();
        }

        // run the validation
        $this->validate($request, $rules);

        // loop possible checkbox validations
        if (count($this->validateCheckboxes)) {
            foreach ($this->validateCheckboxes as $checkbox) {
                $this->checkCheckboxValue($data, $checkbox);
            }
        }

        return $data;
    }

    /**
     * Called when check request is called. Runs the validation rules that can be extended
     *
     * @param boolean   $isNew
     * @param array     $data
     * @param Model|null $model
     *
     * @return array
     */
    abstract protected function getValidationRules($isNew, array $data, $model = null);

    /**
     * Reeturns the validation rules for checkboxes
     * @return array
     */
    protected function getValidationRulesForCheckboxes()
    {
        $rules = [];

        foreach ($this->validateCheckboxes as $checkbox) {
            $rules[$checkbox] = "sometimes|boolean";
        }

        return $rules;
    }

    /**
     * Returns the data for the request. Defaulty returns all the data
     * @param Request $request
     * @return array
     */
    protected function getDataFromRequest(Request $request)
    {
        return $request->all();
    }

    /**
     * Updates the entry by the id. The getClass must be ovireded.
     *
     * @param $id
     * @param Request $request
     * @param \Closure|null  $callback   a callback to run on save request before saving
     *
     * @return Model
     *
     * @throws ModelNotFoundException
     */
    public function update($id, $request, $callback = null)
    {
        $object = $this->findOrFail($id);
        return $this->saveWithRequest($request, $object, $callback);
    }

    /**
     * Updates the netry by the id. Object and redirects back (or to defined action) with success message
     *
     * @param int $id
     * @param Request $request
     * @param string|null   $redirectToAction       the controller name and action method.
     * If the method has index method, it will not add the object key. If you provide string as url without @
     * it will redirect to the given url
     * @param \Closure|null  $callback   a callback to run on save request before saving
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateWithRedirect($id, Request $request, $redirectToAction = null, $callback = null)
    {
        $object = $this->update($id, $request, $callback);
        return $this->createRedirectObjectWithSuccess(rpPackageTrans("messages.updated"), $object, $redirectToAction);
    }

    /**
     * Creates object with the request
     * @param Request       $request
     * @param \Closure|null  $callback   a callback to run on save request before saving
     * @return Model
     */
    public function create(Request $request, $callback = null)
    {
        // create object and save
        return $this->saveWithRequest($request,  $this->getObjectInstance(), $callback);
    }

    /**
     * Creates object and redirects back with success message
     * @param Request       $request
     * @param string|null   $redirectToAction       the controller name and action method.
     * If the method has index method, it will not add the object key. If you provide string as url without @
     * it will redirect to the given url
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createWithRedirect(Request $request, $redirectToAction = null)
    {
        $object = $this->create($request);

        return $this->createRedirectObjectWithSuccess(rpPackageTrans("messages.created"), $object, $redirectToAction);
    }

    /**
     * Creates a redirect object with success message. If the redirect to action and object is not provided
     * it will create back redirect.
     *
     * @param string $message
     * @param Model|null        $object             model to use when $redirectToAction is provided
     * @param string|null       $redirectToAction   the controller name and action method.
     * If the method has index method, it will not add the object key. If you provide string as url without @
     * it will redirect to the given url
     *
     * @return mixed
     */
    public function createRedirectObjectWithSuccess($message, Model $object = null, $redirectToAction = null)
    {
        // create redirect
        $redirect = redirect();

        $finalRedirect = null;

        if (is_null($redirectToAction)) {
            $finalRedirect = $redirect->back();
        } else {
            if (strpos($redirectToAction, "@") === false) {
                $finalRedirect = $redirect->to($redirectToAction);
            } else {

                // determine if we need to send the key
                $isIndex = strpos($redirectToAction, "index") !== false;

                // create and action for redirect
                $finalRedirect = $redirect->action(
                    $redirectToAction,
                    $this->getRedirectDataForAction($isIndex, $object)
                );
            }

        }

        return $finalRedirect->with($this->redirectSuccessIndex, $message);
    }

    /**
     * Returns array of values which will be used for creating the action url
     * @param bool $isIndex
     * @param Model $object
     * @return array
     */
    protected function getRedirectDataForAction($isIndex, $object)
    {
        // check if the obj on non index action
        if (!$isIndex) {
            InvalidOperationException::ifFalse(is_object($object));
        }

        return $isIndex ? [] : [
            $object->getKey()
        ];
    }

    /**
     * Delets the object by the key
     * @param $id
     * @return Model
     */
    public function deleteByKey($id)
    {
        // get the object
        $object = $this->findOrFail($id);

        $this->delete($object);
        return $object;
    }

    /**
     * Deletes the object with redirect
     *
     * @param int $id
     * @param string|null       $redirectToAction   the controller name and action method.
     * If the method has index method, it will not add the object key. If you provide string as url without @
     * it will redirect to the given url
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteWithRedirect($id, $redirectToAction = null)
    {
        $this->deleteByKey($id);
        return $this->createRedirectObjectWithSuccess(rpPackageTrans("messages.deleted"), null, $redirectToAction);
    }

    /**
     * @param Request       $request
     * @param Model         $object     a model to save
     * @param \Closure|null  $callback   a callback to run on save request before saving
     * @return Model
     */
    public function saveWithRequest(Request $request, $object, $callback = null)
    {
        // get the data and check the request
        $data = $this->checkRequest($request, $object);

        // fill the data
        $object->fill($data);

        if (is_callable($callback)) {
            $callback($object);
        }

        // save the object
        $this->save($object);

        return $object;
    }

    /**
     * Checks if the checkbox value is presented in data. If not it will insert
     * the default value
     *
     * @param array     $data
     * @param string    $index
     * @param int       $default
     * @return $this
     */
    public function checkCheckboxValue(array &$data, $index, $default = 0)
    {
        if (!array_key_exists($index, $data)) {
            $data[$index] = $default;
        }

        return $this;
    }
}