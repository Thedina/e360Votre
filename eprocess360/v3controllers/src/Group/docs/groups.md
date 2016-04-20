##Groups

Groups is a core module that allows users to be members of groups.  The groups can then be used by project controllers 
to select and manipulate members with.

* Users can be a member of a group Users_Groups (isPrimary, isActive)
* Each Group has one Controller ID
* Permissions to modify the Group are stored as Roles and bound to Users on Users_Roles 
* Roles are created when the Group is created (see Roles)
* Groups have a title, which is the title stored on Controllers
* Groups do not track their roles via a pivot table
* Use Warden::grantRole() to modify Users_Roles for another user

### Endpoints

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
```

### Roles

These roles should automatically be added to the Roles table when a Group is created.  They can be selected again by
using the Group idController to locate all roles for the group.  The Roles are not going to be editable in the MVP.

* Administrator (ADMIN)
* Facilitator (READ, CREATE, DELETE)
* Member (READ)

### Views

For /groups and /groups/:id, use the StandardView component to display information.