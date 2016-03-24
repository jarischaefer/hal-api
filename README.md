# HAL-API

Enhances your HATEOAS experience by automating common tasks.

# About

This package is based on Laravel 5.
It is designed to automate common tasks in RESTful API programming.
These docs might not always be in sync with all the changes.

# Installation

## Requirements

Requires Laravel 5.2 and PHP 5.6 or PHP 7.0.

## Composer

Either require the package via Composer by issuing the following command

> composer require jarischaefer/hal-api:dev-master

or by including the following in your composer.json.

```json
"require": {
	"jarischaefer/hal-api": "dev-master"
}
```
This is going to install the more stable master version.

## Service Provider

### app.php

Register the Service Provider in your config/app.php file.
```php
'providers' => [
	Jarischaefer\HalApi\Providers\HalApiServiceProvider::class,
]
```

### compile.php (optional step)

Register the Service Provider in your config/compile.php file.
```php
'providers' => [
	Jarischaefer\HalApi\Providers\HalApiServiceProvider::class,
]
```
The next time you generate an optimized classloader using artisan,
the optimized file will contain the contents of this package as well.

# Usage

## Models

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

## Transformer

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

class CommentTransformer extends HalApiTransformer
{

	public function transform(Model $model)
	{
		// ...
	}

}
```

### Linking relationships

Overriding a transformer's getLinks method allows you to link to related resources.
Linking a Post to its User:

```php
class PostTransformer extends HalApiTransformer
{

	private $userRoute;

	private $userRelation;

	public function __construct(LinkFactory $linkFactory, RepresentationFactory $representationFactory, RouteHelper $routeHelper, Route $self, Route $parent)
	{
		parent::__construct($linkFactory, $representationFactory, $routeHelper, $self, $parent);

		$this->userRoute = $routeHelper->byAction(UsersController::actionName(RouteHelper::SHOW));
		$this->userRelation = UsersController::getRelation(RouteHelper::SHOW);
	}

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

	protected function getLinks(Model $model)
	{
		/** @var Post $model */

		return [
			$this->userRelation => $this->linkFactory->create($this->userRoute, $model->user_id),
		];
	}

}
```

Notice the "users.show" relation among the links.

```json
{
	"data": {
		"id": 123,
		"title": "Welcome!",
		"text": "Hello World",
		"user_id": 456
	},
	"_links": {
		"self": {
			"href": "http://hal-api.development/posts/123",
			"templated": true
		},
		"parent": {
			"href": "http://hal-api.development/posts",
			"templated": false
		},
		"users.show": {
			"href": "http://hal-api.development/users/456",
			"templated": true
		},
		"posts.update": {
			"href": "http://hal-api.development/posts/123",
			"templated": true
		},
		"posts.destroy": {
			"href": "http://hal-api.development/posts/123",
			"templated": true
		}
	},
	"_embedded": {
	}
}
```

### Embedded relationships

Once data from two separate Models needs to be combined, the linking-approach
doesn't quite cut it. Displaying Posts' authors (firstname and lastname in User model)
becomes infeasible with more than a dozen items (N+1 GET requests to all "users.show" relationships). Embedding related data is basically the same as eager loading.

```php
class PostTransformer extends HalApiTransformer
{

	private $userTransformer;

	private $userRelation;

	public function __construct(LinkFactory $linkFactory, RepresentationFactory $representationFactory, RouteHelper $routeHelper, Route $self, Route $parent, UserTransformer $userTransformer)
	{
			parent::__construct($linkFactory, $representationFactory, $routeHelper, $self, $parent);

			$this->userTransformer = $userTransformer;
			$this->userRelation = UsersController::getRelation(RouteHelper::SHOW);
	}

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

	protected function getEmbedded(Model $model)
	{
			/** @var Post $model */

			return [
				$this->userRelation => $this->userTransformer->item($model->user),
			];
	}

}
```

Notice the "users.show" relation in the _emedded field.

```json
{
	"data": {
		"id": 123,
		"title": "Welcome!",
		"text": "Hello World",
		"user_id": 456
	},
	"_links": {
		"self": {
			"href": "http://hal-api.development/posts/123",
			"templated": true
		},
		"parent": {
			"href": "http://hal-api.development/posts",
			"templated": false
		},
		"users.show": {
			"href": "http://hal-api.development/users/456",
			"templated": true
		},
		"posts.update": {
			"href": "http://hal-api.development/posts/123",
			"templated": true
		},
		"posts.destroy": {
			"href": "http://hal-api.development/posts/123",
			"templated": true
		}
	},
	"_embedded": {
		"users.show": {
			"data": {
				"id": 456,
				"username": "foo-bar",
				"email": "foo.bar@example.com",
				"firstname": "foo",
				"lastname": "bar",
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
				}
			},
			"_embedded": {
			}
		}
	}
}
```

## Simple Controller

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

## Resource Controller

A resource controller consists of three components: **Controller**, **Transformer** and **Model**.
The model holds data, typically a table row. This data can be transformed to a HAL response using a transformer.
Finally, the controller handles all requests for a given resource and utilizes models and transformers to form its responses.

```php
class UsersController extends HalApiResourceController
{

	const RELATION = 'users';

	public static function getModel()
	{
		return User::class;
	}

	public static function getRelation($action = null)
	{
		return $action ? self::RELATION . '.' . $action : self::RELATION;
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

}
```

## Dependency wiring

It is recommendend that you wire the transformers' and controllers' dependencies in a Service Provider:

```php
class MyServiceProvider extends ServiceProvider
{

	public function boot(Router $router)
	{
		$linkFactory = $this->app->make(LinkFactory::class);
		$representationFactory = $this->app->make(RepresentationFactory::class);
		$routeHelper = $this->app->make(RouteHelper::class);

		$this->app->singleton(UserTransformer::class, function () use ($linkFactory, $representationFactory, $routeHelper) {
			$self = $routeHelper->byAction(UsersController::actionName(RouteHelper::SHOW));
			$parent = $routeHelper->parent($self);

			return new UserTransformer($linkFactory, $representationFactory, $routeHelper, $self, $parent);
		});

		$this->app->singleton(PostTransformer::class, function (Illuminate\Contracts\Foundation\Application $application) use ($linkFactory, $representationFactory, $routeHelper) {
			$self = $routeHelper->byAction(PostsController::actionName(RouteHelper::SHOW));
			$parent = $routeHelper->parent($self);
			$userTransformer = $application->make(UserTransformer::class);

			return new PostTransformer($linkFactory, $representationFactory, $routeHelper, $self, $parent, $userTransformer);
		});

		$this->app->singleton(CommentTransformer::class, function (Illuminate\Contracts\Foundation\Application $application) use ($linkFactory, $representationFactory, $routeHelper) {
			$self = $routeHelper->byAction(PostsController::actionName(RouteHelper::SHOW));
			$parent = $routeHelper->parent($self);
			$userTransformer = $application->make(UserTransformer::class);

			return new CommentTransformer($linkFactory, $representationFactory, $routeHelper, $self, $parent, $userTransformer);
		});
	}

	public function register()
	{
		$this->app->singleton(UsersController::class, function (Illuminate\Contracts\Foundation\Application $application) {
			$parameters = $application->make(HalApiControllerParameters::class);
			$transformer = $application->make(UserTransformer::class);
			$schemaBuilder = $application->make(\Illuminate\Database\DatabaseManager::class)->connection(config('database.default'))->getSchemaBuilder();

			return new UsersController($parameters, $transformer, $schemaBuilder);
		});

		$this->app->singleton(PostsController::class, function (Illuminate\Contracts\Foundation\Application $application) {
			$parameters = $application->make(HalApiControllerParameters::class);
			$transformer = $application->make(PostTransformer::class);
			$schemaBuilder = $application->make(\Illuminate\Database\DatabaseManager::class)->connection(config('database.default'))->getSchemaBuilder();

			return new PostsController($parameters, $transformer, $schemaBuilder);
		});

		$this->app->singleton(CommentsController::class, function (Illuminate\Contracts\Foundation\Application $application) {
			$parameters = $application->make(HalApiControllerParameters::class);
			$transformer = $application->make(CommentTransformer::class);
			$schemaBuilder = $application->make(\Illuminate\Database\DatabaseManager::class)->connection(config('database.default'))->getSchemaBuilder();

			return new CommentsController($parameters, $transformer, $schemaBuilder);
		});
	}

}
```

## routes.php

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

## RouteServiceProvider

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

## Exception handler

The callback above throws NotFoundHttpException if no record was found.
To create a proper response instead of an error page, the exception handler must be amended.
As shown below, various HTTP status codes like 404 and 422 will be returned depending on the exception caught.

```php
class Handler extends ExceptionHandler
{

	public function report(Exception $e)
	{
		parent::report($e);
	}

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

# Examples

## JSON for a specific model (show)

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
		}
	},
	"_embedded": {
	}
}
```

## JSON for a list of models (index)

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

# Contributing

Feel free to contribute anytime.
Take a look at the [Laravel Docs](http://laravel.com/docs/master/packages) regarding package development first.
Once you've made some changes, push them to a new branch and start a pull request.

# License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
