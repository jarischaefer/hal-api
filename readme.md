# HAL-API

## About

The HAL-API is based on Laravel 5 (development). It is designed to automate common tasks in RESTful API programming.

## Installation

### Composer

Either require the package via Composer by issuing the following command

> composer require jarischaefer/hal-api:dev-development

or by including the following in your composer.json.

```json
"require": {
	"jarischaefer/hal-api": "dev-development"
}
```
This is going to install the development version.

### Service Provider

Register the Service Provider in your config/app.php file.
```php
'providers' => [
	'Jarischaefer\HalApi\HalApiServiceProvider',
]
```

## Examples

### HAL Resource Controller

A resource controller consists of three components: **Controller**, **Transformer** and **Model**.
The Transformer is based on [Fractal](http://fractal.thephpleague.com).

#### Models

The following is a simple relationship with three tables. The user has two One-To-Many relationships with both Posts and Comments.

```php
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

	public function posts()
	{
		return $this->hasMany(Post::class);
	}

	public function comments()
	{
		return $this->hasMany(Comment::class);
	}

}

class Post extends Model
{

	// ...

	public function user()
	{
		return $this->belongsTo(User::class);
	}

}

class Comment extends Model
{

	// ...

	public function user()
	{
		return $this->belongsTo(User::class);
	}

}
```

#### Transformer

Read more about transformers in the [Fractal Docs](http://fractal.thephpleague.com/transformers/). They provide an additional layer between your models and how they will be represented in the API.

```php
class UserTransformer extends HalApiTransformer
{

	protected function boot()
	{
		$this->data = [
			'id' => (int)$this->model->id,
			'username' => (string)$this->model->username,
			'email' => (string)$this->model->email,
			'firstname' => (string)$this->model->firstname,
			'lastname' => (string)$this->model->lastname,
			'disabled' => (bool)$this->model->disabled,
		];

		$show = RouteHelper::byAction(UsersController::actionName(RouteHelper::SHOW));
		$this->self = HalLink::make($show, $this->model->id); // Define the link to the current user (e.g. /users/123)
		$this->parent = HalLink::make(RouteHelper::parent($show)); // Define the link to the current user's parent (e.g. /users/123 -> /users)
	}

}

class PostTransformer extends HalApiTransformer
{

	protected function boot()
	{
		$this->data = [
			'id' => (int)$this->model->id,
			'title' => (string)$this->model->title,
			'text' => (string)$this->model->text,
			'user_id' => (int)$this->model->user_id,
		];

		$show = RouteHelper::byAction(PostsController::actionName(RouteHelper::SHOW));
		$this->self = HalLink::make($show, $this->model->id); // Define the link to the current user (e.g. /posts/123)
		$this->parent = HalLink::make(RouteHelper::parent($show)); // Define the link to the current user's parent (e.g. /comments/123 -> /posts)
	}

} 
```

#### Controller

```php
class HomeController extends HalApiController
{

	public function index()
	{
		return $this->api->build(); // Simply return the API
	}

}

class UsersController extends HalApiResourceController
{

	/* @var PostTransformer */
	private $postTransformer;
	
	/* @var CommentTransformer */
	private $commentTransformer;

	protected function boot()
	{
		parent::boot();

		$this->transformer = new UserTransformer(); // The transformer used to display models
		$this->model = User::class; // The model that belongs to the controller

		// Additional transformers used for relationships
		$this->postTransformer = new PostTransformer();
		$this->commentTransformer = new CommentTransformer();
	}

	public function posts(User $user)
	{
		if (!$user || !$user->exists) {
			return Response::make('', 404);
		}

		$posts = $user->posts()->paginate($this->perPage);

		return $this->paginate($posts, PostsController::class, $this->postTransformer)->build();
	}

	public function comments(User $user)
	{
		if (!$user || !$user->exists) {
			return Response::make('', 404);
		}

		$comments = $user->comments()->paginate($this->perPage);

		return $this->paginate($comments, CommentsController::class, $this->commentTransformer)->build();
	}

}

class PostsController extends HalApiResourceController
{

	protected function boot()
	{
		parent::boot();

		$this->transformer = new PostTransformer();
		$this->model = Post::class;
	}

}

class CommentsController extends HalApiResourceController
{

	protected function boot()
	{
		parent::boot();

		$this->transformer = new CommentTransformer();
		$this->model = Comment::class;
	}

}
```

#### routes.php

```php
RouteHelper::make($router)
		->get('/', HomeController::class, 'index') // Link GET / to the index method in HomeController
		
		->resource('users', UsersController::class) // Start a new resource block
			->get('posts', 'posts') // Link GET /users/{users}/posts to the posts method in UsersController
			->get('comments', 'comments') // Links GET /users/{users}/comments to the comments method in UsersController
		->done() // Close the resource block
		
		->resource('posts', PostsController::class)
		->done()

		->resource('comments', CommentsController::class)
		->done()
	
```

#### RouteServiceProvider

```php
public function boot(Router $router)
	{
		parent::boot($router);

		$router->model('users', User::class, HalApiResourceController::getModelBindingCallback());
		$router->model('posts', Post::class, HalApiResourceController::getModelBindingCallback());
		$router->model('comments', Comment::class, HalApiResourceController::getModelBindingCallback());
	}
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
		"users.posts": {
			"href": "http://hal-api.development/users/123/posts",
			"templated": true
		},
		"users.comments": {
			"href": "http://hal-api.development/users/123/comments",
			"templated": true
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
		"users.posts": {
			"href": "http://hal-api.development/users/{users}/posts",
			"templated": true
		},
		"users.comments": {
			"href": "http://hal-api.development/users/{users}/comments",
			"templated": true
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
					"users.posts": {
						"href": "http://hal-api.development/users/123/posts",
						"templated": true
					},
					"users.comments": {
						"href": "http://hal-api.development/users/123/comments",
						"templated": true
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
					"users.posts": {
						"href": "http://hal-api.development/users/456/posts",
						"templated": true
					},
					"users.comments": {
						"href": "http://hal-api.development/users/456/comments",
						"templated": true
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

## Contributing

Feel free to contribute anytime. Take a look at the [Laravel Docs](http://laravel.com/docs/master/packages) regarding package development first. Laravel 5 is still under development as of January 2015, so the docs might not be up to date.
Once you've made some changes, push them to a new branch and start a pull request.

## License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

