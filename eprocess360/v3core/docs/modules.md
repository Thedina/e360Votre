**Modules**

A module is a loose term for a component that is implemented by a Project Controller and used to provide some sort of 
valuable functionality.  Modules typically have reporting and admin functionality as well.  Presently modules are 
integrated with the foundation and not entirely modular, but they stand on their own when interfaced from a project 
controller.  Modules can have their own tables in the database and manipulate project data.  Modules should not 
manipulate the data created by other modules outside of the project scope.

Modules should extend the base Module class \eprocess360\v3core\Module, and should be self contained in their own
directory.  For example, the Submittals module, which uses SubmittalPhasesRoot as a main Class:

\eprocess360\v3core\src\Modules\Submittals\SubmittalPhasesRoot.php
```
namespace \eprocess360\v3core\src\Modules\Submittals
class SubmittalPhasesRoot extends \eprocess360\v3core\Module
```

Modules become activated and accessible per Project via the Controller.  When a Controller maps a module, the module is
loaded and attached.  The module is also responsible for adding routing information into the Controller.

In order to map a module or sub-component of a module into the Controller, use the map() method on the Controller.

```php
$this->map(
    SubmittalPhasesRoot::build(4, 'submittals', 'Submittal Phases')
);
```

In the module instantiation, there are two key components: idEventObject and routingKey.  In the above example, 4 is the
idEventObject and 'submittals' is the routingKey.  idEventObject is used for numerical identification of the module, and
routingKey provides the name of the interface used to access the module via the Project interface.  In this case, the 
module will be available at:

    /project/1234/submittals
    
And the API will be available at:

    /api/v1/project/1234/submittals
    
**Map Procedures**

During module mapping, a barely instantiated module will be passed as a parameter to the map() method on the 
Controller.  The map() method will call methods on the module:

Set the Controller/Project to the Module:

    $module->setController($this);

If the Controller is in register mode or the system detects the current tables are out of data, the Module will be asked 
to prepare databases.  prepareDatabases() should be a function that creates data tables required for the operation of 
the Module.  It should analyze/create both generic and Controller specific data tables.  For Controller specific tables,
the Module can coordinate with the Controller to determine exact settings.

    $module->prepareDatabases();
    
In the case a Module needs to load presets, and executed during register/out-of-date, the following method will be 
called:

    $module->preparePresets();
    
The Module is also given a chance to register itself for Admin functions.  The actual implementation is yet to be
determined.

    $module->register();
    
When the Module creates Tables they should be registered in the TableController (presently not available).  The general
idea is that the Model for the Table is registered so the system can find it later.  Also the ControllerID should be 
specified if it's a Controller specific data table.  Duplicates in the Module Model class + idController will throw an 
Exception, use remove() first.

```php
TableController::add(new Submittals\Models\Submittals);
TableController::add(new Submittals\Models\Submittals, $this->getController()->getControllerID());
```

Controller::map() will lastly register the routingKey into its routing table and tie it to the instantiated Module.

```php
$this->getRouter()->addRoute($module->getRoutingKey(), function() {
    return $module->route();
});
```

**Request Routing**

Module::route() is the main handler for requests that need to be handled by the Module.  Modules can use any system they 
like to handle routing, however AltoRouter is the preferred method.  If the request is for REST API, Module::routeApi() 
will be called instead.

The router will be given full control of the response, however because of the possibility of interrupts on the stack,
should transmit its intended result into the Controller.

    $this->getController->setResponse(200, 'text/html', $response);
    
If the provided response is a callable, the result of the callable will be used.  It will be executed at the last 
possible moment.

If the response uses or needs to incur an API call charge, include the number of charges:

    $this->getController->setResponse(200, 'application/json', $response, 2);
    
**Templating**

When generating an HTML response, the module is required to render the entire page.  Templates for the module should be 
declared in the Templates directory of the Module source directory and this directory should be properly added to Twig 
templates loader in Module::route().

    \eprocess360\v3core\src\Modules\Submittals\Templates

If the Module is not using the standard Module template available at module.html.twig, the Module implementation should 
extend it.  The template contains all the core components needs to render a page in the context of a Controller project.

Component scripts or files such as JavaScript should be stored in the Resources directory:

    \eprocess360\v3core\src\Modules\Submittals\Resources

The base directory for Resources can be pulled into templates via Module::getResourceRoot().  This method is accessible 
via global Pool->Module as well.


