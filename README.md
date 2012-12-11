Moca - auto loading routes for Silex
====

Usage
-----

```php
<?php

use Moca\Application\Controller;

class Page extends Controller {
	
	public function index() {
		return 'page.index';
	}
	
	public function about() {
		return 'page.about';
	}
	
	public function contacts() {
		return 'page.contacts';
	}
	
	public function post_contacts() {
		return 'page.post.contacts';
	}
}
```

Mount your controller

```php
<?php
$app->mount('/', new Page());
```

Thats all simple and easy