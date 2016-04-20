##Submittal API

```
GET     /routerEndpoint                             Get a list of submittals and submittal phases user can view
GET     /routerEndpoint/1                           Get a specific Submittal Phase (accept 'depth' querystring)
GET     /routerEndpoint[/1]/submittals/1            Gets a specific submittal wiht files
GET     /routerEndpoint/1/submittals                Get submittals for a specific Submittal Phase with Files
GET     /routerEndpoint[/1]/submittals/1/files/1     Get a File from a Submittal
POST    /routerEndpoint[/1]                         Create a new Submittal Phase on the root [or the container specified]
POST    /routerEndpoint[/1]/submittals              Create a new submittal under a Submittal Phase
POST    /routerEndpoint[/1]/submittals/1/files      Creates and Uploads new Files under a Submittal
PUT     /routerEndpoint/1                           Updates an existing Submittal Phase
PUT     /routerEndpoint[/1]/submittals/1            Updates an existing submittal
PUT     /routerEndpoint[/1]/submittals/1/files/1    Updates an existing File from a Submittal
DELETE  /routerEndpoint/1                           Deletes an existing Submittal Phase
DELETE  /routerEndpoint[/1]/submittals/1            Deletes an existing submittal
DELETE  /routerEndpoint[/1]/submittals/1/files/1    Deletes an existing File from a Submittal
```
####GET

###/routerEndpoint:
   #Expects:
        {}

   #Returns:
   Example:
        data:
              [
                {
                  "idSubmittalPhase": "1",
                  "title": "RANDOM TITLE",
                  "description": "RANDOM DESU",
                  "idProject": "1",
                  "idParent": "0",
                  "idFolder": "1",
                  "idComponent": "14",
                  "depth": "2",
                  "sequenceNumber": "0",
                  "childNextSequenceNumber": "1",
                  "status": {
                    "isComplete": true,
                    "limitOneIncomplete": false,
                    "isIncomplete": false
                  },
                  "links": [
                    {
                      "rel": "self",
                      "title": "Title 2",
                      "href": "http://104.197.110.179/submittals/31"
                    },
                    {
                      "rel": "parent",
                      "title": "Base Submittals",
                      "href": "http://104.197.110.179/submittals"
                    }
                  ],
                  "children": [
                    {
                      "idSubmittalPhase": "2",
                      "title": "RANDOM TITLE",
                      "description": "RANDOM DESU",
                      "idProject": "1",
                      "idParent": "1",
                      "idFolder": "2",
                      "idComponent": "14",
                      "depth": "1",
                      "sequenceNumber": "0",
                      "childNextSequenceNumber": "1",
                      "status": {
                        "isComplete": true,
                        "limitOneIncomplete": false,
                        "isIncomplete": false
                      },
                      "children": [
                        {
                          "idSubmittalPhase": "3",
                          "title": "RANDOM TITLE",
                          "description": "RANDOM DESU",
                          "idProject": "1",
                          "idParent": "2",
                          "idFolder": "3",
                          "idComponent": "14",
                          "depth": "0",
                          "sequenceNumber": "0",
                          "childNextSequenceNumber": "1",
                          "status": {
                            "isComplete": true,
                            "limitOneIncomplete": false,
                            "isIncomplete": false
                          }
                        },
                        {
                          "idSubmittalPhase": "6",
                          "title": "RANDOM TITLE",
                          "description": "RANDOM DESU",
                          "idProject": "1",
                          "idParent": "2",
                          "idFolder": "6",
                          "idComponent": "14",
                          "depth": "0",
                          "sequenceNumber": "0",
                          "childNextSequenceNumber": "1",
                          "status": {
                            "isComplete": true,
                            "limitOneIncomplete": false,
                            "isIncomplete": false
                          }
                        }
                      ]
                    },
                    {
                      "idSubmittalPhase": "5",
                      "title": "RANDOM TITLE",
                      "description": "RANDOM DESU",
                      "idProject": "1",
                      "idParent": "1",
                      "idFolder": "5",
                      "idComponent": "14",
                      "depth": "1",
                      "sequenceNumber": "0",
                      "childNextSequenceNumber": "1",
                      "status": {
                        "isComplete": true,
                        "limitOneIncomplete": false,
                        "isIncomplete": false
                      }
                    }
                  ]
                },
                {
                  "idSubmittalPhase": "4",
                  "title": "RANDOM TITLE",
                  "description": "RANDOM DESU",
                  "idProject": "1",
                  "idParent": "0",
                  "idFolder": "4",
                  "idComponent": "14",
                  "depth": "2",
                  "sequenceNumber": "0",
                  "childNextSequenceNumber": "1",
                  "status": {
                    "isComplete": true,
                    "limitOneIncomplete": false,
                    "isIncomplete": false
                  }
                }
              ]

####/routerEndpoint/1
   #Expects:
        {}

   #Return:
       data:
            {
                {
                     'idSubmittalPhase': Integer,
                     'title': String,
                     'description': String,
                     'idProject': String,
                     'idParent': Integer,
                     'idFolder': Integer,
                     'idComponent': Integer,
                     'depth': Integer,
                     'sequenceNumber': Integer,
                     'childNextSequenceNumber': Boolean,
                     'status' {
                             'isComplete': Boolean;
                             'limitOneIncomplete': Boolean;
                             }
                },...
            }

###/routerEndpoint[/1]/submittals/1
   #Expects:
          {}

   #Returns:
       data:
           {
               {
                   'idSubmittal': Integer,
                   'sequenceNumber': Integer,
                   'idUser': Integer,
                   'idFolder': Integer,
                   'idSubmittalPhase': Integer,
                   'dateCreated': String,
                   'dateCompleted': String,
                   'status'{
                       'isComplete': Boolean,
                       'hasReview': Boolean
                           }
               },...
           }

###/routerEndpoint/1/submittals
   #Expects:
          {}

   #Returns:
       data:
            {
              "idSubmittal": Integer,
              "sequenceNumber": Integer,
              "idUser": Integer,
              "idFolder": Integer,
              "idSubmittalPhase": Integer,
              "dateCreated": String,
              "dateCompleted": String,
              "status": {
                "isComplete": Boolean,
                "hasReview": Boolean
              },
              "files": [
                {
                  "idFile": Integer,
                  "idFolder": Integer,
                  "idUser": Integer,
                  "fileName": String,
                  "category": String,
                  "description": String,
                  "size": Integer,
                  "dateCreated": String,
                  "cloudDatetime": String,
                  "flags": {
                    "active": Boolean,
                    "local": Boolean
                  },
                  "url": String
                }, ...
              ]
            }

###/routerEndpoint[/1]/submittals/1/files/1
   #Expects:
          {}

   #Returns:
       data:
            {
              "idFile": Integer,
              "idFolder": Integer,
              "idUser": Integer,
              "fileName": String,
              "category": String,
              "description": String,
              "size": Integer,
              "dateCreated": String,
              "cloudDatetime": String,
              "flags": {
                "active": Boolean,
                "local": Boolean
              },
              "url": String
            }

####POST

###routerEndpoint[/1]
   #Expects:
       data:
            {
                 'title': String,
                 'description': String,
            }

   #Return:
       data:
            {
                {
                     'idSubmittalPhase': Integer,
                     'title': String,
                     'description': String,
                     'idProject': String,
                     'idParent': Integer,
                     'idFolder': Integer,
                     'idComponent': Integer,
                     'depth': Integer,
                     'sequenceNumber': Integer,
                     'childNextSequenceNumber': Boolean,
                     'status' {
                             'isComplete': Boolean;
                             'limitOneIncomplete': Boolean;
                             }
                }
            }

###/routerEndpoint/1/submittals
   #Expects:
       data:
           {}

   #Returns:
       data:
           {
               {
                   'idSubmittal': Integer,
                   'sequenceNumber': Integer,
                   'idUser': Integer,
                   'idFolder': Integer,
                   'idSubmittalPhase': Integer,
                   'dateCreated': String,
                   'dateCompleted': String,
                   'status'{
                       'isComplete': Boolean,
                       'hasReview': Boolean
                           }
               }
           }

###/routerEndpoint[/1]/submittals/1/files
   #Expects:
          {}

          in Files:
          'category[]',
          'desc[]'

   #Returns:
       data:
            [
                {
                  "idFile": Integer,
                  "idFolder": Integer,
                  "idUser": Integer,
                  "fileName": String,
                  "category": String,
                  "description": String,
                  "size": Integer,
                  "dateCreated": String,
                  "cloudDatetime": String,
                  "flags": {
                    "active": Boolean,
                    "local": Boolean
                  }
                }, ...
            ]

####PUT
###/routerEndpoint/1
   #Expects:
       data:
            {
                {
                     'idSubmittalPhase': Integer,
                     'title': String,
                     'description': String,
                     'idProject': String,
                     'idParent': Integer,
                     'idFolder': Integer,
                     'idComponent': Integer,
                     'depth': Integer,
                     'sequenceNumber': Integer,
                     'childNextSequenceNumber': Boolean,
                     'status' {
                             'isComplete': Boolean;
                             'limitOneIncomplete': Boolean;
                             }
                }
            }

   #Return:
       data:
            {
                {
                     'idSubmittalPhase': Integer,
                     'title': String,
                     'description': String,
                     'idProject': String,
                     'idParent': Integer,
                     'idFolder': Integer,
                     'idComponent': Integer,
                     'depth': Integer,
                     'sequenceNumber': Integer,
                     'childNextSequenceNumber': Boolean,
                     'status' {
                             'isComplete': Boolean;
                             'limitOneIncomplete': Boolean;
                             }
                }
            }

###/routerEndpoint/1/submittals
   #Expects:
       data:
           {
               {
                   'idSubmittal': Integer,
                   'sequenceNumber': Integer,
                   'idUser': Integer,
                   'idFolder': Integer,
                   'idSubmittalPhase': Integer,
                   'dateCreated': String,
                   'dateCompleted': String,
                   'status'{
                       'isComplete': Boolean,
                       'hasReview': Boolean
                           }
               }
           }

   #Returns:
       data:
           {
               {
                   'idSubmittal': Integer,
                   'sequenceNumber': Integer,
                   'idUser': Integer,
                   'idFolder': Integer,
                   'idSubmittalPhase': Integer,
                   'dateCreated': String,
                   'dateCompleted': String,
                   'status'{
                       'isComplete': Boolean,
                       'hasReview': Boolean
                           }
               }
           }

###/routerEndpoint[/1]/submittals/1/files/1
   #Expects:
       data:
            {
              "idFile": Integer,
              "idFolder": Integer,
              "idUser": Integer,
              "fileName": String,
              "category": String,
              "description": String,
              "size": Integer,
              "dateCreated": String,
              "cloudDatetime": String,
              "flags": {
                "active": Boolean,
                "local": Boolean
              }
            }

   #Returns:
       data:
            {
              "idFile": Integer,
              "idFolder": Integer,
              "idUser": Integer,
              "fileName": String,
              "category": String,
              "description": String,
              "size": Integer,
              "dateCreated": String,
              "cloudDatetime": String,
              "flags": {
                "active": Boolean,
                "local": Boolean
              }
            }

   #Expects:
       data:
           {
               {
                   'idSubmittal': Integer,
                   'sequenceNumber': Integer,
                   'idUser': Integer,
                   'idFolder': Integer,
                   'idSubmittalPhase': Integer,
                   'dateCreated': String,
                   'dateCompleted': String,
                   'status'{
                       'isComplete': Boolean,
                       'hasReview': Boolean
                           }
               }
           }

   #Returns:
       data:
           {
               {
                   'idSubmittal': Integer,
                   'sequenceNumber': Integer,
                   'idUser': Integer,
                   'idFolder': Integer,
                   'idSubmittalPhase': Integer,
                   'dateCreated': String,
                   'dateCompleted': String,
                   'status'{
                       'isComplete': Boolean,
                       'hasReview': Boolean
                           }
               }
           }

####DELETE
###/routerEndpoint/1
###/routerEndpoint[/1]/submittals/1
###/routerEndpoint[/1]/submittals/1/files/1

