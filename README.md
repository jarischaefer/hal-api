# HAL-API

## About

This package is based on Laravel 5.
It is designed to automate common tasks in RESTful API programming.
These docs might not always be in sync with all the changes.

## Installation

### Requirements

Requires Laravel 5.1 and PHP 5.6+.

### Composer

Either require the package via Composer by issuing the following command

> composer require jarischaefer/hal-api:dev-master

or by including the following in your composer.json.

```json
"require": {
	"jarischaefer/hal-api": "dev-master"
}
```
This is going to install the more stable master version.

### Service Provider

#### app.php

Register the Service Provider in your config/app.php file.
```php
'providers' => [
	Jarischaefer\HalApi\Providers\HalApiServiceProvider::class,
]
```

#### compile.php (optional step)

Register the Service Provider in your config/compile.php file.
```php
'providers' => [
	Jarischaefer\HalApi\Providers\HalApiServiceProvider::class,
]
```
The next time you generate an optimized classloader using artisan,
the optimized file will contain the contents of this package as well.

## Examples

#### Models

The following is a simple relationship with three tables.
The user has two One-To-Many relationships with both Posts and Comments.

```php
class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{

	use Authenticatable, CanResetPassword;

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

Transformers provide an additional layer between your models and the controller.
They help you create a HAL response for either a single item or a collection of items.

```php
class UserTransformer extends HalApiTransformer
{

	public function transform(Model $model)
	{
		/** @var User $model */

		return [
			'id' => (int)$model->id,
			'username' => (string)$model->username,
			'email' => (string)$model->email,
			'firstname' => (string)$model->firstname,
			'lastname' => (string)$model->lastname,
			'disabled' => (bool)$model->disabled,
		];
	}

}

class PostTransformer extends HalApiTransformer
{

	public function transform(Model $model)
	{
		/** @var Post $model */

		return [
			'id' => (int)$this->model->id,
			'title' => (string)$this->model->title,
			'text' => (string)$this->model->text,
			'user_id' => (int)$this->model->user_id,
		];
	}

}
```

#### Controller

```php
class HomeController extends HalApiController
{

	public function index()
	{
		return $this->responseFactory->json(
			$this->createResponse()->build() // Simply return the API
		);
	}

}
```

### HAL Resource Controller

A resource controller consists of three components: **Controller**, **Transformer** and **Model**.
The model holds data, typically a table row. This data can be transformed to a HAL response using a transformer.
Finally, the controller handles all requests for a given resource and utilizes models and transformers to form its responses.

```php
class UsersController extends HalApiResourceController
{

	const RELATION = 'users';

	/**
	 * @var HalApiTransformerContract
	 */
	private $postTransformer;
	/**
	 * @var HalApiTransformerContract
	 */
	private $commentTransformer;

	public static function getModel()
	{
		return User::class;
	}

	public static function getRelation($action = null)
	{
		return $action ? self::RELATION . '.' . $action : self::RELATION;
	}

	protected function boot()
	{
		parent::boot();

		$postShow = $this->routeHelper->byAction(PostsController::actionName(RouteHelper::SHOW));
		$postParent = $this->routeHelper->parent($postShow);

		$commentShow = $this->routeHelper->byAction(CommentsController::actionName(RouteHelper::SHOW));
		$commentParent = $this->routeHelper->parent($commentShow);

		// Additional transformers used for relationships
		$this->postTransformer = $this->transformerFactory->create(PostTransformer::class, $postShow, $postParent);
		$this->commentTransformer = $this->transformerFactory->create(CommentTransformer::class, $commentShow, $commentParent);
	}

	public function posts(User $user)
	{
		$posts = $user->posts()->paginate($this->perPage);
		/** @var PostsController $postsController */
		$postsController = $this->application->make(PostsController::class);

		return $this->responseFactory->json($postsController->paginate($posts)->build());
	}

	public function comments(User $user)
	{
		$comments = $user->comments()->paginate($this->perPage);
		/** @var CommentsController $commentsController */
		$commentsController = $this->application->make(CommentsController::class);

		return $this->responseFactory->json($commentsController->paginate($comments)->build());
	}

	protected function getTransformer()
	{
		$self = $this->routeHelper->byAction(static::actionName(RouteHelper::SHOW));
		$parent = $this->routeHelper->parent($self);

		return $this->transformerFactory->create(UserTransformer::class, $self, $parent);
	}

}

class PostsController extends HalApiResourceController
{

	const RELATION = 'posts';

	public static function getModel()
	{
		return Post::class;
	}

	public static function getRelation($action = null)
	{
		return $action ? self::RELATION . '.' . $action : self::RELATION;
	}

	protected function getTransformer()
	{
		$self = $this->routeHelper->byAction(static::actionName(RouteHelper::SHOW));
		$parent = $this->routeHelper->parent($self);

		return $this->transformerFactory->create(PostTransformer::class, $self, $parent);
	}

}

class CommentsController extends HalApiResourceController
{

	const RELATION = 'comments';

	public static function getModel()
	{
		return Comment::class;
	}

	public static function getRelation($action = null)
	{
		return $action ? self::RELATION . '.' . $action : self::RELATION;
	}

	protected function getTransformer()
	{
		$self = $this->routeHelper->byAction(static::actionName(RouteHelper::SHOW));
		$parent = $this->routeHelper->parent($self);

		return $this->transformerFactory->create(CommentTransformer::class, $self, $parent);
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

Make sure you bind all route parameters in the RouteServiceProvider.
The callback shown below handles missing parameters depending on the request method.
For instance, a GET request for a nonexistent database record should yield a 404 response.
The same is true for all other HTTP methods except for PUT. PUT simply creates the resource if it did not exist before.

```php
public function boot(Router $router)
{
	parent::boot($router);

	$callback = HalApiResourceController::getModelBindingCallback();
	$router->model('users', User::class, $callback);
	$router->model('posts', Post::class, $callback);
	$router->model('comments', Comment::class, $callback);
}
```

#### Exception handler

The callback above throws NotFoundHttpException if no record was found.
To create a proper response instead of an error page, the exception handler must be amended.
As shown below, various HTTP status codes like 404 and 422 will be returned depending on the exception caught.

```php
<?php namespace App\Exceptions;

use Config;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Jarischaefer\HalApi\Exceptions\BadPostRequestException;
use Jarischaefer\HalApi\Exceptions\BadPutRequestException;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler {

	/**
	 * Report or log an exception.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	public function report(Exception $e)
	{
		parent::report($e);
	}

	/**
	 * Render an exception into a response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\Response
	 */
	public function render($request, Exception $e)
	{
		switch (get_class($e)) {
			case ModelNotFoundException::class:
				return response('', Response::HTTP_NOT_FOUND);
			case NotFoundHttpException::class:
				return response('', Response::HTTP_NOT_FOUND);
			case BadPutRequestException::class:
				return response('', Response::HTTP_UNPROCESSABLE_ENTITY);
			case BadPostRequestException::class:
				return response('', Response::HTTP_UNPROCESSABLE_ENTITY);
			case TokenMismatchException::class:
				return response('', Response::HTTP_FORBIDDEN);
			case DatabaseConflictException::class:
				return response('', Response::HTTP_CONFLICT);
			case DatabaseSaveException::class:
				$this->report($e);
				return response('', Response::HTTP_UNPROCESSABLE_ENTITY);
			default:
				$this->report($e);

				return Config::get('app.debug') ? parent::render($request, $e) : response('', Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

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

## Contributing

Feel free to contribute anytime.
Take a look at the [Laravel Docs](http://laravel.com/docs/master/packages) regarding package development first.
Once you've made some changes, push them to a new branch and start a pull request.

## License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
