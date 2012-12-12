Moca
====
Easy code scaling for Silex framework.

Usage
-----

```php
<?php

use Moca\Application\Controller;

class Page extends Controller {
	
	public function index() {
		return $this->view('index');
	}
	
	public function about() {
		return $this->view('about');
	}
	
	public function contacts() {
		return $this->view("contacts");
	}
	
	public function post_contacts() {
		$form = $this->app['request']->get('contacts');
		return $this->app->json(array('message' => 'Success'));
	}
}
```

Next mount your controller

```php
<?php
$app->mount('/', new Page());
```

What you need to know
-----
- All public methods of controller are register as routes
- Default method for every controller is `index`
- Every `protected`, private` or `parent` method will be skipped
- Every loaded route automatically adds `/`
- You can change the request method by add it at the beginning `post_routename` or `head_routename`
- Each controller name are added automatically as namespace in Twig
- `render` are used to display the Twig templates. By default tempalte file extension is `.phtml`, if you want to change you can set option `$ app ['twig.options'] ['extension'] = '.html`
- `view` is seeking template filename defined in the View folder `Default Twig folder + current name of the Controller`
- Middlewares `before`, `after`, `beforeRender` and `afterRender`

Examples
-----

`/contacts` will accept only post
```php
<?php

use Moca\Application\Controller;

class Page extends Controller {
	
	public function index() {
		return $this->view('index');
	}
	
	public function about() {}
	
	public function post_contacts() {}
}
```

Will load the template from the folder `View/Page/index.phtml`
```php
<?php

use Moca\Application\Controller;

class Page extends Controller {
	
	public function index() {
		return $this->view('index');
	}
}
```

Will load the template from the folder `View/Block/index.phtml`
```php
<?php

use Moca\Application\Controller;

class User extends Controller {
	
	public function index() {
		return $this->render('Block/index');
	}
}
```

Composer
```
{
    "require": {
        "moca/moca": "dev-master"
    }
}
```

Project structure
```
vendor/
web/
app/
	Controller/
		Page.php
		User.php
	View/
		Page/
			Index.phtml
		User/
			Index.phtml
		Layout/
			Default.phtml
```