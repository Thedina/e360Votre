##Tasks API

```
GET     /tasks        Returns a list of Tasks that the user has assigned to them
POST    /tasks        Creates a new task chosen from a set of preset actions (Admin)
GET     /tasks/[id]   Gets a specific Task
PUT     /tasks/[id]   Edits a specific Task (Admin)
DELETE  /tasks/[id]   Deletes a specific Task (Admin)
GET     /count              Returns the number of Tasks currently assigned to the User that are not complete
GET     /groups/[i:idGroup] Returns a list of Tasks that the group has assigned to them
GET     /groups/count       Returns the number of Tasks currently assigned to the User's groups that are not complete
```

###GET
####/tasks:
    #Expects:
        {
            "startDate": String,
            "endDate": String,
            "showPastDue": Boolean
        }
    #Returns:
        {
            "idTask": Integer,
            "idUser": Integer,
            "title": String,
            "description": String,
            "idGroup": Integer,
            "dateCreated": String,
            "dateDue": String,
            "dateCompleted": String,
            "url": String,
            "pastDue": Integer,
            "status": {
                "isComplete": Boolean,
                "isRead": Boolean,
                "hasReview": Boolean,
                "allDay": Boolean
            }
        },...
####/tasks/[id]:
    #Expects:
        {}
    #Returns:
        {
            "idTask": Integer,
            "idUser": Integer,
            "title": String,
            "description": String,
            "idGroup": Integer,
            "dateCreated": String,
            "dateDue": String,
            "dateCompleted": String,
            "url": String,
            "pastDue": Integer,
            "status": {
                "isComplete": Boolean,
                "isRead": Boolean,
                "hasReview": Boolean,
                "allDay": Boolean
            }

####/tasks/count:
    #Expects:
        {}
    #Returns:
        {
            "taskCount": Integer,
            "pastDue": Integer,
            "groupsCount":
                {
                    "idGroup": Integer,
                    "title": String,
                    "taskCount": Integer,
                    "pastDue": Integer
                },...
        }

####/tasks/groups/[idGroup]:
    #Expects:
        {
            "startDate": String,
            "endDate": String,
            "showPastDue": Boolean
        }
    #Returns:
        {
            "idTask": Integer,
            "idUser": Integer,
            "title": String,
            "description": String,
            "idGroup": Integer,
            "dateCreated": String,
            "dateDue": String,
            "dateCompleted": String,
            "url": String,
            "pastDue": Integer,
            "status": {
                "isComplete": Boolean,
                "isRead": Boolean,
                "hasReview": Boolean,
                "allDay": Boolean
            }
        },...

###POST
####/task/[id]:
    #Expects:
        {
            "idTaskPreset": Integer,
            "idUser": Integer,
            "idGroup": Integer,
            "title": String,
            "description": String,
            "dateDue": String
        }
    #Returns:
        {
            "idTask": Integer,
            "idUser": Integer,
            "title": String,
            "description": String,
            "idGroup": Integer,
            "dateCreated": String,
            "dateDue": String,
            "dateCompleted": String,
            "url": String,
            "pastDue": Integer,
            "status": {
                "isComplete": Boolean,
                "isRead": Boolean,
                "hasReview": Boolean,
                "allDay": Boolean
            }
        }
###PUT

####/tasks/[id]:
    #Expects:
        {
            "idUser": Integer,
            "idGroup": Integer,
            "title": String,
            "description": String,
            "dateDue": String,
            "status": {
                "isComplete": Boolean,
                "isRead": Boolean,
                "allDay": Boolean
            }
        }
    #Returns:
        {
            "idTask": Integer,
            "idUser": Integer,
            "title": String,
            "description": String,
            "idGroup": Integer,
            "dateCreated": String,
            "dateDue": String,
            "dateCompleted": String,
            "url": String,
            "pastDue": Integer,
            "status": {
                "isComplete": Boolean,
                "isRead": Boolean,
                "hasReview": Boolean,
                "allDay": Boolean
            }
        }

###DELETE

####/tasks/[id]:
    #Expects:
        {}
    #Returns:
        BOOLEAN