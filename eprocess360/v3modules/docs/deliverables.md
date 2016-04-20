##Project Approach

This document details how the module or system development projects will be approached, and what the deliverables will 
be for each stage of the development.  For questions or clarifications, please use the WC-3 Slack channels available 
here: [WC-3 Slack](https://wc-3.slack.com/messages/v3dev/).

* 1. Design Requirements
* 2. Design Specifications
* 3. API Specification
* 4. User Interface, Experience
* 5.1 Front End Programming
* 5.2 Back End Programming
* 6. Integration
* 7. Testing

###1. Design Requirements

Performed by WC3.  This is a document that will explain the requirements of the modules or systems that WC3 is 
requesting to be designed and programmed.  The document will be composed of basic functional and data requirements.  It 
may use such things as user stories and use cases.

###2. Design Specifications

Performed by the design team.  This is a GitHub markdown document that will explain how the design team intends to meet 
the module or system requirements expressed in step 1.  The document should go into technical details, including:

* Database Structure
* Flowcharts for UX
* How the Module Connects to the Project Controller
* How the Module will Commission/Decommission
* API Endpoints

Appropriate methodology should be applied when designing systems to limit replication of efforts, extraneous database 
queries, and inefficiencies in the user experience.

Include images as necessary to illustrate the design.

###3. API Specification

Performed by the design team.  This is a GitHub markdown document that will extensively detail how the module or system
API will get and send RESTful JSON data.  The API should use industry standard protocols that correlate the request 
method with an appropriate server-side action.

Protocol Example:

```
GET     /users          Get a list of users
GET     /users/1        Get a specific user
POST    /users          Create a new user
PUT     /users/1        Updates an existing user
PATCH   /users/1        Partially updates an existing user
DELETE  /users/1        Deletes an existing user
```

The API specification must detail every endpoint for every applicable potential action to be performed with or on the 
data associated with the module.  The endpoint names must not contain verbs except in the case of `/update` (see below).

High quality API design and specifications are required.  For examples of good API design please see: 

* [Best Practices for a Pragmatic RESTful API](http://www.vinaysahni.com/best-practices-for-a-pragmatic-restful-api)
* [Enchant REST API](https://dev.enchant.com/api/v1).

####HTTP Status Codes

We use HTTP status codes to indicate success or failure of a request.

#####Success codes:

* 200 OK - Request succeeded. Response included
* 201 Created - Resource created. URL to new resource in Location header
* 204 No Content - Request succeeded, but no response body

#####Error codes:

* 400 Bad Request - Could not parse request
* 401 Unauthorized - No authentication credentials provided or authentication failed
* 403 Forbidden - Authenticated user does not have access, also used to indicate old EULA acceptance
* 404 Not Found - Resource not found
* 415 Unsupported Media Type - POST/PUT/PATCH request occurred without a application/json content type
* 422 Unprocessable Entry - A request to modify or create a resource failed due to a validation error
* 429 Too Many Requests - Request rejected due to rate limiting
* 500, 501, 502, 503, etc - An internal server error occurred

####POST

Post requests are either POST requests from an HTML form, or POST requests through the api for creating a new object. 
When receiving data from an HTML form that updates an object in the system, the form POST requests should go to the POST 
endpoint appended by `/update`.  It should be specifically set aside for receiving form data.

####PUT

Put requests are to be used only to replace data.  They are not to be used for partial updates.

####PATCH

Patch requests should be made implementing [RFC 6902](http://tools.ietf.org/html/rfc6902) standardized PATCH requests.

###4. User Interface, Experience

Performed by the design team.  This is one or more Bootstrap templates with a supporting flowchart that details how they
connect to each other.  This step of the design can be performed at the same time as step 3.

eProcess360 is a 'Mobile First' web application.  All interfaces must be mobile friendly, and furthermore must make use 
of the Bootstrap functionality present in the core eProcess360 system with little to no modification.  In this way it's 
possible to stylize and adjust the interface without breaking dependencies.  Custom colors, stylesheets, and especially 
inline styles, are strongly discouraged.

Pushing the user from page to page for minute operations should be avoided.  Modals (pop-ups) are to be used only when 
absolutely necessary, but they are a good candidate if it helps to prevent the user from being pushed around.

###5. Programming

To be added later.

###6. Integration

To be added later.

###7. Testing

To be added later.