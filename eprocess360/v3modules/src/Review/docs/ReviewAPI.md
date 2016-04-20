##Reviews API

####NOTE:
    This Reviews API is on top of other API that use Reviews /submittals/1/reviews.

```
GET     /reviews        Returns a list of Reviews for the specified item
POST    /reviews        Creates a new Review on the specified item
GET     /reviews/[id]   Gets a specific Review
PUT     /reviews/[id]   Edits a specific Review
DELETE  /reviews/[id]   Deletes a specific Review

GET     /reviews/[id]/files         Gets a list of files in a specific Review
POST    /reviews/[id]/files         Creates a new file on a Review
GET     /reviews/[id]/files/[id]    Gets a specific file from a Review
PUT     /reviews/[id]/files/[id]    Edits a specific file from a Review
DELETE  /reviews/[id]/files/[id]    Deletes an existing File from a Review

GET     /reviews/types  Returns a list of Types for this Review set
```

###GET
####/reviews:
    #Expects:
        {}
    #Returns:
        [
            "reviews": [
                {
                    "idReview": Integer,
                    "idUser": Integer,
                    'idGroup': Integer,
                    "type": String,
                    "title": String,
                    "description": String,
                    "dateCreated": String,
                    "dateDue": String,
                    "dateCompleted": String,
                    "status": {
                        "isAccepted": Boolean
                        "isComplete": Boolean
                    }
                },...
            ],
            "reviewTypes": [
                String, ...
            ],
            "groups": [
                {
                    "idUser": Integer,
                    "title": String
                }, ...
            ],
            "reviewers": [

                {
                    "idUser": Integer,
                    "firstName": String,
                    "lastName": String,
                    "idRole": Integer
                }, ...
            ],
            "links": [
                {
                    "rel": "self",
                    "title": "Reviews",
                    "href": String
                },
                {
                    "rel": "parent",
                    "title": String,
                    "href": String
                }
            ]
        ]
####/reviews/[id]:
    #Expects:
        {}
    #Returns:
        {
            "idReview": Integer,
            "idUser": Integer,
            'idGroup': Integer,
            "type": String,
            "title": String,
            "description": String,
            "dateCreated": String,
            "dateDue": String,
            "dateCompleted": String,
            "status": {
                "isAccepted": Boolean
                "isComplete": Boolean
            }
            "links": [
                {
                    "rel": "self",
                    "title": String,
                    "href": String
                },
                {
                    "rel": "parent",
                    "title": "Reviews",
                    "href": String
                },
                {
                    "rel": "grandparent",
                    "title": String,
                    "href": String
                }
            ]
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
            "reviewableFiles": [
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



####/reviews/[id]/files:
    #Expects:
    {}
    #Returns:
        {
            "idReview": Integer,
            "idUser": Integer,
            'idGroup': Integer,
            "type": String,
            "title": String,
            "description": String,
            "dateCreated": String,
            "dateDue": String,
            "dateCompleted": String,
            "status": {
                "isAccepted": Boolean
                "isComplete": Boolean
            }
            "links": [
                {
                    "rel": "self",
                    "title": String,
                    "href": String
                },
                {
                    "rel": "parent",
                    "title": "Reviews",
                    "href": String
                },
                {
                    "rel": "grandparent",
                    "title": String,
                    "href": String
                }
            ]
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
            "reviewableFiles": [
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


####/reviews/[id]/files/[id]:
    #Expects:
    {}
    #Returns:
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

####/reviews/types:
    #Expects:
    {}
    #Returns:
        [
            String,
            String,
            ...
        ]


###POST

####/reviews:
    #Expects:
        {
            "type": String,
            "description": Boolean,
            "idUser": Integer,
            "idGroup": Integer,
            "dueDate": String
        }
    #Returns:
        {
            "idReview": Integer,
            "idUser": Integer,
            'idGroup': Integer,
            "type": String,
            "title": String,
            "description": String,
            "dateCreated": String,
            "dateDue": String,
            "dateCompleted": String,
            "status": {
                "isAccepted": Boolean
                "isComplete": Boolean
            }
        }


####/reviews/[id]/files:
    #Expects:
        {}

        in Files:
            {
                'category[]',
                'desc[]'
            }
    #Returns:
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

###PUT

####/reviews/[id]:
    #Expects:
        {
            "type": String,
            "description": Boolean,
            "idUser": Integer,
            "idGroup": Integer,
            "dueDate": String,
            "isAccepted": Boolean,
            "isComplete": Boolean
        }
    #Returns:
        {
            "idReview": Integer,
            "idUser": Integer,
            'idGroup': Integer,
            "type": String,
            "title": String,
            "description": String,
            "dateCreated": String,
            "dateDue": String,
            "dateCompleted": String,
            "status": {
                "isAccepted": Boolean
                "isComplete": Boolean
            }
        }


####/reviews/[id]/files/[id]:
    #Expects:
        {
            "idFile": Integer,
            "fileName": String,
            "category": String,
            "description": String
        }
    #Returns:
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

###DELETE

####/reviews/[id]:
    #Expects:
        {}
    #Returns:
        BOOLEAN

####/reviews/[id]/files/[id]:
    #Expects:
        {}
    #Returns:
        BOOLEAN