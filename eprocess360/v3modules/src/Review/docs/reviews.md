##Reviews

An object can have ReviewCoordinator from the Controller.  When this is specified, the 
object should operate as though it has reviews.

```
// in PC in initSubmittals();
Submittals::bindReviewCoordinator($this->getModule('reviews'));
// in Submittals
public function bindReviewCoordinator(ReviewCoordinator $coordinator)
{
    $this->reviewCoordinator = $coordinator;
    // After an object has been bound with a ReviewCoordinator, the module responsible for the object should activate 
    // 'reviews' endpoints.  bindRoutes will also determine the object identifier using the variables in the path.
    $this->reviewCoordinator->bindRoutes($thisModule, '/submittals/[i:idSubmittal]');
    // In this case idSubmittal becomes the identifier
}
```

```
$this->routes->map('GET', '/submittals/[i:idSubmittal]/reviews', function () {
    $this->verifyRole(Roles::ROLE_READ);
    $this->getReviewCoordinator()->main();
}
public function getReviewCoordinator()
{
    $reviewCoordinator = $this->reviewCoordinator;
    $reviewCoordinator->identify($this->data->idSubmittal);
    return $reviewCoordinator;
}
```

It will have an admin interface and a commission method.  The admin interface will allow project variables to include 
reviews, and the commission method will setup the tables.

As a response to a trigger that has a Submittal as an eventObject:

```
$submittal->getReviewCoordinator()
    ->addDefaultReviews();
```

The following endpoints are expected:

    GET     /reviews
    POST    /reviews
    GET     /reviews/[id]
    PUT     /reviews/[id]
    PATCH   /reviews/[id]
    DELETE  /reviews/[id]

Each review has a files endpoint:

    GET     /reviews/[id]/files
    POST    /reviews/[id]/files
    PUT     /reviews/[id]/files/[id]
    PATCH   /reviews/[id]/files/[id]
    DELETE  /reviews/[id]/files/[id]
    
Additionally, the types endpoint should be available for reporting available ReviewTypes:

    GET     /reviews/types

###Tasks

When a review is created, a task is created as well, and the idTask generated is saved into the Reviews table.

```
Reviews: idTask, idObject, idFolder, idReviewType, status [isAccepted]
Tasks: idTask, idComponent, idProject, idGroup, idUser, type, description, dateStart, dateDue, dateEnd, status [isComplete]
```

The ReviewCoordinator provides access to triggers for Task events:

    /**
     * @param ReviewCoordinator $obj
     */
    public function initReviews(ReviewCoordinator $obj)
    {
        $obj->setTask($this->getTask('review')); // may not be necessary
        
        // can use callbacks to get information fro mthe review object on the fly
        $obj->setTaskType(function ($reviewObject) {
            return "{$reviewObject->getReviewType()} Review";
        });
        $obj->setTaskDescription(function ($reviewObject) {
            return "{$this->getKeydict()->address->get()}";
        });
        $obj->onReviewComplete(function() use ($obj) {
            // do something because a review is complete
        });
        $obj->onReviewOverdue(function() use ($obj) {
            // this would be called by the Task engine via /reviews/{$idTask}/overdue
        });
    }

###Admin

In order for project variables to be translated to user groups to perform reviews, the ReviewCoordinator should 
implement reusable tools for picking preferred user groups and assigning them a description:

``` /controller/1/reviews/types

* Add, edit, remove reviews that can be used
* Possible idGroup options come from system groups
* The title describes the review (Building, Structural, etc)
* Currently one idGroup for one idReviewType
* store in ReviewTypes [idReviewType, idGroup, title, idController, idComponent]

``` /controller/1/reviews/rules

* Add, edit, remove rules that are used to determine what review types are applied
* Rules are checked in order
* variable is the path to the keydict variable to check
* comparison says how to compare the variables (=, !=, etc)
* against is the value to compare against
* inverseSet will cancel a previously true result
* This should be built as a reusable tool set
* Always operates on the entire project keydict
* ReviewRules [idReviewRule, idController, idComponent, itemOrder, variable, comparison, against, inverseSet, idReviewType]
