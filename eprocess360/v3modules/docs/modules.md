##v3 Controller Module Design

There are two different types of modules in eProcess360.  This document concerns those that operate as pieces of project
controllers.  These modules need to accomplish meaningful work in the scope of a project without making assumptions 
about the project or controller configuration outside of the module.

###Environment

Controller Modules depend on the Controller-Project environment to run.  The controller will load one or more instances 
of one or modules, assign them basic operating parameters, and then route requests to the modules when the client 
requests the endpoints given to the module.

```php
/**
 * The demonstration module 'Quotes' being included in a Controller.  This module will be made available at the project 
 * endpoint: /projects/:idProject/quote
 */
$this->addModule(
    Quotes::build(10, 'quote', 'Quote of the Microsecond')
);
```

In order for the modules to be properly configured within the Controller environment, the modules must be extensions of
the Module base class.  This allows the modules to gain access to the Controller, the Project, and also integrate with 
Warden, the Controller security system.

```php
use eprocess360\v3core\Module;
class Quotes extends Module
{ }
```

###Structure

Modules should closely adhere to the following structure of files and includes.  Provided is an example of the Quotes 
module:

```
\v3modules                  Located in /eprocess360 on v3 Git
    \Quotes
        \static             For static files, usually templates but could be images
            \twig           Twig templates only
            \handlebars     HandlebarsJS templates only
                module.quotes.main.html
            \presets        For presets and basic configurations (including those that might initially be loaded into DB
                quotes.php
        \Model              For specifying Model/Table classes that are not dynamic (see Models)
        Quotes.php          The main module class
```

Use PSR-4 naming conventions for the Classes and structure wherever possible.  The main Module class name should match
the directory name.  Template files should use the following naming conventions:

    HandlebarsJS    module.{moduleName}.{templateName}.html
    Twig            module.{moduleName}.{templateName}.html.twig

###Initialization

The Module can be initialized by either the Controller in a Project context or a setup context:

####Controller-Project Initialization

When the Controller expects to route a request to the Module, the `init()` method of the Controller is called.  This is 
useful for declaring variables and making connections that might otherwise be declared in the __construct() function.

```php
    public function init()
    {
        $this->keydict = new Keydict();
    }
```

####Controller-Module Commission (Initialization), Decommission

When a Controller is configured in the ProjectControllers table, the Controller will be soft-run to detect the required
settings and configurations for the Controller and the Modules attached to it.  It's possible to specify operations for 
when this occurs, and also when the Controller configuration is deleted.

    public function commission();
    public function decommission();
    
These functions should be responsible for setting up required database tables.  Controller Modules are only alive during
periods in which they are attached to Controllers, so at no time outside of Controller Module commissioning will these 
sorts of functions be activated.  Because of this, it's important to program these functions in the context of there 
being one or more instances of the module on one or more controllers at any one time, and to potentially detect when the 
module is absolutely not used anymore and perform cleanup operations.

###Routing

eProcess360 uses AltoRouter for routing requests.  After extending the Module base class, create a function in the new 
Module that adds routes to the router.  Each route here should route requests relative to the module (do not include
information for how to match things at the `/project/:idProject` directive).

```php
    public function routes()
    {
        $this->routes->map('GET', '/?[i:id]?', function ($id = false) {
            // ...
        });
    }
```

###Model-View-Controller

Modules are expected to be structured as MVC software.  In this way, all responses from the module back to the client 
should clearly differentiate between Model and View.  The core tenant of eProcess360's MVC implementation is the use of
an 'API First' methodology and a two-way approach to templating through the server-side Twig templating system, and 
HandlebarsJS for client-side templating.  Another important aspect of module design is that the API handler is the same 
as the HTML output handler, the only difference is that template information is provided to the client when serving an
HTML response.

###User Interface and Experience Approach

eProcess360 is a 'Mobile First' web application.  All interfaces must be mobile friendly, and furthermore must make use 
of the Bootstrap functionality present in the core eProcess360 system with little to no modification.  In this way it's 
possible to stylize and adjust the interface without breaking dependencies.  Custom colors, stylesheets, and especially 
inline styles, are strongly discouraged.

Pushing the user from page to page for minute operations should be avoided.  Modals (pop-ups) are to be used only when 
absolutely necessary, but they are a good candidate if it helps to prevent the user from being pushed around.

###Templating

Templates are specified in Twig or HandlebarsJS.  They are located in their respective directories in the module 
`/static` directory, which is located in the module root directory.

The module router is responsible for conveying the templates back to the controller via:

    $this->getController()->setTemplate( $this->getTemplates() );

####Server-side Templating

When serving static requests that will not make use of AJAX calls or dynamic content, use static Twig templates.  The 
templates should be placed in the `/static/twig` directory of the module root directory.

Only a single server-side template can be specified by the module, and that template needs to be communicated back to
the controller.

    $this->setTemplate('module.quotes.main.html.twig', 'server');
    
####Client-side Templating

Client-side templating allows for high controller over the user interface, including reusing templates and compiling 
new HTML from JSON data.  HandlebarsJS is used to handle all client-side templating.  It's possible to specify multiple 
templates to be loaded in the client, however by default only the `#hb-main-template` template is rendered.

    $this->setTemplate('module.quotes.main.html', 'client');
    $this->setTemplate(['module.quotes.main.html', ...], 'client');

Client-side templating works by using a special server-side template file.  This template file is `hb.base.html.twig` 
and is specially rendered with multiple avenues for modification.  It's possible to specify a custom Twig client-site 
handler template by extending the base file and modifying the Twig blocks.  For any custom AJAX or multiple-template 
interfaces, at least a partial extension of the base Twig file is necessary.

In most cases only `hbRuntime` will need to be modified.  See the `hb.base.html.twig` for the base example.

```twig
{% block hbData %}{# HandlebarsJS Data #}{% endblock %}
{% block hbTemplates %}{# HandlebarsJS Templates #}{% endblock %}
{% block hbInit %}{# HandlebarsJS Init Scripts #}{% endblock %}
{% block hbRuntime %}{# HandlebarsJS Runtime Scripts #}{% endblock %}
```

Client-side templates should be put in the `/static/handlebars` directory.

###Models and Data Storage, Access

The definition of structures and the storage of data, in the case of Controller Modules, must happen within the context
of the Controller and more specifically the Project.  Some modules will need to create global tables for storing 
information, while others will need access to the Controller Project information.  Some modules will need to even adjust
the Controller Project Model, and this will need to happen before the Controller Project table is created in the 
database.
