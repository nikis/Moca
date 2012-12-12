<?php
namespace Moca\Application;

use Silex\Application,
	Silex\ControllerProviderInterface,
	Exception;

/**
 * This class provide automatic routes for Silex framework
 * @author nikis
 *
 */
abstract class Controller implements ControllerProviderInterface {
	
	/**
	 * Silex application
	 * @var Silex\Application
	 */
	protected $app;
	
	/**
	 * Controller collection
	 */
	protected $collection;
	
	/**
	 * @var string
	 */
	protected $viewScope = null;
	
	/**
	 * @param Silex\Application $app
	 */
	public function connect(Application $app) {
		$this->app = $app;
		$this->collection = $this->app['controllers_factory'];
		$this->register();
		return $this->collection;
	}
	
	/**
	 * Create view
	 * @param string $path
	 * @param string $data
	 * @throws \Exception when twig service provider is not registered
	 */
	protected function render($path, array $data=array()) {
		if(!isset($this->app['twig'])) {
			throw new Exception('Register Twig service provider before use views');
		} else if(!isset($this->app['twig.options']['extension'])) {
			$this->app['twig.options']['extension'] = '.phtml';
		}
		if(substr($path, strlen($this->app['twig.options']['extension'])*-1) != $this->app['twig.options']['extension']) {
			$path .= $this->app['twig.options']['extension'];
		}
		return call_user_func_array(array($this->app['twig'], 'render'), array($path, $data));
	}
	
	/**
	 * Render view with controller scope
	 * @param string $path
	 * @param array $data
	 * @return string
	 */
	protected function view($path, array $data=array()) {
		$this->beforeRender();
		$path = '@'.$this->viewScope.'/'.$path;
		$return = $this->render($path, $data);
		$this->afterRender();
		return $return;
	}
	
	/**
	 * Default index action
	 */
	abstract public function index();
	
	/**
	 * Before middlewear
	 */
	public function before() {}
	
	/**
	 * After middlewear
	 */
	public function after() {}
	
	/**
	 * After render middlewear
	 */
	protected function beforeRender() {}
	
	/**
	 * After render middlewear
	 */
	protected function afterRender() {}
	
	/**
	 * Register routes from class methods
	 */
	protected function register() {
		$hidden = get_class_methods(__CLASS__);
		$hidden = array_merge($hidden, array('before', 'after'));
		
		$route = array();
		
		$ref = new \ReflectionClass($this);
		$methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
		
		foreach($methods as $method) {
			if(in_array($method->name, $hidden)) {
				continue;
			}
			$route[] = $method->name;
		}
		
		if(false !== $key = array_search('index', $route)) {
			unset($route[$key]);
		}
		
		$this->collection->before(array($this, 'before'));
		$this->collection->after(array($this, 'after'));

		$this->collection->get('/', array($this, 'index'));
		foreach($route as $name) {
			$methods = explode('_', $name);
			if(count($methods) == 1) {
				$method = 'GET';
				$route = $name;
			} else {
				$method = $methods[0];
				$route = implode('_', array_slice($methods, 1));
			}
			$this->collection->match($route, array($this, $name))->method($method);
			$this->collection->match($route.'/', array($this, $name))->method($method);
		}
		
		if(isset($this->app['twig'])) {
			$className = explode('\\', get_class($this));
			$className = end($className);
			$this->viewScope = $className;
			$path = $this->app['twig.path'].'/'.$className;
			$this->app['twig.loader.filesystem']->addPath($path, $this->viewScope);
		}
	}
}