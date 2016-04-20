##Inspections

The v3 system is based on series of connected Controllers, organized in a tree structure. Each Controller will have its own ID that can be obtained through the method `getId()`. Controllers may also store ObjectIds, for example IDs of instantiated Projects, and may also refer to children elements, which may be other Controllers. Each Controller has as its root the base Controller, `SystemController`. Controllers may also be Persistent; Persistent Controllers are stored in the database, as opposed to being dynamically allocated.

Inspections will be a Module Controller; it will exist under any Controller that uses the Project Trait. Controllers that use the Project Trait are called Workflows.

Workflows are defined in folders under the base app folder `v3\app`; they combine Module Controllers together with logic to guide processes in a coherent way and satisfy business needs. Currently the only Workflow being used is `Dummy`.

Module Controllers have their Initialization and Commissioning functions defined inside their Workflow. Initialization functions are the functions that are run any time a particular Module is built, i.e. in every runtime the module is used. Commissioning functions are used for Persistent Controllers, which run once when the Module is first used. Once a Module is commissioned, the status of the Module is updated in its database structure to reflect such.

The initialization functions for Inspections will be placed inside `Dummy.php`, but ALL Inspections development goes in the folder `eprocess360\v3modules\Inspection`.

Inspections as a Module is a child of Dummy. Inspections initialization and commission functions are defined in `Dummy.php`:

`initInspections(Inspection $obj)`
`commissionInspections(Inspection $obj)`

For some practical examples of our current architecture and style being used, please refer to the other modules in `eprocess360\v3modules`. Task, specifically, is very good example of how our frontend works.

Loosely following a MVC format, a module is laid out like the following:

####Main Controller, Inspection.php:
The main Module Controller hosts the API Routes and any function that the Workflow needs in order to interact with Inspections.

Basic API for most data structures are:

    * GET
    * POST
    * PUT
    * DELETE

####Basic Functions
* `public function routes()`
   Function that defines all api routes in a given Module.

* `$this->getParent()->getObjectId();`
   In the case of Inspection, this retreives the objectID, in other words the Project ID, from its parent Workflow.

* `public function standardResponse($data)`
   Standard Response function to consolidate all settings of the Client-side and Server-side templates that will be used by the Module.


####Models, Model\Inspections.php:
Currently our system does not have a traditional Migrations system. Instead, database tables are built from the `keydict()` function in their corresponding Model. A model's `keydict()` function creates a table based on a collection of Keydict entries. Keydict entries themselves provide a standard way to validate data delivered to and received from the database. Keydict entries can also convert data between a SQL-friendly format and the data's natural format through wakeup() and sleep() methods.

All Keydict entries can be found in the folder `eprocess360\v3core\Keydict\Entry`.

Any function that deals directly with the Database should also be placed the Model.

####Basic Functions

`DB::sql($sqlString);`
   Function used to run SQL strings.

###Static:
For the frontend, we use a combination of Twig Templating and Backbone.js, to process and display information on Server and Client side respectively. Please look through the static folder in eprocess360\v3modules\Task\static to find out more about out front end architecture.