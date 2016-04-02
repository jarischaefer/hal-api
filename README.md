# HAL-API

Enhances your HATEOAS experience by automating common tasks.

# About

This package is based on Laravel 5.2.
It is designed to automate common tasks in RESTful API programming.
These docs might not always be in sync with all the changes.

# Installation

## Requirements

Requires Laravel 5.2 and PHP 7.0.

## Composer

Either require the package via Composer by issuing the following command

> composer require jarischaefer/hal-api:dev-master

or by including the following in your composer.json.

```json
"require": {
	"jarischaefer/hal-api": "dev-master"
}
```
Check the releases page for a list of available versions.


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

Run `php artisan optimize --force` to compile an optimized classloader.


# Usage

## Simple Controller

This type of controller is not backed by a model and provides no CRUD operations.
A typical use case is an entry point for the API.
The following controller should be routed to the root of the API and
lists all relationships.

```php
class HomeController extends HalApiController
{

	public function index(HalApiRequestParameters $parameters)
	{
		return $this->responseFactory->json($this->createResponse($parameters)->build());
	}

}
```

## Resource Controller

Resource controllers require three additional components:

* Model: Resources' data is contained within models
* Repository: Repositories retrieve and store models
* Transformer: Transforms models into HAL representations

```php
class UsersController extends HalApiResourceController
{

	public static function getRelationName(): string
	{
		return 'users';
	}

	public function __construct(HalApiControllerParameters $parameters, UserTransformer $transformer, UserRepository $repository)
	{
		parent::__construct($parameters, $transformer, $repository);
	}

	public function posts(HalApiRequestParameters $parameters, PostsController $postsController, User $user): Response
	{
		$posts = $user->posts()->paginate($parameters->getPerPage());
		$response = $postsController->paginate($parameters, $posts)->build();

		return $this->responseFactory->json($response);
	}

}

class PostsController extends HalApiResourceController
{

	public static function getRelationName(): string
	{
		return 'posts';
	}

	public function __construct(HalApiControllerParameters $parameters, PostTransformer $transformer, PostRepository $repository)
	{
		parent::__construct($parameters, $transformer, $repository);
	}

}
```

## Models

The following is a simple relationship with two tables.
User has a One-To-Many relationship with Post.

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

}

class Post extends Model
{

	// ...

	public function user()
	{
		return $this->belongsTo(User::class);
	}

}
```

## Repository

You may create an [Eloquent](https://laravel.com/docs/5.2/eloquent)-compatible
repository by extending **HalApiEloquentRepository** and implementing
its getModelClass() method.

```php
class UserRepository extends HalApiEloquentRepository
{

	public static function getModelClass(): string
	{
		return User::class;
	}

}

class PostRepository extends HalApiEloquentRepository
{

	public static function getModelClass(): string
	{
		return Post::class;
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
			'id' => (int)$model->id,
			'title' => (string)$model->title,
			'text' => (string)$model->text,
			'user_id' => (int)$model->user_id,
		];
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
			'id' => (int)$model->id,
			'title' => (string)$model->title,
			'text' => (string)$model->text,
			'user_id' => (int)$model->user_id,
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
			'id' => (int)$model->id,
			'title' => (string)$model->title,
			'text' => (string)$model->text,
			'user_id' => (int)$model->user_id,
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

## Dependency wiring

It is recommendend that you wire the transformers' dependencies in a Service Provider:

```php
class MyServiceProvider extends ServiceProvider
{

	public function boot(Router $router)
	{
		$this->app->singleton(UserTransformer::class, function (Illuminate\Contracts\Foundation\Application $application) {
			$linkFactory = $application->make(LinkFactory::class);
			$representationFactory = $application->make(RepresentationFactory::class);
			$routeHelper = $application->make(RouteHelper::class);
			$self = $routeHelper->byAction(UsersController::actionName(RouteHelper::SHOW));
			$parent = $routeHelper->parent($self);

			return new UserTransformer($linkFactory, $representationFactory, $routeHelper, $self, $parent);
		});

		$this->app->singleton(PostTransformer::class, function (Illuminate\Contracts\Foundation\Application $application) {
			$linkFactory = $application->make(LinkFactory::class);
			$representationFactory = $application->make(RepresentationFactory::class);
			$routeHelper = $application->make(RouteHelper::class);
			$self = $routeHelper->byAction(PostsController::actionName(RouteHelper::SHOW));
			$parent = $routeHelper->parent($self);
			$userTransformer = $application->make(UserTransformer::class);

			return new PostTransformer($linkFactory, $representationFactory, $routeHelper, $self, $parent, $userTransformer);
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
	->done() // Close the resource block

	->resource('posts', PostsController::class)
	->done();
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

	$callback = RouteHelper::getModelBindingCallback();
	$router->model('users', User::class, $callback);
	$router->model('posts', Post::class, $callback);
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
			"href": "http://hal-api.development/users/{users}/posts",
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
					"users.update": {
						"href": "http://hal-api.development/users/123",
						"templated": true
					},
					"users.destroy": {
						"href": "http://hal-api.development/users/123",
						"templated": true
					},
					"users.posts": {
						"href": "http://hal-api.development/users/123/posts",
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
					"users.update": {
						"href": "http://hal-api.development/users/456",
						"templated": true
					},
					"users.destroy": {
						"href": "http://hal-api.development/users/456",
						"templated": true
					},
					"users.posts": {
						"href": "http://hal-api.development/users/456/posts",
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
