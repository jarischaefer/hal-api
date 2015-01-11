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
    ->done()
```

### License

This project is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
