##Groups API

```
GET     /groups     View group memberships (sys admin can view all)
POST    /groups     Create a group (sys admin only)

GET     /groups/1   View a group (verify read permission)
PUT     /groups/1   Edit group title, names of the roles, home (verify group admin)
DELETE  /groups/1   Delete a group (sys admin)

GET     /groups/1/users     View users in group (verify group read)
POST    /groups/1/users     Add a user to group (verify group create)
PUT     /groups/1/users/1   Edit a user (verify group write; for modifying user role)
DELETE  /groups/1/users/1   Delete a user from group (verify group delete)

GET     /groups/1/roles     View GroupRoles in a Group (verify admin)
POST    /groups/1/roles     Add a GroupRole to a group (verify admin)
DELETE  /groups/1/roles/1   Delete a GroupRole from a group (verify admin)
```

####GET
###/groups:
    #Expects:
        {}
    #Returns:
        [
            {
                "idGroup": Integer,
                "idController": Integer,
                "title": String,
                "status": {
                  "isActive": Boolean
                }
            }, ...
        ]


###/groups/1:
    #Expects:
        {}

    #Returns:
        {
            "title": String,
            "idGroup": Integer,
            "idController": Integer,
            "status": {
                "isActive": Boolean
            },
            "users": [
                {
                    "idUser": Integer,
                    "firstName": String,
                    "lastName": String,
                    "idRole": Integer
                    "status": {
                        "isActive": Boolean
                    }
                }, ...
            ],
            "roles": [
                {
                    "idRole": Integer,
                    "title": String
                }, ...
            ]
        }

###/groups/1/users:
    #Expects:
    {}
    #Returns:
        {
            "title": String,
            "idGroup": Integer,
            "idController": Integer,
            "status": {
                "isActive": Boolean
            },
            "users": [
                {
                    "idUser": Integer,
                    "firstName": String,
                    "lastName": String,
                    "idRole": Integer
                    "status": {
                        "isActive": Boolean
                    }
                }, ...
            ],
            "roles": [
                {
                    "idRole": Integer,
                    "title": String
                }, ...
            ]
        }

###/groups/1/roles:
    #Expects:
        {}

    #Returns:
        {
            "idGroupRole": Integer,
            "idGroup": Integer,
            "idSystemRole": Integer,
            "idProject": Integer,
            "idLocalRole": Integer
        },...

####POST

###/groups:
    #Expects:
        {
            "title": String,
            "status": {
                "isActive": Boolean
            }
        }
    #Returns:
        {
            "idGroup": Integer,
            "idController": Integer,
            "title": String,
            "status": {
              "isActive": Boolean
            }
        }

###/groups/1/users:
    #Expects:
        {
            "idUser": Integer,
            "idRole": Integer,
            "status": {
                "isActive": Boolean
            }
        }
    #Returns:
        {
            "idUser": Integer,
            "firstName": String,
            "lastName": String,
            "idRole": Integer
            "status": {
                "isActive": Boolean
            }
        }

###/groups/1/roles:
    #Expects:
        {
            "idGroup": Integer,
            "idSystemRole": Integer,
            "idProject": Integer,
            "idLocalRole": Integer
        }

    #Returns:
        {
            "idGroupRole": Integer,
            "idGroup": Integer,
            "idSystemRole": Integer,
            "idProject": Integer,
            "idLocalRole": Integer
        }
####PUT

###/groups/1:
    #Expects:
        {
            "idGroup": Integer,
            "idController": Integer,
            "title": String,
            "status": {
              "isActive": Boolean
            }
        }
    #Returns:
        {
            "idGroup": Integer,
            "idController": Integer,
            "title": String,
            "status": {
              "isActive": Boolean
            }
        }

###/groups/1/users/1:
    #Expects:
        {
            "idRole": Integer,
            "status": {
                "isActive": Boolean
            }
        }
    #Returns:
        {
            "idUser": Integer,
            "firstName": String,
            "lastName": String,
            "idRole": Integer
            "status": {
                "isActive": Boolean
            }
        }

####DELETE

###/groups/1:
    #Expects:
    {}
    #Returns:
    BOOLEAN

###/groups/1/users/1:
    #Expects:
    {}
    #Returns:
    BOOLEAN

###/groups/1/roles/1:
    #Expects:
    {}
    #Returns:
    BOOLEAN



















