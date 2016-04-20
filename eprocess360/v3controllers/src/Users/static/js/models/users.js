/**
 * Users: Models
 */

/**
 * Backbone model for user
 * @typedef {Object} UserModel
 */
var UserModel = BizzyBone.BaseModel.extend({
    urlRoot: hbInitData().meta.Users.apiPath,
    idAttribute: 'idUser',
    defaults: {
        email: '',
        firstName: '',
        lastName: '',
        title: '',
        phone: ''
    }
});

// Add multiview functionality to UserModel
UserModel = Multiview.modelMultiviewable(UserModel);

/**
 * A list of users
 * @typedef {Object} UserList
 */
var UserList = BizzyBone.BaseCollection.extend({
    model: UserModel,
    url: hbInitData().meta.Users.apiPath
});

// Add multiview functionality to UserList
UserList = Multiview.collectionMultiviewable(UserList);