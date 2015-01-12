## HAL-API

### About

The HAL-API is based on Laravel 5 (development). It is designed to automate common tasks in RESTful API programming.


### Examples

#### Resource Controller

A resource controller consists of three components: Controller, Transformer and Model.
The Transformer is based on [Fractal](http://fractal.thephpleague.com).

```php
class UsersController extends HalApiResourceController
{

	protected function boot()
	{
		parent::boot();

		$this->transformer = new UserTransformer();
		$this->model = User::class;
	}

}

class UserTransformer extends HalApiTransformer
{

	protected function boot()
	{
		$this->data = [
			'id'			=> (int)$this->model->id,
			'username'		=> (string)$this->model->username,
			'email'	        => (string)$this->model->email,
			'firstname'     => (string)$this->model->firstname,
			'lastname'      => (string)$this->model->lastname,
			'disabled'		=> (bool)$this->model->disabled,
		];

		$show = RouteHelper::byAction(UsersController::actionName(RouteHelper::SHOW));
		$this->self = HalLink::make($show, $this->model->id);
		$this->parent = HalLink::make(RouteHelper::parent($show));
	}

}

// Laravel 5 User Model
class User extends Model implements AuthenticatableContract, CanResetPasswordContract {

	use Authenticatable, CanResetPassword;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = ['password', 'remember_token'];

}

// routes.php
RouteHelper::make($router)
    ->resource('user', UsersController::class)
    ->get('posts', 'posts')
    ->done()
```

#### JSON for a specific model (show)

The example above would yield the following result for a user with user id 123.

```json
{
	"data": {
		"id": 123,
		"username": "FB",
		"email": "foo.bar@example.com",
		"firstname": "foo",
		"lastname": "bar",
		"disabled": false
	},
	"_links": {
		"self": {
			"href": "http://hal-api.development/users/123",
			"templated": true
		},
		"parent": {
			"href": "http://hal-api.development/users",
			"templated": false
		},
		"users.update": {
			"href": "http://hal-api.development/users/123",
			"templated": true
		},
		"users.destroy": {
			"href": "http://hal-api.development/users/123",
			"templated": true
		},
		"users.posts": {
			"href": "http:/hal-api.development/users/123/posts",
			"templated": true
		}
	},
	"_embedded": {
	
	}
}
```

#### JSON for a list of models (index)

The following is an index. It contains multiple models inside the _embedded field.

```json
{
	"_links": {
		"self": {
			"href": "http://hal-api.development/users",
			"templated": false
		},
		"parent": {
			"href": "http://hal-api.development",
			"templated": false
		},
		"users.show": {
        	"href": "http://hal-api.development/users/{users}",
        	"templated": true
        },
        "users.store": {
        	"href": "http://hal-api.development/users",
        	"templated": false
        },
		"users.update": {
			"href": "http://hal-api.development/users/{users}",
			"templated": true
		},
		"users.destroy": {
			"href": "http://hal-api.development/users/{users}",
			"templated": true
		},
		"users.posts": {
			"href": "http:/hal-api.development/users/{users}/posts",
			"templated": true
		},
		"first": {
			"href": "http://hal-api.development/users?current_page=1",
			"templated": false
		},
		"next": {
			"href": "http://hal-api.development/users?current_page=2",
			"templated": false
		},
		"last": {
			"href": "http://hal-api.development/users?current_page=10",
			"templated": false
		}
	},
	"_embedded": {
		"users.show": [
			{
            	"data": {
            		"id": 123,
            		"username": "FB",
            		"email": "foo.bar@example.com",
            		"firstname": "Foo",
            		"lastname": "Bar",
            		"disabled": false
            	},
            	"_links": {
            		"self": {
            			"href": "http://hal-api.development/users/123",
            			"templated": true
            		},
            		"parent": {
            			"href": "http://hal-api.development/users",
            			"templated": false
            		},
            		"users.update": {
            			"href": "http://hal-api.development/users/123",
            			"templated": true
            		},
            		"users.destroy": {
            			"href": "http://hal-api.development/users/123",
            			"templated": true
            		},
            		"users.posts": {
            			"href": "http:/hal-api.development/users/123/posts",
            			"templated": true
            		}
            	},
            	"_embedded": {
            	
            	}
            },
            {
            	"data": {
            		"id": 456,
            		"username": "JD",
            		"email": "john.doe@example.com",
            		"firstname": "John",
            		"lastname": "Doe",
            		"disabled": false
            	},
            	"_links": {
            		"self": {
            			"href": "http://hal-api.development/users/456",
            			"templated": true
            		},
            		"parent": {
            			"href": "http://hal-api.development/users",
            			"templated": false
            		},
            		"users.update": {
            			"href": "http://hal-api.development/users/456",
            			"templated": true
            		},
            		"users.destroy": {
            			"href": "http://hal-api.development/users/456",
            			"templated": true
            		},
            		"users.posts": {
            			"href": "http:/hal-api.development/users/456/posts",
            			"templated": true
            		}
            	},
            	"_embedded": {
            	
            	}
            }
		]
	}
}
```

#### HAL Controller

The HAL Controller automatically adds all descending routes to the current resource. In the above examples,
taking http://hal-api.development/users as our current resource, everything to its right (e.g. /users/123 or /users/123/posts)
would be considered a child. Child links as well as self and parent links are automatically added to the _links field.

### License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
